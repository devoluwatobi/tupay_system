<?php

namespace App\Http\Controllers;

use Exception;
use DateTimeZone;
use Carbon\Carbon;
use App\Models\Utility;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\UtilityBillTransaction;
use App\Services\SafeHavenService;
use Illuminate\Support\Facades\Validator;

class UtilityBillTransactionController extends Controller
{

    public function getNetworks()
    {
        // create an object array
        $mtn = [
            'Network' => 'MTN',
            'service_id' => Utility::NETWORK_MTN,
            'data_service_id' => Utility::NETWORK_MTN . '-data',
            'image' => env('APP_URL') . '/images/utility/mtn.png',
            'red_product' => "MTN"
        ];
        $mobile9 = [
            'Network' => '9Mobile',
            'service_id' => Utility::NETWORK_9MOBILE,
            'data_service_id' => Utility::NETWORK_9MOBILE . '-data',
            'image' => env('APP_URL') . '/images/utility/9-mobile.png',
            'red_product' => "9mobile"
        ];
        $airtel = [
            'Network' => 'Airtel',
            'service_id' => Utility::NETWORK_AIRTEL,
            'data_service_id' => Utility::NETWORK_AIRTEL . '-data',
            'image' => env('APP_URL') . '/images/utility/airtel.png',
            'red_product' => "Airtel"
        ];
        $glo = [
            'Network' => 'Glo',
            'service_id' => Utility::NETWORK_GLO,
            'data_service_id' => Utility::NETWORK_GLO . '-data',
            'image' => env('APP_URL') . '/images/utility/glo.png',
            'red_product' => "Glo"
        ];

        // create an array
        $networks = [
            $mtn,
            $mobile9,
            $airtel,
            $glo,
        ];

        SafeHavenService::getService();


        return response($networks, 200);
    }

    public function getTvList()
    {
        // create an object array
        $dstv = [
            'cable' => 'DSTV',
            'service_id' => Utility::CABLE_DSTV,
            'image' => env('APP_URL') . '/images/utility/dstv.png',
            'red_product' => 'DStv'
        ];
        $gotv = [
            'cable' => 'GOTV',
            'service_id' => Utility::CABLE_GOTV,
            'image' => env('APP_URL') . '/images/utility/gotv.png',
            'red_product' => 'GOtv'
        ];
        $startimes = [
            'cable' => 'STARTIMES',
            'service_id' => Utility::CABLE_STARTIMES,
            'image' => env('APP_URL') . '/images/utility/startimes.png',
            'red_product' => 'StarTimes'
        ];
        // $showmax = [
        //     'cable' => 'SHOWMAX',
        //     'service_id' => Utility::CABLE_SHOWMAX,
        //     'image' => env('APP_URL') . '/images/utility/showmax.png',
        // ];

        // create an array
        $tv = [
            $dstv,
            $gotv,
            $startimes,
            // $showmax,
        ];




        return response($tv, 200);
    }

    public function getElectricList()
    {
        // create an object array
        $ikeja = [
            'location' => 'lagos',
            'service_id' => Utility::POWER_IKEJA,
            'image' => env('APP_URL') . '/images/utility/ikedc.png',
            'red_product' => 'Ikeja'
        ];
        $eko = [
            'location' => 'lagos',
            'service_id' => Utility::POWER_EKO,
            'image' => env('APP_URL') . '/images/utility/ekedc.png',
            'red_product' => 'Eko'
        ];
        $kano = [
            'location' => 'kano',
            'service_id' => Utility::POWER_KANO,
            'image' => env('APP_URL') . '/images/utility/kedco.png',
            'red_product' => 'Kano'
        ];
        $ph = [
            'location' => 'portharcourt',
            'service_id' => Utility::POWER_PH,
            'image' => env('APP_URL') . '/images/utility/phed.png',
            'red_product' => 'Porthacourt'
        ];
        $jos = [
            'location' => 'jos',
            'service_id' => Utility::POWER_JOS,
            'image' => env('APP_URL') . '/images/utility/jed.png',
            'red_product' => 'Jos'
        ];
        $abuja = [
            'location' => 'abuja',
            'service_id' => Utility::POWER_ABUJA,
            'image' => env('APP_URL') . '/images/utility/aedc.png',
            'red_product' => 'Abuja'
        ];
        $kaduna = [
            'location' => 'kaduna',
            'service_id' => Utility::POWER_KADUNA,
            'image' => env('APP_URL') . '/images/utility/kaedco.png',
            'red_product' => 'Kaduna'
        ];
        $ibadan = [
            'location' => 'ibadan',
            'service_id' => Utility::POWER_IBADAN,
            'image' => env('APP_URL') . '/images/utility/ibedc.png',
            'red_product' => 'Ibadan'
        ];




        // create an array
        $electric = [
            $ikeja,
            $eko,
            $kano,
            $ph,
            $jos,
            $abuja,
            $kaduna,
            $ibadan,
        ];


        return response($electric, 200);
    }

    public function getElectricTypes()
    {
        // create an object array
        $types = [
            Utility::POWER_PREPAID,
            Utility::POWER_POSTPAID,
        ];

        return response($types, 200);
    }

    public function verifyRedMeter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meter_no' => 'required',
            'product' => 'required',
            'meter_type' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.live.redbiller.com/1.1/bills/disco/meter/verify', $request->all());


        Log::info($response);

        $data = $response->json();



        return response($response->json(), $data['response']);
    }

    public function verifyRedDecoder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'smart_card_no' => 'required',
            'product' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.test.redbiller.com/1.0/bills/cable/decoder/verify', $request->all());


        Log::info($response);

        $data = $response->json();

        return response($response->json(), $data['response']);
    }


    public function buyRedElectricity(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'meter_no' => 'required',
            'meter_type' => 'required',
            'phone_no' => 'required',
            'amount' => 'required',
            'product' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['message' => $validator->errors()->all(), "message" => $validator->errors()->first()], 422);
        }
        $user = auth('api')->user();
        $wallet = $user->wallet;
        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }


        if ($request->amount > $wallet->balance) {
            return response(['message' => 'Insufficient funds in Naira wallet'], 422);
        }


        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }

        // generate unique ref from date time in gmt +1
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = [
            "meter_no" => $request->meter_no,
            "customer_name" => $request->customer_name,
            "meter_type" => $request->meter_type,
            'product' => $request->product,
            'phone_no' => $request->phone_no,
            'amount' => $request->amount,
            'reference' => $transactionRef,
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.test.redbiller.com/1.1/bills/disco/purchase/create', $body);

        Log::info($response);

        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {
            $status = strtolower($data['meta']['status']);
            $response_2 = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post("https://api.test.redbiller.com/1.0/bills/disco/purchase/status", [
                "reference" => $transactionRef
            ]);

            Log::info($response_2);

            $data_2 = $response_2->json();

            if ($data_2['status'] == true && $data_2['response'] == 200) {
                $status = strtolower($data['meta']['status']);
                $data = $response_2->json();
            }
            // update wallet balance
            if ($status != "cancelled") {
                $wallet->balance = $wallet->balance - $request->amount;
                $wallet->save();
            }

            $ref =    time() . '-' . $user->id;

            switch ($request->product) {
                case "Ikeja":
                    $icon = '/images/utility/ikedc.png';
                    break;
                case "Jos":
                    $icon = '/images/utility/jed.png';
                    break;
                case "Abuja":
                    $icon = '/images/utility/aedc.png';
                    break;
                case "Eko":
                    $icon = '/images/utility/ekedc.png';
                    break;
                case "ibadan":
                    $icon = '/images/utility/ibedc.png';
                    break;
                case "Kaduna":
                    $icon = '/images/utility/kaedco.png';
                    break;
                case "Kano":
                    $icon = '/images/utility/kedco.png';
                    break;
                case "Portharcourt":
                    $icon = '/images/utility/phed.png';
                    break;
                default:
                    $icon =  '/uploads/images/services//1653394162.png';
                    break;
            }

            $billTransaction = new UtilityBillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->meter_no;
            $billTransaction->type = 'Power (' . ucwords($request->product, "-") . ')';
            $billTransaction->service_name = $request->product;
            $billTransaction->transaction_ref = $transactionRef;
            $billTransaction->service_icon = $icon;
            $billTransaction->status = $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1));
            $billTransaction->utility_id = 4;
            $billTransaction->token = strlen($data['details']['token']) > 9 ? $data['details']['token'] : $data['details']['units'];
            $billTransaction->trx_status = $data['meta']['status'];

            $billTransaction->save();

            // make amount readable
            $amount = number_format($request->amount, 2);
            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);

            try {
                FCMService::sendToID(
                    $user->id,
                    [
                        'title' => 'Electricity Bill Payment Successfull',
                        'body' => "Your Electricity bill payment with the reference " . $billTransaction->transaction_ref . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            // Dispatch the mail job to queue
            try {
                $billTransaction->sendStatusUpdateEmail();
            } catch (\Exception $e) {
                Log::error('Mail Error: ' . $e->getMessage());
            }

            return response(['success' => 'Power purchased successfully'], 200);
        } else {
            return response(['message' => 'Transaction failed'], 422);
        }
    }

    public function getRedDataList($product)
    {

        // make sure product code is not empty
        if (!$product) {
            return response(['error' => 'Product is required'], 422);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.test.redbiller.com/1.0/bills/data/plans/list', ["product" => $product]);

        // convert response to json
        $response = $response->json();

        Log::info($response);

        return response($response, 200);
    }

    public function buyRedAirtime(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product' => 'required',
            'phone_no' => 'required',
            'amount' => 'required',

        ]);
        if ($validator->fails()) {
            return response(['message' => $validator->errors()->all(), "message" => $validator->errors()->first()], 422);
        }

        $user = auth('api')->user();

        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }


        $wallet = $user->wallet;

        if ($request->amount > $wallet->balance) {
            return response(['message' => 'Insufficient funds in Naira wallet'], 422);
        }

        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }

        // check if the amount is below the minimum amount of 50
        if ($request->amount < 50) {
            return response(['error' => 'Minimum amount is 50'], 422);
        }

        // generate unique ref from date time in gmt +1
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = [
            'product' => $request->product,
            'phone_no' => $request->phone_no,
            'amount' => $request->amount,
            'reference' => $transactionRef,
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.test.redbiller.com/1.0/bills/airtime/purchase/create', $body);

        Log::info($response);

        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {
            $status = strtolower($data['meta']['status']);

            // update wallet balance
            if ($status != "cancelled") {
                $wallet->balance = $wallet->balance - $request->amount;
                $wallet->save();
            }


            // add to trx table
            // now update main transaction table
            $ref =    time() . '-' . $user->id;
            switch ($request->product) {
                case "MTN":
                    $icon = '/images/utility/mtn.png';
                    break;
                case "Airtel":
                    $icon = '/images/utility/airtel.png';
                    break;
                case "Glo":
                    $icon = '/images/utility/glo.png';
                    break;
                case "9Mobile":
                    $icon = '/images/utility/9-mobile.png';
                    break;
                default:
                    $icon =  'uploads/images/utility/1653393129.png';
                    break;
            }
            if (str_contains($request->service_id, 'etisalat')) {
                $service = "9-Mobile";
            } else {
                $service = $request->product;
            }


            $billTransaction = new UtilityBillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->phone_no;
            $billTransaction->type = 'Airtime Purchase';
            $billTransaction->service_name = $request->product;
            $billTransaction->service_icon = $icon;
            $billTransaction->transaction_ref = $transactionRef;
            $billTransaction->status = $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1));
            $billTransaction->trx_status = $data['meta']['status'];
            $billTransaction->utility_id = 1;
            $billTransaction->package = ucwords($service);
            $billTransaction->save();


            // make amount readable
            $amount = number_format($request->amount, 2);
            $wa_time = new DateTimeZone('Africa/Lagos');
            $time = $billTransaction->created_at->setTimezone($wa_time);


            try {
                FCMService::sendToID(
                    $user->id,
                    [
                        'title' => 'Airtime Successfully',
                        'body' => "Your Airtime purchase with the reference " . $billTransaction->id . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            // Dispatch the mail job to queue
            try {
                $billTransaction->sendStatusUpdateEmail();
            } catch (\Exception $e) {
                Log::error('Mail Error: ' . $e->getMessage());
            }

            return response(['success' => 'Airtime purchased successfully'], 200);
        } else {
            return response(['message' => 'Transaction failed'], 422);
        }
    }

    public function buyRedData(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone_no' => 'required',
            'code' => 'required',
            'variation_name' => 'required',
            'amount' => 'required',
            'product' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all(), "message" => $validator->errors()->first()], 422);
        }
        $user = auth('api')->user();
        $wallet = $user->wallet;
        $wallet = $user->wallet;

        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }




        if ($request->amount > $wallet->balance) {
            return response(['message' => 'Insufficient funds in Naira wallet'], 422);
        }


        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }

        // generate unique ref from date time in gmt +1
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = [
            'product' => $request->product,
            'phone_no' => $request->phone_no,
            'amount' => $request->amount,
            'reference' => $transactionRef,
            'code' => $request->code,
            'variation_name' => $request->variation_name,
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.test.redbiller.com/1.0/bills/data/plans/purchase/create', $body);

        Log::info($response);

        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {
            $status = strtolower($data['meta']['status']);
            // update wallet balance
            if ($status != "cancelled") {
                $wallet->balance = $wallet->balance - $request->amount;
                $wallet->save();
            }

            $ref =    time() . '-' . $user->id;
            switch ($request->product) {
                case "MTN":
                    $icon = '/images/utility/mtn.png';
                    break;
                case "Airtel":
                    $icon = '/images/utility/airtel.png';
                    break;
                case "Glo":
                    $icon = '/images/utility/glo.png';
                    break;
                case "9Mobile":
                    $icon = '/images/utility/9-mobile.png';
                    break;
                default:
                    $icon =  'uploads/images/utility/1653393129.png';
                    break;
            }
            if (str_contains($request->service_id, 'etisalat')) {
                $service = "9-Mobile";
            } else {
                $service = $request->product;
            }

            $billTransaction = new UtilityBillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->phone_no;
            $billTransaction->type = 'Data Bundle';
            $billTransaction->service_name = $request->product;
            $billTransaction->service_icon = $icon;
            $billTransaction->utility_id = 2;
            $billTransaction->transaction_ref = $transactionRef;
            $billTransaction->status = $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1));
            $billTransaction->trx_status = $data['meta']['status'];
            $billTransaction->package = $request->variation_name;

            $billTransaction->save();


            // make amount readable
            $amount = number_format($request->amount, 2);

            try {
                FCMService::sendToID(
                    $user->id,
                    [
                        'title' => 'Data Subscription Successfull',
                        'body' => "Your Data Subscription payment was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }


            // Dispatch the mail job to queue
            try {
                $billTransaction->sendStatusUpdateEmail();
            } catch (\Exception $e) {
                Log::error('Mail Error: ' . $e->getMessage());
            }

            return response(['success' => 'Data purchased successfully'], 200);
        } else {
            return response(['message' => 'Transaction failed, please try again'], 422);
        }
    }

    public function getRedTvPackages($product)
    {

        // make sure product code is not empty
        if (!$product) {
            return response(['error' => 'Product is required'], 422);
        }

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.test.redbiller.com/1.0/bills/cable/plans/list', ["product" => $product]);

        // convert response to json
        $response = $response->json();

        return response($response, 200);
    }

    public function buyRedTVSub(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone_no' => 'required',
            'code' => 'required',
            'variation_name' => 'required',
            'smart_card_no' => 'required',
            'customer_name' => 'required',
            'amount' => 'required',
            'product' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()->all(), "message" => $validator->errors()->first()], 422);
        }
        $user = auth('api')->user();
        $wallet = $user->wallet;
        $wallet = $user->wallet;

        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }



        if ($request->amount > $wallet->balance) {
            return response(['message' => 'Insufficient funds in Naira wallet'], 422);
        }


        if ($user->status != 1) {
            $response = ["message" => "Account Restricted.\n. Contact support for more information"];
            return response($response, 422);
        }

        // generate unique ref from date time in gmt +1
        $useRef = Carbon::now();
        $transactionRef = $useRef->format('Ymdhis');
        File::put('redbiller/' . env('REDBILLER_HOOK') . '/' . $transactionRef, $transactionRef);

        $body = [
            'product' => $request->product,
            'phone_no' => $request->phone_no,
            'amount' => $request->amount,
            'reference' => $transactionRef,
            'code' => $request->code,
            'variation_name' => $request->variation_name,
            'customer_name' => $request->customer_name,
            'smart_card_no' => $request->smart_card_no,
        ];

        $response = Http::withHeaders(['Private-Key' => env("REDBILLER_PRIV_KEY"), 'Content-Type' => 'application/json'])->post('https://api.test.redbiller.com/1.0/bills/cable/plans/purchase/create', $body);

        Log::info($response);

        $data = $response->json();

        if ($data['status'] == true && $data['response'] == 200) {
            $status = strtolower($data['meta']['status']);
            // update wallet balance
            if ($status != "cancelled") {
                $wallet->balance = $wallet->balance - $request->amount;
                $wallet->save();
            }

            $type = 'TV (' . $request->product . ')';

            $ref =    time() . '-' . $user->id;
            switch (strtolower($request->product)) {
                case "dstv":
                    $icon = '/images/utility/dstv.png';
                    break;
                case "gotv":
                    $icon = '/images/utility/gotv.png';
                    break;
                case "startimes":
                    $icon = '/images/utility/startimes.png';
                    break;
                case "showmax":
                    $icon = '/images/utility/showmax.png';
                    break;
                default:
                    $icon =  '/uploads/images/services//1653394130.png';
                    break;
            }
            if (str_contains($request->service_id, 'etisalat')) {
                $service = "9-Mobile";
            } else {
                $service = $request->product;
            }

            $type = 'TV (' . $request->product . ')';

            $billTransaction = new UtilityBillTransaction();
            $billTransaction->user_id = $user->id;
            $billTransaction->amount = $request->amount;
            $billTransaction->number = $request->smart_card_no;
            $billTransaction->type = $type;
            $billTransaction->status = $status == "approved" ? 1 : ($status == "pending" ? 0 : ($status == "cancelled" ? 2 : 1));
            $billTransaction->trx_status = $data['meta']['status'];
            $billTransaction->utility_id = 3;
            $billTransaction->service_icon = $icon;
            $billTransaction->service_name = $request->product;
            $billTransaction->transaction_ref = $transactionRef;
            $billTransaction->package = $request->variation_name;
            $billTransaction->save();


            try {
                FCMService::sendToID(
                    $user->id,
                    [
                        'title' => 'TV Bill Payment Successfull',
                        'body' => "Your TV bill payment with the reference " . $billTransaction->transaction_ref . " was successful. Pleae check app for more details.",
                    ]
                );
            } //catch exception
            catch (Exception $e) {
                Log::error('Message: ' . $e->getMessage());
            }

            return response(['success' => 'Tv package purchased successfully', 'message' => 'Tv package purchased successfully'], 200);
            // make amount readable

        } else {
            return response(['message' => 'Transaction failed'], 422);
        }
    }
}
