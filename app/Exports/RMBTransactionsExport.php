<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\RMBTransaction;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RMBTransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct(Carbon $startDate, Carbon $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return RMBTransaction::where('status', '<>', 0)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Created At',
            'Updated At',
            'Transaction ID',
            'Method',
            'Type',
            'Amount',
            'Rate',
            'Charge',
            'Paid Via',
            'NGN Total',
            'RMB Total',
            'Remark',
            'Status'
        ];
    }

    public function map($transaction): array
    {
        $status = $this->getTransactionStatus($transaction->status);
        $isPaidWithRmb = strtoupper($transaction->paid_with) === "RMB";
        $totalAmount = ($transaction->amount * $transaction->rate) + $transaction->charge;

        return [
            $transaction->created_at,
            $transaction->updated_at,
            $transaction->id,
            $transaction->r_m_b_payment_method_title,
            $transaction->r_m_b_payment_type_title,
            round($transaction->amount, 2),
            round($transaction->rate, 2),
            round($transaction->charge, 2),
            strtoupper($transaction->paid_with),
            $isPaidWithRmb ? 0 : round($totalAmount, 2),
            $isPaidWithRmb ? round($totalAmount, 2) : 0,
            $transaction->remark,
            $status['status'],
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

    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'F5F5F5'] // Light gray header
            ]
        ]);

        // Apply status colors to each row
        $highestRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $statusCell = $sheet->getCell("M{$row}");
            $statusValue = $statusCell->getValue();

            $color = $this->getColorForStatus($statusValue);

            $sheet->getStyle("M{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => $color]
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'] // White text for better contrast
                ]
            ]);
        }

        // Auto-size columns for better display
        foreach (range('A', 'M') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        return [];
    }

    protected function getColorForStatus(string $status): string
    {
        $statusMap = [
            'Pending' => 'EE7541',
            'Completed' => '2F949A',
            'Failed' => 'FF3B30',
            'Cancelled' => '4A36C2',
            'Processing' => 'EE7541',
        ];

        return $statusMap[$status] ?? '0160E1'; // Default blue
    }
}
