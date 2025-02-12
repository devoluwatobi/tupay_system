<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'bank_id',
        'approved_by',
        'rejected_by',
        'rejected_reason',
        'transaction_ref',
        'transaction_id',
        'bank',
        'bank_name',
        'account_name',
        'account_number',
        'status',
        'charge',
        'trx_status',
        'type',
        'server',
        'session_id',
        'data'

    ];

    // CREATE CONSTANTS FOR STATUS
    const PENDING = 0;
    const APPROVED = 1;
    const REJECTED = 2;
    const CANCELLED = 3;

    // create relationship with user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
