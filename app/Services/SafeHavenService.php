<?php

namespace App\Services;

use Exception;
use DateTimeZone;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Config;
use App\Models\Wallet;
use App\Models\BankDetails;
use Facade\FlareClient\Api;
use App\Models\SystemConfig;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Models\TupaySubAccount;
use App\Models\SafeVerification;
use App\Models\TupaySubAccounts;
use App\Models\WalletTransaction;
use App\Models\WalletTransactions;
use App\Models\FundTransferRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;


class SafeHavenService
{
    protected $base_url;

    public function __construct()
    {
        $this->base_url = env("SAFEHAVEN_BASE_URL");
    }

    public  static function refreshAccess()
    {


        $refresh = SystemConfig::where('name', 'safehaven_refresh')->first();
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $assertion = SystemConfig::where('name', 'safehaven_assertion')->first();

        $body = [
            "grant_type" => "refresh_token",
            "refresh_token" => $refresh->value,
            "client_id" => env('SAFEHAVEN_ID'),
            "client_assertion_type" => "urn:ietf:params:oauth:client-assertion-type:jwt-bearer",
            "client_assertion" => $assertion->value
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post(env("SAFEHAVEN_BASE_URL") . '/oauth2/token', $body);

        $data = $response->json();

        if ($data) {
            // Token
            if (isset($data['access_token'])) {
                SystemConfig::updateOrCreate(
                    ['name' => 'safehaven_token'],
                    [
                        "name" => "safehaven_token",
                        "value" => $data['access_token'],
                        "updated_by" => 0
                    ]
                );
            }
            // Client ID
            if (isset($data['ibs_client_id'])) {
                SystemConfig::updateOrCreate(
                    ['name' => 'ibs_client_id'],
                    [
                        "name" => "ibs_client_id",
                        "value" => $data['ibs_client_id'],
                        "updated_by" => 0
                    ]
                );
            }
            // User ID
            if (isset($data['ibs_user_id'])) {
                SystemConfig::updateOrCreate(
                    ['name' => 'ibs_user_id'],
                    [
                        "name" => "ibs_user_id",
                        "value" => $data['ibs_user_id'],
                        "updated_by" => 0
                    ]
                );
            }
        } else {
            Log::error($response);
        }
    }

    public  static function replaceToken()
    {


        $refresh = SystemConfig::where('name', 'safehaven_refresh')->first();
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $assertion = SystemConfig::where('name', 'safehaven_assertion')->first();

        $body = [
            "grant_type" => "client_credentials",
            "client_id" => env('SAFEHAVEN_ID'),
            "client_assertion_type" => "urn:ietf:params:oauth:client-assertion-type:jwt-bearer",
            "client_assertion" => $assertion->value
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])->post(env("SAFEHAVEN_BASE_URL") . '/oauth2/token', $body);

        $data = $response->json();

        if ($data) {
            // Token
            if (isset($data['access_token'])) {
                SystemConfig::updateOrCreate(
                    ['name' => 'safehaven_token'],
                    [
                        "name" => "safehaven_token",
                        "value" => $data['access_token'],
                        "updated_by" => 0
                    ]
                );
            }
            // Client ID
            if (isset($data['ibs_client_id'])) {
                SystemConfig::updateOrCreate(
                    ['name' => 'ibs_client_id'],
                    [
                        "name" => "ibs_client_id",
                        "value" => $data['ibs_client_id'],
                        "updated_by" => 0
                    ]
                );
            }
            // User ID
            if (isset($data['ibs_user_id'])) {
                SystemConfig::updateOrCreate(
                    ['name' => 'ibs_user_id'],
                    [
                        "name" => "ibs_user_id",
                        "value" => $data['ibs_user_id'],
                        "updated_by" => 0
                    ]
                );
            }
        } else {
            Log::error($response);
        }
    }

    public  static function updateToSafeBank($bank)
    {
        $bankAccount = BankDetails::where('id', $bank)->first();
        if ($bankAccount == null || !$bankAccount) {
            return false;
        } else if (!$bankAccount->safehaven_name_id || !$bankAccount->safehaven_bank_code) {


            $body = [
                "bankCode" => $bankAccount->safehaven_bank_code ?? $bankAccount->bank,
                "accountNumber" => $bankAccount->account_number,
            ];

            Log::info($body);

            SafeHavenService::refreshAccess();

            $refresh = SystemConfig::where('name', 'safehaven_refresh')->first();
            $token = SystemConfig::where('name', 'safehaven_token')->first();
            $assertion = SystemConfig::where('name', 'safehaven_assertion')->first();

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token->value, 'Content-Type' => 'application/json', 'ClientID' => env('SAFEHAVEN_ID')])->post(env("SAFEHAVEN_BASE_URL") . '/transfers/name-enquiry', $body);

            $server_output = json_decode($response);

            Log::info($response);

            if ($server_output != null && $server_output->statusCode == 200 && $server_output->data) {
                $bankAccount2 = BankDetails::where('id', $bankAccount->id)->first();
                $bankAccount2->update([
                    "safehaven_bank_code" => $server_output->data->bankCode,
                    "safehaven_name_id" => $server_output->data->sessionId
                ]);
            } else {
                return false;
            }
        }

        return true;
    }


    public  static function withdrawFunds($bank_id, $amount, $ip, $agent, $data)
    {
        if (!$bank_id || !$amount) {
            return  [
                'data' => [
                    'error' => true,
                    'status' => false,
                    'message' => "Please provide all neccessary information to complete transaction"
                ],
                'status' => 422
            ];
        }
        $charge_amount = 50;
        SafeHavenService::updateToSafeBank($bank_id);
        $bankAccount = BankDetails::where('id', $bank_id)->first();
        if ($bankAccount == null || !$bankAccount || !$bankAccount->safehaven_name_id || !$bankAccount->safehaven_bank_code) {
            return  [
                'data' => [
                    'error' => true,
                    'status' => false,
                    'message' => "Bank not verified"
                ],
                'status' => 422
            ];
        }

        $user_id = $bankAccount->user_id;

        //   send money
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis') . '|' . rand(1000, 9999);
        $body  = [
            "nameEnquiryReference" => $bankAccount->safehaven_name_id,
            "debitAccountNumber" => env('SAFEHAVEN_DEBIT_ACCOUNT'),
            "beneficiaryBankCode" => $bankAccount->safehaven_bank_code,
            "beneficiaryAccountNumber" => $bankAccount->account_number,
            "narration" => "Withdrawal",
            "amount" => floor(($amount) / 10) * 10,
            "saveBeneficiary" => false,
            "paymentReference" => $transactionRef,
        ];

        $user_wallet = Wallet::where("user_id", $user_id)->first();
        // charge wallet
        $user_wallet->update(["balance" => $user_wallet->balance - ($amount + $charge_amount)]);
        $user = User::where("id", $user_id)->first();

        $walletTransaction = WalletTransaction::create([
            'user_id' => $user_id,
            'amount' => $amount,
            'bank_id' => $bank_id,
            'approved_by' => $user->id,
            // 'rejected_by',
            // 'rejected_reason',
            'transaction_ref' => $transactionRef,
            'transaction_id' => $transactionRef,
            'bank' => $bankAccount->safehaven_bank_code,
            'bank_name' => $bankAccount->bank_name,
            'account_name' => $bankAccount->account_name,
            'account_number'  => $bankAccount->account_number,
            'status' => 4,
            'trx_status' => "Processing",
            'charge'  => $charge_amount,
            'server' => "save_haven",
            // 'session_id',
            // 'data' => $response,
        ]);

        SafeHavenService::refreshAccess();
        $refresh = SystemConfig::where('name', 'safehaven_refresh')->first();
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $assertion = SystemConfig::where('name', 'safehaven_assertion')->first();

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token->value, 'Content-Type' => 'application/json', 'ClientID' => env('SAFEHAVEN_ID')])->post(env("SAFEHAVEN_BASE_URL") . '/transfers', $body);


        try {
            $walletTransaction->update(['data' => $response]);
        } catch (\Exception $e) {
            info($e);
        }


        $response_data = null;
        try {
            $response_data = json_decode($response);
        } catch (\Exception $e) {
            info($e);
        }
        try {
            // SlackAlert::message(json_encode([
            // "ipAddress" => $ip,
            // "agent" => $agent,
            // "data"=> $data,
            // "response"=> json_decode($response),
            // "user" => [
            //     "id" => $user->id,
            //     "name" => $user->name,
            //     "email" => $user->email,
            //     "phone" => $user->phone,
            //     ]
            // ]));

        } catch (\Exception $e) {
            info($e);
        }

        if ($response != null && $response->status() >= 400) {

            $walletTransaction->update(
                [
                    'status' => 2,
                    'trx_status' => 'failed',
                    'session_id' =>  'xxx'
                ]
            );

            $user_walletx = Wallet::where("user_id", $user_id)->first();
            // refund wallet
            $user_walletx->update(["balance" => $user_walletx->balance + ($amount + $charge_amount)]);

            return [
                'data' => [
                    'error' => true,
                    'status' => false,
                    'message' => "Transaction Failed",
                    'trx' => null,
                ],
                'status' => 423
            ];
        }

        if ($response != null &&  $response_data != null && isset($response_data->statusCode) && $response_data->statusCode >= 400) {

            // The

            $walletTransaction->update(["status" => 2, 'trx_status' => "Failed"]);

            // refund wallet
            $user_wallet2 = Wallet::where("user_id", $user_id)->first();

            $user_wallet2->update(["balance" => $user_wallet2->balance + ($amount + $charge_amount)]);




            return  [
                'data' => [
                    'error' => true,
                    'status' => false,
                    'message' => "Withdrawal failed, please try again"
                ],
                'status' => 422
            ];
        }



        if ($response == null || $response_data == null || !isset($response_data->data) ||  $response_data->data == null) {
            $walletTransaction->update(["status" => 4, 'trx_status' => "Pending"]);
        } else {
            $walletTransaction->update(
                [
                    'status' => $response_data->data->queued || $response_data->responseCode != "00" ? 4 : 1,
                    'trx_status' => $response_data->data->queued || $response_data->responseCode != "00" ? 'Processing' : 'Approved',
                    'session_id' =>  $response_data->data->sessionId
                ]
            );
        }

        // Send push notification
        try {
            FCMService::sendToID(
                $user->id,
                [
                    'title' => 'Withdrawal Successful! 🎉💳',
                    'body' => "Your withdrawal has been successful! Enjoy your funds and thank you for using Tupay. 🚀💼",
                ]
            );
        } catch (Exception $e) {
            Log::error("Error: " . $e->getMessage());
        }

        // Send Email
        try {

            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $walletTransaction->created_at->setTimezone($wa_time);
            $emailData = [
                'name' => $user->name,
                'amount' => number_format($walletTransaction->amount, 2),
                'bank' => $bankAccount->bank_name,
                'account_number' =>  $bankAccount->account_number,
                'status' => 'withdrawal' . ((isset($response_data->data) && $response_data->data->queued) || $response_data->responseCode != "00" ? 'Processing' : 'Approved'),
                'time' => date("h:iA, F jS, Y", strtotime("$time")),
            ];

            // Mail::to($user->email)->send(new \App\Mail\approvedWithdraw($emailData));
        } catch (\Exception $e) {
            info($e);
        }




        // end
        return  [
            'data' => [
                'error' => false,
                'message' => "Approved or completed successfully",
                'trx' => null,
            ],
            'status' => 200
        ];
    }

    public  static function createSubAccount($user_id, $id_type, $otp)
    {
        $user = User::where('id', $user_id)->first();

        Log::info($user_id . " | " . $id_type . " | " .  $otp);

        $verification = SafeVerification::where('user_id', $user_id)->where('type', $id_type)->first();

        $verif_body = [

            "phoneNumber" => $user->phone,
            "emailAddress" => $user->email,
            "externalReference" => strval($user->id) . "|" . ($user->created_at ? $user->created_at->timestamp : 'nil') .  rand(1000, 9999),
            "identityType" => $verification->type,
            "identityNumber" => $verification->value,
            "otp" => $otp,
            "callbackUrl" => "https://webhook.site/2a530298-5ae4-4955-aa48-9c1420a2bc3d",
            "identityId" => $verification->safe_id,
            "autoSweep" => true,
            "autoSweepDetails" => [
                "schedule" => "Instant",
                "bankCode" => "090286",
                "accountNumber" => env('SAFEHAVEN_DEBIT_ACCOUNT'),
            ],

        ];

        SafeHavenService::refreshAccess();

        // safe_haven
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $client_id = SystemConfig::where('name', 'ibs_client_id')->first();


        // verify bank details first

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token->value, 'ClientID' => $client_id->value, 'Content-Type' => 'application/json'])->post(env("SAFEHAVEN_BASE_URL") . '/accounts/v2/subaccount', $verif_body);

        $server_output = json_decode($response);

        $sub_account_data = $response->json();

        Log::info($sub_account_data);

        if ($server_output != null && $server_output->statusCode == 200 && $server_output->data) {
            TupaySubAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'external_id' => $server_output->data->_id,
                    'externalReference' => $server_output->data->externalReference,
                ],
                [
                    'user_id' => $user->id,
                    'external_id' => $server_output->data->_id,
                    'provider' => "SAFE HAVEN MICROFINANCE BANK",
                    'accountProduct' => $server_output->data->accountProduct,
                    'accountNumber' => $server_output->data->accountNumber,
                    'accountName'  => $server_output->data->accountName,
                    'accountType'  => $server_output->data->accountType,
                    'currencyCode' => $server_output->data->currencyCode,
                    'bvn'  => $server_output->data->bvn,
                    'nin'  => $server_output->data->nin,
                    'accountBalance' => $server_output->data->accountBalance,
                    'external_status' => $server_output->data->status,
                    'callbackUrl' => $server_output->data->callbackUrl,
                    'firstName' => $server_output->data->subAccountDetails->firstName,
                    'lastName' => $server_output->data->subAccountDetails->lastName,
                    'emailAddress' => $server_output->data->subAccountDetails->emailAddress,
                    'subAccountType' => $server_output->data->subAccountDetails->accountType,
                    'externalReference' => $server_output->data->externalReference,
                    'data' => $response,
                ]
            );

            return true;
        }
        return false;
    }


    public  static function createVirtualAccount($user_id) {}

    public  static function getService()
    {
        $refresh = SystemConfig::where('name', 'safehaven_refresh')->first();
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $assertion = SystemConfig::where('name', 'safehaven_assertion')->first();

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token->value, 'Content-Type' => 'application/json', 'ClientID' => env('SAFEHAVEN_ID')])->get(env("SAFEHAVEN_BASE_URL") . '/vas/service/61efabb2da92348f9dde5f6e/service-categories');

        $data = $response->json();

        Log::error($response);
        Log::error($data);
    }
}
