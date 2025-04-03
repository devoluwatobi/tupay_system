<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Cache;

class CustomRateLimiter
{
    public function limit(Request $request)
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = 1; // Define your max attempts
        $decayMinutes = 1; // Define the decay time

        if (Cache::has($key)) {
            $attempts = Cache::get($key);

            if ($attempts >= $maxAttempts) {
                return false;
            }
            
            Cache::increment($key);
        } else {
            Cache::put($key, 1, $decayMinutes * 60);
        }

        return true;
    }

    protected function resolveRequestSignature(Request $request)
    {
        return sha1($request->ip());
    }
}
