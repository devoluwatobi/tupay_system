<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Wallet;
use App\Models\OTPCodes;
use Illuminate\Support\Str;
use App\Mail\VerifyOtpEmail;
use App\Models\RewardWallet;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\RewardWalletTransaction;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
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

        if (!str_contains($request->phone, '+')) {
            $request['phone'] = "+" . $request->phone;
        }
        $request['password'] = Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $pattern = '/[^A-Za-z0-9\s]/';
        $username = strtolower(str_replace(' ', '', preg_replace($pattern, '', $request->last_name)));
        $count = User::where('username', $username)->count();
        if ($count > 0) {
            $username .= ($count + 1);
        }
        $username = $username . rand(1000, 9999);
        $request['username'] = $username;
        $userraw = User::create($request->toArray());
        $user = User::where('id', $userraw->id)->first();
        $token = $user->createToken('Tupay Password Grant Client')->accessToken;
        // send mail to user

        // create the user wallet
        $wallet =  [
            'user_id' => $user->id,
            'balance' => 0.0,
        ];

        Wallet::create($wallet);

        $otp_token =  random_int(100000, 999999);

        OTPCodes::create([
            "token" => $otp_token,
            "destination" => $user->email,
            "user_id" => $user->id,
        ]);

        // Handle Referral
        if ($request->referrer) {
            $referr = User::where("username", $request->referrer)->first();
            if ($referr) {

                RewardWalletTransaction::create([
                    "amount" => 5,
                    "user_id" => $referr->id,
                    "type" => "referral",
                    "referred_user_id" => $user->id,
                    "status" => 1,
                ]);

                $referrer_reward_wallet = RewardWallet::where("user_id", $referr->id)->first();
                if ($referrer_reward_wallet == null) {
                    RewardWallet::create([
                        "user_id" => $referr->id,
                        "balance" => 0,
                    ]);
                }
                $referrer_reward_wallet = RewardWallet::where("user_id", $referr->id)->first();
                $referrer_reward_wallet->balance = $referrer_reward_wallet->balance + 200;
                $referrer_reward_wallet->save();

                try {
                    FCMService::sendToID(
                        $referr->id,
                        [
                            'title' => 'NGN2000 Reward Earned 🚀',
                            'body' => "You just earned NGN2000 reward from a referral.",
                        ]
                    );
                } //catch exception
                catch (Exception $e) {
                    Log::error('Message: ' . $e->getMessage());
                }
            }
        }


        try {
            Mail::to($user->email)->send(new VerifyOtpEmail($user->email, $otp_token, $user->first_name,));
        } catch (Exception $e) {
            Log::error("Error: " . $e->getMessage());
        }


        $response = [
            'token' => $token,
            'user' => $user,
            'otp' => $otp_token,
        ];
        return response($response, 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
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

        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                if ($user->status == 1) {
                    if ($user->role < 1) {
                        $user->tokens()->delete();
                    }
                    $token = $user->createToken('Tupay Password Grant Client')->accessToken;
                    $response = ['token' => $token, 'user' => $user];
                    return response($response, 200);
                } else {
                    $response = ["message" => "Account Restricted.\n. Contact support for more information"];
                    return response($response, 422);
                }
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = [
                "error" => true,
                "message" => 'User does not exist'
            ];
            return response($response, 422);
        }
    }

    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
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

        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                if ($user->status == 1) {

                    if ($user->role < 1) {
                        $response = ["message" => "Permission Denied. Contact your supervisor for more information"];
                        return response($response, 422);
                    }
                    $token = $user->createToken('Tupay Password Grant Client')->accessToken;
                    $response = ['token' => $token, 'user' => $user];
                    return response($response, 200);
                } else {
                    $response = ["message" => "Account Restricted.\n. Contact support for more information"];
                    return response($response, 422);
                }
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = [
                "error" => true,
                "message" => 'User does not exist'
            ];
            return response($response, 422);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
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

        $email =  $request->email;
        //You can add validation login here
        $user = User::where('email', $email)->first();
        //Check if the user exists
        if (!$user) {
            $response = [
                'message' => "User is not registered",
            ];
            return response($response, 422);
        }
        $token = random_int(100000, 999999);
        // $token = 123456;

        $tokendata = DB::table('password_resets')->where('email', $email)->first();

        if ($tokendata) {
            DB::table('password_resets')->where('email', $email)->update([
                'token' => $token,
                'created_at' => date("Y-m-d H:i:s", strtotime('now'))
            ]);
        } else {

            //Create Password Reset Token
            DB::table('password_resets')->insert([
                'email' => $user->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);
        }



        $receiverNumber = $request->phone;
        $message = "Your Tupay verification code is " . $token;

        try {

            Mail::to($user->email)->send(new VerifyOtpEmail($user->email, $token, $user->first_name));
        } catch (Exception $e) {
            // Log::error("Error: " . $e->getMessage());
        }

        $response = [
            'message' => "Password reset code sent",
            'otp' => $token,
        ];
        return response($response, 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|string|max:255',
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

        $token = $request->token;
        $tokendata = DB::table('password_resets')->where('token', $token)->where('email', $request->email)->first();
        if ($tokendata) {

            if ($tokendata->created_at > Carbon::now()->subMinutes(60)->toDateTimeString() && $tokendata->email == $request->email) {
                $user = User::where('email', $tokendata->email)->first();
                $validator = Validator::make($request->all(), [
                    'password' => 'required|string|min:6',
                ]);
                $request['password'] = Hash::make($request['password']);
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
                } else {
                    $user = User::find($user->id)->update($request->toArray());
                }
            } else {
                $response = [
                    'message' => "Token expired or not accepted "
                ];
                return response($response, 422);
            }
        } else {
            $response = [
                'message' => "Token expired or not accepted "
            ];
            return response($response, 422);
        }

        $response = [
            'message' => "password updated"
        ];
        return response($response, 200);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }

    public function verifyEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
            'email' => 'required',
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


        $user = User::where('email', $request->email)->first();

        if ($user) {

            $otp = OTPCodes::where("user_id", $user->id)->first();

            if ($otp->updated_at < Carbon::now()->subMinutes(60)) {
                return response(['message' => 'Token Expired'], 200);
            }
            if ($otp && $otp->updated_at > Carbon::now()->subMinutes(60) && $otp->token == $request->otp) {
                $form = [
                    'email_verified_at' =>  date("Y-m-d H:i:s", strtotime('now')),
                ];
                User::where('id', $user->id)->update($form);
                $response = ['message' => 'Email Verified'];

                return response($response, 200);
            } else {
                $response = [
                    'message' => "Token expired or not accepted "
                ];
                return response($response, 422);
            }
        } else {
            $response = ['message' => 'Email not registered'];
            return response($response, 422);
        }
    }

    public function resendActivationOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
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

        $user = User::where("email", $request->email)->first();

        if (!$user) {
            return response(
                [
                    'error' => true,
                    'message' => "User does not exist"
                ],
                422
            );
        }

        $otp_token =  random_int(100000, 999999);

        $otp = OTPCodes::where("user_id", $user->id)->first();
        // $otp = $user->activationCode();



        if ($otp) {
            $otp->update([
                "token" => $otp_token
            ]);
        } else {
            OTPCodes::create([
                "token" => $otp_token,
                "destination" => $user->email,
                "user_id" => $user->id,
            ]);
        }



        $receiverNumber = $request->phone;
        $message = "Your Tupay verification code is " . $otp_token;

        try {

            if ($user) {
                Mail::to($user->email)->send(new VerifyOtpEmail($user->email, $otp_token, $user->first_name,));
            }
        } catch (Exception $e) {
            Log::error("Error: " . $e->getMessage());
        }


        $response = [
            'token' => $otp_token,
            'message' => 'otp Sent'
        ];
        return response($response, 200);
    }
}
