<?php

namespace LaravelApi\StarterKit\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

trait ApiResponser
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            config('api-starter-kit.response.success_key', 'data') => $data,
            config('api-starter-kit.response.message_key', 'message') => $message,
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $message = 'Error', int $code = 400, $errors = null)
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
     * Return a simple message response.
     *
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function showMessage(string $message, int $code = 200)
    {
        return response()->json([
            config('api-starter-kit.response.success_key', 'data') => $message,
        ], $code);
    }

    /**
     * Return a single instance JSON response.
     *
     * @param Model $instance
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function showOne(Model $instance, int $code = 200)
    {
        $transformer = $instance->getTransformer();

        if ($transformer) {
            $data = $this->transformData($instance, $transformer);

            return response()->json($data, $code);
        }

        return $this->successResponse($instance, $code);
    }

    /**
     * Return a paginated paginator JSON response.
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'Success', int $code = 200)
    {
        $firstItem = $paginator->getCollection()->first();
        $transformer = $firstItem ? ($firstItem instanceof Model ? $firstItem->getTransformer() : null) : null;

        if ($transformer) {
            $data = $this->transformData($paginator, $transformer);

            return response()->json($data, $code);
        }

        return $this->successResponse($paginator, $message, $code);
    }

    /**
     * Alias for successResponse for convenience.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data, string $message = 'Success', int $code = 200)
    {
        return $this->successResponse($data, $message, $code);
    }

    /**
     * Return a collection JSON response with transformation, filtering, sorting and pagination.
     *
     * @param Collection $collection
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function showAll(Collection $collection, int $code = 200)
    {
        if ($collection->isEmpty()) {
            return $this->successResponse([], $code);
        }

        $transformer = $collection->first()->getTransformer();

        if ($transformer) {
            $collection = $this->filterData($collection, $transformer);
            $collection = $this->sortData($collection, $transformer);
            $collection = $this->paginateCollection($collection);
            $collection = $this->transformData($collection, $transformer);

            if (config('api-starter-kit.cache.enabled', false)) {
                $collection = $this->cacheResponse($collection);
            }

            return response()->json($collection, $code);
        }

        return $this->successResponse($collection, $code);
    }

    /**
     * Filter data based on query parameters and transformer original attributes.
     */
    protected function filterData(Collection $collection, $transformer)
    {
        foreach (request()->query() as $query => $value) {
            $attribute = $transformer::originalAttribute($query);

            if (isset($attribute, $value)) {
                $collection = $collection->where($attribute, $value);
            }
        }

        return $collection;
    }

    /**
     * Sort data based on 'sort_by' query parameter.
     */
    protected function sortData(Collection $collection, $transformer)
    {
        if (request()->has('sort_by')) {
            $attribute = $transformer::originalAttribute(request()->sort_by);

            if (request()->has('desc') && request()->desc) {
                $collection = $collection->sortByDesc($attribute);
            } else {
                $collection = $collection->sortBy($attribute);
            }
        }

        return $collection;
    }

    /**
     * Paginate the collection based on 'per_page' query parameter.
     */
    protected function paginateCollection(Collection $collection)
    {
        $rules = [
            'per_page' => 'integer|min:2|max:100',
        ];

        Validator::validate(request()->all(), $rules);

        $page = LengthAwarePaginator::resolveCurrentPage();

        $perPage = 15;
        if (request()->has('per_page')) {
            $perPage = (int) request()->per_page;
        }

        $results = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator($results, $collection->count(), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

        $paginated->appends(request()->all());

        return $paginated;
    }

    /**
     * Transform data using Fractal.
     */
    protected function transformData($data, $transformer)
    {
        $transformation = fractal($data, new $transformer);

        return $transformation->toArray();
    }

    /**
     * Cache the response based on the full URL and query parameters.
     */
    protected function cacheResponse($data)
    {
        $url = request()->url();
        $queryParams = request()->query();

        ksort($queryParams);

        $queryString = http_build_query($queryParams);

        $fullUrl = "{$url}?{$queryString}";

        $ttl = config('api-starter-kit.cache.ttl', 30);

        return Cache::remember($fullUrl, $ttl, function () use ($data) {
            return $data;
        });
    }
}
