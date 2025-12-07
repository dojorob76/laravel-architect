<?php

namespace LaravelArchitect\Tests;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;

use Illuminate\Support\Facades\File;
use LaravelArchitect\LaravelArchitectServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

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
        // Determine which database to use based on environment variable
        $connection = env('DB_CONNECTION', 'testbench');

        if ($connection === 'mysql') {
            $app['config']->set('database.default', 'mysql');
            $app['config']->set('database.connections.mysql', [
                'driver'   => 'mysql',
                'host'     => env('DB_HOST', 'mysql'),
                'port'     => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'laravel_architect_test'),
                'username' => env('DB_USERNAME', 'laravel'),
                'password' => env('DB_PASSWORD', 'secret'),
                'charset'  => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'   => '',
                'strict'   => true,
                'engine'   => null,
            ]);
        } elseif ($connection === 'pgsql') {
            $app['config']->set('database.default', 'pgsql');
            $app['config']->set('database.connections.pgsql', [
                'driver'   => 'pgsql',
                'host'     => env('DB_HOST', 'postgres'),
                'port'     => env('DB_PORT', '5432'),
                'database' => env('DB_DATABASE', 'laravel_architect_test'),
                'username' => env('DB_USERNAME', 'laravel'),
                'password' => env('DB_PASSWORD', 'secret'),
                'charset'  => 'utf8',
                'prefix'   => '',
                'schema'   => 'public',
                'sslmode'  => 'prefer',
            ]);
        } else {
            // Default to SQLite in-memory (existing configuration)
            $app['config']->set('database.default', 'testbench');
            $app['config']->set('database.connections.testbench', [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ]);
        }

        $app['config']->set('api_scaffolding', [
            'paths' => [
                'models' => 'app/Models',
                'repositories' => 'app/Repositories',
                'contracts' => 'app/Contracts',
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
                'contracts' => 'App\Contracts',
                'services' => 'App\Services',
                'controllers' => 'App\Http\Controllers\Api',
                'base_controller' => 'App\Http\Controllers\Controller',
                'requests' => 'App\Http\Requests',
                'resources' => 'App\Http\Resources',
                'factories' => 'Database\Factories',
            ],
            // Driver configuration for each operation type
            'drivers' => [
                'crud' => 'eloquent',
                'bulk' => 'eloquent',
                'soft_delete' => 'eloquent',
                'bulk_soft_delete' => 'eloquent',
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
        $configPaths = ConfigHelper::paths();

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
        $path = ConfigHelper::path($type);
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
        $migrationPath = ConfigHelper::basePath('migrations');

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
