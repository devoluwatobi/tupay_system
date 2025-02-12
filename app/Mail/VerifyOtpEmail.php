<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyOtpEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $token;
    public $name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $token, $name)
    {
        $this->email = $email;
        $this->token = $token;
        $this->name = $name;
    }


    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Verify OTP | Tupay')
            ->view('auth.otp');
    }
}
