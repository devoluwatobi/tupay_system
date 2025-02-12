<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        return response(['payment_methods' => $payment_methods,], 200);
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
