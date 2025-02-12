<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TupaySubAccount extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'external_id',
        'provider',
        'accountProduct',
        'accountNumber',
        'accountName',
        'accountType',
        'currencyCode',
        'bvn',
        'nin',
        'accountBalance',
        'external_status',
        'callbackUrl',
        'firstName',
        'lastName',
        'emailAddress',
        'accountType',
        'externalReference',
        'data',
    ];


    protected $hidden = [
        'bvn',
        'nin',
        'data',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id',);
    }
}
