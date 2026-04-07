<?php

namespace LaravelApi\StarterKit\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

/**
 * Base API Model with transformer support
 * 
 * Extend this class for your API models to enable automatic transformer support.
 * Do not use this class directly for queries.
 */
class ApiModel extends BaseModel
{
    /**
     * The transformer class for this model.
     *
     * @var string
     */
    public static $transformer;

    /**
     * Get the transformer class for this model.
     *
     * @return string|null
     */
    public function getTransformer(): ?string
    {
        return static::$transformer ?? null;
    }
}
