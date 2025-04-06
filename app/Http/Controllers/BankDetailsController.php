<?php

namespace App\Http\Controllers;

use App\Models\AppConfig;
use App\Models\BankDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

if (!function_exists('areNamesSimilar')) {
    /**
     * Check if two names have at least two common words.
     *
     * @param string $name1
     * @param string $name2
     * @return bool
     */
    function areNamesSimilar($name1, $name2)
    {
        // Remove special characters and convert to lowercase
        $name1 = strtolower(preg_replace("/[^a-z\s]/", "", $name1));
        $name2 = strtolower(preg_replace("/[^a-z\s]/", "", $name2));

        // Split the names into arrays of words
        $words1 = explode(' ', $name1);
        $words2 = explode(' ', $name2);

        // Find the common words
        $commonWords = array_intersect($words1, $words2);

        // Return true if there are at least 2 common words
        return count($commonWords) >= 2;
    }
}

class BankDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth('api')->user();
        $banks = BankDetails::where("user_id", $user->id)->select('id', 'user_id', 'account_number', 'account_name', 'bank', 'bank_name');

        // if user has bank details

        $data = [
            'status' => 'success',
            'message' => 'Bank accounts gotten successfully',
            'data' => $banks->first() ? $banks : [],
        ];


        return response($data, 200);
    }

    public function getRedBillerBanks()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.live.redbiller.com/1.0/payout/bank-transfer/banks/list",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Private-Key:" . env("REDBILLER_PRIV_KEY")
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return response($response, 200);
    }

    public function verifyCreateRedBillerAccount(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'account_no' => 'required',
            'bank_code' => 'required',
            'bank_name' => 'required',
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

        $bank = BankDetails::where('user_id', $user->id)->where('account_number', $request->account_no)->where('bank', $request->bank_code)->first();

        if ($bank) {
            return response(['status' => false, 'message' => 'Bank account already exixt on account'], 422);
        }


        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/2.6/kyc/bank-account/verify', $request->all());

        $server_output = json_decode($response);


        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {

            $bankname = $data['details']['account_name'];

            $bankname = str_replace(",", "", $bankname);
            // $bankname = explode(' ', $bankname);
            // $firstname = $bankname[0];
            // $lastname = $bankname[1];

            // // convert first name and last name to lowercase
            // $firstname = strtolower($firstname);
            // $lastname = strtolower($lastname);

            // $userFirstName = $user->first_name;
            // $userLastName = $user->last_name;


            $similar = areNamesSimilar($bankname, $user->last_name . " " . $user->first_name);


            if ($similar || true) {

                // check if user has existing bank details

                $bank = new BankDetails();
                $bank->user_id = $user->id;
                $bank->account_number = $request->account_no;
                $bank->account_name = $data['details']['account_name'];
                $bank->bank = $request->bank_code;
                $bank->bank_name = $request->bank_name;
                $bank->save();


                return response(['status' => true, 'message' => 'Bank account verified and saved successfully'], 200);
            } else {
                return response(['status' => false, 'message' => 'Bank account verification failed, Please make sure you name is the same as the one on your bank account'], 422);
            }
        } else {
            return response(['message' => 'Bank not found'], 422);
            Log::error($response);
        }
    }

    public function oldToRedBillerBanks()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.live.redbiller.com/1.0/payout/bank-transfer/banks/list",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Private-Key:" . env("REDBILLER_PRIV_KEY")
            ),
        ));

        $response = curl_exec($curl);


        curl_close($curl);

        $server_output = json_decode($response);
        $red_banks_json = AppConfig::where("name", "red_banks_json")->first();
        // Log::info($response);
        $banks_data = [];
        if ($server_output->response == 200) {
            foreach ($server_output->details as $bank) {
                $bank->id = intval($bank->bank_code);
                $bank->name = $bank->bank_name;
                $bank->code = $bank->bank_code;
            }
            $red_banks_json->update(
                [
                    "value" => $response
                ]
            );
        } else { {

                if ($red_banks_json->value) {
                    $server_output = json_decode($red_banks_json->value);
                    foreach ($server_output->details as $bank) {
                        $bank->id = intval($bank->bank_code);
                        $bank->name = $bank->bank_name;
                        $bank->code = $bank->bank_code;
                    }
                    return response([
                        "status" => $server_output->status == "true",
                        "message" => $server_output->message ?? "Banks retrieved",
                        "data" => $server_output->details
                    ], 200);
                }
            }

            return response([
                "status" => $server_output->status == "true",
                "message" => $server_output->message ?? "Banks retrieved",
                "data" => $server_output->details
            ], 400);
        }





        return response([
            "status" => $server_output->status == "true",
            "message" => $server_output->message ?? "Banks retrieved",
            "data" => $server_output->details
        ], 200);
    }

    public function removeBank($id)
    {
        $user = auth('api')->user();
        $bank_count = BankDetails::where("user_id", $user->id)->count();
        if ($bank_count < 2) {
            $data = [
                'status' => 'error',
                'message' => "You cannot delete the only bank saved under this account. Please add another before deleting this one",

            ];
            return response($data, 422);
        }
        $bank = BankDetails::where("user_id", $user->id)->where("id", $id)->first();
        if ($bank) {
            $bank->delete();
            $data = [
                'status' => 'error',
                'message' => "Bank Deleted Successfully",

            ];
        } else {
            $banks = BankDetails::where("user_id", $user->id)->get();
            $data = [
                'status' => 'error',
                'message' => "Unable to delete bank at this time",

            ];
        }

        $data['data'] = $banks = BankDetails::where("user_id", $user->id)->get();
        return response($data, $bank ? 200 : 422);
    }


}
