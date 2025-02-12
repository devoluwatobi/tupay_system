<?php

namespace App\Models;

use App\Models\UserFundBankAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FundTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'settlement',
        'charge',
        'reference',
        'profile_first_name',
        'profile_surname',
        'profile_phone_no',
        'profile_email',
        'profile_blacklisted',
        'account_name',
        'account_no',
        'bank_name',
        'acccount_reference',
        'transaction_status',
        'status',
        'user_fund_bank_account_id',
        'payer_account_name',
        'payer_account_no',
        'payer_bank_name',
    ];

    // CREATE CONSTANTS FOR STATUS
    const PENDING = 0;
    const APPROVED = 1;
    const REJECTED = 2;

    // create relationship with user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fundAccount(): BelongsTo
    {
        return $this->belongsTo(UserFundBankAccount::class, 'user_fund_bank_account_id');
    }
}
