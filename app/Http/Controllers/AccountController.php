<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\DeleteAccountRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AccountController extends Controller
{

    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', Rule::unique('users')->ignore($user->id)],
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all()], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n Contact support for more information"];
            return response($response, 422);
        }

        $user_email = User::where('email', $request->email)->first();
        if ($user_email && $user_email->id != $user->id) {
            $response = ["message" => "Email already exists"];
            return response($response, 422);
        }

        $form = $request->all();

        if ($request->phone) {
            if (!str_contains($request->phone, '+')) {
                $form['phone'] = "+" . $request->phone;
            }
        }

        $user_phone = User::where('phone', $request->phone)->first();
        if ($user_phone && $user_phone->id != $user->id) {
            $response = ["message" => "Phone already exists"];
            return response($response, 422);
        }



        if ($request->has('photo')) {
            $uploadedFileUrl = Cloudinary::upload($request->qr_image->getRealPath())->getSecurePath();
            $form['photo'] = $uploadedFileUrl;
        }



        User::find($user->id)->update($form);



        $response = [
            'message' => 'Profile updated',
            'user' => User::find($user->id),
        ];
        return response($response, 200);
    }

    public function updatePassword(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required|string|min:6',

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

        if (!Hash::check($request->password, $user->password)) {
            $response = ["message" => "Password mismatch"];
            return response($response, 422);
        }

        $request['password'] = Hash::make($request['password']);


        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }

        $user = User::find($user->id)->update($request->toArray());
        $response = [
            'message' => "password updated"
        ];
        return response($response, 200);
    }

    public function updateFcm(Request $request)
    {
        // validate the request...
        $validator = Validator::make($request->all(), [
            'fcm' => 'required|string',
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


        $user->fcm = $request->fcm;
        $user = User::find($user->id)->update($request->toArray());
        return response($user, 200);
    }

    public function deleteAccountRequest(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
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

        $delReq = DeleteAccountRequest::where('user_id', $user->id)->first();
        if ($delReq) {
            return response([
                'error' => true,
                'message' => 'You already have an account deletion request submitted',
            ], 373);
        }

        $deleteRequest = DeleteAccountRequest::create([
            'user_id' => $user->id,
            'reason' => $request->reason,
        ]);



        return response([
            'error' => false,
            'message' => 'Account Deletion request submitted and would be reviewed shortly.',
            'request_details' => $deleteRequest,
        ], 200);
    }

    public function getUser($id)
    {
        // return response([], 200);
        // $users = User::where('role', '>', 0)->select('id', 'first_name', 'last_name', 'email', 'phone', 'role', 'created_at', 'photo', 'status')->get();
        $user = User::where("id", $id)->first();

        if (!$user) {
            return response(['message' => 'User not found'], 422);
        }

        $bvn = Verification::where("type", "bvn")->where("user_id", $user->id)->first();
        $nin = Verification::where("type", "nin")->where("user_id", $user->id)->first();
        $user_wallet = Wallet::where("user_id", $user->id)->first();
        $user->balance = $user_wallet->balance;
        $user->photo = env('APP_URL') . $user->photo;
        $user->bvn_verification = $bvn && $bvn->verification_status == "Approved" ? 1 : 0;
        $user->nin_verification = $nin && $nin->verification_status == "Approved" ? 1 : 0;
        if ($user->referrer && $user->referrer !=  null) {
            $referrer = User::where("username", $user->referrer)->first();
            if ($referrer) {
                $user->referrer =  $referrer->first_name . " " . User::where("username", $user->referrer)->first()->last_name;
            }
        }




        // // return response with message and data
        return response($user, 200);
    }

    public function usersList()
    {
        $signedInUser = auth('api')->user();

        if ($signedInUser->role < 1) {
            $response = ["message" => "Permission Denied. Contact your supervisor for more information"];
            return response($response, 422);
        }

        $latestUsers = User::orderBy('created_at', 'desc')
        ->take(100)
            ->get();

        // Get users that have phone numbers
        $admins = User::where('role', '>', 0)->get();

        $combinedUsers = $latestUsers->merge($admins);
        $uniqueUsers = $combinedUsers->unique('id');


        foreach ($uniqueUsers  as $user) {
            $bvn = Verification::where("type", "bvn")->where("user_id", $user->id)->first();
            $nin = Verification::where("type", "nin")->where("user_id", $user->id)->first();
            $user_wallet = Wallet::where("user_id", $user->id)->first();
            $user->balance = $user_wallet->balance;
            $user->photo = env('APP_URL') . $user->photo;
            $user->bvn_verification = $bvn && $bvn->verification_status == "Approved" ? 1 : 0;
            $user->nin_verification = $nin && $nin->verification_status == "Approved" ? 1 : 0;
            if ($user->referrer && $user->referrer !=  null) {
                $referrer = User::where("username", $user->referrer)->first();
                if ($referrer) {
                    $user->referrer =  $referrer->first_name . " " . User::where("username", $user->referrer)->first()->last_name;
                }
            }
        }


        // // return response with message and data
        return response($uniqueUsers, 200);
    }

}
