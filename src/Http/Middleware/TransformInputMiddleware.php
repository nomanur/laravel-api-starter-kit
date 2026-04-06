<?php

namespace LaravelApi\StarterKit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TransformInputMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $transformer
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $transformer)
    {
        $transformedData = [];

        foreach ($request->all() as $key => $value) {
            $originalKey = $transformer::originalAttribute($key);

            if ($originalKey !== null) {
                $transformedData[$originalKey] = $value;
            } else {
                $transformedData[$key] = $value;
            }
        }

        $request->merge($transformedData);

        return $next($request);
    }
}
