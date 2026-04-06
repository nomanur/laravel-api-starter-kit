<?php

namespace LaravelApi\StarterKit\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use LaravelApi\StarterKit\Traits\ApiResponser;

/**
 * Base API Controller that provides common functionality
 */
abstract class ApiBaseController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, ApiResponser;

    /**
     * Instantiation and middleware setup
     */
    public function __construct()
    {
        // Override this in your controllers to add middleware
        // Example: $this->middleware('auth:api')->except(['index', 'show']);
    }
}
