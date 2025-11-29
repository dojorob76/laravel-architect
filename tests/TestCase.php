<?php

namespace tests;

use Illuminate\Support\Facades\File;
use LaravelArchitect\LaravelArchitectServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use function LaravelArchitect\Tests\base_path;
use function LaravelArchitect\Tests\config;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFiles();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelArchitectServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('api_scaffolding', [
            'paths' => [
                'models' => 'app/Models',
                'repositories' => 'app/Repositories',
                'repository_interfaces' => 'app/Repositories/Interfaces',
                'services' => 'app/Services',
                'controllers' => 'app/Http/Controllers/Api',
                'requests' => 'app/Http/Requests',
                'resources' => 'app/Http/Resources',
                'migrations' => 'database/migrations',
                'tests' => 'tests/Feature',
                'factories' => 'database/factories',
                'routes' => 'routes/api.php',
            ],
            'namespaces' => [
                'models' => 'App\Models',
                'repositories' => 'App\Repositories',
                'repository_interfaces' => 'App\Repositories\Interfaces',
                'services' => 'App\Services',
                'controllers' => 'App\Http\Controllers\Api',
                'base_controller' => 'App\Http\Controllers\Controller',
                'requests' => 'App\Http\Requests',
                'resources' => 'App\Http\Resources',
                'factories' => 'Database\Factories',
            ],
            'response_settings' => [
                'json_key' => 'data',
                'pagination_limit' => 15,
            ],
            'status_codes' => [
                'success' => 200,
                'created' => 201,
                'no_content' => 204,
                'not_found' => 404,
                'unprocessable_entity' => 422,
                'server_error' => 500,
            ],
            'messages' => [
                'created' => 'Resource created successfully.',
                'updated' => 'Resource updated successfully.',
                'deleted' => 'Resource deleted successfully.',
                'restored' => 'Resource restored successfully.',
                'not_found' => 'Resource not found.',
            ],
        ]);
    }

    protected function setUpDatabase(): void
    {
        // Database setup if needed
    }

    protected function cleanupGeneratedFiles(): void
    {
        $configPaths = config('api_scaffolding.paths');

        foreach ($configPaths as $key => $path) {
            if ($key === 'routes') {
                continue;
            }

            $fullPath = base_path($path);

            if (File::exists($fullPath)) {
                File::cleanDirectory($fullPath);
            }
        }
    }

    protected function getGeneratedFilePath(string $type, string $filename): string
    {
        $path = config("api_scaffolding.paths.{$type}");
        return base_path("{$path}/{$filename}");
    }

    protected function assertFileWasGenerated(string $type, string $filename): void
    {
        $path = $this->getGeneratedFilePath($type, $filename);

        $this->assertTrue(
            File::exists($path),
            "Expected file to be generated at: {$path}"
        );
    }

    protected function assertFileContains(string $type, string $filename, string $needle): void
    {
        $this->assertFileWasGenerated($type, $filename);

        $path = $this->getGeneratedFilePath($type, $filename);
        $content = File::get($path);

        $this->assertStringContainsString(
            $needle,
            $content,
            "Expected file {$path} to contain: {$needle}"
        );
    }

    protected function assertFileDoesNotContain(string $type, string $filename, string $needle): void
    {
        $this->assertFileWasGenerated($type, $filename);

        $path = $this->getGeneratedFilePath($type, $filename);
        $content = File::get($path);

        $this->assertStringNotContainsString(
            $needle,
            $content,
            "Expected file {$path} to NOT contain: {$needle}"
        );
    }

    protected function getGeneratedFileContent(string $type, string $filename): string
    {
        $path = $this->getGeneratedFilePath($type, $filename);
        return File::get($path);
    }

    protected function findMigrationFile(string $tableName): ?string
    {
        $migrationPath = base_path(config('api_scaffolding.paths.migrations'));

        if (!File::exists($migrationPath)) {
            return null;
        }

        $files = File::files($migrationPath);

        foreach ($files as $file) {
            if (str_contains($file->getFilename(), "create_{$tableName}_table")) {
                return $file->getPathname();
            }
        }

        return null;
    }

    protected function assertMigrationExists(string $tableName): void
    {
        $file = $this->findMigrationFile($tableName);

        $this->assertNotNull(
            $file,
            "Expected migration for table '{$tableName}' to exist"
        );
    }

    protected function getMigrationContent(string $tableName): string
    {
        $file = $this->findMigrationFile($tableName);

        if (!$file) {
            $this->fail("Migration file for table '{$tableName}' not found");
        }

        return File::get($file);
    }
}
