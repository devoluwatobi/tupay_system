<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\CustomRateLimiter;

class CustomThrottleRequests
{
    protected $limiter;

    public function __construct(CustomRateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle($request, Closure $next)
    {
        if (!$this->limiter->limit($request)) {
            return response()->json(['message' => 'Too Many Requests'], 429);
        }

        return $next($request);
    }
}
