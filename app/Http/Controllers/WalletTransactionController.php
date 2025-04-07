<?php

namespace App\Http\Controllers;

use DateTimeZone;
use Carbon\Carbon;
use App\Models\User;
use App\Models\AppConfig;
use App\Models\BankDetails;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Models\WalletTransaction;
use App\Services\SafeHavenService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class WalletTransactionController extends Controller
{

    public function withdraw(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:2500000',
        ]);



        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all(), 'message' => $validator->errors()->first()], 422);
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

        // check if user has bank details
        $bankAccount = null;
        if ($request->my_bank_id) {
            $bankAccount = BankDetails::where('user_id', $user->id)->where("id", $request->my_bank_id)->first();
        } else {
            $bankAccount = BankDetails::where('user_id', $user->id)->first();
        }

        if (!$bankAccount) {
            return response(['error' => 'You have no bank details', 'message' => "User's bank does not exist, please add or update bank to continue"], 400);
        }

        // check if the user has enough money to withdraw
        if ($wallet->balance < $request->amount) {
            return response(['message' => 'You do not have enough money in your wallet to withdraw'], 422);
        }

        if ($wallet->balance < ($request->amount + 50)) {
            return response(['message' => 'You do not have enough to cover for the charges of this transaction'], 422);
        }

        // check if the balance is within the range of the minimum and maximum withdrawal amount
        if ($request->amount < 1000 || $request->amount > 2500000) {
            return response(['message' => 'The amount you are trying to withdraw is not within the range of the minimum and maximum withdrawal amount'], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }



        $amount = number_format($request->amount, 2);

        $is_auto = AppConfig::where("name", "auto_payment")->first();


        if (($is_auto && ($is_auto->value == "1"))) {
            $trx_response = null;
            $trx_response =  SafeHavenService::withdrawFunds($bankAccount->id, $request->amount, $request->ip(), $request->header('User-Agent'), $request->all());
            if ($trx_response) {

                return response($trx_response['data'], $trx_response['status']);
            }

            return response(['message' => 'Wallet withdrawal request created successfully'], 200);
        } else {
        }

        $walletTransaction = new WalletTransaction;
        $walletTransaction->user_id = $user->id;
        $walletTransaction->amount = $request->amount;
        $walletTransaction->bank_id = $bankAccount->id;
        $walletTransaction->status = 0;
        $walletTransaction->bank = $bankAccount->bank;
        $walletTransaction->bank_name = $bankAccount->bank_name;
        $walletTransaction->account_number = $bankAccount->account_number;
        $walletTransaction->account_name = $bankAccount->account_name;
        $walletTransaction->charge = 50;

        $wallet->balance = $wallet->balance - ($request->amount + 50);
        $wallet->save();

        // create unique transaction reference id
        $transaction_ref = uniqid();
        $walletTransaction->transaction_ref = $transaction_ref;
        $walletTransaction->save();


        try {

            FCMService::sendToAdmins(
                [
                    'title' => 'New Withdrawal Request',
                    'body' => "There is a new withdrawal request of NGN" . $request->amount . " by a Tupay user",
                ]
            );

            // $wa_time = new DateTimeZone('Africa/Lagos');
            // $time = $walletTransaction->created_at->setTimezone($wa_time);

            // //send a funds confirmation email to the user
            // Mail::to($user->email)->send(new \App\Mail\WithdrawWallet($user, $amount, number_format($wallet->balance, 2), date("h:iA, F jS, Y", strtotime("$time"))));
        } catch (\Exception $e) {
            info($e);
        }

        // create form with the transaction reference id, amount and status
        $form = [
            'user_id' => $user->id,
            'amount' => $request->amount,
            'status' => 0,
            'transaction_ref' => $transaction_ref,
        ];

        // return response with form data and message
        return response([
            'message' => 'Wallet withdrawal request created successfully',
            'data' => $form,
        ], 200);
    }
}
