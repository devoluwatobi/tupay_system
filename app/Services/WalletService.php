<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Wallet;
use GuzzleHttp\Client;
use App\Models\RMBTransaction;
use GuzzleHttp\RequestOptions;
use App\Models\WalletTransaction;
use App\Models\BettingTransaction;
use App\Models\RewardWalletTransaction;
use Illuminate\Support\Facades\Log;
use App\Models\RMBWalletTransaction;
use App\Models\UtilityBillTransaction;
use App\Models\TupaySubAccountTransaction;

class WalletService
{
    public  static function getBookBalance($id)
    {

        // Define a helper function for status mapping
        $getStatus = function ($statusCode) {
            switch ($statusCode) {
                case 0:
                    $status = 'Pending';
                    break;
                case 1:
                    $status = 'Completed';
                    break;
                case 2:
                    $status = 'Failed';
                    break;
                case 3:
                    $status = 'Cancelled';
                    break;
                case 4:
                    $status = 'Processing';
                    break;
                default:
                    $status = 'Pending';
                    break;
            }
            return $status;
        };

        $user = User::where("id", $id)->first();
        $wallet = Wallet::where("user_id", $id)->first();

        $debit = 0;
        $credit = 0;
        $trxs = [];

        $rmb_debit = 0;
        $rmb_credit = 0;


        $betData = BettingTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $utiData = UtilityBillTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $walData = WalletTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $rmb = RMBTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $rmb_trx = RMBWalletTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $fund = TupaySubAccountTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $refData = RewardWalletTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');


        foreach ($utiData as $billTransaction) {
            if ($billTransaction->status != 2 && $billTransaction->status != 3) {
                $debit += (float) $billTransaction->amount;
            }
        }

        foreach ($walData as $walletTransaction) {
            if ($walletTransaction->status != 2 && $walletTransaction->status != 3) {
                $debit += (float) ($walletTransaction->charge + $walletTransaction->amount);
            }
        }

        foreach ($refData as $rewardTransaction) {
            if ($rewardTransaction->status == 1) {
                $credit += (float) ($rewardTransaction->amount);
            }
        }


        foreach ($betData as $betTransaction) {
            if ($betTransaction->status != 2) {
                $debit += (float) ($betTransaction->amount + $betTransaction->charge);
            }
        }

        foreach ($rmb as $transaction) {
            if ($transaction->status != 2) {
                $amount = ($transaction->amount  * $transaction->amount) + $transaction->charge;
                if ($transaction->paid_with == "rmb" || $transaction->paid_with == "RMB") {
                    $rmb_debit += (float) ($amount);
                } else {
                    $debit += (float) ($amount);
                }
            }
        }

        foreach ($rmb_trx as $transaction) {
            if ($transaction->status != 2) {

                if ($transaction->type == 1) {
                    $rmb_credit += $transaction->amount;
                    $debit += ($transaction->amount * $transaction->rate) + $transaction->charge;
                } else {
                    $rmb_debit += $transaction->amount + $transaction->charge;
                    $credit += $transaction->amount * $transaction->rate;
                }
            }
        }

        foreach ($fund as $transaction) {
            if ($transaction->status == 1) {
                $credit += (float) $transaction->settlement;
            }
        }



        $book_balance = $credit - $debit;

        return [
            "ngn" => $credit - $debit,
            "rmb" => $rmb_credit - $rmb_debit,
        ];
    }
}
