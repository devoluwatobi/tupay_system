<?php

namespace App\Http\Controllers;

use App\Models\UserFundBankAccount;
use Illuminate\Http\Request;

class UserFundBankAccountController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();

        return response(['status' => true, 'message' => 'Fund Account Fetched successfully', 'fund_bank_details' => UserFundBankAccount::where("user_id", $user->id)->select('id', 'user_id', 'account_no', 'account_name', 'status', 'bank_name')->first(),], 200);
    }
}
