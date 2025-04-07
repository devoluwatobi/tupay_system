<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSuspendedAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->status != 1) {

            return response()->json(['error' => 'Unauthorized', 'message' => 'Service Restricted, Contact support for more information'], 401);

            abort(403, 'Service Restricted, Contact support for more information');
        }
        return $next($request);
    }
}
