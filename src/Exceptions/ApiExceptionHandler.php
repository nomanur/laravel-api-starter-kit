<?php

namespace LaravelApi\StarterKit\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use LaravelApi\StarterKit\Traits\ApiResponser;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionHandler extends ExceptionHandler
{
    use ApiResponser;

    /**
     * A list of exception types with their custom messages.
     *
     * @var array<class-string<Throwable>, string>
     */
    protected $messages = [];

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $exception
     * @return Response|JsonResponse
     */
    public function render($request, Throwable $exception)
    {
        // Only handle as API if it's an API request
        if ($this->isApiRequest($request)) {
            return $this->handleApiException($exception, $request);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions and return standardized JSON responses.
     *
     * @param Throwable $exception
     * @param Request|null $request
     * @return JsonResponse
     */
    protected function handleApiException(Throwable $exception, ?Request $request = null): JsonResponse
    {
        $debug = config('api-starter-kit.exceptions.debug', false);
        $hideMessage = config('api-starter-kit.exceptions.hide_exception_message', true);
        $defaultMessage = config('api-starter-kit.exceptions.default_exception_message', 'An error occurred. Please try again later.');

        if ($exception instanceof ValidationException) {
            return $this->handleValidationException($exception, $request);
        }

        if ($exception instanceof ModelNotFoundException) {
            $modelName = class_basename($exception->getModel());
            $modelName = strtolower($modelName);
            return $this->errorResponse(
                "The requested {$modelName} was not found.",
                404
            );
        }

        if ($exception instanceof AuthenticationException) {
            return $this->handleAuthenticationException($exception);
        }

        if ($exception instanceof AuthorizationException) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->errorResponse('The specified URL cannot be found.', 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse('The specified method for the request is invalid.', 405);
        }

        if ($exception instanceof HttpException) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        }

        if ($exception instanceof QueryException) {
            $errorCode = $exception->errorInfo[1] ?? null;

            if ($errorCode == 1451) {
                return $this->errorResponse(
                    'Cannot delete this resource permanently. It is related to other resources.',
                    409
                );
            }

            if ($errorCode == 1062) {
                return $this->errorResponse('A resource with the same unique field already exists.', 409);
            }
        }

        // In debug mode, show detailed error
        if ($debug) {
            return response()->json([
                'error' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ], 500);
        }

        // In production, hide details
        return $this->errorResponse(
            $hideMessage ? $defaultMessage : $exception->getMessage(),
            500
        );
    }

    /**
     * Handle validation exception.
     *
     * @param ValidationException $exception
     * @param Request|null $request
     * @return JsonResponse
     */
    protected function handleValidationException(ValidationException $exception, ?Request $request = null): JsonResponse
    {
        $errors = $exception->validator->errors()->getMessages();

        $format = config('api-starter-kit.validation.error_format', 'laravel');
        $includeField = config('api-starter-kit.validation.include_field_in_error', true);

        // Transform field names using the transformer if available
        $transformer = $this->getTransformerFromRequest($request);
        if ($transformer) {
            $transformedErrors = [];
            foreach ($errors as $field => $messages) {
                $transformedField = $transformer::transformedAttribute($field) ?? $field;
                $transformedErrors[$transformedField] = $messages;
            }
            $errors = $transformedErrors;
        }

        if ($format === 'flat') {
            $flatErrors = [];
            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $flatErrors[] = $includeField ? "{$field}: {$message}" : $message;
                }
            }
            return $this->errorResponse('Validation failed', 422, $flatErrors);
        }

        return $this->errorResponse('Validation failed', 422, $errors);
    }

    /**
     * Get the transformer class from the current request context.
     *
     * @param Request|null $request
     * @return string|null
     */
    protected function getTransformerFromRequest(?Request $request = null): ?string
    {
        $request = $request ?? request();

        // Check request attributes set by middleware
        $transformer = $request->attributes->get('_transformer');
        if ($transformer) {
            return $transformer;
        }

        // Fallback: Try to get model from route binding
        $route = $request->route();
        if (!$route) {
            return null;
        }

        foreach ($route->parameters() as $parameter) {
            if (is_object($parameter) && method_exists($parameter, 'getTransformer')) {
                return $parameter->getTransformer();
            }
        }

        return null;
    }

    /**
     * Handle authentication exception.
     *
     * @param AuthenticationException $exception
     * @return JsonResponse
     */
    protected function handleAuthenticationException(AuthenticationException $exception): JsonResponse
    {
        return $this->errorResponse('Unauthenticated.', 401);
    }

    /**
     * Determine if the request is an API request.
     *
     * @param Request $request
     * @return bool
     */
    protected function isApiRequest(Request $request): bool
    {
        $prefix = config('api-starter-kit.prefix', 'api');
        return $request->is($prefix . '/*') || $request->is($prefix);
    }
}
