<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserFundBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_name',
        'account_no',
        'bank_name',
        "auto_settlement",
        "reference",
        "status",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
