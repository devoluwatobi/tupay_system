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

class RMBPaymentMethodController extends Controller
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
     * @param  \App\Models\RMBPaymentMethod  $rMBPaymentMethod
     * @return \Illuminate\Http\Response
     */
    public function show(RMBPaymentMethod $rMBPaymentMethod)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RMBPaymentMethod  $rMBPaymentMethod
     * @return \Illuminate\Http\Response
     */
    public function edit(RMBPaymentMethod $rMBPaymentMethod)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RMBPaymentMethod  $rMBPaymentMethod
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RMBPaymentMethod $rMBPaymentMethod) {}

    public function updateRates(Request $request)
    {
        $user = auth('api')->user();
        $validator = Validator::make($request->all(), [
            'method_id' => 'required',
            'rate' => 'required',
            'charge' => 'required',
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

        $method =  RMBPaymentMethod::where('id', $request->method_id)->first();

        if (!$method) {
            return response(
                [
                    'error' => true,
                    'message' => "Invalid payment method. Please check and retry again"
                ],
                422
            );
        }

        $method->update([
            'rate' => $request->rate,
            'charge' => $request->charge,
        ]);

        $payment_methods = RMBPaymentMethod::where('status', 1)->get();

        foreach ($payment_methods as $method) {
            $method->logo = env('APP_URL') . $method->logo;
        }

        // return response(['payment_methods' => $payment_methods,], 200);

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
            'me' => $user,
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RMBPaymentMethod  $rMBPaymentMethod
     * @return \Illuminate\Http\Response
     */
    public function destroy(RMBPaymentMethod $rMBPaymentMethod)
    {
        //
    }
}
