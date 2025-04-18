<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\UtilityBillTransaction;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class AirtimePurchased extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $transaction;

    public $tries = 5;           // Retry up to 5 times
    public $backoff = 60;        // Wait 60 seconds between retries

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, UtilityBillTransaction $transaction)
    {
        $this->user = $user;
        $this->transaction = $transaction;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }

    public function build()
    {
        return $this->subject('Airtime purchase | ' . env('APP_NAME'))
            ->view('emails.utility.airtime')
            ->with([
                'user' => $this->user,
                'transaction' => $this->transaction,
            ]);
    }
}
