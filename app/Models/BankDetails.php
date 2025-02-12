<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_number',
        'account_name',
        'bank',
        'bank_name',
        'external_id',
        'safehaven_bank_code',
        'safehaven_name_id',


    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
