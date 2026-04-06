<?php

namespace LaravelApi\StarterKit\Traits;

use Illuminate\Http\Request;

trait ValidatesRequests
{
    /**
     * Validate the given request data with the given rules.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return array
     */
    protected function validateRequest(Request $request, array $rules, array $messages = [], array $customAttributes = []): array
    {
        $validator = validator()->make(
            $request->all(),
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate the given data with the given rules.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return array
     */
    protected function validateData(array $data, array $rules, array $messages = [], array $customAttributes = []): array
    {
        $validator = validator()->make(
            $data,
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Common validation rules for API requests.
     *
     * @return array
     */
    protected function commonValidationRules(): array
    {
        return [
            'pagination' => [
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
            ],
            'sorting' => [
                'sort_by' => 'string|in:id,name,created_at,updated_at',
                'sort_order' => 'string|in:asc,desc',
            ],
            'filtering' => [
                'search' => 'string|max:255',
                'filter' => 'array',
            ],
        ];
    }

    /**
     * Get pagination validation rules.
     *
     * @return array
     */
    protected function paginationRules(): array
    {
        return $this->commonValidationRules()['pagination'];
    }

    /**
     * Get sorting validation rules.
     *
     * @param array $allowedFields
     * @return array
     */
    protected function sortingRules(array $allowedFields = []): array
    {
        $rules = $this->commonValidationRules()['sorting'];

        if (!empty($allowedFields)) {
            $rules['sort_by'] = 'string|in:' . implode(',', $allowedFields);
        }

        return $rules;
    }

    /**
     * Get filtering validation rules.
     *
     * @return array
     */
    protected function filteringRules(): array
    {
        return $this->commonValidationRules()['filtering'];
    }
}
