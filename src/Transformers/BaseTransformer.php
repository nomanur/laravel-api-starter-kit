<?php

namespace LaravelApi\StarterKit\Transformers;

use League\Fractal\TransformerAbstract;

abstract class BaseTransformer extends TransformerAbstract
{
    /**
     * Map transformed attributes to original attributes.
     */
    abstract public static function originalAttribute(string $index): ?string;

    /**
     * Map original attributes to transformed attributes.
     */
    abstract public static function transformedAttribute(string $index): ?string;
}
