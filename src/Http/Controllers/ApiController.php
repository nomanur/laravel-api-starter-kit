<?php

namespace LaravelApi\StarterKit\Http\Controllers;

use LaravelApi\StarterKit\Traits\ApiExceptionHandlerTrait;

/**
 * Base API Controller with constructor middleware support
 * Extend this class for your API controllers
 */
abstract class ApiController extends ApiBaseController
{
    use ApiExceptionHandlerTrait;

    /**
     * Instantiate the controller and setup middleware
     */
    public function __construct()
    {
        // Override this in your controllers to add middleware
        // Example: $this->middleware('auth:api')->except(['index', 'show']);
    }
}
