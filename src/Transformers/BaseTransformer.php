<?php

namespace LaravelApi\StarterKit\Transformers;

use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{
    /**
     * Map original attributes to transformed attributes.
     */
    abstract public static function originalAttribute($index);

    /**
     * Map transformed attributes back to original attributes.
     */
    abstract public static function transformedAttribute($index);
}
