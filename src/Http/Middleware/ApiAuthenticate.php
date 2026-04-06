<?php

namespace LaravelApi\StarterKit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $guard = null)
    {
        $authDriver = config('api-starter-kit.auth.driver', 'sanctum');
        $guard = $guard ?? config('api-starter-kit.auth.guard', 'sanctum');

        if ($authDriver === 'sanctum') {
            if (!$request->user($guard)) {
                return response()->json([
                    'error' => 'Unauthenticated.',
                    'message' => 'Authentication required.',
                ], 401);
            }
        } elseif ($authDriver === 'token') {
            $tokenField = config('api-starter-kit.auth.token_field', 'api_token');
            $token = $request->bearerToken() ?? $request->input($tokenField);

            if (!$token) {
                return response()->json([
                    'error' => 'Unauthenticated.',
                    'message' => 'API token required.',
                ], 401);
            }

            // Token validation will be handled by the auth guard
            Auth::shouldUse($guard);
        }

        return $next($request);
    }
}
