<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Models\RMBPaymentType;
use App\Models\RMBTransaction;
use App\Models\RMBPaymentMethod;
use App\Mail\RMBTransactionCreated;
use App\Models\RMBWallet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class RMBTransactionController extends Controller
{
    public function makeTransaction(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            'r_m_b_payment_method_id' => 'required',
            'r_m_b_payment_type_id' => 'required',
            // 'recipient_id' => 'required',
            // 'recipient_name' => 'required',
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

        $method =  RMBPaymentMethod::where("id", $request->r_m_b_payment_method_id)->first();
        $type =  RMBPaymentType::where("id", $request->r_m_b_payment_type_id)->first();



        $form = $request->toArray();

        if (!$method || !$type) {
            return response(
                [
                    'error' => true,
                    'message' => "Invalid payment method or type. Please check and retry again"
                ],
                422
            );
        }



        $wallet = Wallet::where("user_id", $user->id)->first();
        $rmb_wallet = RMBWallet::where("user_id", $user->id)->first();

        if ($request->paid_with != null && ($request->paid_with == 'RMB' || $request->paid_with == 'rmb')) {
            $balance = $rmb_wallet->balance;
            $charge = $method->rmb_charge;
            $rate = 1;
            $total = ($request->amount) + $method->rmb_charge;
            $use_wallet = $rmb_wallet;
        } else {
            $balance = $wallet->balance;
            $charge = $method->charge;
            $rate = $method->rate;
            $total = ($request->amount * $method->rate) + $method->charge;
            $use_wallet = $wallet;
        }

        if ($total > $balance) {
            $response = ['message' => "You don't have enough in your " . $request->paid_with . " balance for this transaction. Please try funding " . $request->paid_with . " your wallet"];
            return response(
                $response,
                422
            );
        }

        $updates = [];

        $action = [
            "user_id" => $user->id,
            "time" => Carbon::now(),
            "status" => 0,
            "description" => "Created a new Transaction"
        ];

        if ($request->has('qr_image')) {
            $uploadedFileUrl = Cloudinary::upload($request->qr_image->getRealPath())->getSecurePath();
            $form['qrCode'] = $uploadedFileUrl;
        }

        $updates[] = $action;

        $form["user_id"] = $user->id;
        $form["rate"] = $rate;
        $form["charge"] = $charge;
        $form["r_m_b_payment_method_title"] = $method->title;
        $form["r_m_b_payment_type_title"] = $type->title;
        $form["proofs"] = json_encode([]);
        $form["updates"] = json_encode($updates);

        // Debit user
        $use_wallet->balance = $use_wallet->balance - $total;
        $use_wallet->save();

        // Create Transaction
        $transaction =  RMBTransaction::create($form);

        try {
            FCMService::sendToID(
                $user->id,
                [
                    'title' => 'CN¥' . $request->amount . ' ' .  $method->title . ' Funds Requested',
                    'body' => "Your " . $method->title . " Transaction of CN¥" . $request->amount . " has been created successfully",
                ]
            );
            FCMService::sendToAdmins(
                [
                    'title' => 'New RMB Transaction',
                    'body' => "There's a new CN¥" . $request->amount . " transaction submitted.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }

        // Dispatch the mail job to queue
        try {
            $transaction->sendStatusUpdateEmail();
        } catch (\Exception $e) {
            Log::error('Mail Error: ' . $e->getMessage());
        }



        $response = ["message" => 'Transaction created successfully', 'data' => $transaction];
        return response($response, 200);
    }

    public function getTransaction($id)
    {


        $transaction = RMBTransaction::where('id', $id)->first();


        $data = [
            'message' => 'Transaction fetched successfully',
            'rmb_transaction' => $transaction,
        ];
        return response($data, 200);
    }

    public function fail(Request $request)
    {
        $user = auth('api')->user();
        if ($user->role < 1) {
            return response(['message' => ['Permission denied.']], 422);
        }
        $validator = Validator::make($request->all(), [
            'trx_id' => 'required',
            'remark' => 'required',
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

        $transaction = RMBTransaction::where('id', $request->trx_id)->first();
        if ($transaction->status > 0) {
            return response(['message' => ['Transaction status cannot be updated.']], 422);
        }

        $form = [
            'status' => 2,
            'remark' => $request->remark,
        ];

        $updates = json_decode($transaction->updates);


        $action = [
            "user_id" => $user->id,
            "time" => Carbon::now(),
            "status" => 2,
            "description" => "Failed Transaction \n" . $request->remark,
        ];


        $updates[] = $action;

        $form["updates"] = json_encode($updates);

        $trxUser = User::where('id', $transaction->user_id)->first();

        $transaction->update($form);
        $response = ["message" => 'Transaction rejected'];

        try {
            FCMService::sendToID(
                $trxUser->id,
                [
                    'title' => 'RMB Transaction Failed',
                    'body' => "Your TMB transaction with the reference has failed unfortunately. Pleae check app for more details.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }

        // Dispatch the mail job to queue
        try {
            $transaction->sendStatusUpdateEmail();
        } catch (\Exception $e) {
            Log::error('Mail Error: ' . $e->getMessage());
        }





        return response($response, 200);
    }

    public function approve(Request $request)
    {
        $user = auth('api')->user();
        if ($user->role < 1) {
            return response(['message' => ['Permission denied.']], 422);
        }
        $validator = Validator::make($request->all(), [
            'trx_id' => 'required',
            'remark' => 'required',
            'proofs' => 'required',
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

        $transaction = RMBTransaction::where('id', $request->trx_id)->first();

        if ($transaction->status > 0) {
            return response(['message' => ['Transaction status cannot be updated.']], 422);
        }

        $uploadedFilesUrls = [];
        foreach ($request->file('proofs') as $key => $file) {
            $uploadedFileUrl = Cloudinary::upload($file->getRealPath())->getSecurePath();
            $uploadedFilesUrls[] = $uploadedFileUrl;
        }

        $form = [
            'status' => 1,
            'remark' => $request->remark,
            'proofs' => json_encode($uploadedFilesUrls),
        ];

        $updates = json_decode($transaction->updates);


        $action = [
            "user_id" => $user->id,
            "time" => Carbon::now(),
            "status" => 1,
            "description" => "Approved Transaction \n" . $request->remark,
        ];


        $updates[] = $action;

        $form["updates"] = json_encode($updates);

        $trxUser = User::where('id', $transaction->user_id)->first();

        $transaction->update($form);
        $response = ["message" => 'Transaction rejected'];

        try {
            FCMService::sendToID(
                $trxUser->id,
                [
                    'title' => "CN¥" . $transaction->amount . ' Successfull',
                    'body' => "Your RMB transaction of CN¥" . $transaction->amount . " is complete. Please check app for more details.",
                ]
            );
        } //catch exception
        catch (Exception $e) {
            Log::error('Message: ' . $e->getMessage());
        }

        // Dispatch the mail job to queue
        try {
            $transaction->sendStatusUpdateEmail();
        } catch (\Exception $e) {
            Log::error('Mail Error: ' . $e->getMessage());
        }





        return response($response, 200);
    }
}
