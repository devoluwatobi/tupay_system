<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TupaySubAccountTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'fees',
        'settlement',
        'charge',
        'type',
        'sessionId',
        'paymentReference',
        'creditAccountName',
        'creditAccountNumber',
        'destinationInstitutionCode',
        'debitAccountName',
        'debitAccountNumber',
        'narration',
        'transaction_status',
        'status',
        'data',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id',);
    }
}
