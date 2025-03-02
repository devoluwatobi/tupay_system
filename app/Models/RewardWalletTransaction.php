<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RewardWalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'referred_user_id',
        'status',
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
