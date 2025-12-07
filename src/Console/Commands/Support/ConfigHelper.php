<?php

namespace LaravelArchitect\Console\Commands\Support;

class ConfigHelper
{
    /**
     * Get all configured namespaces as an object.
     */
    public static function namespaces(): object
    {
        return (object) [
            'models' => config('api_scaffolding.namespaces.models'),
            'controllers' => config('api_scaffolding.namespaces.controllers'),
            'services' => config('api_scaffolding.namespaces.services'),
            'repositories' => config('api_scaffolding.namespaces.repositories'),
            'contracts' => config('api_scaffolding.namespaces.contracts'),
            'requests' => config('api_scaffolding.namespaces.requests'),
            'resources' => config('api_scaffolding.namespaces.resources'),
            'factories' => config('api_scaffolding.namespaces.factories'),
            'baseController' => config('api_scaffolding.namespaces.base_controller'),
        ];
    }

    /**
     * Get all configured paths as an object.
     */
    public static function paths(): object
    {
        return (object) [
            'models' => config('api_scaffolding.paths.models'),
            'controllers' => config('api_scaffolding.paths.controllers'),
            'services' => config('api_scaffolding.paths.services'),
            'repositories' => config('api_scaffolding.paths.repositories'),
            'contracts' => config('api_scaffolding.paths.contracts'),
            'requests' => config('api_scaffolding.paths.requests'),
            'resources' => config('api_scaffolding.paths.resources'),
            'factories' => config('api_scaffolding.paths.factories'),
            'migrations' => config('api_scaffolding.paths.migrations'),
            'tests' => config('api_scaffolding.paths.tests'),
            'routes' => config('api_scaffolding.paths.routes'),
        ];
    }

    /**
     * Get a specific namespace by key.
     */
    public static function namespace(string $key): ?string
    {
        return config("api_scaffolding.namespaces.{$key}");
    }

    /**
     * Get a specific path by key.
     */
    public static function path(string $key): string
    {
        return config("api_scaffolding.paths.{$key}");
    }

    /**
     * Get a status code from config
     */
    public static function statusCode(string $key): int
    {
        return (int) config("api_scaffolding.status_codes.{$key}", 200);
    }

    /**
     * Get all status codes
     */
    public static function statusCodes(): object
    {
        return (object) config('api_scaffolding.status_codes', []);
    }

    /**
     * Get a message from config
     */
    public static function message(string $key): string
    {
        return (string) config("api_scaffolding.messages.{$key}", '');
    }

    /**
     * Get all messages
     */
    public static function messages(): object
    {
        return (object) config('api_scaffolding.messages', []);
    }

    /**
     * Get pagination limit
     */
    public static function paginationLimit(): int
    {
        return (int) config('api_scaffolding.response_settings.pagination_limit', 15);
    }

    /**
     * Get JSON key for responses
     */
    public static function jsonKey(): string
    {
        return (string) config('api_scaffolding.response_settings.json_key', 'data');
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
        return config("api_scaffolding.drivers.{$type}", 'eloquent');
    }

    /**
     * Get all driver configurations.
     *
     * @return array Array of operation types mapped to their drivers
     */
    public static function drivers(): array
    {
        return config('api_scaffolding.drivers', [
            'crud' => 'eloquent',
            'bulk' => 'eloquent',
            'soft_delete' => 'eloquent',
            'bulk_soft_delete' => 'eloquent',
        ]);
    }
}
