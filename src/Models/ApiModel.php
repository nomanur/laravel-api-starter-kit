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
    public $transformer;

    /**
     * Boot the model and set the transformer if defined.
     */
    protected static function boot()
    {
        parent::boot();

        if (property_exists(static::class, 'transformer')) {
            static::retrieved(function ($model) {
                $model->transformer = static::$transformer;
            });
        }
    }
}
