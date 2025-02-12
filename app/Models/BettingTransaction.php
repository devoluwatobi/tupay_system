<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BettingTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'amount',
        'reference',
        'charge',
        'product',
        'customer_id',
        'first_name',
        'surname',
        'username',
        'date',
        'bet_status',
        'status',
    ];

    // create relationship with user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
