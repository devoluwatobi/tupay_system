<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Models\BettingTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class BettingTransactionController extends Controller
{
    public function getBettingPlatforms()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.live.redbiller.com/1.4/bills/betting/providers/list",
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


    public function verifyBettingAccount(Request $request)
    {

        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'product' => 'required',
            'customer_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // make http request with Authorization header
        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.4/bills/betting/account/verify', $request->all());

        $server_output = json_decode($response);

        $data = $response->json();
        if ($server_output->response != 200) {
            $data->message = "Unable to complete request, please try again";
            return response($data, $server_output->response);
        }

        if ($data) {
            $data = $response->json();
            return response($data, $server_output->response);
        } else {
            return response($server_output, [
                "product" => $request->product,
                "customer_id" => $request->customer_id,
                "profile" => [
                    "first_name" => "Nil",
                    "surname" => "Nil",
                    "username" => $request->customer_id,
                ]
            ]);
        }
    }

    public function fundBettingAccount(Request $request)
    {

        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'product' => 'required',
            'customer_id' => 'required',
            'amount' => 'required|numeric|min:100|max:2500000',
            // 'reference' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        if ($user->status != 1 || $user->status != 1) {
            $response = ["message" => 'User Account Restricted.\n'];
            return response($response, 423);
        }

        $wallet = $user->wallet;

        $wallet = $user->wallet;
        $rmb_wallet = $user->rmb;

        $book_balance = WalletService::getBookBalance($user->id);

        if ((($book_balance['ngn'] + 100) < $wallet->balance) || (($book_balance['rmb'] + 100) < $rmb_wallet->balance)) {


            User::find($user->id)->update([
                "status" => 0
            ]);

            FCMService::sendToAdmins([
                "title" => "User account got restricted",
                "body" => "A user account ( " . $user->email . " ) with irregular balance just got restricted. Please do check."
            ]);

            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }

        // check if the user has enough money to withdraw
        if ($wallet->balance < $request->amount) {
            return response(['message' => 'You do not have enough money in your wallet to withdraw'], 422);
        }

        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = $request->all();
        $body["reference"] = $transactionRef;


        // make http request with Authorization header
        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.test.redbiller.com/1.4/bills/betting/account/payment/create', $body);

        $server_output = json_decode($response);

        $data = $response->json();
        if ($server_output->response == 200) {
            $data = [
                'user_id' => $user->id,
                'amount' => $server_output->details->amount,
                'reference' => $server_output->details->reference,
                'product' => $server_output->details->product,
                'customer_id' => $server_output->details->customer_id,
                'first_name' => $server_output->details->profile->first_name,
                'surname' => $server_output->details->profile->surname,
                'username' => $server_output->details->profile->username,
                'date' => $server_output->details->date,
                'charge' => $server_output->details->charge,
                'bet_status' => $server_output->meta->status,
                'status' => 1,
            ];
            $trx =   BettingTransaction::create($data);

            return response($trx, 200);
        } else {
            Log::info($data);
        }

        return response($data, $server_output->response);
    }
}
