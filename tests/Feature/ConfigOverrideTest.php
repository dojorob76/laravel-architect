<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

describe('commands respect custom config paths', function () {
    beforeEach(function () {
        // Override to custom paths
        config([
            'api_scaffolding.paths.repositories' => 'src/Infrastructure/Repositories',
            'api_scaffolding.paths.contracts' => 'src/Infrastructure/Contracts',
            'api_scaffolding.namespaces.repositories' => 'App\\Infrastructure\\Repositories',
            'api_scaffolding.namespaces.contracts' => 'App\\Infrastructure\\Contracts',
        ]);

        // Ensure custom directories exist
        File::ensureDirectoryExists(base_path('src/Infrastructure/Repositories'));
        File::ensureDirectoryExists(base_path('src/Infrastructure/Contracts'));
    });

    afterEach(function () {
        // Clean up custom directories
        if (File::exists(base_path('src'))) {
            File::deleteDirectory(base_path('src'));
        }
    });

    test('repository command generates files at custom path', function () {
        Artisan::call('make:api-repository', ['name' => 'Video']);

        expect(File::exists(base_path('src/Infrastructure/Repositories/EloquentVideoRepository.php')))->toBeTrue();
        expect(File::exists(base_path('src/Infrastructure/Contracts/VideoRepositoryInterface.php')))->toBeTrue();
    });

    test('generated repository has custom namespace', function () {
        Artisan::call('make:api-repository', ['name' => 'Video']);

        $content = File::get(base_path('src/Infrastructure/Repositories/EloquentVideoRepository.php'));

        expect($content)->toContain('namespace App\\Infrastructure\\Repositories;');
    });

    test('generated interface has custom namespace', function () {
        Artisan::call('make:api-repository', ['name' => 'Video']);

        $content = File::get(base_path('src/Infrastructure/Contracts/VideoRepositoryInterface.php'));

        expect($content)->toContain('namespace App\\Infrastructure\\Contracts;');
    });
});

describe('commands respect custom config namespaces', function () {
    beforeEach(function () {
        // Override namespaces while keeping default paths
        config([
            'api_scaffolding.namespaces.requests' => 'Domain\\Requests',
            'api_scaffolding.namespaces.resources' => 'Domain\\Resources',
            'api_scaffolding.namespaces.factories' => 'Domain\\Factories',
        ]);

        // Ensure directories exist using default paths
        File::ensureDirectoryExists(ConfigHelper::basePath('requests'));
        File::ensureDirectoryExists(ConfigHelper::basePath('resources'));
        File::ensureDirectoryExists(ConfigHelper::basePath('factories'));
    });

    test('request command uses custom namespace', function () {
        Artisan::call('make:api-request', ['name' => 'Video']);

        $content = $this->getGeneratedFileContent('requests', 'VideoRequest.php');

        expect($content)->toContain('namespace Domain\\Requests');
    });

    test('resource command uses custom namespace', function () {
        Artisan::call('make:api-resource', ['name' => 'Video']);

        $content = $this->getGeneratedFileContent('resources', 'VideoResource.php');

        expect($content)->toContain('namespace Domain\\Resources');
    });

    test('factory command uses custom namespace', function () {
        Artisan::call('make:api-factory', ['name' => 'Video']);

        $content = $this->getGeneratedFileContent('factories', 'VideoFactory.php');

        expect($content)->toContain('namespace Domain\\Factories');
    });
});

describe('commands respect custom config for both paths and namespaces', function () {
    beforeEach(function () {
        // Override both paths and namespaces
        config([
            'api_scaffolding.paths.controllers' => 'src/Application/Controllers',
            'api_scaffolding.namespaces.controllers' => 'App\\Application\\Controllers',
        ]);

        // Ensure custom directory exists
        File::ensureDirectoryExists(base_path('src/Application/Controllers'));
    });

    afterEach(function () {
        // Clean up custom directory
        if (File::exists(base_path('src'))) {
            File::deleteDirectory(base_path('src'));
        }
    });

    test('controller command generates file at custom path with custom namespace', function () {
        Artisan::call('make:api-controller', ['name' => 'Video']);

        // Check file exists at custom path
        expect(File::exists(base_path('src/Application/Controllers/VideoController.php')))->toBeTrue();

        // Check file has custom namespace
        $content = File::get(base_path('src/Application/Controllers/VideoController.php'));
        expect($content)->toContain('namespace App\\Application\\Controllers;');
    });
});

describe('ConfigHelper config override behavior', function () {
    test('path returns overridden value when config changes', function () {
        // Store original
        $original = ConfigHelper::path('repositories');
        expect($original)->toBe('app/Repositories');

        // Override config
        config(['api_scaffolding.paths.repositories' => 'custom/repos']);

        // Verify override works
        expect(ConfigHelper::path('repositories'))->toBe('custom/repos');

        // Restore original
        config(['api_scaffolding.paths.repositories' => $original]);
    });

    test('namespace returns overridden value when config changes', function () {
        // Store original
        $original = ConfigHelper::namespace('models');
        expect($original)->toBe('App\\Models');

        // Override config
        config(['api_scaffolding.namespaces.models' => 'Domain\\Models']);

        // Verify override works
        expect(ConfigHelper::namespace('models'))->toBe('Domain\\Models');

        // Restore original
        config(['api_scaffolding.namespaces.models' => $original]);
    });

    test('basePath returns overridden value when config changes', function () {
        // Override config
        config(['api_scaffolding.paths.services' => 'custom/services']);

        // Verify basePath reflects the override
        $basePath = ConfigHelper::basePath('services');
        expect($basePath)->toEndWith('custom/services');

        // Restore original
        config(['api_scaffolding.paths.services' => 'app/Services']);
    });
});
