<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $repoPath = ConfigHelper::basePath('repositories');
    $interfacePath = ConfigHelper::basePath('contracts');

    File::ensureDirectoryExists($repoPath);
    File::ensureDirectoryExists($interfacePath);
});

test('creates repository interface', function () {
    Artisan::call('make:api-repository', ['name' => 'Video']);

    $this->assertFileWasGenerated('contracts', 'VideoRepositoryInterface.php');
    $this->assertFileContains('contracts', 'VideoRepositoryInterface.php', 'interface VideoRepositoryInterface');
    $this->assertFileContains('contracts', 'VideoRepositoryInterface.php', 'extends BaseRepositoryInterface');
});

test('creates eloquent repository by default', function () {
    Artisan::call('make:api-repository', ['name' => 'Video']);

    $this->assertFileWasGenerated('repositories', 'EloquentVideoRepository.php');
    $this->assertFileContains('repositories', 'EloquentVideoRepository.php', 'extends BaseEloquentRepository');
    $this->assertFileContains('repositories', 'EloquentVideoRepository.php', 'implements VideoRepositoryInterface');
});

test('creates mysql repository when specified', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--driver' => 'mysql'
    ]);

    $this->assertFileWasGenerated('repositories', 'MySQLVideoRepository.php');
    $this->assertFileContains('repositories', 'MySQLVideoRepository.php', 'extends BaseMySQLRepository');
    $this->assertFileContains('repositories', 'MySQLVideoRepository.php', "protected string \$table = 'videos'");
});

test('creates postgres repository when specified', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--driver' => 'postgres'
    ]);

    $this->assertFileWasGenerated('repositories', 'PostgresVideoRepository.php');
    $this->assertFileContains('repositories', 'PostgresVideoRepository.php', 'extends BasePostgresRepository');
});

test('creates all repository types when driver is all', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--driver' => 'all'
    ]);

    $this->assertFileWasGenerated('repositories', 'EloquentVideoRepository.php');
    $this->assertFileWasGenerated('repositories', 'MySQLVideoRepository.php');
    $this->assertFileWasGenerated('repositories', 'PostgresVideoRepository.php');
});

test('repository has batch operation methods', function () {
    Artisan::call('make:api-repository', ['name' => 'Video']);

    $content = $this->getGeneratedFileContent('repositories', 'EloquentVideoRepository.php');

    // Batch methods are inherited from BaseEloquentRepository
    expect($content)->toContain('extends BaseEloquentRepository')
        ->and($content)->toContain('implements VideoRepositoryInterface');
});

describe('buildClassInfo method', function () {
    beforeEach(function () {
        // Create command instance for testing
        $this->command = new \LaravelArchitect\Console\Commands\MakeApiRepositoryCommand();

        // Make buildClassInfo accessible via reflection
        $reflection = new \ReflectionClass($this->command);
        $this->buildClassInfoMethod = $reflection->getMethod('buildClassInfo');
        $this->buildClassInfoMethod->setAccessible(true);
    });

    describe('CRUD repository class info', function () {
        test('returns correct info for eloquent CRUD repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'crud',
                'repository'
            );

            expect($result)->toHaveKeys(['name', 'import', 'path'])
                ->and($result['name'])->toBe('EloquentVideoRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\BaseEloquentRepository')
                ->and($result['path'])->toContain('app/Repositories/EloquentVideoRepository.php');
        });

        test('returns correct info for mysql CRUD repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'mysql',
                'Video',
                'crud',
                'repository'
            );

            expect($result['name'])->toBe('MySQLVideoRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\BaseMySQLRepository')
                ->and($result['path'])->toContain('app/Repositories/MySQLVideoRepository.php');
        });

        test('returns correct info for postgres CRUD repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'postgres',
                'Video',
                'crud',
                'repository'
            );

            expect($result['name'])->toBe('PostgresVideoRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\BasePostgresRepository')
                ->and($result['path'])->toContain('app/Repositories/PostgresVideoRepository.php');
        });
    });

    describe('Bulk repository class info', function () {
        test('returns correct info for eloquent bulk repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'bulk',
                'repository'
            );

            expect($result['name'])->toBe('EloquentVideoBulkRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkEloquentRepository')
                ->and($result['path'])->toContain('app/Repositories/EloquentVideoBulkRepository.php');
        });

        test('returns correct info for mysql bulk repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'mysql',
                'Video',
                'bulk',
                'repository'
            );

            expect($result['name'])->toBe('MySQLVideoBulkRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkMySQLRepository')
                ->and($result['path'])->toContain('app/Repositories/MySQLVideoBulkRepository.php');
        });

        test('returns correct info for postgres bulk repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'postgres',
                'Video',
                'bulk',
                'repository'
            );

            expect($result['name'])->toBe('PostgresVideoBulkRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkPostgresRepository')
                ->and($result['path'])->toContain('app/Repositories/PostgresVideoBulkRepository.php');
        });
    });

    describe('SoftDelete repository class info', function () {
        test('returns correct info for eloquent soft delete repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'soft_delete',
                'repository'
            );

            expect($result['name'])->toBe('EloquentVideoSoftDeleteRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\SoftDeleteEloquentRepository')
                ->and($result['path'])->toContain('app/Repositories/EloquentVideoSoftDeleteRepository.php');
        });

        test('returns correct info for mysql soft delete repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'mysql',
                'Video',
                'soft_delete',
                'repository'
            );

            expect($result['name'])->toBe('MySQLVideoSoftDeleteRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\SoftDeleteMySQLRepository')
                ->and($result['path'])->toContain('app/Repositories/MySQLVideoSoftDeleteRepository.php');
        });

        test('returns correct info for postgres soft delete repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'postgres',
                'Video',
                'soft_delete',
                'repository'
            );

            expect($result['name'])->toBe('PostgresVideoSoftDeleteRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\SoftDeletePostgresRepository')
                ->and($result['path'])->toContain('app/Repositories/PostgresVideoSoftDeleteRepository.php');
        });
    });

    describe('BulkSoftDelete repository class info', function () {
        test('returns correct info for eloquent bulk soft delete repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'bulk_soft_delete',
                'repository'
            );

            expect($result['name'])->toBe('EloquentVideoBulkSoftDeleteRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkSoftDeleteEloquentRepository')
                ->and($result['path'])->toContain('app/Repositories/EloquentVideoBulkSoftDeleteRepository.php');
        });

        test('returns correct info for mysql bulk soft delete repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'mysql',
                'Video',
                'bulk_soft_delete',
                'repository'
            );

            expect($result['name'])->toBe('MySQLVideoBulkSoftDeleteRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkSoftDeleteMySQLRepository')
                ->and($result['path'])->toContain('app/Repositories/MySQLVideoBulkSoftDeleteRepository.php');
        });

        test('returns correct info for postgres bulk soft delete repository', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'postgres',
                'Video',
                'bulk_soft_delete',
                'repository'
            );

            expect($result['name'])->toBe('PostgresVideoBulkSoftDeleteRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkSoftDeletePostgresRepository')
                ->and($result['path'])->toContain('app/Repositories/PostgresVideoBulkSoftDeleteRepository.php');
        });
    });

    describe('Interface class info', function () {
        test('returns correct info for CRUD interface', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'crud',
                'interface'
            );

            expect($result['name'])->toBe('VideoRepositoryInterface')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Contracts\\BaseRepositoryInterface')
                ->and($result['path'])->toContain('app/Contracts/VideoRepositoryInterface.php');
        });

        test('returns correct info for bulk interface', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'bulk',
                'interface'
            );

            expect($result['name'])->toBe('VideoBulkRepositoryInterface')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Contracts\\BulkRepositoryInterface')
                ->and($result['path'])->toContain('app/Contracts/VideoBulkRepositoryInterface.php');
        });

        test('returns correct info for soft delete interface', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'soft_delete',
                'interface'
            );

            expect($result['name'])->toBe('VideoSoftDeleteRepositoryInterface')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Contracts\\SoftDeleteRepositoryInterface')
                ->and($result['path'])->toContain('app/Contracts/VideoSoftDeleteRepositoryInterface.php');
        });

        test('returns correct info for bulk soft delete interface', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'bulk_soft_delete',
                'interface'
            );

            expect($result['name'])->toBe('VideoBulkSoftDeleteRepositoryInterface')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Contracts\\BulkSoftDeleteRepositoryInterface')
                ->and($result['path'])->toContain('app/Contracts/VideoBulkSoftDeleteRepositoryInterface.php');
        });

        test('interface info is driver-agnostic', function () {
            // Interfaces should be the same regardless of driver
            $eloquent = $this->buildClassInfoMethod->invoke($this->command, 'eloquent', 'Video', 'crud', 'interface');
            $mysql = $this->buildClassInfoMethod->invoke($this->command, 'mysql', 'Video', 'crud', 'interface');
            $postgres = $this->buildClassInfoMethod->invoke($this->command, 'postgres', 'Video', 'crud', 'interface');

            expect($eloquent['name'])->toBe($mysql['name'])
                ->and($eloquent['name'])->toBe($postgres['name'])
                ->and($eloquent['import'])->toBe($mysql['import'])
                ->and($eloquent['import'])->toBe($postgres['import']);
        });
    });

    describe('Trait class info', function () {
        test('returns correct info for bulk eloquent trait', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'bulk',
                'trait'
            );

            expect($result['name'])->toBe('BulkEloquentRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkEloquentRepository')
                ->and($result['path'])->toBeNull();
        });

        test('returns correct info for soft delete mysql trait', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'mysql',
                'Video',
                'soft_delete',
                'trait'
            );

            expect($result['name'])->toBe('SoftDeleteMySQLRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\SoftDeleteMySQLRepository')
                ->and($result['path'])->toBeNull();
        });

        test('returns correct info for bulk soft delete postgres trait', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'postgres',
                'Video',
                'bulk_soft_delete',
                'trait'
            );

            expect($result['name'])->toBe('BulkSoftDeletePostgresRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\Traits\\BulkSoftDeletePostgresRepository')
                ->and($result['path'])->toBeNull();
        });

        test('trait info ignores model parameter', function () {
            // Model name should not appear in trait info
            $result1 = $this->buildClassInfoMethod->invoke($this->command, 'eloquent', 'Video', 'bulk', 'trait');
            $result2 = $this->buildClassInfoMethod->invoke($this->command, 'eloquent', 'Post', 'bulk', 'trait');

            expect($result1['name'])->toBe($result2['name'])
                ->and($result1['import'])->toBe($result2['import']);
        });
    });

    describe('Base class info', function () {
        test('returns correct info for eloquent base class', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'crud',
                'base'
            );

            expect($result['name'])->toBe('BaseEloquentRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\BaseEloquentRepository')
                ->and($result['path'])->toBeNull();
        });

        test('returns correct info for mysql base class', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'mysql',
                'Video',
                'crud',
                'base'
            );

            expect($result['name'])->toBe('BaseMySQLRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\BaseMySQLRepository')
                ->and($result['path'])->toBeNull();
        });

        test('returns correct info for postgres base class', function () {
            $result = $this->buildClassInfoMethod->invoke(
                $this->command,
                'postgres',
                'Video',
                'crud',
                'base'
            );

            expect($result['name'])->toBe('BasePostgresRepository')
                ->and($result['import'])->toBe('LaravelArchitect\\Base\\BasePostgresRepository')
                ->and($result['path'])->toBeNull();
        });

        test('base class info ignores model and operation type parameters', function () {
            // Model and operation type should not affect base class info
            $result1 = $this->buildClassInfoMethod->invoke($this->command, 'eloquent', 'Video', 'crud', 'base');
            $result2 = $this->buildClassInfoMethod->invoke($this->command, 'eloquent', 'Post', 'bulk', 'base');

            expect($result1['name'])->toBe($result2['name'])
                ->and($result1['import'])->toBe($result2['import']);
        });
    });

    describe('Error handling', function () {
        test('throws exception for invalid class type', function () {
            expect(fn() => $this->buildClassInfoMethod->invoke(
                $this->command,
                'eloquent',
                'Video',
                'crud',
                'invalid_type'
            ))->toThrow(\InvalidArgumentException::class, 'Unknown class type: invalid_type');
        });
    });
});
