<?php

namespace LaravelApi\StarterKit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class ApiBoilerplate
{
    /**
     * Get the API version from config.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return config('api-starter-kit.version', 'v1');
    }

    /**
     * Get the API prefix from config.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return config('api-starter-kit.prefix', 'api');
    }

    /**
     * Get the full API base path.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return '/' . $this->getPrefix() . '/' . $this->getVersion();
    }

    /**
     * Generate API response with standard format.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param array $meta
     * @return \Illuminate\Http\JsonResponse
     */
    public function response($data = null, string $message = '', int $statusCode = 200, array $meta = [])
    {
        $response = [
            config('api-starter-kit.response.success_key', 'data') => $data,
        ];

        if (!empty($message)) {
            $response[config('api-starter-kit.response.message_key', 'message')] = $message;
        }

        if (!empty($meta)) {
            $response[config('api-starter-kit.response.meta_key', 'meta')] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Generate API success response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($data = null, string $message = 'Success', int $statusCode = 200)
    {
        return $this->response($data, $message, $statusCode);
    }

    /**
     * Generate API error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    public function error(string $message = 'Error', int $statusCode = 400, $errors = null)
    {
        $response = [
            config('api-starter-kit.response.error_key', 'error') => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Validate request data.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validate(array $data, array $rules, array $messages = [])
    {
        return Validator::make($data, $rules, $messages);
    }

    /**
     * Get pagination information from request.
     *
     * @return array
     */
    public function getPaginationParams(): array
    {
        return [
            'per_page' => request()->input('per_page', 15),
            'page' => request()->input('page', 1),
            'sort_by' => request()->input('sort_by', 'id'),
            'sort_order' => request()->input('sort_order', 'asc'),
        ];
    }

    /**
     * Add pagination metadata to response.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return array
     */
    public function getPaginationMeta(LengthAwarePaginator $paginator): array
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
     * Add pagination links to response.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @return array
     */
    public function getPaginationLinks(LengthAwarePaginator $paginator): array
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
     * Check if caching is enabled.
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return config('api-starter-kit.cache.enabled', false);
    }

    /**
     * Cache API response.
     *
     * @param string $key
     * @param mixed $data
     * @param int|null $ttl
     * @return mixed
     */
    public function cacheResponse(string $key, $data, ?int $ttl = null)
    {
        $ttl = $ttl ?? config('api-starter-kit.cache.ttl', 30);

        return Cache::remember($key, $ttl, function () use ($data) {
            return $data;
        });
    }

    /**
     * Clear cached API response.
     *
     * @param string $key
     * @return bool
     */
    public function clearCache(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Generate cache key from request.
     *
     * @return string
     */
    public function generateCacheKey(): string
    {
        $url = request()->url();
        $queryParams = request()->query();

        ksort($queryParams);

        $queryString = http_build_query($queryParams);

        return "{$url}?{$queryString}";
    }
}
