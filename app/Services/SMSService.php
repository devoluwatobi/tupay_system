<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;


class SMSService
{
    public static function sendOTP($phone, $otp)
    {

        $client = new Client([
            'base_uri' => "https://api.ng.termii.com/api/",
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);

        try {
            $response = $client->request(
                'POST',
                'sms/send',
                [
                    RequestOptions::JSON => [
                        "api_key" => "TLK5cz8X7B7dqFLMNy0ZyFOMOvs7JPghhUkm4yeUbx8Ybg54bh1eOagJtEyf7g",
                        "to" => $phone,
                        "from" => "N-Alert",
                        "sms" => "Your TuPay confirmation code is " . $otp . ". It expires in 30min",
                        "type" => "plain",
                        "channel" => "dnd",
                    ],
                ]
            );
        } catch (Exception $e) {
            Log::error($e);
        }
        return $response->getStatusCode();
    }

    public static function sendSMS($phone, $message)
    {

        $curl = curl_init();
        $data = array(
            "api_key" => "TLK5cz8X7B7dqFLMNy0ZyFOMOvs7JPghhUkm4yeUbx8Ybg54bh1eOagJtEyf7g", "to" => $phone,  "from" => "TuPay",
            "sms" => $message,  "type" => "plain",  "channel" => "generic"
        );

        $post_data = json_encode($data);

        $client = new Client([
            'base_uri' => "https://api.ng.termii.com/api/",
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);

        try {
            $response = $client->request(
                'POST',
                'sms/send',
                [
                    RequestOptions::JSON => [
                        "api_key" => "TLK5cz8X7B7dqFLMNy0ZyFOMOvs7JPghhUkm4yeUbx8Ybg54bh1eOagJtEyf7g",
                        "to" => $phone,
                        "from" => "TuPay",
                        "sms" => $message,
                        "type" => "plain",
                        "channel" => "generic",
                    ],
                ]
            );
        } catch (
            Exception $e
        ) {
            Log::error($e);
        }

        return $response->getStatusCode();
    }
}
