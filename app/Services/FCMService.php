<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class FCMService
{
    public static function send($token, $notification)
    {
        // ONE SIGNAL
        $user = User::where('fcm', $token)->first();

        if ($user) {
            $user_id = $user->id;

            $body = [
                "target_channel" => "push",
                "include_aliases" => ["external_id" => ["$user_id"]],
                "app_id" => env('ONESIGNAL_APP_ID'),
                "headings" => [
                    "en" => $notification['title']
                ],
                "contents" => [
                    "en" => $notification['body']
                ],
            ];

            $response = Http::withHeaders(['Authorization' => "Basic " . env('ONESIGNAL_TOKEN'), 'Content-Type' => 'application/json'])->post('https://api.onesignal.com/notifications', $body);
        }

        // FCM
        Http::acceptJson()->withToken(config('fcm.token'))->post(
            'https://fcm.googleapis.com/fcm/send',
            [
                'to' => $token,
                'notification' => $notification,
                "sound" => "default",
                "channel_id" => "channelId",
                "android" => [
                    "notification" => [
                        "channel_id" => "channel_id",
                        "sound" => "default",
                    ]
                ]
            ]
        );
    }

    public static function sendToID($user_id, $notification)
    {

        $body = [
            "target_channel" => "push",
            "include_aliases" => ["external_id" => ["$user_id"]],
            "app_id" => env('ONESIGNAL_APP_ID'),
            "headings" => [
                "en" => $notification['title']
            ],
            "contents" => [
                "en" => $notification['body']
            ],
        ];

        $response = Http::withHeaders(['Authorization' => "Basic " . env('ONESIGNAL_TOKEN'), 'Content-Type' => 'application/json'])->post('https://api.onesignal.com/notifications', $body);
    }

    public static function sendToAdmins($notification)
    {
        // ONE SIGNAL
        $body = [
            "target_channel" => "push",
            "included_segments" => ["Total Subscriptions"],
            "app_id" => env('ONESIGNAL_ADMIN_APP_ID'),
            "headings" => [
                "en" => $notification['title']
            ],
            "contents" => [
                "en" => $notification['body']
            ],
        ];

        $response = Http::withHeaders(['Authorization' => "Basic " . env('ONESIGNAL_ADMIN_TOKEN'), 'Content-Type' => 'application/json'])->post('https://api.onesignal.com/notifications', $body);

        // FCM
        Http::acceptJson()->withToken(config('fcm.token'))->post(
            'https://fcm.googleapis.com/fcm/send',
            [
                'to' => "/topics/all_admins",
                'notification' => $notification,
                "sound" => "default",
                "channel_id" => "channelId",
                "android" => [
                    "notification" => [
                        "channel_id" => "channelId",
                        "sound" => "default",
                    ]
                ]
            ]
        );

    }

    public static function sendToAllUsers($notification)
    {
        // ONE SIGNAL
        $body = [
            "target_channel" => "push",
            "included_segments" => ["Total Subscriptions"],
            "app_id" => env('ONESIGNAL_APP_ID'),
            "headings" => [
                "en" => $notification['title']
            ],
            "contents" => [
                "en" => $notification['body']
            ],
        ];

        $response = Http::withHeaders(['Authorization' => "Basic " . env('ONESIGNAL_TOKEN'), 'Content-Type' => 'application/json'])->post('https://api.onesignal.com/notifications', $body);

        // FCM
        Http::acceptJson()->withToken(config('fcm.token'))->post(
            'https://fcm.googleapis.com/fcm/send',
            [
                'to' => "/topics/all_users",
                'notification' => $notification,
                "sound" => "default",
                "channel_id" => "channelId",
                "android" => [
                    "notification" => [
                        "channel_id" => "com.myfavex.app",
                        "sound" => "default",
                    ]
                ]
            ]
        );
    }
}
