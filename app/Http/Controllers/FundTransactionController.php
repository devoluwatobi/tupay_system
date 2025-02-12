<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Wallet;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Workbench\App\Models\User;
use App\Models\FundTransaction;
use App\Models\UserFundBankAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FundTransactionController extends Controller
{
    public function webhook(Request $request)
    {
        Log::info($request);
        // Log::info($request["details"]['sub_account']['reference']);

        $x_trx = FundTransaction::where("reference", $request["details"]['reference'])->first();

        if ($x_trx) {
            return response([
                'message' => 'Transaction Exists Already',
                'data' => $request,
            ], 422);
        }

        $user_bank = UserFundBankAccount::where("reference", $request["details"]["sub_account"]['reference'])->first();

        if (!$user_bank) {
            $walletTransaction = new FundTransaction();

            $walletTransaction->user_id = 0;
            $walletTransaction->amount = $request["details"]["amount"];
            $walletTransaction->settlement = $request["details"]['settlement'];
            $walletTransaction->charge = $request["details"]['charge'];
            $walletTransaction->reference = $request["details"]['reference'];
            $walletTransaction->profile_first_name = $request["details"]["profile"]["first_name"];
            $walletTransaction->profile_surname = $request["details"]["profile"]["surname"];
            $walletTransaction->profile_phone_no = $request["details"]["profile"]['phone_no'];
            $walletTransaction->profile_email = $request["details"]["profile"]['email'];
            $walletTransaction->profile_blacklisted = $request["details"]["profile"]['blacklisted'];
            $walletTransaction->account_name = $request["details"]["sub_account"]['account_name'];
            $walletTransaction->account_no = $request["details"]["sub_account"]['account_no'];
            $walletTransaction->bank_name = $request["details"]["sub_account"]['bank_name'];
            $walletTransaction->acccount_reference = $request["details"]["sub_account"]['reference'];
            $walletTransaction->transaction_status = $request["meta"]['status'] ?? $request[0]['status'];
            $walletTransaction->status = $request["meta"]['status'] ?? $request[0]['status'] == "Approved" ? 1 : 0;
            $walletTransaction->save();
            return response([
                'message' => 'Could not find bank account on our platform',
                'data' => $request,
            ], 422);
        }


        $user = User::where("id", $user_bank->user_id)->first();

        if (!$user) {
            return response([
                'message' => 'Could not detect user account, probably deleted',
                'data' => $request,
            ], 422);
        }


        // verify the payment

        $body  = [
            "reference" => $request["details"]['reference'],
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/collections/PSA/payments/verify', $body);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($response);

        if (!($data['status'] == true && $data['response'] == 200)) {

            $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/collections/PSA/payments/verify', $body);

            $server_output = json_decode($response);

            $data = $response->json();

            Log::info($response);
        }
        if (!($data['status'] == true && $data['response'] == 200)) {

            return response([
                'message' => 'Could not confirm transaction',
                'data' => $request,
            ], 422);
        }

        $payer =  $data['details']['payer'];

        $walletTransaction = new FundTransaction();

        $walletTransaction->user_id = $user->id;
        $walletTransaction->amount = $data["details"]["amount"];
        $walletTransaction->settlement = $data["details"]['settlement'];
        $walletTransaction->charge = $data["details"]['charge'];
        $walletTransaction->reference = $request["details"]['reference'];
        $walletTransaction->profile_first_name = $data["details"]["profile"]["first_name"];
        $walletTransaction->profile_surname = $data["details"]["profile"]["surname"];
        $walletTransaction->profile_phone_no = $data["details"]["profile"]['phone_no'];
        $walletTransaction->profile_email = $data["details"]["profile"]['email'];
        $walletTransaction->profile_blacklisted = $data["details"]["profile"]['blacklisted'];
        $walletTransaction->account_name = $data["details"]["sub_account"]['account_name'];
        $walletTransaction->account_no = $data["details"]["sub_account"]['account_no'];
        $walletTransaction->bank_name = $data["details"]["sub_account"]['bank_name'];
        $walletTransaction->acccount_reference = $data["details"]["sub_account"]['reference'];
        $walletTransaction->transaction_status =   $data["details"]['status'] ?? $request["meta"]['status'] ?? $request[0]['status'];
        $walletTransaction->status = $data["details"]['status'] ?? $request["meta"]['status'] ?? $request[0]['status'] == "Approved" ? 1 : 0;
        $walletTransaction->payer_account_name = $data["details"]["payer"]['account_name'];
        $walletTransaction->payer_account_no = $data["details"]["payer"]['account_no'];
        $walletTransaction->payer_bank_name = $data["details"]["payer"]['bank_name'];



        $walletTransaction->save();

        $amount = number_format($request["details"]['settlement'], 2);

        // credit money wallet
        if ($data["details"]['status'] ?? $request["meta"]['status'] ?? $request[0]['status'] == "Approved") {
            $user_wallet = Wallet::where("user_id", $user->id)->first();

            if (!$user_wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'balance' => $data["details"]['amount'],
                ]);
            } else {
                $user_wallet->balance = $user_wallet->balance + $data["details"]['amount'];
                $user_wallet->save();
            }
        }


        try {
            FCMService::sendToID(
                $user->id,
                [
                    'title' => 'Faveremit Wallet Funded',
                    'body' => "You just funded your Faveremit wallet with an amount of NGN" . $data["details"]['amount'] . " ðŸ’¸.",
                ]
            );
        } catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        // create form with the transaction reference id, amount and status
        $form = [
            'user_id' => $user->id,
            'amount' => $request["details"]['settlement'],
            'status' => 0,
            'transaction_ref' => $walletTransaction->id,
        ];

        // return response with form data and message
        return response([
            'message' => 'Fund Transaction  created successfully',
            'data' => $request,
        ], 200);
    }

    public function paystackWebhook(Request $request) {}

    public function getTransaction($id)
    {
        $user = auth('api')->user();

        $transaction = FundTransaction::where('id', $id)->select(
            'user_id',
            'amount',
            'settlement',
            'charge',
            'account_name',
            'account_no',
            'bank_name',
            'transaction_status',
            'status',
            'payer_account_name',
            'payer_account_no',
            'payer_bank_name',
            'id',
            'created_at'
        )->first();


        // Status String
        switch ($transaction->status) {
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

            default:
                $status = 'Pending';
                break;
        }


        $data = [
            'message' => 'Transaction fetched successfully',
            'fund_transaction' => $transaction,
        ];
        return response($data, 200);
    }
}
