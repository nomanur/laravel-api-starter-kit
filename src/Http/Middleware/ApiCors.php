<?php

namespace LaravelApi\StarterKit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiCors
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $headers = [
            'Access-Control-Allow-Origin' => config('api-starter-kit.cors.allowed_origins', '*'),
            'Access-Control-Allow-Methods' => config('api-starter-kit.cors.allowed_methods', 'GET, POST, PUT, DELETE, OPTIONS'),
            'Access-Control-Allow-Headers' => config('api-starter-kit.cors.allowed_headers', 'Content-Type, Authorization, X-Requested-With'),
            'Access-Control-Allow-Credentials' => config('api-starter-kit.cors.allow_credentials', 'false'),
        ];

        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            return response()->json('', 200, $headers);
        }

        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
