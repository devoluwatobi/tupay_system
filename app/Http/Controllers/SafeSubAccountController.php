<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use App\Models\SafeSubAccount;
use App\Models\TupaySubAccount;
use App\Models\SafeVerification;
use App\Services\SafeHavenService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SafeSubAccountController extends Controller
{

    public function initiate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'number' => 'required',
            'type' => 'required',
        ]);


        if ($validator->fails()) {
            // get summary
            $errors = $validator->errors();
            $errorMessages = [];

            foreach ($errors->all() as $message) {
                $errorMessages[] = $message;
            }

            $summary = implode(" \n", $errorMessages);

            return response(
                [
                    'error' => true,
                    'message' => $summary
                ],
                422
            );
        }

        $user = auth('api')->user();
        $user = User::find($user->id);

        $old_v = SafeSubAccount::where('id_type', $request->type)->where("id_value", $request->number)->where("status", 1)->first();

        if ($old_v &&  $old_v->status == 1) {
            return response(['status' => true, 'message' => 'An account with the ' . $request->type . ' has already been verified and saved successfully',], 422);
        }

        $verif_body = [
            "type" => $request->type,
            "number" => $request->number,
            "async" => false,
            "debitAccountNumber" => env('SAFEHAVEN_DEBIT_ACCOUNT'),
        ];

        Log::info($verif_body);

        SafeHavenService::refreshAccess();

        // safe_haven
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $client_id = SystemConfig::where('name', 'ibs_client_id')->first();


        // verify bank details first

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token->value, 'ClientID' => $client_id->value, 'Content-Type' => 'application/json'])->post(env("SAFEHAVEN_BASE_URL") . '/identity/v2', $verif_body);

        $server_output = json_decode($response);

        $verification_data = $response->json();

        Log::info($verification_data);

        if ($server_output != null && $server_output->statusCode == 200 && $server_output->data) {
            SafeSubAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'id_type' => $request->type,
                ],
                [
                    'user_id' => $user->id,
                    'id_safe_id' => $server_output->data->_id,
                    'id_type' => $request->type,
                    "id_value" => $request->number,
                    "otp_id" => $server_output->data->otpId,
                    "id_request_data" =>  $response,
                ]
            );

            return response(
                [
                    'message' => $server_output->message,

                ],
                200
            );
        }

        return response(
            [
                'message' => "Verification failed, Please confirm the details submitted and retry again"
            ],
            400
        );
    }


    public function createSubAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
        ]);


        if ($validator->fails()) {
            // get summary
            $errors = $validator->errors();
            $errorMessages = [];

            foreach ($errors->all() as $message) {
                $errorMessages[] = $message;
            }

            $summary = implode(" \n", $errorMessages);

            return response(
                [
                    'error' => true,
                    'message' => $summary
                ],
                422
            );
        }


        $user = auth('api')->user();
        $user = User::find($user->id);

        $sub_account = SafeSubAccount::where("user_id", $user->id)->where("status", 0)->first();

        $verif_body = [
            "phoneNumber" => $user->phone,
            "emailAddress" => $user->email,
            "externalReference" => strval($user->id) . "|" . ($user->created_at ? $user->created_at->timestamp : 'nil'),
            "identityType" => $sub_account->id_type,
            "identityNumber" => $sub_account->id_value,
            "otp" => $request->otp,
            "callbackUrl" => "https://api.tupay.ng/api/safe-hook",
            "identityId" => $sub_account->id_safe_id,
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


        $bank_ok =  SafeHavenService::createSubAccount($user->id, $sub_account->id_type, $request->otp);

        if ($bank_ok) {
            return response(
                [
                    'message' => $request->type . " Verified successfully",

                    'data' => TupaySubAccount::where('user_id', $user->id)->first(),
                ],
                200
            );
        }
    }
}
