<?php

namespace LaravelApi\StarterKit\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use LaravelApi\StarterKit\Traits\ApiResponser;
use LaravelApi\StarterKit\Traits\ApiExceptionHandlerTrait;

/**
 * Base API Controller that provides common functionality
 */
abstract class ApiBaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests, ApiResponser, ApiExceptionHandlerTrait;

    /**
     * Success response helper
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            config('api-starter-kit.response.success_key', 'data') => $data,
            config('api-starter-kit.response.message_key', 'message') => $message,
        ], $code);
    }

    /**
     * Error response helper
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = [
            config('api-starter-kit.response.error_key', 'error') => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response helper
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginatedResponse($paginator, string $message = 'Success', int $code = 200)
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
