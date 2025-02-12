<?php

namespace App\Http\Controllers;

use App\Console\Commands\SafeHaven;
use Carbon\Carbon;
use App\Models\User;
use App\Models\SystemConfig;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\SafeVerification;
use App\Services\SafeHavenService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    public function index()
    {

        $user = auth('api')->user();

        return response(['status' => true, 'message' => 'Verifications Fetched successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
        //
    }

    public function verifyBVN(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'account_no' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
            'bvn' => 'required',
        ]);


        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }
        $user = auth('api')->user();
        $user = User::find($user->id);

        $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first();

        if ($old_v &&  $old_v->status == 1) {
            return response(['status' => true, 'message' => 'BVN verified and saved successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
        }


        // verify bank details first
        $bank_response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/2.6/kyc/bank-account/verify', $request->all());

        $bank_server_output = json_decode($bank_response);

        $bank_data = $bank_response->json();

        if ($bank_data['status'] == true && $bank_data['response'] == 200) {

            $bank_name = $bank_data['details']['account_name'];
            $bank_name = str_replace(",", "", $bank_name);
            $bank_name = explode(' ', $bank_name);
            $first_name = $bank_name[0];
            $last_name = $bank_name[1];

            // convert first name and last name to lowercase
            $first_name = strtolower($first_name);
            $last_name = strtolower($last_name);

            $userFirstName = $user->first_name;
            $userLastName = $user->last_name;

            if (!(strtolower($userFirstName) == $first_name || strtolower($userLastName) == $last_name || strtolower($userFirstName) == $last_name || strtolower($userLastName) == $first_name)) {
                return response(['status' => false, 'message' => 'BVN verification failed, Account name does not correlate with kdc trade account name', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
            }
        } else {
            return response(['status' => false, 'message' => 'BVN verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
        }
        // end verify bank details

        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        $body  = $request->all();
        $body["reference"] =  $transactionRef;

        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.0/kyc/bvn/verify.3.0', $body);

        $server_output = json_decode($response);

        $data = $response->json();

        Log::info($data);


        // return response(['status' => false, 'message' => 'BVN verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(), 'data' => $data], 422);




        if ($data['status'] == true && $data['response'] == 200) {

            $firstname = $data['details']['personal']['first_name'];
            $lastname = $data['details']['personal']['surname'];

            // convert first name and last name to lowercase
            $firstname = strtolower($firstname);
            $lastname = strtolower($lastname);

            $userFirstName = $user->first_name;
            $userLastName = $user->last_name;


            // check if firstname and lastname matches either firstname or lastname of user

            Log::info($data['details']['identification']['bvn'] == $request->bvn);


            if ((strtolower($userFirstName) == $firstname || strtolower($userLastName) == $lastname || strtolower($userFirstName) == $lastname || strtolower($userLastName) == $firstname) && $data['details']['identification']['bvn'] == $request->bvn) {

                // check if user has existing bank details
                $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first(); {
                    if ($old_v) {
                        $old_v->update(
                            [
                                "verification_status" => $data['meta']['status'],
                                "name" => $data['details']['personal']['first_name'] . " " . $data['details']['personal']['surname'],
                                "reference" => $data['details']['reference'],
                                "status" => 1,
                                "value" => $data['details']['identification']['bvn'],
                                "dob" => $data['details']['personal']['date_of_birth'],
                            ]
                        );
                    } else {
                        $verification = new Verification();
                        $verification->user_id = $user->id;
                        $verification->type = "bvn";
                        $verification->verification_status = $data['meta']['status'];
                        $verification->name = $data['details']['personal']['first_name'] . " " . $data['details']['personal']['surname'];
                        $verification->reference = $data['details']['reference'];
                        $verification->value = $data['details']['identification']['bvn'];
                        $verification->status = 1;
                        $verification->dob = $data['details']['personal']['date_of_birth'];

                        $verification->save();
                    }
                }

                $user->update([
                    "first_name" => $data['details']['personal']['first_name'],
                    "last_name" => $data['details']['personal']['surname'],
                ]);

                return response(['status' => true, 'message' => 'BVN verified and saved successfully', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 200);
            } else {

                $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first(); {
                    if ($old_v) {
                        $old_v->update(
                            [
                                "verification_status" => "Failed",
                                "name" =>  "nil",
                                "reference" => "nil",
                                "status" => 0,
                            ]
                        );
                    } else {
                        $verification = new Verification();
                        $verification->user_id = $user->id;
                        $verification->type = "bvn";
                        $verification->verification_status = "Failed";
                        $verification->name = "nil";
                        $verification->reference = "nil";
                        $verification->status = 0;

                        $verification->save();
                    }
                }


                return response(['status' => false, 'message' => 'BVN verification failed, Account name does not correlate with faveremit account name', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
            }
        } else {
            $old_v = Verification::where("type", "bvn")->where("user_id", $user->id)->first(); {
                if ($old_v) {
                    $old_v->update(
                        [
                            "verification_status" => "Failed",
                            "name" =>  "nil",
                            "reference" => "nil",
                            "status" => 0,
                        ]
                    );
                } else {
                    $verification = new Verification();
                    $verification->user_id = $user->id;
                    $verification->type = "bvn";
                    $verification->verification_status = "Failed";
                    $verification->name = "nil";
                    $verification->reference = "nil";
                    $verification->status = 0;

                    $verification->save();
                }
            }

            return response(['status' => false, 'message' => 'BVN verification failed', 'verification_details' => Verification::where("user_id", $user->id)->get(),], 422);
        }
    }
}
