<?php

namespace App\Models;

use App\Mail\RMBTransactionUpdate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RMBTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'rate',
        'charge',
        'status',
        'r_m_b_payment_method_id',
        'r_m_b_payment_method_title',
        'r_m_b_payment_type_id',
        'r_m_b_payment_type_title',
        'recipient_id',
        'recipient_name',
        'proofs',
        'updates',
        'qrCode',
        'qrCode',
        'account_details',
        'remark',
        'paid_with'
    ];


    // CREATE CONSTANTS FOR STATUS
    const PENDING = 0;
    const APPROVED = 1;
    const REJECTED = 2;
    const CANCELLED = 3;
    const PROCESSING = 4;


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
            ->queue(new RMBTransactionUpdate($user, $this));
    }
}
