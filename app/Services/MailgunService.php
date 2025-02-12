<?php

namespace App\Services;

use Mailgun\Mailgun;

class MailgunService
{
    protected $mailgun;
    protected $domain;

    public function __construct()
    {
        $this->mailgun = Mailgun::create(env('MAILGUN_SECRET'));
        $this->domain = env('MAILGUN_DOMAIN');
    }

    public  static function addUserToMailingList($email, $name)
    {
        $domain = env('MAILGUN_DOMAIN');
        $mailgun = Mailgun::create(env('MAILGUN_SECRET'), 'https://api.mailgun.net/v3/');


        $response = $mailgun->mailingList()->member()->create("marketing@msg.pgoldapp.com", $email, $name);

        return $response;
    }
}
