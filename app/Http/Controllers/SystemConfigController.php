<?php

namespace App\Http\Controllers;

use App\Models\SystemConfig;
use Illuminate\Http\Request;
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



        return response(['total_circulation' => $available, 'total_incoming' => $incoming,], 200);
    }
}
