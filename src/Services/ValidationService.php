<?php

declare(strict_types=1);

namespace LaravelArchitect\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Centralized validation service for single and batch operations.
 *
 * Uses model's static validation methods (baseRules, updateOverrides, validationMessages)
 * to maintain a single source of truth for validation.
 */
class ValidationService
{
    /**
     * Validate data for creating a single record
     *
     * @param string $modelClass Fully qualified model class name
     * @param array $data Data to validate
     * @param array $context Additional context for composite unique validation
     * @throws ValidationException
     */
    public function validateForCreate(string $modelClass, array $data, array $context = []): array
    {
        $rules = $this->getCreateRules($modelClass, $context);
        $messages = $this->getMessages($modelClass);

        return $this->validate($data, $rules, $messages);
    }

    /**
     * Validate data for updating a single record
     *
     * @param string $modelClass Fully qualified model class name
     * @param array $data Data to validate
     * @param int|null $id Record ID for unique rule exclusion
     * @param array $context Additional context for composite unique validation
     * @throws ValidationException
     */
    public function validateForUpdate(string $modelClass, array $data, ?int $id = null, array $context = []): array
    {
        $rules = $this->getUpdateRules($modelClass, $id, $context);
        $messages = $this->getMessages($modelClass);

        return $this->validate($data, $rules, $messages);
    }

    /**
     * Validate data for batch create operation
     *
     * @param string $modelClass Fully qualified model class name
     * @param array $records Array of records to validate
     * @param array $context Additional context for composite unique validation
     * @throws ValidationException With all errors from all records
     */
    public function validateBatchCreate(string $modelClass, array $records, array $context = []): array
    {
        $rules = $this->getCreateRules($modelClass, $context);
        $messages = $this->getMessages($modelClass);

        return $this->validateBatch($records, $rules, $messages, 'records');
    }

    /**
     * Validate data for batch update operation
     *
     * @param string $modelClass Fully qualified model class name
     * @param array $records Array of records to validate (each must have 'id')
     * @param array $context Additional context for composite unique validation
     * @throws ValidationException
     */
    public function validateBatchUpdate(string $modelClass, array $records, array $context = []): array
    {
        $messages = $this->getMessages($modelClass);
        $errors = [];
        $validated = [];

        foreach ($records as $index => $record) {
            $id = $record['id'] ?? null;
            $rules = $this->getUpdateRules($modelClass, $id, $context);

            try {
                $validated[] = $this->validate($record, $rules, $messages);
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $fieldErrors) {
                    $errors["records.{$index}.{$field}"] = $fieldErrors;
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    /**
     * Get create validation rules from model
     */
    protected function getCreateRules(string $modelClass, array $context = []): array
    {
        if (method_exists($modelClass, 'baseRules')) {
            return $modelClass::baseRules($context);
        }

        return [];
    }

    /**
     * Get update validation rules from model
     */
    protected function getUpdateRules(string $modelClass, ?int $id = null, array $context = []): array
    {
        $rules = $this->getCreateRules($modelClass, $context);

        if (method_exists($modelClass, 'updateOverrides')) {
            $overrides = $modelClass::updateOverrides($id, $context);
            $rules = array_merge($rules, $overrides);
        }

        // Convert 'required' to 'sometimes' for updates
        foreach ($rules as $field => $fieldRules) {
            if (is_array($fieldRules)) {
                $rules[$field] = array_map(
                    fn($rule) => $rule === 'required' ? 'sometimes' : $rule,
                    $fieldRules
                );
            } elseif (is_string($fieldRules)) {
                $rules[$field] = str_replace('required', 'sometimes', $fieldRules);
            }
        }

        return $rules;
    }

    /**
     * Get custom validation messages from model
     */
    protected function getMessages(string $modelClass): array
    {
        if (method_exists($modelClass, 'validationMessages')) {
            return $modelClass::validationMessages();
        }

        return [];
    }

    /**
     * Run validation and return validated data
     *
     * @throws ValidationException
     */
    protected function validate(array $data, array $rules, array $messages): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate batch of records
     *
     * @throws ValidationException
     */
    protected function validateBatch(array $records, array $rules, array $messages, string $prefix): array
    {
        $batchRules = [];
        foreach ($rules as $field => $fieldRules) {
            $batchRules["{$prefix}.*.{$field}"] = $fieldRules;
        }

        $batchMessages = [];
        foreach ($messages as $key => $message) {
            $batchMessages["{$prefix}.*.{$key}"] = $message;
        }

        return $this->validate([$prefix => $records], $batchRules, $batchMessages);
    }
}
