<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\RMBTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class RMBTransactionUpdate extends Mailable
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
    public function __construct(User $user, RMBTransaction $transaction)
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
        $subject = '';

        switch ($this->transaction->status) {
            case RMBTransaction::PENDING:
                $subject = 'Your RMB Payment is Pending';
                break;
            case RMBTransaction::APPROVED:
                $subject = 'Your RMB Payment is Completed';
                break;
            case RMBTransaction::REJECTED:
                $subject = 'Your RMB Payment was Rejected';
                break;
            case RMBTransaction::CANCELLED:
                $subject = 'Your RMB Payment was Cancelled';
                break;
            case RMBTransaction::PROCESSING:
                $subject = 'Your RMB Payment is Being Processed';
                break;
            default:
                $subject = 'RMB Payment Status Update';
        }

        return $this->subject($subject)
            ->view('emails.rmb.transaction_update')
            ->with([
                'user' => $this->user,
                'transaction' => $this->transaction,
            ]);
    }

    public function failed(\Exception $exception)
    {
        // Log or handle the failure
        Log::error('Failed to send RMB Transaction mail: ' . $exception->getMessage());
    }
}
