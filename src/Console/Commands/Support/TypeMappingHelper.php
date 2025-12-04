<?php

declare(strict_types=1);

namespace LaravelArchitect\Console\Commands\Support;

/**
 * Centralized type mapping for schema types to various representations.
 *
 * Handles mapping from schema types (string, integer, etc.) to:
 * - Eloquent casts
 * - Validation rules (Step 05)
 * - Faker methods (Step 06)
 * - Test values (Step 07)
 */
class TypeMappingHelper
{
    /**
     * Schema type to Eloquent cast mapping
     */
    protected static array $castMap = [
        'boolean' => 'boolean',
        'integer' => 'integer',
        'unsignedBigInteger' => 'integer',
        'bigInteger' => 'integer',
        'decimal' => 'decimal:2',
        'float' => 'decimal:2',
        'double' => 'decimal:2',
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'datetime',
        'json' => 'array',
        // These types don't need casts
        'string' => null,
        'text' => null,
        'email' => null,
    ];

    /**
     * Schema type to validation rule mapping
     */
    protected static array $validationMap = [
        'string' => 'string',
        'text' => 'string',
        'email' => 'email',
        'integer' => 'integer',
        'unsignedBigInteger' => 'integer',
        'bigInteger' => 'integer',
        'boolean' => 'boolean',
        'decimal' => 'numeric',
        'float' => 'numeric',
        'double' => 'numeric',
        'date' => 'date',
        'datetime' => 'date',
        'timestamp' => 'date',
        'json' => 'array',
    ];

    /**
     * Get Eloquent cast type for a schema type
     */
    public static function getCast(string $type): ?string
    {
        return self::$castMap[$type] ?? null;
    }

    /**
     * Get all supported schema types
     */
    public static function getSupportedTypes(): array
    {
        return array_keys(self::$castMap);
    }

    /**
     * Check if a type requires a cast
     */
    public static function requiresCast(string $type): bool
    {
        return self::getCast($type) !== null;
    }

    /**
     * Get all cast mappings
     */
    public static function getAllCasts(): array
    {
        return array_filter(self::$castMap, fn($cast) => $cast !== null);
    }

    /**
     * Get validation rule for a schema type
     */
    public static function getValidationRule(string $type): string
    {
        return self::$validationMap[$type] ?? 'string';
    }

    /**
     * Build complete validation rules array for a field
     *
     * @param array $field Schema field definition
     * @return array Validation rules array
     */
    public static function buildValidationRules(array $field): array
    {
        $rules = [];

        // Required or nullable
        if ($field['required'] ?? false) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Type-based rule
        $rules[] = self::getValidationRule($field['type'] ?? 'string');

        return $rules;
    }

    /**
     * Get all validation mappings
     */
    public static function getAllValidationRules(): array
    {
        return self::$validationMap;
    }
}
