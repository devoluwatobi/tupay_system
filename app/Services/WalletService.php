<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Wallet;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use App\Models\RMBTransaction;
use GuzzleHttp\RequestOptions;
use App\Models\WalletTransaction;
use App\Mail\AccountStatementMail;
use App\Models\BettingTransaction;
use App\Models\RewardWallet;
use Illuminate\Support\Facades\Log;
use App\Models\RMBWalletTransaction;
use Illuminate\Support\Facades\Mail;
use App\Models\UtilityBillTransaction;
use App\Models\RewardWalletTransaction;
use App\Models\RMBWallet;
use Illuminate\Support\Facades\Storage;
use App\Models\TupaySubAccountTransaction;

class WalletService
{

    private static function getStatus($statusCode)
    {
        return match ($statusCode) {
            0 => 'Pending',
            1 => 'Completed',
            2 => 'Failed',
            3 => 'Cancelled',
            4 => 'Processing',
            default => 'Pending',
        };
    }

    private static function collectTransactionData($userId)
    {
        return [
            'betData' => BettingTransaction::where('user_id', $userId)->get()->sortByDesc('created_at'),
            'utiData' => UtilityBillTransaction::where('user_id', $userId)->get()->sortByDesc('created_at'),
            'walData' => WalletTransaction::where('user_id', $userId)->get()->sortByDesc('created_at'),
            'rmb' => RMBTransaction::where('user_id', $userId)->get()->sortByDesc('created_at'),
            'rmb_trx' => RMBWalletTransaction::where('user_id', $userId)->get()->sortByDesc('created_at'),
            'fund' => TupaySubAccountTransaction::where('user_id', $userId)->get()->sortByDesc('created_at'),
            'refData' => RewardWalletTransaction::where('user_id', $userId)->get()->sortByDesc('created_at'),
        ];
    }


    private static function processTransactions($transactions)
    {
        $debit = $credit = $rmb_debit = $rmb_credit = 0;
        $trxs = [];

        // Process utility bill transactions
        foreach ($transactions['utiData'] as $billTransaction) {
            $amount = (float)$billTransaction->amount;
            $isActive = $billTransaction->status != 2 && $billTransaction->status != 3;

            $trxs[] = self::createTransactionRow(
                $billTransaction->created_at,
                $billTransaction->id,
                $billTransaction->type,
                'DEBIT',
                $amount,
                $billTransaction->status,
                $isActive ? $amount : 0,
                0,
                0,
                0
            );

            if ($isActive) $debit += $amount;
        }

        // Process wallet transactions
        foreach ($transactions['walData'] as $walletTransaction) {
            $amount = (float)($walletTransaction->amount + $walletTransaction->charge);
            $isActive = $walletTransaction->status != 2 && $walletTransaction->status != 3;

            $trxs[] = self::createTransactionRow(
                $walletTransaction->created_at,
                $walletTransaction->id,
                "Withdrawal",
                'DEBIT',
                $amount,
                $walletTransaction->status,
                $isActive ? $amount : 0,
                0,
                0,
                0
            );

            if ($isActive) $debit += $amount;
        }

        // Process reward transactions
        foreach ($transactions['refData'] as $rewardTransaction) {
            $amount = (float)$rewardTransaction->amount;
            $isActive = $rewardTransaction->status == 1;

            $trxs[] = self::createTransactionRow(
                $rewardTransaction->created_at,
                $rewardTransaction->id,
                "Referral Reward",
                'CREDIT',
                $amount,
                $rewardTransaction->status,
                0,
                0,
                $isActive ? $amount : 0,
                0
            );

            if ($isActive) $credit += $amount;
        }

        // Process betting transactions
        foreach ($transactions['betData'] as $betTransaction) {
            $amount = (float)($betTransaction->amount + $betTransaction->charge);
            $isActive = $betTransaction->status != 2;

            $trxs[] = self::createTransactionRow(
                $betTransaction->created_at,
                $betTransaction->id,
                $betTransaction->product . " Funding",
                'DEBIT',
                $amount,
                $betTransaction->status,
                $isActive ? $amount : 0,
                0,
                0,
                0
            );

            if ($isActive) $debit += $amount;
        }

        // Process RMB transactions
        foreach ($transactions['rmb'] as $transaction) {
            $amount = ($transaction->amount * $transaction->rate) + $transaction->charge;
            $isActive = $transaction->status != 2;
            $isRmb = in_array(strtolower($transaction->paid_with), ['rmb']);

            $trxs[] = self::createTransactionRow(
                $transaction->created_at,
                $transaction->id,
                $transaction->r_m_b_payment_method_title . ($transaction->recipient_name ? ' (' . $transaction->recipient_name . ')' : ''),
                'DEBIT',
                $amount,
                $transaction->status,
                $isActive && !$isRmb ? $amount : 0,
                $isActive && $isRmb ? $amount : 0,
                0,
                0
            );

            if ($isActive) {
                if ($isRmb) {
                    $rmb_debit += $amount;
                } else {
                    $debit += $amount;
                }
            }
        }

        // Process RMB wallet transactions
        foreach ($transactions['rmb_trx'] as $transaction) {
            $isActive = $transaction->status != 2;
            $isTopup = $transaction->type == 1;

            $trxs[] = self::createTransactionRow(
                $transaction->created_at,
                $transaction->id,
                "RMB Wallet " . ($isTopup ? "Top-up" : "Withdrawal"),
                'DEBIT | CREDIT',
                ($transaction->amount * $transaction->rate) + $transaction->charge,
                $transaction->status,
                $isActive ? ($isTopup ? ($transaction->amount * $transaction->rate) + $transaction->charge : 0) : 0,
                $isActive ? (!$isTopup ? $transaction->amount + $transaction->charge : 0) : 0,
                $isActive ? (!$isTopup ? $transaction->amount * $transaction->rate : 0) : 0,
                $isActive ? ($isTopup ? $transaction->amount : 0) : 0
            );

            if ($isActive) {
                if ($isTopup) {
                    $rmb_credit += $transaction->amount;
                    $debit += ($transaction->amount * $transaction->rate) + $transaction->charge;
                } else {
                    $rmb_debit += $transaction->amount + $transaction->charge;
                    $credit += $transaction->amount * $transaction->rate;
                }
            }
        }

        // Process fund transactions
        foreach ($transactions['fund'] as $transaction) {
            $amount = (float)$transaction->settlement;
            $isActive = $transaction->status == 1;

            $trxs[] = self::createTransactionRow(
                $transaction->created_at,
                $transaction->id,
                "Wallet Top-Up",
                'CREDIT',
                $amount,
                $transaction->status,
                0,
                0,
                $isActive ? $amount : 0,
                0
            );

            if ($isActive) $credit += $amount;
        }

        return [
            'trxs' => $trxs,
            'balances' => [
                'ngn' => round($credit - $debit, 2),
                'rmb' => round($rmb_credit - $rmb_debit, 2),
            ],
            'totals' => [
                'debit' => $debit,
                'credit' => $credit,
                'rmb_debit' => $rmb_debit,
                'rmb_credit' => $rmb_credit,
            ]
        ];
    }

    private static function createTransactionRow(
        $date,
        $id,
        $title,
        $type,
        $amount,
        $status,
        $ngnDebit,
        $rmbDebit,
        $ngnCredit,
        $rmbCredit
    ) {
        return [
            $date,
            $id,
            $title,
            $type,
            round($amount, 2),
            self::getStatus($status),
            round($ngnDebit, 2),
            round($rmbDebit, 2),
            round($ngnCredit, 2),
            round($rmbCredit, 2),
        ];
    }

    private static function generateCsv($trxs, $totals, $balances)
    {
        usort($trxs, function ($a, $b) {
            return strtotime($a[0]) - strtotime($b[0]);
        });

        $runningNgn = $runningRmb = 0;
        $csvData = "DATE CREATED,TRANSACTION ID,TITLE,TYPE,AMOUNT,STATUS,NGN DEBIT,RMB DEBIT,NGN CREDIT,RMB CREDIT,RUNNING NGN,RUNNING RMB\n";

        foreach ($trxs as $row) {
            if ($row[3] === "DEBIT") {
                $runningNgn -= $row[6];
                $runningRmb -= $row[7];
            } elseif ($row[3] === "CREDIT") {
                $runningNgn += $row[8];
                $runningRmb += $row[9];
            } elseif ($row[3] === "DEBIT | CREDIT") {
                $runningNgn -= $row[6];
                $runningRmb -= $row[7];
                $runningNgn += $row[8];
                $runningRmb += $row[9];
            }

            $row[] = $runningNgn;
            $row[] = $runningRmb;

            $csvData .= implode(',', array_map(function ($item) {
                return '"' . str_replace('"', '""', $item) . '"';
            }, $row)) . "\n";
        }

        $csvData .= "\n\n";
        $csvData .= 'Total, , , , , ,' . $totals['debit'] . ',' . $totals['rmb_debit'] . ',' . $totals['credit'] . ',' . $totals['rmb_credit'] . "\n";
        $csvData .= "\n";
        $csvData .= 'NGN Book Balance, , , , , , , , , ,' . $balances['ngn'] . "\n";
        $csvData .= 'RMB Book Balance, , , , , , , , , , ,' . $balances['rmb'] . "\n";

        return $csvData;
    }

    public static function bookBalance($id)
    {
        $transactions = self::collectTransactionData($id);
        $processed = self::processTransactions($transactions);
        return $processed['balances'];
    }

    public static function sendStatement($id)
    {
        $user = User::where("id", $id)->first();
        $transactions = self::collectTransactionData($id);
        $processed = self::processTransactions($transactions);
        $csvData = self::generateCsv($processed['trxs'], $processed['totals'], $processed['balances']);

        $fileName = 'export_' . Str::random(10) . '.csv';
        Storage::disk('local')->put($fileName, $csvData);

        try {
            Mail::to($user->email)->send(new AccountStatementMail($user, $csvData));
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
        }

        return $processed['balances'];
    }

    public static function getBookBalance($id)
    {
        return self::bookBalance($id);
    }

    public static function getAudit($id)
    {
        $transactions = self::collectTransactionData($id);
        $processed = self::processTransactions($transactions);
        $processed['trxs'] = null;
        return $processed;
    }

    public static function resetBalance($id)
    {
        $transactions = self::collectTransactionData($id);
        $processed = self::processTransactions($transactions);

        $ngn_wallet = Wallet::where("user_id", $id)->first();
        $rmb_wallet = RMBWallet::where("user_id", $id)->first();
        $reward_wallet = RewardWallet::where("user_id", $id)->first();

        $ngn_wallet->update([
            "balance" => $processed['balances']['ngn']
        ]);

        $rmb_wallet->update([
            "balance" => $processed['balances']['rmb']
        ]);

        $reward_wallet->update([
            "balance" => 0
        ]);

        $processed['trxs'] = null;

        return $processed;
    }
}
