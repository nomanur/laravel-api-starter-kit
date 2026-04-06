<?php

namespace LaravelApi\StarterKit\Transformers;

use Illuminate\Database\Eloquent\Model;

class UserTransformer extends BaseTransformer
{
    /**
     * Transform the model into an array.
     *
     * @param Model $user
     * @return array
     */
    public function transform(Model $user)
    {
        return [
            'identifier' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'isVerified' => (int) $user->verified,
            'isAdmin' => ($user->admin === 'true'),
            'creationDate' => $user->created_at,
            'lastChange' => $user->updated_at,
            'deleteDate' => isset($user->deleted_at) ? (string) $user->deleted_at : null,

            'links' => [
                [
                    'rel' => 'self',
                    'href' => route('users.show', $user->id),
                ],
            ],
        ];
    }

    /**
     * Map original attributes to transformed attributes.
     *
     * @param string $index
     * @return string|null
     */
    public static function originalAttribute(string $index): ?string
    {
        $attribute = [
            'identifier' => 'id',
            'name' => 'name',
            'email' => 'email',
            'isVerified' => 'verified',
            'isAdmin' => 'admin',
            'creationDate' => 'created_at',
            'lastChange' => 'updated_at',
            'deleteDate' => 'deleted_at',
        ];

        return isset($attribute[$index]) ? $attribute[$index] : null;
    }

    /**
     * Map transformed attributes back to original attributes.
     *
     * @param string $index
     * @return string|null
     */
    public static function transformedAttribute(string $index): ?string
    {
        $attributes = [
            'id' => 'identifier',
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
            'verified' => 'isVerified',
            'admin' => 'isAdmin',
            'created_at' => 'creationDate',
            'updated_at' => 'lastChange',
            'deleted_at' => 'deletedDate',
        ];

        return isset($attributes[$index]) ? $attributes[$index] : null;
    }
}
