<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */

    public $attachment;
    public $user;


    public function __construct($user, $attachment)
    {

        $this->user = $user;
        $this->attachment = $attachment;
    }

    public function build()
    {

        // $attachment = $this->attachment;
        return $this->subject('Account E-Statement | Tupay')->view('emails.account.statement')->attachData($this->attachment, strtoupper($this->user->email) . ' Statement.csv', [
            'mime' => 'application/csv',
        ]);
    }
}
