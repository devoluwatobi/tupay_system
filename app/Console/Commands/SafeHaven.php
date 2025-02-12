<?php

namespace App\Console\Commands;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\SystemConfig;
use Illuminate\Console\Command;
use App\Services\SafeHavenService;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class SafeHaven extends Command
{
    protected $signature = 'safehaven:refresh';
    protected $description = 'Refresh SafeHaven API';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $privateKey = file_get_contents(storage_path('keys/privatekey.pem'));



        $refresh = SystemConfig::where('name', 'safehaven_refresh')->first();
        $token = SystemConfig::where('name', 'safehaven_token')->first();



        $payload = [
            'iss' => 'https://tupay.ng',
            'sub' => env('SAFEHAVEN_ID'),
            'aud' => env('SAFEHAVEN_BASE_URL'),
            'iat' => time(),
            'exp' => time() + 432000 // 5 Days expiration
        ];

        $jwt = JWT::encode($payload, $privateKey, 'RS256');
        // echo $jwt;

        SystemConfig::updateOrCreate(
            ['name' => 'safehaven_assertion'],
            [
                "name" => "safehaven_assertion",
                "value" => $jwt,
                "updated_by" => 0
            ]
        );

        SafeHavenService::replaceToken();
    }
}
