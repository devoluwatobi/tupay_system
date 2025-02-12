<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\SlackAlerts\Facades\SlackAlert;

class BlockIPMiddleware
{
    /**
     * List of blocked IP addresses.
     *
     * @var array
     */
    protected $blockedIps = [
        '5.62.63.55',
        '5.62.61.63',
        '185.183.106.118',
        '123.123.123.123', // Add IPs you want to block
        '111.111.111.111',
        '102.89.69.61',
        '105.113.117.231',
        '102.88.34.247',
        // '114.10.41.239',
        // '114.10.115.17',
        // '114.10.112.38',
        '105.113.103.126',
        '5.62.61.63',
    ];
    
    protected $blockedDevice = [
        // 'TP1A.220624.014',
        // 'QP1A.190711.020',
    ];


    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (in_array($request->ip(), $this->blockedIps) || in_array($request->header('deviceID'), $this->blockedDevice)  ) {
            // Optionally, you can return a custom error response here
            // try {
            //             SlackAlert::to('auth')->message(json_encode([
            //             "action" => "!!! BLACKLISTED IP ADDRESS !!!️",
            //             "device_id" => $request->header('deviceID') ?? "##",
            //             "ipAddress" => $request->ip(),
            //             "agent" => $request->header('User-Agent'),
            //             "route" => $request->path(),              // Current route path
            //             "method" => $request->method(),           // HTTP method (GET, POST, etc.)
            //             "full_url" => $request->fullUrl(),        // Full URL including query parameters
            //             "headers" => $request->headers->all(),
            //             "data" => $request->all(),
            //             "user" => $user,
            //             ]));
            //         } catch (Exception $e) {
            //             Log::error("Blacklist Error -> " . $e);
            //         }
            abort(403, 'Your IP is blacklisted.');
            
        }

        return $next($request);
    }
}
