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
     * Schema type to Faker method mapping
     */
    protected static array $fakerMap = [
        'string' => 'fake()->word()',
        'text' => 'fake()->paragraph()',
        'email' => 'fake()->safeEmail()',
        'integer' => 'fake()->numberBetween(1, 1000)',
        'unsignedBigInteger' => 'fake()->numberBetween(1, 1000)',
        'bigInteger' => 'fake()->numberBetween(1, 1000)',
        'boolean' => 'fake()->boolean()',
        'decimal' => 'fake()->randomFloat(2, 0, 1000)',
        'float' => 'fake()->randomFloat(2, 0, 1000)',
        'double' => 'fake()->randomFloat(2, 0, 1000)',
        'date' => 'fake()->date()',
        'datetime' => 'fake()->dateTime()',
        'timestamp' => 'fake()->dateTime()',
        'json' => "['key' => fake()->word()]",
    ];

    /**
     * Semantic column name patterns to Faker methods
     * Order matters: more specific patterns should come first
     */
    protected static array $semanticFakerMap = [
        'email' => 'fake()->safeEmail()',
        'phone' => 'fake()->phoneNumber()',
        'zip' => 'fake()->postcode()',
        'postal' => 'fake()->postcode()',
        'address' => 'fake()->address()',
        'city' => 'fake()->city()',
        'country' => 'fake()->country()',
        'url' => 'fake()->url()',
        'link' => 'fake()->url()',
        'description' => 'fake()->paragraph()',
        'content' => 'fake()->paragraph()',
        'body' => 'fake()->paragraph()',
        'title' => 'fake()->words(3, true)',
        'name' => 'fake()->words(3, true)',
        'slug' => 'fake()->slug()',
        'uuid' => 'fake()->uuid()',
        'image' => 'fake()->imageUrl()',
        'avatar' => 'fake()->imageUrl(64, 64)',
        'password' => 'bcrypt(fake()->password())',
        'token' => 'Str::random(60)',
        'user_agent' => 'fake()->userAgent()',
        'color' => 'fake()->hexColor()',
        'company' => 'fake()->company()',
        'ip' => 'fake()->ipv4()',
    ];

    /**
     * Patterns to exclude from semantic matching
     * Prevents false positives (e.g., 'filename' shouldn't match 'name')
     */
    protected static array $semanticExclusions = [
        'name' => ['file', 'class', 'table', 'column', 'field', 'path', 'host', 'domain'],
    ];

    /**
     * Faker methods that require specific use statements
     */
    protected static array $fakerImports = [
        'Str::random' => 'Illuminate\Support\Str',
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

    /**
     * Get Faker method for a schema type
     */
    public static function getFakerMethod(string $type): string
    {
        return self::$fakerMap[$type] ?? 'fake()->word()';
    }

    /**
     * Get semantic Faker method based on column name
     * Returns null if no semantic match found
     */
    public static function getSemanticFakerMethod(string $columnName): ?string
    {
        $lowerName = strtolower($columnName);

        // Check each pattern in order
        foreach (self::$semanticFakerMap as $pattern => $faker) {
            if (str_contains($lowerName, $pattern)) {
                // Check exclusions for this pattern
                if (isset(self::$semanticExclusions[$pattern])) {
                    $excluded = false;
                    foreach (self::$semanticExclusions[$pattern] as $exclusion) {
                        if (str_contains($lowerName, $exclusion)) {
                            $excluded = true;
                            break;
                        }
                    }
                    if ($excluded) {
                        continue;
                    }
                }
                return $faker;
            }
        }

        return null;
    }

    /**
     * Get best Faker method considering both column name and type
     */
    public static function getBestFakerMethod(string $type, ?string $columnName = null): string
    {
        // First try semantic matching based on column name
        if ($columnName !== null) {
            $semantic = self::getSemanticFakerMethod($columnName);
            if ($semantic !== null) {
                return $semantic;
            }
        }

        // Fall back to type-based faker
        return self::getFakerMethod($type);
    }

    /**
     * Get all semantic faker patterns
     */
    public static function getSemanticPatterns(): array
    {
        return array_keys(self::$semanticFakerMap);
    }

    /**
     * Get all faker mappings
     */
    public static function getAllFakerMethods(): array
    {
        return self::$fakerMap;
    }

    /**
     * Get required use statements for a faker method
     *
     * @param string $fakerMethod The faker method code (e.g., 'Str::random(60)')
     * @return array Array of fully qualified class names that need to be imported
     */
    public static function getRequiredImports(string $fakerMethod): array
    {
        $imports = [];

        foreach (self::$fakerImports as $pattern => $import) {
            if (str_contains($fakerMethod, $pattern)) {
                $imports[] = $import;
            }
        }

        return $imports;
    }

    /**
     * Schema type to test value mapping (for create operations)
     */
    protected static array $testValueMap = [
        'string' => "'Test String'",
        'text' => "'Test text content'",
        'email' => "'test@example.com'",
        'integer' => '100',
        'unsignedBigInteger' => '100',
        'bigInteger' => '100',
        'boolean' => 'true',
        'decimal' => '100.50',
        'float' => '100.50',
        'double' => '100.50',
        'date' => "'2024-01-01'",
        'datetime' => "'2024-01-01 12:00:00'",
        'timestamp' => "'2024-01-01 12:00:00'",
        'json' => "['key' => 'value']",
    ];

    /**
     * Schema type to updated test value mapping (for update operations)
     */
    protected static array $updateTestValueMap = [
        'string' => "'Test String Updated'",
        'text' => "'Test text content Updated'",
        'email' => "'testUpdated@example.com'",
        'integer' => '200',
        'unsignedBigInteger' => '200',
        'bigInteger' => '200',
        'boolean' => 'false',
        'decimal' => '200.50',
        'float' => '200.50',
        'double' => '200.50',
        'date' => "'2024-02-01'",
        'datetime' => "'2024-02-01 12:00:00'",
        'timestamp' => "'2024-02-01 12:00:00'",
        'json' => "['updated' => true]",
    ];

    /**
     * Schema type to invalid test value mapping (for validation tests)
     */
    protected static array $invalidTestValueMap = [
        'string' => '12345',
        'text' => '12345',
        'email' => "'not-an-email'",
        'integer' => "'not-an-integer'",
        'unsignedBigInteger' => "'not-an-integer'",
        'bigInteger' => "'not-an-integer'",
        'boolean' => "'not-a-boolean'",
        'decimal' => "'not-a-number'",
        'float' => "'not-a-number'",
        'double' => "'not-a-number'",
        'date' => "'not-a-date'",
        'datetime' => "'not-a-datetime'",
        'timestamp' => "'not-a-timestamp'",
        'json' => "'not-valid-json'",
    ];

    /**
     * Get test value for a schema type
     */
    public static function getTestValue(string $type, bool $isUpdate = false): string
    {
        if ($isUpdate) {
            return self::$updateTestValueMap[$type] ?? "'Test Value Updated'";
        }
        return self::$testValueMap[$type] ?? "'Test Value'";
    }

    /**
     * Get invalid test value for validation testing
     */
    public static function getInvalidTestValue(string $type): string
    {
        return self::$invalidTestValueMap[$type] ?? 'null';
    }

    /**
     * Build test data array for a set of fields
     *
     * @param array $fields Schema fields
     * @param bool $isUpdate Whether this is update data
     * @param callable|null $customValueFn Optional callback for custom values: fn(array $field, bool $isUpdate): ?string
     * @return array Field name => test value mapping
     */
    public static function buildTestData(array $fields, bool $isUpdate = false, ?callable $customValueFn = null): array
    {
        $data = [];
        foreach ($fields as $field) {
            // Expects normalized schema with 'field' key
            $name = $field['field'] ?? null;

            // Skip fields that don't have a field name (e.g., parentModel before normalization)
            if ($name === null) {
                continue;
            }

            // Try custom value function first
            if ($customValueFn !== null) {
                $customValue = $customValueFn($field, $isUpdate);
                if ($customValue !== null) {
                    $data[$name] = $customValue;
                    continue;
                }
            }

            // Fall back to type-based value
            $type = $field['type'] ?? 'string';
            $data[$name] = self::getTestValue($type, $isUpdate);
        }
        return $data;
    }

    /**
     * Build partial update test data (subset of fields)
     *
     * @param array $fields Schema fields
     * @param int $count Number of fields to include
     * @param callable|null $customValueFn Optional callback for custom values
     * @return array Field name => test value mapping
     */
    public static function buildPartialTestData(array $fields, int $count = 1, ?callable $customValueFn = null): array
    {
        $subset = array_slice($fields, 0, $count);
        return self::buildTestData($subset, true, $customValueFn);
    }
}
