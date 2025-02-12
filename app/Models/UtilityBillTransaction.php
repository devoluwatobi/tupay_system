<?php

namespace App\Models;

use App\Mail\UtilityBillPurchased;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UtilityBillTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'number',
        'type',
        'utility_id',
        'token',
        'transaction_ref',
        'service_name',
        'service_icon',
        'package',
        'status',
        'trx_status'
    ];


    // CREATE CONSTANTS FOR STATUS
    const PENDING = 0;
    const APPROVED = 1;
    const FAILED = 2;
    const CANCELLED = 3;

    // create constants for type
    const AIRTIME = 'airtime';
    const DATA = 'data';
    const TV = 'tv';
    const ELECTRICITY = 'electricity';



    // create relationship with user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Method to send the email with queue
    public function sendStatusUpdateEmail()
    {
        $user = $this->user;

        Mail::to($user->email)
            ->queue(new UtilityBillPurchased($user, $this));
    }
}
