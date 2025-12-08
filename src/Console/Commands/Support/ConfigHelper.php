<?php

namespace LaravelArchitect\Console\Commands\Support;

class ConfigHelper
{
    /**
     * Build a helpful error message for missing config values.
     *
     * @param string $configKey The full config key that's missing
     * @return string
     */
    private static function buildErrorMessage(string $configKey): string
    {
        return "Missing required config value: {$configKey}. " .
               "Please ensure your config/api_scaffolding.php file is properly configured. " .
               "If you haven't published the config file yet, run: " .
               "php artisan vendor:publish --tag=laravel-architect-config";
    }

    /**
     * Generic config retrieval with type safety and validation.
     *
     * @param string $section Config section (paths, namespaces, drivers, etc.)
     * @param string $key     Config key within section
     * @param string|null $cast Type to cast result to (int, string, bool, array, object)
     * @param bool $nullable Whether null is acceptable (default: false)
     * @return mixed
     * @throws \RuntimeException If config key is missing or null when not nullable
     */
    private static function get(string $section, string $key, ?string $cast = null, bool $nullable = false): mixed
    {
        $configKey = "api_scaffolding.{$section}.{$key}";
        $value = config($configKey);

        if ($value === null && !$nullable) {
            throw new \RuntimeException(self::buildErrorMessage($configKey));
        }

        return $cast ? self::castValue($value, $cast) : $value;
    }

    /**
     * Cast value to specified type.
     */
    private static function castValue(mixed $value, string $type): mixed
    {
        return match($type) {
            'int', 'integer' => (int) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array' => (array) $value,
            'object' => (object) $value,
            default => $value,
        };
    }

    /**
     * Get an entire config section dynamically.
     *
     * @param string $section Section name (paths, namespaces, etc.)
     * @param string $returnType 'object' or 'array'
     * @return object|array
     * @throws \RuntimeException If config section is missing or empty
     */
    private static function getSection(string $section, string $returnType = 'object'): object|array
    {
        $configKey = "api_scaffolding.{$section}";
        $data = config($configKey);

        if (!is_array($data) || empty($data)) {
            throw new \RuntimeException(self::buildErrorMessage($configKey));
        }

        return $returnType === 'object' ? (object) $data : $data;
    }

    /**
     * Get all configured namespaces as an object.
     */
    public static function namespaces(): object
    {
        return self::getSection('namespaces', 'object');
    }

    /**
     * Get all configured paths as an object.
     */
    public static function paths(): object
    {
        return self::getSection('paths', 'object');
    }

    /**
     * Get a specific namespace by key.
     */
    public static function namespace(string $key): ?string
    {
        return self::get('namespaces', $key, null, nullable: true);
    }

    /**
     * Get a specific path by key.
     */
    public static function path(string $key): string
    {
        return self::get('paths', $key);
    }

    /**
     * Get a status code from config
     */
    public static function statusCode(string $key): int
    {
        return self::get('status_codes', $key, 'int');
    }

    /**
     * Get all status codes
     */
    public static function statusCodes(): object
    {
        return self::getSection('status_codes', 'object');
    }

    /**
     * Get a message from config
     */
    public static function message(string $key): string
    {
        return self::get('messages', $key, 'string');
    }

    /**
     * Get all messages
     */
    public static function messages(): object
    {
        return self::getSection('messages', 'object');
    }

    /**
     * Get pagination limit
     */
    public static function paginationLimit(): int
    {
        return self::get('response_settings', 'pagination_limit', 'int');
    }

    /**
     * Get JSON key for responses
     */
    public static function jsonKey(): string
    {
        return self::get('response_settings', 'json_key', 'string');
    }

    /**
     * Get full base path for a component
     */
    public static function basePath(string $key): string
    {
        return base_path(self::path($key));
    }

    /**
     * Get the driver for a specific operation type.
     *
     * @param string $type Operation type: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @return string Driver name: 'eloquent', 'mysql', or 'postgres'
     */
    public static function driver(string $type): string
    {
        return self::get('drivers', $type, 'string');
    }

    /**
     * Get all driver configurations.
     *
     * @return array Array of operation types mapped to their drivers
     */
    public static function drivers(): array
    {
        return self::getSection('drivers', 'array');
    }
}
