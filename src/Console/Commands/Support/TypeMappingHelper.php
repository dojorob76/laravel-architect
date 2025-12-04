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
}
