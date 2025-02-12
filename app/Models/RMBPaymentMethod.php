<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMBPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'logo',
        'charge',
        'rmb_charge',
        'rate',
        'status'
    ];
}
