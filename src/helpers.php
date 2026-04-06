<?php

use Illuminate\Pagination\LengthAwarePaginator;

if (!function_exists('api_response')) {
    /**
     * Return a standardized API success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    function api_response($data = null, string $message = '', int $code = 200)
    {
        $response = [
            config('api-starter-kit.response.success_key', 'data') => $data,
        ];

        if (!empty($message)) {
            $response[config('api-starter-kit.response.message_key', 'message')] = $message;
        }

        return response()->json($response, $code);
    }
}

if (!function_exists('api_error')) {
    /**
     * Return an error API response.
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    function api_error(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = [
            config('api-starter-kit.response.error_key', 'error') => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}

if (!function_exists('api_paginated')) {
    /**
     * Return a paginated API response.
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    function api_paginated(LengthAwarePaginator $paginator, string $message = '', int $code = 200)
    {
        return response()->json([
            config('api-starter-kit.response.success_key', 'data') => $paginator->items(),
            config('api-starter-kit.response.message_key', 'message') => $message,
            config('api-starter-kit.response.meta_key', 'meta') => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            config('api-starter-kit.response.links_key', 'links') => [
                'self' => $paginator->url($paginator->currentPage()),
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ], $code);
    }
}

if (!function_exists('api_cache_key')) {
    /**
     * Generate a cache key for API responses.
     *
     * @param string $resource
     * @param array $params
     * @return string
     */
    function api_cache_key(string $resource, array $params = []): string
    {
        ksort($params);
        $queryString = http_build_query($params);
        $prefix = config('api-starter-kit.prefix', 'api');
        $version = config('api-starter-kit.version', 'v1');

        return "{$prefix}_{$version}_{$resource}" . ($queryString ? "_{$queryString}" : '');
    }
}

if (!function_exists('api_is_enabled')) {
    /**
     * Check if a feature is enabled in the API config.
     *
     * @param string $feature
     * @return bool
     */
    function api_is_enabled(string $feature): bool
    {
        return config("api-starter-kit.{$feature}.enabled", false);
    }
}

if (!function_exists('api_version')) {
    /**
     * Get the current API version.
     *
     * @return string
     */
    function api_version(): string
    {
        return config('api-starter-kit.version', 'v1');
    }
}

if (!function_exists('api_base_path')) {
    /**
     * Get the base API path.
     *
     * @return string
     */
    function api_base_path(): string
    {
        $prefix = config('api-starter-kit.prefix', 'api');
        $version = api_version();

        return "/{$prefix}/{$version}";
    }
}
