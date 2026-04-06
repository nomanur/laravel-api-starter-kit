<?php

namespace LaravelApi\StarterKit;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LaravelApi\StarterKit\ApiBoilerplate
 */
class ApiBoilerplateFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'api-boilerplate';
    }
}
