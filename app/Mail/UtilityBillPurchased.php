<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use App\Models\UtilityBillTransaction;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class UtilityBillPurchased extends Mailable
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
        $subject = "Your " . $this->transaction->type;

        switch ($this->transaction->status) {
            case UtilityBillTransaction::PENDING:
                $subject = $subject . ' is Pending';
                break;
            case UtilityBillTransaction::APPROVED:
                $subject = $subject . ' is Completed';
                break;
            case UtilityBillTransaction::FAILED:
                $subject = $subject . ' was Rejected';
                break;
            case UtilityBillTransaction::CANCELLED:
                $subject = $subject . ' was Cancelled';
                break;
            default:
                $subject = $subject . ' Status Update';
        }

        return $this->subject($subject . " | " . env('APP_NAME'))
            ->view('emails.utility.transaction_update')
            ->with([
                'user' => $this->user,
                'transaction' => $this->transaction,
            ]);
    }

    public function failed(\Exception $exception)
    {
        // Log or handle the failure
        Log::error('Failed to send Utility Transaction mail: ' . $exception->getMessage());
    }
}
