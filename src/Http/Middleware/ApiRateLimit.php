<?php

namespace LaravelApi\StarterKit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $maxAttempts
     * @param string|null $decayMinutes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $maxAttempts = null, ?string $decayMinutes = null)
    {
        $enabled = config('api-starter-kit.rate_limit.enabled', true);

        if (!$enabled) {
            return $next($request);
        }

        $maxAttempts = (int) ($maxAttempts ?? config('api-starter-kit.rate_limit.max_attempts', 60));
        $decayMinutes = (int) ($decayMinutes ?? config('api-starter-kit.rate_limit.decay_minutes', 1));

        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Too Many Attempts.',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $maxAttempts - RateLimiter::attempts($key),
        ]);
    }

    /**
     * Resolve request signature for rate limiting.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sha1($user->getAuthIdentifier());
        }

        return sha1($request->ip() . '|' . $request->userAgent());
    }
}
