<?php

namespace LaravelApi\StarterKit\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

/**
 * Base API Model with transformer support
 */
abstract class ApiModel extends BaseModel
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
