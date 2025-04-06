<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\SlackAlerts\Facades\SlackAlert;

class BlockAdminIPMiddleware
{

    /**
     * List of blocked IP addresses.
     *
     * @var array
     */
    protected $whiteIps = [
        '105.113.75.10',
        '102.89.44.230',
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

        if ($user && $user->role < 1) {

            abort(403, 'Your IP is blacklisted.');
        }

        return $next($request);
    }

}

