<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\RMBTransaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RMBTransactionsExport;
use Illuminate\Support\Facades\Storage;

class RMBTransactionService
{
    public function exportTransactions(array $filters = [])
    {
        // Validate and set date range
        $dateRange = $this->validateAndSetDateRange($filters);
        if (isset($dateRange['error'])) {
            return $dateRange;
        }

        [$startDate, $endDate] = $dateRange;

        // // Fetch and format transactions
        // $transactions = $this->getFormattedTransactions($startDate, $endDate);

        // // Generate CSV
        // $csvData = $this->generateCsv($transactions);

        // // Store file and return response
        // return $this->createExportResponse($transactions, $csvData);

        // Generate file name and path
        $fileName = 'exports/rmb_transactions_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = storage_path('app/public/' . $fileName);

        // Store the file
        Excel::store(
            new RMBTransactionsExport($startDate, $endDate),
            $fileName,
            'public',
            \Maatwebsite\Excel\Excel::XLSX
        );

        // Generate download URL
        $url = Storage::disk('public')->url($fileName);

        return [
            'success' => true,
            'download_url' => $url,
            'file_name' => basename($fileName),
            'expires_at' => now()->addHours(24)->toDateTimeString()
        ];
    }

    protected function validateAndSetDateRange(array $filters): array
    {
        $startDate = Carbon::now()->subDays(1)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        if (!empty($filters['start_date'])) {
            $startDate = Carbon::parse($filters['start_date'])->startOfDay();
        }
        if (!empty($filters['end_date'])) {
            $endDate = Carbon::parse($filters['end_date'])->endOfDay();
        }

        if ($endDate->lt($startDate)) {
            return ['error' => 'The end_date cannot be before the start_date.', 'code' => 400];
        }

        return [$startDate, $endDate];
    }

    protected function getFormattedTransactions(Carbon $startDate, Carbon $endDate): Collection
    {
        return RMBTransaction::where('status', '<>', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($transaction) {
                return $this->formatTransaction($transaction);
            })
            ->sortByDesc('created_at')
            ->values();
    }

    protected function formatTransaction(RMBTransaction $transaction): array
    {
        $status = $this->getTransactionStatus($transaction->status);
        $isPaidWithRmb = strtoupper($transaction->paid_with) === "RMB";
        $totalAmount = ($transaction->amount * $transaction->rate) + $transaction->charge;

        return [
            'created_at' => $transaction->created_at,
            'updated_at' => $transaction->updated_at,
            'id' => $transaction->id,
            'method' => $transaction->r_m_b_payment_method_title,
            'type' => $transaction->r_m_b_payment_type_title,
            'amount' => round($transaction->amount, 2),
            'rate' => round($transaction->rate, 2),
            'charge' => round($transaction->charge, 2),
            'paid_via' => strtoupper($transaction->paid_with),
            'ngn_total' => $isPaidWithRmb ? 0 : round($totalAmount, 2),
            'rmb_total' => $isPaidWithRmb ? round($totalAmount, 2) : 0,
            'remark' => $transaction->remark,
            'status' => $status['status'],
        ];
    }

    protected function getTransactionStatus(int $statusCode): array
    {
        $statuses = [
            0 => ['status' => 'Pending', 'color' => 'EE7541'],
            1 => ['status' => 'Completed', 'color' => '2F949A'],
            2 => ['status' => 'Failed', 'color' => 'FF3B30'],
            3 => ['status' => 'Cancelled', 'color' => '4A36C2'],
            4 => ['status' => 'Processing', 'color' => 'EE7541'],
        ];

        return $statuses[$statusCode] ?? ['status' => 'Pending', 'color' => '0160E1'];
    }

    protected function generateCsv(Collection $transactions): string
    {
        $csvHeader = "CREATED,UPDATED,TRANSACTION ID,METHOD,TYPE,AMOUNT,RATE,CHARGE,PAID VIA,NGN TOTAL,RMB TOTAL,REMARK,STATUS\n";

        $csvRows = $transactions->map(function ($transaction) {
            return implode(',', array_map(function ($item) {
                return '"' . str_replace('"', '""', $item) . '"';
            }, array_values($transaction)));
        })->implode("\n");

        return $csvHeader . $csvRows;
    }

    protected function createExportResponse(Collection $transactions, string $csvData): array
    {
        $fileName = 'export_' . Str::random(10) . '.csv';
        Storage::disk('local')->put($fileName, $csvData);

        return [
            'data' => $transactions,
            'download_url' =>  Storage::disk('local')->url($fileName),
            'file_name' => $fileName,
            'success' => true,
        ];
    }
}
