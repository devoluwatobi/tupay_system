<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Wallet;
use App\Models\RMBWallet;
use App\Models\SystemConfig;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Services\WalletService;
use Illuminate\Support\Facades\Log;
use App\Models\RMBWalletTransaction;
use Illuminate\Support\Facades\Validator;

class RMBWalletTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function convert(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10|max:2500000',
        ]);

        if ($validator->fails()) {
            return response([
                'error' => true,
                'message' => implode("\n", $validator->errors()->all())
            ], 422);
        }

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

        $rate = SystemConfig::where('name', 'rmb2ngn_rate')->first()->value;
        $charge = SystemConfig::where('name', 'rmb2ngn_charge')->first()->value;


        $rmb_wallet = RMBWallet::where("user_id", $user->id)->first();
        $ngn_wallet = Wallet::where("user_id", $user->id)->first();

        $rmb_total = $request->amount + $charge;
        $ngn_total = $request->amount * $rate;



        if ($rmb_total > $rmb_wallet->balance) {
            $response = ['message' => "You don't have enough in your RMB wallet for this transaction. Please try funding your RMB wallet"];
            return response(
                $response,
                422
            );
        } else {
            $rmb_wallet->update([
                'balance' => $rmb_wallet->balance - $rmb_total,
            ]);

            $ngn_wallet->update([
                'balance' => $ngn_wallet->balance + ($ngn_total),
            ]);
        }
        RMBWalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => 0,
            'charge' => $charge,
            'rate' => $rate,
            'status' => 1,
        ]);

        $circulation_config = SystemConfig::where("name", "total_circulation")->first();

        if ($circulation_config) {
            $circulation_config->update([
                "value" => $circulation_config->value + $request->amount + $charge
            ]);
        }

        try {
            FCMService::sendToID(
                $user->id,
                [
                    'title' => 'CN¥' . $request->amount . ' Withdrawn',
                    'body' => "Your withdrawal of CN¥" . $request->amount . " has been processed successfully",
                ]
            );
            FCMService::sendToAdmins(
                [
                    'title' => 'New RMB Withdrawal',
                    'body' => "There's a new CN¥" . $request->amount . " withdrawal submitted.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }
    }

    public function topup(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:50|max:2500000',
        ]);

        if ($validator->fails()) {
            return response([
                'error' => true,
                'message' => implode("\n", $validator->errors()->all())
            ], 422);
        }

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

        $rate = SystemConfig::where('name', 'ngn2rmb_rate')->first()->value;
        $charge = SystemConfig::where('name', 'ngn2rmb_charge')->first()->value;


        $rmb_wallet = RMBWallet::where("user_id", $user->id)->first();
        $ngn_wallet = Wallet::where("user_id", $user->id)->first();

        $rmb_total = $request->amount;
        $ngn_total = ($request->amount * $rate) + $charge;

        if ($ngn_total > $ngn_wallet->balance) {
            $response = ['message' => "You don't have enough in your Naira wallet for this transaction. Please try funding your Naira wallet"];
            return response(
                $response,
                422
            );
        } else {
            $rmb_wallet->update([
                'balance' => $rmb_wallet->balance + $rmb_total,
            ]);

            $ngn_wallet->update([
                'balance' => $ngn_wallet->balance - $ngn_total,
            ]);
        }
        RMBWalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => 1,
            'charge' => $charge,
            'rate' => $rate,
            'status' => 1,
        ]);

        $circulation_config = SystemConfig::where("name", "total_circulation")->first();

        if ($circulation_config) {
            $circulation_config->update([
                "value" => $circulation_config->value - $request->amount
            ]);
        }

        try {
            FCMService::sendToID(
                $user->id,
                [
                    'title' => 'CN¥' . $request->amount . ' Topup',
                    'body' => "Your topup of CN¥" . $request->amount . " has been processed successfully",
                ]
            );
            FCMService::sendToAdmins(
                [
                    'title' => 'New RMB Top-up',
                    'body' => "There's a new CN¥" . $request->amount . " Top-up submitted.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(RMBWalletTransaction $rMBWalletTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RMBWalletTransaction $rMBWalletTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RMBWalletTransaction $rMBWalletTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RMBWalletTransaction $rMBWalletTransaction)
    {
        //
    }
}
