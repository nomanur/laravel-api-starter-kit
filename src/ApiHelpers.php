<?php

namespace LaravelApi\StarterKit;

class ApiHelpers
{
    /**
     * Format success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return array
     */
    public static function successResponse($data = null, string $message = 'Success', int $code = 200): array
    {
        return [
            'data' => $data,
            'message' => $message,
            'code' => $code,
        ];
    }

    /**
     * Format error response.
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return array
     */
    public static function errorResponse(string $message = 'Error', int $code = 400, $errors = null): array
    {
        $response = [
            'error' => $message,
            'code' => $code,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * Format validation errors.
     *
     * @param array $errors
     * @return array
     */
    public static function validationErrors(array $errors): array
    {
        $formatted = [];

        foreach ($errors as $field => $messages) {
            $formatted[$field] = $messages;
        }

        return $formatted;
    }

    /**
     * Format pagination metadata.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return array
     */
    public static function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * Format pagination links.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return array
     */
    public static function paginationLinks($paginator): array
    {
        return [
            'self' => $paginator->url($paginator->currentPage()),
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'next' => $paginator->nextPageUrl(),
            'prev' => $paginator->previousPageUrl(),
        ];
    }

    /**
     * Generate API route name.
     *
     * @param string $resource
     * @param string $action
     * @param string $version
     * @return string
     */
    public static function routeName(string $resource, string $action = '', string $version = 'v1'): string
    {
        $prefix = config('api-starter-kit.prefix', 'api');
        $action = $action ? ".{$action}" : '';

        return "{$prefix}.{$version}.{$resource}{$action}";
    }

    /**
     * Check if request wants JSON response.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public static function isApiRequest($request): bool
    {
        $prefix = config('api-starter-kit.prefix', 'api');
        return $request->is("{$prefix}/*") || $request->is($prefix);
    }

    /**
     * Get client IP address.
     *
     * @return string|null
     */
    public static function getClientIp(): ?string
    {
        $request = request();

        if (!$request) {
            return null;
        }

        return $request->ip();
    }

    /**
     * Sanitize input data.
     *
     * @param array $data
     * @return array
     */
    public static function sanitizeInput(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeInput($value);
            } else {
                $sanitized[$key] = is_string($value) ? trim($value) : $value;
            }
        }

        return $sanitized;
    }

    /**
     * Generate cache key for API response.
     *
     * @param string $resource
     * @param array $params
     * @return string
     */
    public static function cacheKey(string $resource, array $params = []): string
    {
        ksort($params);
        $queryString = http_build_query($params);
        $prefix = config('api-starter-kit.prefix', 'api');
        $version = config('api-starter-kit.version', 'v1');

        return "{$prefix}_{$version}_{$resource}" . ($queryString ? "_{$queryString}" : '');
    }

    /**
     * Format datetime to ISO 8601.
     *
     * @param mixed $datetime
     * @return string|null
     */
    public static function formatDatetime($datetime): ?string
    {
        if (!$datetime) {
            return null;
        }

        if (is_string($datetime)) {
            $datetime = new \DateTime($datetime);
        }

        return $datetime->format(\DateTime::ISO8601);
    }

    /**
     * Check if feature is enabled in config.
     *
     * @param string $feature
     * @return bool
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return config("api-starter-kit.{$feature}.enabled", false);
    }
}
