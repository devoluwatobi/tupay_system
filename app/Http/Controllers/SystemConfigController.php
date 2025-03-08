<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\AppConfig;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use App\Models\RMBPaymentType;
use App\Models\RMBTransaction;
use App\Models\RMBPaymentMethod;
use Illuminate\Support\Facades\Validator;

class SystemConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SystemConfig  $systemConfig
     * @return \Illuminate\Http\Response
     */
    public function show(SystemConfig $systemConfig)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SystemConfig  $systemConfig
     * @return \Illuminate\Http\Response
     */
    public function edit(SystemConfig $systemConfig)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SystemConfig  $systemConfig
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SystemConfig $systemConfig)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SystemConfig  $systemConfig
     * @return \Illuminate\Http\Response
     */
    public function destroy(SystemConfig $systemConfig)
    {
        //
    }

    public function updateBoardData(Request $request)
    {
        $signedInUser = auth('api')->user();

        if ($signedInUser->role < 1) {
            $response = ["message" => "Permission Denied. Contact your supervisor for more information"];
            return response($response, 422);
        }
        $validator = Validator::make($request->all(), [
            'available' => 'required',
            'incoming' => 'required',
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



        // Update or create the record
        $available = SystemConfig::updateOrCreate([
            'name' => 'total_circulation',

        ], [
            'value' => $request->available,
        ]);

        $incoming = SystemConfig::updateOrCreate([
            'name' => 'total_incoming',

        ], [
            'value' => $request->incoming,
        ]);



        // return home data

        $configs = AppConfig::all();
        $sys_configs = SystemConfig::all();
        $payment_methods = RMBPaymentMethod::where('status', 1)->get();

        foreach ($payment_methods as $method) {
            $method->logo = env('APP_URL') . $method->logo;
        }

        $circulation_config = SystemConfig::where("name", "total_circulation")->first();

        if ($circulation_config) {
            $circulation =  $circulation_config->value;
        }

        $incoming_config = SystemConfig::where("name", "total_incoming")->first();

        if ($incoming_config) {
            $incoming =  $incoming_config->value;
        }

        $data = [
            'payment_methods' => $payment_methods,
            'payment_types' => RMBPaymentType::where('status', 1)->get(),
            'me' => $signedInUser,
            'system_configs' =>  $sys_configs,
            'app_configs' =>  $configs,
            'board_data' => [
                "total_available" =>  $circulation ?? "0",
                "incoming" => $incoming ?? "0",
            ],
            'rmb2ngn' => [
                'rate' => SystemConfig::where('name', 'rmb2ngn_rate')->first()->value,
                'charge' => SystemConfig::where('name', 'rmb2ngn_charge')->first()->value,
                'title' => 'RMB to NGN',
                'id' => 'rmb2ngn'
            ],
            'ngn2rmb' => [
                'rate' => SystemConfig::where('name', 'ngn2rmb_rate')->first()->value,
                'charge' => SystemConfig::where('name', 'ngn2rmb_charge')->first()->value,
                'title' => 'NGN to RMB',
                'id' => 'ngn2rmb'
            ],
            'stat' => [
                "users" => [
                    "all" => User::where('id', '<>', 0)->get()->count(),
                    "this_month" => User::whereMonth('created_at', Carbon::now()->month)->get()->count(),
                    "last_month" => User::whereMonth('created_at', Carbon::now()->subMonth(1))->get()->count(),
                ],
                'rmb' => [
                    'pending' => RMBTransaction::where("status", 0)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                    'pending_naira' => RMBTransaction::where("status", 0)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'completed' => RMBTransaction::where("status", 1)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'completed_naira' => RMBTransaction::where("status", 1)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                    'this_month' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'this_month_naira' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                    'last_month' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'last_month_naira' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                ],

            ],
        ];
        return response($data, 200);
    }

    public function updateConversionRates(Request $request)
    {
        $signedInUser = auth('api')->user();

        if ($signedInUser->role < 1) {
            $response = ["message" => "Permission Denied. Contact your supervisor for more information"];
            return response($response, 401);
        }
        $validator = Validator::make($request->all(), [
            'rate' => 'required',
            'charge' => 'required',
            'id' => 'required',
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

        if ($request->id != 'rmb2ngn' && $request->id != 'ngn2rmb') {
            $response = ["message" => "Wrong ID provided. Contact your supervisor for more information"];
            return response($response, 422);
        }

        if ($request->id == "rmb2ngn") {
            SystemConfig::updateOrCreate(
                ['name' => 'rmb2ngn_rate'],
                [
                    "name" => "rmb2ngn_rate",
                    "value" => $request->rate,
                    "updated_by" => $signedInUser->id
                ]
            );
            SystemConfig::updateOrCreate(
                ['name' => 'rmb2ngn_charge'],
                [
                    "name" => "rmb2ngn_charge",
                    "value" => $request->charge,
                    "updated_by" => $signedInUser->id
                ]
            );
        } else {
            SystemConfig::updateOrCreate(
                ['name' => 'ngn2rmb_rate'],
                [
                    "name" => "ngn2rmb_rate",
                    "value" => $request->rate,
                    "updated_by" => $signedInUser->id
                ]
            );
            SystemConfig::updateOrCreate(
                ['name' => 'ngn2rmb_charge'],
                [
                    "name" => "ngn2rmb_charge",
                    "value" => $request->charge,
                    "updated_by" => $signedInUser->id
                ]
            );
        }

        // return home data

        $configs = AppConfig::all();
        $sys_configs = SystemConfig::all();
        $payment_methods = RMBPaymentMethod::where('status', 1)->get();

        foreach ($payment_methods as $method) {
            $method->logo = env('APP_URL') . $method->logo;
        }

        $circulation_config = SystemConfig::where("name", "total_circulation")->first();

        if ($circulation_config) {
            $circulation =  $circulation_config->value;
        }

        $incoming_config = SystemConfig::where("name", "total_incoming")->first();

        if ($incoming_config) {
            $incoming =  $incoming_config->value;
        }

        $data = [
            'payment_methods' => $payment_methods,
            'payment_types' => RMBPaymentType::where('status', 1)->get(),
            'me' => $signedInUser,
            'system_configs' =>  $sys_configs,
            'app_configs' =>  $configs,
            'board_data' => [
                "total_available" =>  $circulation ?? "0",
                "incoming" => $incoming ?? "0",
            ],
            'rmb2ngn' => [
                'rate' => SystemConfig::where('name', 'rmb2ngn_rate')->first()->value,
                'charge' => SystemConfig::where('name', 'rmb2ngn_charge')->first()->value,
                'title' => 'RMB to NGN',
                'id' => 'rmb2ngn'
            ],
            'ngn2rmb' => [
                'rate' => SystemConfig::where('name', 'ngn2rmb_rate')->first()->value,
                'charge' => SystemConfig::where('name', 'ngn2rmb_charge')->first()->value,
                'title' => 'NGN to RMB',
                'id' => 'ngn2rmb'
            ],
            'stat' => [
                "users" => [
                    "all" => User::where('id', '<>', 0)->get()->count(),
                    "this_month" => User::whereMonth('created_at', Carbon::now()->month)->get()->count(),
                    "last_month" => User::whereMonth('created_at', Carbon::now()->subMonth(1))->get()->count(),
                ],
                'rmb' => [
                    'pending' => RMBTransaction::where("status", 0)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                    'pending_naira' => RMBTransaction::where("status", 0)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'completed' => RMBTransaction::where("status", 1)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'completed_naira' => RMBTransaction::where("status", 1)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                    'this_month' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'this_month_naira' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                    'last_month' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(rate * amount) as total_value')->value('total_value'),
                    'last_month_naira' => RMBTransaction::whereMonth('created_at', Carbon::now()->month)->where("status", 1)->selectRaw('SUM(amount) as total_value')->value('total_value'),
                ],

            ],
        ];
        return response($data, 200);
    }
}
