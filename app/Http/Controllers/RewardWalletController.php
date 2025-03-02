<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Wallet;
use App\Models\RewardWallet;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\RewardWalletTransaction;

class RewardWalletController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();

        // Fetch user's wallet
        $wallet = RewardWallet::where("user_id", $user->id)->first();

        if ($wallet == null) {
            RewardWallet::create([
                "user_id" => $user->id,
                "balance" => 0,
            ]);
        }

        $wallet = RewardWallet::where("user_id", $user->id)->first();

        // Handle case where no wallet exists
        $balance = $wallet ? $wallet->balance : 0;

        // Fetch referrals with counts for pending and successful status
        $refs = RewardWalletTransaction::where("user_id", $user->id)
            ->where("type", "referral")
            ->get();

        foreach ($refs as $ref) {
            $d_u = User::where('id', $ref->referred_user_id)->first();
            $ref->referred_user = $d_u->first_name;
        }

        $pending_refs_count = $refs->where("status", 0)->count();
        $completed_refs_count = $refs->where("status", 1)->count();

        return response([
            "balance" => $balance,
            "total_ref" => $refs->count(),
            "pending_ref" => $pending_refs_count,
            "successful_ref" => $completed_refs_count,
            "referrals" => $refs,
        ]);
    }

    public function claim()
    {
        $user = auth('api')->user();


        $reward_wallet = RewardWallet::where("user_id", $user->id)->first();

        // check if user has bank details

        // check if the user has enough money to withdraw
        if ($reward_wallet->balance < 2000) {
            return response(['errors' => ['minimum withrawal is #2000']], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Suspended. Contact support for more information"];
            return response($response, 422);
        }


        $walletTransaction = new RewardWalletTransaction;
        $walletTransaction->user_id = $user->id;
        $walletTransaction->amount = $reward_wallet->balance;
        $walletTransaction->status = 1;
        $walletTransaction->type = "withdrawal";



        $amount = $reward_wallet->balance;

        $reward_wallet->balance = 0;
        $reward_wallet->save();
        $walletTransaction->save();

        // credit money wallet
        $user_wallet = Wallet::where("user_id", $user->id)->first();
        $user_wallet->balance = $user_wallet->balance + $amount;
        $user_wallet->save();

        try {
            FCMService::sendToID(
                $user->id,
                [
                    'title' => 'Reward Points Withdrawal',
                    'body' => "You just withdrew " . $amount . " reward  💸.",
                ]
            );
        } catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }


        // respond
        // Fetch referrals with counts for pending and successful status
        $refs = RewardWalletTransaction::where("user_id", $user->id)
            ->where("type", "referral")
            ->get();

        foreach ($refs as $ref) {
            $d_u = User::where('id', $ref->referred_user_id)->first();
            $ref->referred_user = $d_u->first_name;
        }
        $pending_refs_count = $refs->where("status", 0)->count();
        $completed_refs_count = $refs->where("status", 1)->count();

        // return response with form data and message
        return response([
            'message' => 'Reward Point withdrawal request created successfully',
            "balance" => 0,
            "total_ref" => $refs->count(),
            "pending_ref" => $pending_refs_count,
            "successful_ref" => $completed_refs_count,
            "referrals" => $refs,
        ], 200);
    }
}
