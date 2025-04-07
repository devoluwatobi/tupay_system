<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SystemConfig;
use App\Models\Verification;
use Illuminate\Http\Request;
use App\Models\SafeVerification;
use App\Models\TupaySubAccount;
use App\Services\SafeHavenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SafeVerificationController extends Controller
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

        $old_v = SafeVerification::where("user_id", $user->id)->where("otp", "!=", "")->where("status", 1)->first();

        if ($old_v &&  $old_v->status == 1) {

            SafeHavenService::createSubAccount($user->id, $old_v->type, $old_v->otp);

            $user->update(
                [
                    'first_name' => $old_v->firstName ??  $user->first_name,
                    'last_name' => $old_v->lastName ??  $user->last_name,
                ]
            );
            return response(['status' => true, 'message' => $request->type . ' has already been verified and saved successfully', 'verification_details' => SafeVerification::where("user_id", $user->id)->where("type", $request->type)->where("status", 1)->get(),], 200);
        }

        $ex_v =  SafeVerification::where('type', $request->type)->where("value", $request->number)->where("status", 1)->first();

        if ($ex_v &&  $ex_v->status == 1) {

            SafeHavenService::createSubAccount($user->id, $old_v->type, $old_v->otp);

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
            SafeVerification::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $request->type,
                ],
                [
                    'user_id' => $user->id,
                    'safe_id' => $server_output->data->_id,
                    'type' => $request->type,
                    'otp' => "____",
                    "value" => $request->number,
                    "otp_id" => $server_output->data->otpId,
                    "request_data" =>  $response,
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

    public function validateVerification(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'otp' => 'required',
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

        $pend_v = SafeVerification::where("type", $request->type)->where("user_id", $user->id)->first();
        $old_v = SafeVerification::where("user_id", $user->id)->where("status", 1)->where("otp", "!=", "")->first();

        if ($old_v &&  $old_v->status == 1) {


            $user->update(
                [
                    'first_name' => $old_v->firstName ??  $user->first_name,
                    'last_name' => $old_v->lastName ??  $user->last_name,
                ]
            );
            return response(['status' => true, 'message' => $request->type . ' has already been verified and saved successfully', 'verification_details' => SafeVerification::where("user_id", $user->id)->where("type", $request->type)->where("status", 1)->get(),], 200);
        }

        if (!$pend_v ||  $pend_v == null) {
            return response(['status' => false, 'message' => $request->type . ' verification failed, please request a new OTP and try again'], 403);
        }

        $verif_body = [
            "identityId" => $pend_v->safe_id,
            "type" => $request->type,
            "otp" => $request->otp,
        ];

        Log::info($verif_body);

        SafeHavenService::refreshAccess();

        // safe_haven
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $client_id = SystemConfig::where('name', 'ibs_client_id')->first();


        // verify bank details first

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token->value, 'ClientID' => $client_id->value, 'Content-Type' => 'application/json'])->post(env("SAFEHAVEN_BASE_URL") . '/identity/v2/validate', $verif_body);

        $server_output = json_decode($response);

        $verification_data = $response->json();

        Log::info($verification_data);

        if ($server_output != null && $server_output->statusCode == 200 && $server_output->data && $server_output->data->providerResponse) {
            SafeVerification::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => $request->type,
                ],
                [
                    'user_id' => $user->id,
                    'type' => $request->type,
                    'otp' => $request->otp,
                    'status' => 1,
                    'firstName' => $server_output->data->providerResponse->firstName ?? "",
                    'middleName' => $server_output->data->providerResponse->middleName ?? "",
                    'lastName' => $server_output->data->providerResponse->lastName ?? "",
                    'dateOfBirth'  => $server_output->data->providerResponse->dateOfBirth ?? "",
                    'phoneNumber1' => $server_output->data->providerResponse->phoneNumber1 ?? "",
                    'phoneNumber2'  => $server_output->data->providerResponse->phoneNumber2,
                    'gender' => $server_output->data->providerResponse->gender,
                    'enrollmentBank' => $server_output->data->providerResponse->enrollmentBank,
                    'enrollmentBranch' => $server_output->data->providerResponse->enrollmentBranch,
                    'email' => $server_output->data->providerResponse->email,
                    'lgaOfOrigin' => $server_output->data->providerResponse->lgaOfOrigin,
                    'lgaOfResidence' => $server_output->data->providerResponse->lgaOfResidence,
                    'maritalStatus' => $server_output->data->providerResponse->maritalStatus,
                    'nationality' => $server_output->data->providerResponse->nationality,
                    'residentialAddress' => $server_output->data->providerResponse->residentialAddress,
                    'stateOfOrigin' => $server_output->data->providerResponse->stateOfOrigin,
                    'stateOfResidence' => $server_output->data->providerResponse->stateOfResidence,
                    'title' => $server_output->data->providerResponse->title,
                    'watchListed' => $server_output->data->providerResponse->watchListed,
                    'levelOfAccount' => $server_output->data->providerResponse->levelOfAccount,
                    'registrationDate' => $server_output->data->providerResponse->registrationDate,
                    'imageBase64' => $server_output->data->providerResponse->imageBase64,
                    "validation_data" =>  $response,
                ]
            );

            $user->update(
                [
                    'first_name' => $server_output->data->providerResponse->firstName ?? $user->first_name,
                    'last_name' => $server_output->data->providerResponse->lastName ?? $user->last_name,
                ]
            );


            return response(
                [
                    'message' => $request->type . " Verified successfully",


                ],
                200
            );

            return response(
                [
                    'message' => $request->type . " Verified successfully",

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


        $user = auth('api')->user();
        $user = User::find($user->id);

        $verification = SafeVerification::where("user_id", $user->id)->where("status", 1)->first();

        $verif_body = [
            "phoneNumber" => $user->phone,
            "emailAddress" => $user->email,
            "externalReference" => strval($user->id) . "|" . ($user->created_at ? $user->created_at->timestamp : 'nil'),
            "identityType" => $verification->type,
            "identityNumber" => $verification->value,
            "otp" => $verification->value,
            "callbackUrl" => "https://webhook.site/2a530298-5ae4-4955-aa48-9c1420a2bc3d",
            "identityId" => $verification->safe_id,
            "autoSweep" => true,
            "autoSweepDetails" => [
                "schedule" => "Instant",
                "accountNumber" => env('SAFEHAVEN_DEBIT_ACCOUNT'),
            ],

        ];

        Log::info(json_encode($verif_body));

        SafeHavenService::refreshAccess();

        // safe_haven
        $token = SystemConfig::where('name', 'safehaven_token')->first();
        $client_id = SystemConfig::where('name', 'ibs_client_id')->first();


        // verify bank details first

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token->value, 'ClientID' => $client_id->value, 'Content-Type' => 'application/json'])->post(env("SAFEHAVEN_BASE_URL") . '/accounts/v2/subaccount', $verif_body);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($data);
    }
}
