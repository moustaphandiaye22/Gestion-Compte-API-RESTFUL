<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RatingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log les informations de rate limiting
        $user = $request->user();
        if ($user) {
            $remaining = $request->header('X-RateLimit-Remaining');
            $limit = $request->header('X-RateLimit-Limit');

            if ($remaining !== null && $limit !== null && (int)$remaining < 10) {
                Log::info('Rate limit approaching', [
                    'user_id' => $user->id,
                    'user_type' => $user->userable_type,
                    'remaining' => $remaining,
                    'limit' => $limit,
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ]);
            }
        }

        return $next($request);
    }
}
