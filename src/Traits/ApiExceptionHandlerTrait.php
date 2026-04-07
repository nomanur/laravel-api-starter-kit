<?php

namespace LaravelApi\StarterKit\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait ApiExceptionHandlerTrait
{
    use ApiResponser;

    /**
     * Handle API exceptions and return standardized JSON responses.
     */
    public function handleApiExceptions(Throwable $exception, Request $request)
    {
        if ($exception instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        if ($exception instanceof ModelNotFoundException) {
            $modelName = strtolower(class_basename($exception->getModel()));
            return $this->errorResponse("Does not exist any {$modelName} with the specified identifier", 404);
        }

        if ($exception instanceof AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }

        if ($exception instanceof AuthorizationException) {
            return $this->errorResponse($exception->getMessage(), 403);
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->errorResponse('The specified URL cannot be found', 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse('The specified method for the request is invalid', 405);
        }

        if ($exception instanceof HttpException) {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        }

        if ($exception instanceof QueryException) {
            $errorCode = $exception->errorInfo[1];

            if ($errorCode == 1451) {
                return $this->errorResponse('Cannot remove this resource permanently. It is related with any other resource', 409);
            }
        }

        if (config('app.debug')) {
            return parent::render($request, $exception);
        }

        return $this->errorResponse('Unexpected Exception. Try later', 500);
    }

    /**
     * Create a response object from the given validation exception.
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        $errors = $e->validator->errors()->getMessages();

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

        return $this->errorResponse('Validation failed', 422, $errors);
    }

    /**
     * Get the transformer class from the current request context.
     */
    protected function getTransformerFromRequest($request = null): ?string
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
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->errorResponse('Unauthenticated', 401);
    }
}
