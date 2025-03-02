<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Wallet;
use App\Models\RewardWallet;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\RewardWalletTransaction;
use Illuminate\Support\Facades\Validator;

class RewardWalletTransactionController extends Controller
{
    public function withdraw(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
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

        $reward_wallet = RewardWallet::where("user_id", $user->id)->first();

        // check if user has bank details

        // check if the user has enough money to withdraw
        if ($reward_wallet->balance < $request->amount) {
            return response(['errors' => ['You do not have enough in your wallet to withdraw']], 422);
        }

        // check if the balance is within the range of the minimum and maximum withdrawal amount
        if ($request->amount < 2000 || $request->amount > 100) {
            return response(['errors' => ['The amount you are trying to withdraw is not within the range of the minimum and maximum withdrawal amount']], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Deactivated. Contact support for more information"];
            return response($response, 422);
        }


        $walletTransaction = new RewardWalletTransaction;
        $walletTransaction->user_id = $user->id;
        $walletTransaction->amount = $request->amount;
        $walletTransaction->status = 1;
        $walletTransaction->type = "withdrawal";

        $reward_wallet->balance = $reward_wallet->balance - $request->amount;
        $reward_wallet->save();
        $walletTransaction->save();

        $amount = number_format($request->amount, 2);

        // credit money wallet
        $user_wallet = Wallet::where("user_id", $user->id)->first();
        $user_wallet->balance = $user_wallet->balance + ($request->amount * 25);
        $user_wallet->save();

        try {
            FCMService::sendToID(
                $user->id,
                [
                    'title' => 'Reward Points Withdrawal',
                    'body' => "You just withdrew " . $request->amount . " reward points and got NGN" . $request->amount * 25 . " 💸.",
                ]
            );
        } catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        // create form with the transaction reference id, amount and status
        $form = [
            'user_id' => $user->id,
            'amount' => $request->amount,
            'status' => 0,
            'transaction_ref' => $walletTransaction->id,
        ];

        // return response with form data and message
        return response([
            'message' => 'Reward Point withdrawal request created successfully',
            'data' => $form,
        ], 200);
    }
}
