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
            'repositoryInterfaces' => config('api_scaffolding.namespaces.repository_interfaces'),
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
            'models' => base_path(config('api_scaffolding.paths.models')),
            'controllers' => base_path(config('api_scaffolding.paths.controllers')),
            'services' => base_path(config('api_scaffolding.paths.services')),
            'repositories' => base_path(config('api_scaffolding.paths.repositories')),
            'repositoryInterfaces' => base_path(config('api_scaffolding.paths.repository_interfaces')),
            'requests' => base_path(config('api_scaffolding.paths.requests')),
            'resources' => base_path(config('api_scaffolding.paths.resources')),
            'factories' => base_path(config('api_scaffolding.paths.factories')),
            'migrations' => base_path(config('api_scaffolding.paths.migrations')),
            'tests' => base_path(config('api_scaffolding.paths.tests')),
            'routes' => base_path(config('api_scaffolding.paths.routes')),
        ];
    }

    /**
     * Get a specific namespace by key.
     */
    public static function namespace(string $key): string
    {
        return config("api_scaffolding.namespaces.{$key}");
    }

    /**
     * Get a specific path by key.
     */
    public static function path(string $key): string
    {
        return base_path(config("api_scaffolding.paths.{$key}"));
    }
}
