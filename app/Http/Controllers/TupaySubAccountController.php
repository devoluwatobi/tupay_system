<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\TupaySubAccount;

class TupaySubAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $user = auth('api')->user();
        $user = User::find($user->id);

        return response(
            [
                'message' => " Fetched successfully",

                'data' => TupaySubAccount::where('user_id', $user->id)->first(),
            ],
            200
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    public function show(TupaySubAccount $tupaySubAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TupaySubAccount $tupaySubAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TupaySubAccount $tupaySubAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TupaySubAccount $tupaySubAccount)
    {
        //
    }
}
