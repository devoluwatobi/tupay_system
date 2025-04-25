<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Wallet;
use App\Models\SystemConfig;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Models\TupaySubAccount;
use App\Services\SafeHavenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\TupaySubAccountTransaction;

if (!function_exists('isNamesSimilar')) {
    /**
     * Check if two names have at least two common words.
     *
     * @param string $full_name
     * @param string $first_name
     * @param string $last_name
     * @return bool
     */

    function isNamesSimilar($full_name, $first_name, $last_name)
    {
        // Return true if there are at least 1 common words
        return (str_contains(strtolower($full_name), strtolower($first_name))) && (str_contains(strtolower($full_name), strtolower($last_name)));
    }
}

class TupaySubAccountTransactionController extends Controller
{

    public function webhook(Request $request)
    {
        Log::info($request);
        // Log::info($request["data"]['sub_account']['reference']);

        if ($request["type"] != "transfer") {

            return response([
                'message' => 'okay',
                'data' => $request,
            ], 200);
        }

        $x_trx = TupaySubAccountTransaction::where("sessionId", $request["data"]['sessionId'])->first();

        if ($x_trx) {
            return response([
                'message' => 'Transaction Exists Already',
                'data' => $request,
            ], 422);
        }

        $charge = 50;



        $user_bank = TupaySubAccount::where("external_id", $request["data"]["account"])->first();

        if (!$user_bank) {

            $walletTransaction = new TupaySubAccountTransaction();

            $walletTransaction->user_id = 0;
            $walletTransaction->amount = $request["data"]["amount"];
            $walletTransaction->fees = $request["data"]["fees"];
            $walletTransaction->settlement = $request["data"]["amount"] - $charge;
            $walletTransaction->charge = $charge;
            $walletTransaction->type = $request["data"]['type'];
            $walletTransaction->sessionId = $request["data"]['sessionId'];
            $walletTransaction->paymentReference = $request["data"]['paymentReference'];
            $walletTransaction->creditAccountName = $request["data"]["creditAccountName"];
            $walletTransaction->creditAccountNumber = $request["data"]["creditAccountNumber"];
            $walletTransaction->destinationInstitutionCode = $request["data"]["destinationInstitutionCode"];
            $walletTransaction->debitAccountName = $request["data"]["debitAccountName"];
            $walletTransaction->debitAccountNumber = $request["data"]["debitAccountNumber"];
            $walletTransaction->narration = $request["data"]["narration"];
            $walletTransaction->transaction_status = $request["data"]["status"];
            $walletTransaction->status = 1;
            $walletTransaction->data = json_encode($request);

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

        SafeHavenService::refreshAccess();

        // safe_haven
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $client_id = SystemConfig::where('name', 'ibs_client_id')->first();


        // verify the payment

        $body  = [
            "sessionId" => $request["data"]['sessionId'],
        ];

        $response =  Http::withHeaders(['Authorization' => 'Bearer ' . $token->value, 'ClientID' => $client_id->value, 'Content-Type' => 'application/json'])->post(env("SAFEHAVEN_BASE_URL") . '/transfers/status', $body);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($response->status());

        if (!(($response->status() == 200 || $response->status() == 201) && $data['statusCode'] == 200 && $request["data"]['account'] == $request["data"]['account'] && $request["data"]['account'] == $request["data"]['account'] && $request["data"]['sessionId'] == $request["data"]['sessionId'] && $request["data"]['amount'] == $request["data"]['amount'])) {

            return response([
                'message' => 'Could not confirm transaction',
                'data' => $request,
            ], 422);
        }


        $walletTransaction = new TupaySubAccountTransaction();

        $walletTransaction->user_id = $user_bank->user_id;
        $walletTransaction->amount = $request["data"]["amount"];
        $walletTransaction->fees = $request["data"]["fees"];
        $walletTransaction->settlement = $request["data"]["amount"] - $charge;
        $walletTransaction->charge = $charge;
        $walletTransaction->type = $request["data"]['type'];
        $walletTransaction->sessionId = $request["data"]['sessionId'];
        $walletTransaction->paymentReference = $request["data"]['paymentReference'];
        $walletTransaction->creditAccountName = $request["data"]["creditAccountName"];
        $walletTransaction->creditAccountNumber = $request["data"]["creditAccountNumber"];
        $walletTransaction->destinationInstitutionCode = $request["data"]["destinationInstitutionCode"];
        $walletTransaction->debitAccountName = $request["data"]["debitAccountName"];
        $walletTransaction->debitAccountNumber = $request["data"]["debitAccountNumber"];
        $walletTransaction->narration = $request["data"]["narration"];
        $walletTransaction->transaction_status = $request["data"]["status"];
        $walletTransaction->status = 1;
        $walletTransaction->data = $response;


        $walletTransaction->save();

        $walletTransaction->save();

        if (!isNamesSimilar($request["data"]["debitAccountName"], $user->first_name, $user->last_name)) {
            User::find($user->id)->update([
                "status" => 0
            ]);
            FCMService::sendToAdmins([
                "title" => "User account got restricted",
                "body" => "A user account ( " . $user->email . " ) funded account with a bank that is not theirs. Please do check."
            ]);
        }

        $amount = number_format($request["data"]['amount'], 2);

        // credit money wallet
        if ($data["data"]['status'] ?? $request["meta"]['status'] ?? $request[0]['status'] == "Approved") {
            $user_wallet = Wallet::where("user_id", $user->id)->first();

            if (!$user_wallet) {
                Wallet::create([
                    'user_id' => $user->id,
                    'balance' => ($data["data"]['amount'] - $charge),
                ]);
            } else {
                $user_wallet->balance = $user_wallet->balance + ($data["data"]['amount'] - $charge);
                $user_wallet->save();
            }
        }


        try {
            FCMService::sendToID(
                $user->id,
                [
                    'title' => 'Tupay Wallet Funded',
                    'body' => "You just funded your Tupay wallet with an amount of NGN" . ($data["data"]['amount'] - $charge) . "💸",
                ]
            );
        } catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        // create form with the transaction reference id, amount and status
        $form = [
            'user_id' => $user->id,
            'amount' => $request["data"]['amount'],
            'status' => 0,
            'transaction_ref' => $walletTransaction->id,
        ];

        // return response with form data and message
        return response([
            'message' => 'Fund Transaction  created successfully',
            'data' => $request,
        ], 200);
    }
}
