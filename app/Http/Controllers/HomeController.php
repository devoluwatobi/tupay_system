<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Utility;
use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\RMBPaymentType;
use App\Models\RMBTransaction;
use App\Models\RMBPaymentMethod;
use App\Models\WalletTransaction;
use App\Models\BettingTransaction;
use App\Models\RMBWallet;
use App\Models\RMBWalletTransaction;
use App\Models\SystemConfig;
use App\Models\TupaySubAccountTransaction;
use App\Models\UtilityBillTransaction;

if (!function_exists('hideEmailAddress')) {
    /**
     * Check if two names have at least two common words.
     *
     * @param string $name1
     * @param string $name2
     * @return bool
     */
    function hideEmailAddresshideEmailAddress($email)
    {
        // Split the email into parts
        list($local, $domain) = explode('@', $email);

        // Hide part of the local part
        $local = substr($local, 0, 2) . str_repeat('*', 6);

        // Split the domain into parts
        list($domain_name, $domain_extension) = explode('.', $domain);

        // Hide part of the domain name
        $domain_name = str_repeat('*', 3);

        // Combine the parts back together
        return $local . '@' . $domain_name . '.' . $domain_extension;
    }
}

class HomeController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();
        $configs = AppConfig::all();
        $payment_methods = RMBPaymentMethod::where('status', 1)->get();

        $rmb = RMBWallet::where("user_id", $user->id)->first();

        if ($rmb == null) {
            RMBWallet::updateOrCreate([
                "user_id" => $user->id
            ], [
                "user_id" => $user->id,
                "balance" => 0,
            ]);
        }

        foreach ($payment_methods as $method) {
            $method->logo = env('APP_URL') . $method->logo;
        }
        $data = [
            'payment_methods' => $payment_methods,
            'payment_types' => RMBPaymentType::where('status', 1)->get(),
            'me' => $user,
            'wallet_balance' =>  number_format((float) $user->wallet->balance, 2),
            'rmb_balance' =>  number_format((float) $user->rmb->balance, 2),
            'banks' => $user->banks,
            'fund_account' => $user->fundAccount->first(),
            'verifications' => $user->verifications,
            'configs' =>  $configs,
            'rmb2ngn' => [
                'rate' => '1650',
                'charge' => '0.5',
            ],
            'ngn2rmb' => [
                'rate' => '1700',
                'charge' => '800',
            ]


        ];
        return response($data, 200);
    }

    public function adminIndex()
    {
        $user = auth('api')->user();
        $configs = AppConfig::all();
        $sys_configs = SystemConfig::all();
        $payment_methods = RMBPaymentMethod::where('status', 1)->get();

        foreach ($payment_methods as $method) {
            $method->logo = env('APP_URL') . $method->logo;
        }

        $circulation_config = SystemConfig::where("name", "total_circulation")->first();

        if ($circulation_config) {
            $circulation =  $circulation_config->value;
        }

        $incoming_config = SystemConfig::where("name", "total_incoming")->first();

        if ($incoming_config) {
            $incoming =  $incoming_config->value;
        }

        $data = [
            'payment_methods' => $payment_methods,
            'payment_types' => RMBPaymentType::where('status', 1)->get(),
            'me' => $user,
            'system_configs' =>  $sys_configs,
            'app_configs' =>  $configs,
            'board_data' => [
                "total_available" =>  $circulation ?? "0",
                "incoming" => $incoming ?? "0",
            ],
            'stat' => [
                "users" => [
                    "all" => User::where('id', '<>', 0)->get()->count(),
                    "this_month" => User::whereMonth('created_at', Carbon::now()->month)->get()->count(),
                    "last_month" => User::whereMonth('created_at', Carbon::now()->subMonth(1))->get()->count(),
                ],
                'rmb' => [
                    'pending' => RMBTransaction::where("status", 0)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                    'pending_naira' => RMBTransaction::where("status", 0)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'completed' => RMBTransaction::where("status", 1)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'completed_naira' => RMBTransaction::where("status", 1)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                    'this_month' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'this_month_naira' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                    'last_month' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'last_month_naira' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                ],

            ],
        ];
        return response($data, 200);
    }

    public function allPendingTransactions()
    {

        $walData = WalletTransaction::where('status', 0)->get()->sortBy('created_at');
        $rmb = RMBTransaction::where('status', '<>', 9)->get()->sortBy('created_at');


        foreach ($walData as $walletTransaction) {
            switch ($walletTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }

            // fill up trx
            $transaction = $walletTransaction;
            $transaction->status = $status;

            $wallTrans[] = [
                'id' => $walletTransaction->id,
                'title' => "Withdrawal",
                'type' => 'wallet',
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/withdraw.png",
                'amount' => number_format((float) $walletTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $walletTransaction->created_at,
                'updated_at' => $walletTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($rmb as $rmbTransaction) {
            switch ($rmbTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }

            $method =  RMBPaymentMethod::where("id", $rmbTransaction->r_m_b_payment_method_id)->first();


            // fill up trx
            $rmbTransaction->proofs = json_decode($rmbTransaction->proofs);
            $transaction = $rmbTransaction;
            // $transaction->proofs = json_decode($rmbTransaction->proofs);
            $transaction->status = $status;
            $rmbTrans[] = [
                'id' => $rmbTransaction->id,
                'title' => $rmbTransaction->r_m_b_payment_method_title . ($rmbTransaction->recipient_name ? ' (' . $rmbTransaction->recipient_name . ')' : ''),
                'type' => 'rmb-' . $rmbTransaction->r_m_b_payment_method_title,
                'sub_type_id' => $rmbTransaction->id,
                'icon' => env('APP_URL') . $method->logo,
                'currency' => "CN¥",
                'amount' => number_format($rmbTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $rmbTransaction->created_at,
                'updated_at' => $rmbTransaction->updated_at,
                'trx' => $transaction,
            ];
        }

        $transactions = array_merge(
            $wallTrans ?? [],
            $rmbTrans ?? []
        );

        if (count($transactions) < 1) {
            return response([], 200);
        }

        // sort by latest created at
        usort($transactions, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });
        return response($transactions, 200);
    }

    public function oldTransactions()
    {

        $walData = WalletTransaction::where('status', '<>', 0)->get()->sortBy('created_at');
        $rmb = RMBTransaction::where('status', '<>', 0)->get()->sortBy('created_at');


        foreach ($walData as $walletTransaction) {
            switch ($walletTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }

            // fill up trx
            $transaction = $walletTransaction;
            $transaction->status = $status;

            $wallTrans[] = [
                'id' => $walletTransaction->id,
                'title' => "Withdrawal",
                'type' => 'wallet',
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/withdraw.png",
                'amount' => number_format((float) $walletTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $walletTransaction->created_at,
                'updated_at' => $walletTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($rmb as $rmbTransaction) {
            switch ($rmbTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }

            $method =  RMBPaymentMethod::where("id", $rmbTransaction->r_m_b_payment_method_id)->first();


            // fill up trx
            $rmbTransaction->proofs = json_decode($rmbTransaction->proofs);
            $transaction = $rmbTransaction;
            // $transaction->proofs = json_decode($rmbTransaction->proofs);
            $transaction->status = $status;
            $rmbTrans[] = [
                'id' => $rmbTransaction->id,
                'title' => $rmbTransaction->r_m_b_payment_method_title . ($rmbTransaction->recipient_name ? ' (' . $rmbTransaction->recipient_name . ')' : ''),
                'type' => 'rmb-' . $rmbTransaction->r_m_b_payment_method_title,
                'sub_type_id' => $rmbTransaction->id,
                'icon' => env('APP_URL') . $method->logo,
                'currency' => "CN¥",
                'amount' => number_format($rmbTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $rmbTransaction->created_at,
                'updated_at' => $rmbTransaction->updated_at,
                'trx' => $transaction,
            ];
        }

        $transactions = array_merge(
            $wallTrans ?? [],
            $rmbTrans ?? []
        );

        if (count($transactions) < 1) {
            return response([], 200);
        }

        // sort by latest created at
        usort($transactions, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });
        return response($transactions, 200);
    }

    public function myTransactions()
    {
        $user = auth('api')->user();
        $betData = BettingTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $utiData = UtilityBillTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $walData = WalletTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $rmb = RMBTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $rmb_trx = RMBWalletTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $fund = TupaySubAccountTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');


        foreach ($utiData as $billTransaction) {
            // $utility = Utility::where('id', $billTransaction->utility_id)->first();
            switch ($billTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }


            // fill up trx
            $transaction = $billTransaction;
            $transaction->service_icon = env('APP_URL') . $billTransaction->service_icon;
            $transaction->status = $status;
            $transaction->utility_name = $billTransaction->type;
            $transaction->utility_prefix = $billTransaction->type;

            $billTrans[] = [
                'id' => $billTransaction->id,
                'title' => $billTransaction->type,
                'type' => 'bill',
                'sub_type_id' => $billTransaction->utility_id,
                'icon' => $transaction->service_icon,
                'amount' => $billTransaction->amount,
                'status' => $status,
                'color' => $color,
                'created_at' => $billTransaction->created_at,
                'updated_at' => $billTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($walData as $walletTransaction) {
            switch ($walletTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }

            // fill up trx
            $transaction = $walletTransaction;
            $transaction->status = $status;

            $wallTrans[] = [
                'id' => $walletTransaction->id,
                'title' => "Withdrawal",
                'type' => 'wallet',
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/withdraw.png",
                'amount' => number_format((float) $walletTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $walletTransaction->created_at,
                'updated_at' => $walletTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($betData as $betTransaction) {
            switch ($betTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }


            // fill up trx
            $transaction = $betTransaction;
            $transaction->status = $status;
            $betTrans[] = [
                'id' => $betTransaction->id,
                'title' => $betTransaction->product,
                'type' => 'bet',
                'sub_type_id' => $betTransaction->id,
                // 'icon' => "https://res.cloudinary.com/db3c1repq/image/upload/v1713497804/_1baa1896-2b36-44f9-8cd4-b71dcfc2efae_ghvd6j.jpg",
                'icon' => env('APP_URL') . "/images/services/bet.png",
                'amount' => number_format($betTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $betTransaction->created_at,
                'updated_at' => $betTransaction->updated_at,
                'trx' => $transaction,
            ];
        }

        foreach ($rmb as $rmbTransaction) {
            switch ($rmbTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }

            $method =  RMBPaymentMethod::where("id", $rmbTransaction->r_m_b_payment_method_id)->first();


            // fill up trx
            $rmbTransaction->proofs = json_decode($rmbTransaction->proofs);
            $transaction = $rmbTransaction;
            // $transaction->proofs = json_decode($rmbTransaction->proofs);
            $transaction->status = $status;
            $rmbTrans[] = [
                'id' => $rmbTransaction->id,
                'title' => $rmbTransaction->r_m_b_payment_method_title . ($rmbTransaction->recipient_name ? ' (' . $rmbTransaction->recipient_name . ')' : ''),
                'type' => 'rmb-' . $rmbTransaction->r_m_b_payment_method_title,
                'sub_type_id' => $rmbTransaction->id,
                'icon' => env('APP_URL') . $method->logo,
                'currency' => "CN¥",
                'amount' => number_format($rmbTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $rmbTransaction->created_at,
                'updated_at' => $rmbTransaction->updated_at,
                'trx' => $transaction,
            ];
        }


        foreach ($betData as $betTransaction) {
            switch ($betTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }


            // fill up trx
            $transaction = $betTransaction;
            $transaction->status = $status;
            $betTrans[] = [
                'id' => $betTransaction->id,
                'title' => $betTransaction->product,
                'type' => 'bet',
                'sub_type_id' => $betTransaction->id,
                'icon' => "https://res.cloudinary.com/db3c1repq/image/upload/v1713497804/_1baa1896-2b36-44f9-8cd4-b71dcfc2efae_ghvd6j.jpg",
                'amount' => number_format($betTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $betTransaction->created_at,
                'updated_at' => $betTransaction->updated_at,
                'trx' => $transaction,
            ];
        }

        foreach ($fund as $transaction) {

            switch ($transaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }



            // $transaction->proofs = json_decode($rmbTransaction->proofs);
            $transaction->status = $status;
            $fundTrx[] = [
                'id' => $transaction->id,
                'title' => "Wallet Top-Up",
                'type' => 'fund',
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/fund.png",
                'currency' => "₦",
                'amount' => number_format($transaction->settlement, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $transaction->created_at,
                'updated_at' => $transaction->updated_at,
                'trx' => $transaction,
            ];
        }

        $transactions = array_merge(
            $wallTrans ?? [],
            $billTrans ?? [],
            $betTrans ?? [],
            $rmbTrans ?? [],
            $fundTrx ?? []
        );

        if (count($transactions) < 1) {
            return response([], 200);
        }




        // sort by latest created at
        usort($transactions, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });
        return response($transactions, 200);
    }

    public function allUserTransactions($id)
    {
        $signedInUser = auth('api')->user();

        if ($signedInUser->role < 1) {
            $response = ["message" => "Permission Denied. Contact your supervisor for more information"];
            return response($response, 422);
        }

        $user = User::find($id);
        if (!$user) {
            return response(['message' => 'User not found'], 422);
        }
        $betData = BettingTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $utiData = UtilityBillTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $walData = WalletTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');
        $rmb = RMBTransaction::where('user_id', $user->id)->get()->sortByDesc('created_at');


        foreach ($utiData as $billTransaction) {
            // $utility = Utility::where('id', $billTransaction->utility_id)->first();
            switch ($billTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }


            // fill up trx
            $transaction = $billTransaction;
            $transaction->service_icon = env('APP_URL') . $billTransaction->service_icon;
            $transaction->status = $status;
            $transaction->utility_name = $billTransaction->type;
            $transaction->utility_prefix = $billTransaction->type;

            $billTrans[] = [
                'id' => $billTransaction->id,
                'title' => $billTransaction->type,
                'type' => 'bill',
                'sub_type_id' => $billTransaction->utility_id,
                'icon' => $transaction->service_icon,
                'amount' => $billTransaction->amount,
                'status' => $status,
                'color' => $color,
                'created_at' => $billTransaction->created_at,
                'updated_at' => $billTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($walData as $walletTransaction) {
            switch ($walletTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }

            // fill up trx
            $transaction = $walletTransaction;
            $transaction->status = $status;

            $wallTrans[] = [
                'id' => $walletTransaction->id,
                'title' => "Withdrawal",
                'type' => 'wallet',
                'sub_type_id' => 0,
                'icon' => env('APP_URL') . "/images/services/withdraw.png",
                'amount' => number_format((float) $walletTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $walletTransaction->created_at,
                'updated_at' => $walletTransaction->created_at,
                'trx' => $transaction,
            ];
        }

        foreach ($betData as $betTransaction) {
            switch ($betTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }


            // fill up trx
            $transaction = $betTransaction;
            $transaction->status = $status;
            $betTrans[] = [
                'id' => $betTransaction->id,
                'title' => $betTransaction->product,
                'type' => 'bet',
                'sub_type_id' => $betTransaction->id,
                'icon' => "https://res.cloudinary.com/db3c1repq/image/upload/v1713497804/_1baa1896-2b36-44f9-8cd4-b71dcfc2efae_ghvd6j.jpg",
                'amount' => number_format($betTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $betTransaction->created_at,
                'updated_at' => $betTransaction->updated_at,
                'trx' => $transaction,
            ];
        }

        foreach ($rmb as $rmbTransaction) {
            switch ($rmbTransaction->status) {
                case 0:
                    $status = 'Pending';
                    $color = 'EE7541';
                    break;
                case 1:
                    $status = 'Completed';
                    $color = '2F949A';
                    break;
                case 2:
                    $status = 'Failed';
                    $color = 'FF3B30';
                    break;
                case 3:
                    $status = 'Cancelled';
                    $color = '4A36C2';
                    break;
                default:
                    $status = 'Pending';
                    $color = '0160E1';
                    break;
            }

            $method =  RMBPaymentMethod::where("id", $rmbTransaction->r_m_b_payment_method_id)->first();


            // fill up trx
            $rmbTransaction->proofs = json_decode($rmbTransaction->proofs);
            $transaction = $rmbTransaction;
            // $transaction->proofs = json_decode($rmbTransaction->proofs);
            $transaction->status = $status;
            $rmbTrans[] = [
                'id' => $rmbTransaction->id,
                'title' => $rmbTransaction->r_m_b_payment_method_title . ($rmbTransaction->recipient_name ? ' (' . $rmbTransaction->recipient_name . ')' : ''),
                'type' => 'rmb-' . $rmbTransaction->r_m_b_payment_method_title,
                'sub_type_id' => $rmbTransaction->id,
                'icon' => env('APP_URL') . $method->logo,
                'currency' => "CN¥",
                'amount' => number_format($rmbTransaction->amount, 2),
                'status' => $status,
                'color' => $color,
                'created_at' => $rmbTransaction->created_at,
                'updated_at' => $rmbTransaction->updated_at,
                'trx' => $transaction,
            ];
        }

        $transactions = array_merge(
            $wallTrans ?? [],
            $billTrans ?? [],
            $betTrans ?? [],
            $rmbTrans ?? []
        );

        if (count($transactions) < 1) {
            return response([], 200);
        }

        // sort by latest created at
        usort($transactions, function ($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });
        return response($transactions, 200);
    }

    public function boardData()
    {
        $user = auth('api')->user();
        $rmb = RMBTransaction::where('status', 0)->get()->sortBy('created_at');

        foreach ($rmb as $trx) {
            $owner = User::where('id', $trx->user_id)->first();
            $trx->owner_name = $owner->first_name . ' ' . $owner->last_name[0];
            $trx->owner_mail = hideEmailAddresshideEmailAddress($owner->email);
            $trx->owner_photo = $owner->photo;
        }

        $circulation_config = SystemConfig::where("name", "total_circulation")->first();

        if ($circulation_config) {
            $circulation =  $circulation_config->value;
        }

        $incoming_config = SystemConfig::where("name", "total_incoming")->first();

        if ($incoming_config) {
            $incoming =  $incoming_config->value;
        }

        return response([
            "total_available" =>  $circulation ?? "0",
            "incoming" => $incoming ?? "0",
            "requests" => $rmb,
        ], 200);
    }
}
