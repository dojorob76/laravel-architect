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
        '--crud-driver' => 'mysql'
    ]);

    $this->assertFileWasGenerated('repositories', 'MySQLVideoRepository.php');
    $this->assertFileContains('repositories', 'MySQLVideoRepository.php', 'extends BaseMySQLRepository');
    $this->assertFileContains('repositories', 'MySQLVideoRepository.php', "protected string \$table = 'videos'");
});

test('creates postgres repository when specified', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--crud-driver' => 'postgres'
    ]);

    $this->assertFileWasGenerated('repositories', 'PostgresVideoRepository.php');
    $this->assertFileContains('repositories', 'PostgresVideoRepository.php', 'extends BasePostgresRepository');
});

test('creates all repository types when driver is all', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--crud-driver' => 'all'
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

// Note: Tests for buildClassInfo() logic have been moved to ClassMetaBuilderHelperTest.php
// since this functionality is now handled by ClassMetaBuilderHelper::build()

// ============================================================
// Bulk Interface and Repository Tests
// ============================================================

test('creates bulk interface by default', function () {
    Artisan::call('make:api-repository', ['name' => 'Video']);

    $this->assertFileWasGenerated('contracts', 'VideoBulkRepositoryInterface.php');

    $content = $this->getGeneratedFileContent('contracts', 'VideoBulkRepositoryInterface.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('contracts'))
        ->and($content)->toContain('use LaravelArchitect\\Base\\Contracts\\BulkRepositoryInterface;')
        ->and($content)->toContain('interface VideoBulkRepositoryInterface')
        ->and($content)->toContain('extends BulkRepositoryInterface');
});

test('creates bulk repository with eloquent driver by default', function () {
    Artisan::call('make:api-repository', ['name' => 'Video']);

    $this->assertFileWasGenerated('repositories', 'EloquentVideoBulkRepository.php');

    $content = $this->getGeneratedFileContent('repositories', 'EloquentVideoBulkRepository.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('repositories'))
        ->and($content)->toContain('use LaravelArchitect\\Base\\Traits\\BulkEloquentRepository;')
        ->and($content)->toContain('class EloquentVideoBulkRepository')
        ->and($content)->toContain('implements VideoBulkRepositoryInterface')
        ->and($content)->toContain('use BulkEloquentRepository;');
});

test('creates bulk repository with postgres driver when specified', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--bulk-driver' => 'postgres'
    ]);

    $this->assertFileWasGenerated('repositories', 'PostgresVideoBulkRepository.php');

    $content = $this->getGeneratedFileContent('repositories', 'PostgresVideoBulkRepository.php');

    expect($content)->toContain('use LaravelArchitect\\Base\\Traits\\BulkPostgresRepository;')
        ->and($content)->toContain('class PostgresVideoBulkRepository')
        ->and($content)->toContain('use BulkPostgresRepository;')
        ->and($content)->toContain("protected string \$table = 'videos'");
});

test('does not create bulk interface or repository when --no-bulk flag is used', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--no-bulk' => true
    ]);

    // CRUD should still be generated
    $this->assertFileWasGenerated('contracts', 'VideoRepositoryInterface.php');
    $this->assertFileWasGenerated('repositories', 'EloquentVideoRepository.php');

    // Bulk should NOT be generated
    $bulkInterfacePath = ConfigHelper::basePath('contracts') . '/VideoBulkRepositoryInterface.php';
    $bulkRepoPath = ConfigHelper::basePath('repositories') . '/EloquentVideoBulkRepository.php';

    expect(file_exists($bulkInterfacePath))->toBeFalse()
        ->and(file_exists($bulkRepoPath))->toBeFalse();
});

// ============================================================
// Soft Delete Interface and Repository Tests
// ============================================================

test('creates soft delete interface when --soft-deletes flag is used', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--soft-deletes' => true
    ]);

    $this->assertFileWasGenerated('contracts', 'VideoSoftDeleteRepositoryInterface.php');

    $content = $this->getGeneratedFileContent('contracts', 'VideoSoftDeleteRepositoryInterface.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('contracts'))
        ->and($content)->toContain('use LaravelArchitect\\Base\\Contracts\\SoftDeleteRepositoryInterface;')
        ->and($content)->toContain('interface VideoSoftDeleteRepositoryInterface')
        ->and($content)->toContain('extends SoftDeleteRepositoryInterface');
});

test('creates soft delete repository with eloquent driver by default', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--soft-deletes' => true
    ]);

    $this->assertFileWasGenerated('repositories', 'EloquentVideoSoftDeleteRepository.php');

    $content = $this->getGeneratedFileContent('repositories', 'EloquentVideoSoftDeleteRepository.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('repositories'))
        ->and($content)->toContain('use LaravelArchitect\\Base\\Traits\\SoftDeleteEloquentRepository;')
        ->and($content)->toContain('class EloquentVideoSoftDeleteRepository')
        ->and($content)->toContain('implements VideoSoftDeleteRepositoryInterface')
        ->and($content)->toContain('use SoftDeleteEloquentRepository;');
});

test('creates soft delete repository with mysql driver when specified', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--soft-deletes' => true,
        '--soft-delete-driver' => 'mysql'
    ]);

    $this->assertFileWasGenerated('repositories', 'MySQLVideoSoftDeleteRepository.php');

    $content = $this->getGeneratedFileContent('repositories', 'MySQLVideoSoftDeleteRepository.php');

    expect($content)->toContain('use LaravelArchitect\\Base\\Traits\\SoftDeleteMySQLRepository;')
        ->and($content)->toContain('class MySQLVideoSoftDeleteRepository')
        ->and($content)->toContain('use SoftDeleteMySQLRepository;');
});

test('does not create soft delete interface or repository when --soft-deletes flag is not used', function () {
    Artisan::call('make:api-repository', ['name' => 'Video']);

    $softDeleteInterfacePath = ConfigHelper::basePath('contracts') . '/VideoSoftDeleteRepositoryInterface.php';
    $softDeleteRepoPath = ConfigHelper::basePath('repositories') . '/EloquentVideoSoftDeleteRepository.php';

    expect(file_exists($softDeleteInterfacePath))->toBeFalse()
        ->and(file_exists($softDeleteRepoPath))->toBeFalse();
});

// ============================================================
// Bulk Soft Delete Interface and Repository Tests
// ============================================================

test('creates bulk soft delete interface when --soft-deletes flag is used', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--soft-deletes' => true
    ]);

    $this->assertFileWasGenerated('contracts', 'VideoBulkSoftDeleteRepositoryInterface.php');

    $content = $this->getGeneratedFileContent('contracts', 'VideoBulkSoftDeleteRepositoryInterface.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('contracts'))
        ->and($content)->toContain('use LaravelArchitect\\Base\\Contracts\\BulkSoftDeleteRepositoryInterface;')
        ->and($content)->toContain('interface VideoBulkSoftDeleteRepositoryInterface')
        ->and($content)->toContain('extends BulkSoftDeleteRepositoryInterface');
});

test('creates bulk soft delete repository with eloquent driver by default', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--soft-deletes' => true
    ]);

    $this->assertFileWasGenerated('repositories', 'EloquentVideoBulkSoftDeleteRepository.php');

    $content = $this->getGeneratedFileContent('repositories', 'EloquentVideoBulkSoftDeleteRepository.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('repositories'))
        ->and($content)->toContain('use LaravelArchitect\\Base\\Traits\\BulkSoftDeleteEloquentRepository;')
        ->and($content)->toContain('class EloquentVideoBulkSoftDeleteRepository')
        ->and($content)->toContain('implements VideoBulkSoftDeleteRepositoryInterface')
        ->and($content)->toContain('use BulkSoftDeleteEloquentRepository;');
});

test('creates bulk soft delete repository with postgres driver when specified', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--soft-deletes' => true,
        '--bulk-soft-delete-driver' => 'postgres'
    ]);

    $this->assertFileWasGenerated('repositories', 'PostgresVideoBulkSoftDeleteRepository.php');

    $content = $this->getGeneratedFileContent('repositories', 'PostgresVideoBulkSoftDeleteRepository.php');

    expect($content)->toContain('use LaravelArchitect\\Base\\Traits\\BulkSoftDeletePostgresRepository;')
        ->and($content)->toContain('class PostgresVideoBulkSoftDeleteRepository')
        ->and($content)->toContain('use BulkSoftDeletePostgresRepository;');
});

test('does not create bulk soft delete when --soft-deletes with --no-bulk', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--soft-deletes' => true,
        '--no-bulk' => true
    ]);

    // Soft delete should be generated
    $this->assertFileWasGenerated('contracts', 'VideoSoftDeleteRepositoryInterface.php');
    $this->assertFileWasGenerated('repositories', 'EloquentVideoSoftDeleteRepository.php');

    // Bulk soft delete should NOT be generated
    $bulkSoftDeleteInterfacePath = ConfigHelper::basePath('contracts') . '/VideoBulkSoftDeleteRepositoryInterface.php';
    $bulkSoftDeleteRepoPath = ConfigHelper::basePath('repositories') . '/EloquentVideoBulkSoftDeleteRepository.php';

    expect(file_exists($bulkSoftDeleteInterfacePath))->toBeFalse()
        ->and(file_exists($bulkSoftDeleteRepoPath))->toBeFalse();
});

// ============================================================
// Mixed Driver Tests
// ============================================================

test('supports different drivers for each operation type', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--soft-deletes' => true,
        '--crud-driver' => 'eloquent',
        '--bulk-driver' => 'postgres',
        '--soft-delete-driver' => 'mysql',
        '--bulk-soft-delete-driver' => 'postgres'
    ]);

    // CRUD - Eloquent
    $this->assertFileWasGenerated('repositories', 'EloquentVideoRepository.php');
    $crudContent = $this->getGeneratedFileContent('repositories', 'EloquentVideoRepository.php');
    expect($crudContent)->toContain('extends BaseEloquentRepository');

    // Bulk - Postgres
    $this->assertFileWasGenerated('repositories', 'PostgresVideoBulkRepository.php');
    $bulkContent = $this->getGeneratedFileContent('repositories', 'PostgresVideoBulkRepository.php');
    expect($bulkContent)->toContain('use BulkPostgresRepository;');

    // SoftDelete - MySQL
    $this->assertFileWasGenerated('repositories', 'MySQLVideoSoftDeleteRepository.php');
    $softDeleteContent = $this->getGeneratedFileContent('repositories', 'MySQLVideoSoftDeleteRepository.php');
    expect($softDeleteContent)->toContain('use SoftDeleteMySQLRepository;');

    // BulkSoftDelete - Postgres
    $this->assertFileWasGenerated('repositories', 'PostgresVideoBulkSoftDeleteRepository.php');
    $bulkSoftDeleteContent = $this->getGeneratedFileContent('repositories', 'PostgresVideoBulkSoftDeleteRepository.php');
    expect($bulkSoftDeleteContent)->toContain('use BulkSoftDeletePostgresRepository;');
});

test('creates all bulk repository types when bulk-driver is all', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--bulk-driver' => 'all'
    ]);

    $this->assertFileWasGenerated('repositories', 'EloquentVideoBulkRepository.php');
    $this->assertFileWasGenerated('repositories', 'MySQLVideoBulkRepository.php');
    $this->assertFileWasGenerated('repositories', 'PostgresVideoBulkRepository.php');
});

// ============================================================
// Binding Instructions Output Tests
// ============================================================

test('outputs binding instructions for generated repositories', function () {
    Artisan::call('make:api-repository', [
        'name' => 'Video',
        '--soft-deletes' => true
    ]);

    $output = Artisan::output();

    // Check for CRUD binding
    expect($output)->toContain('VideoRepositoryInterface')
        ->and($output)->toContain('EloquentVideoRepository');

    // Check for Bulk binding
    expect($output)->toContain('VideoBulkRepositoryInterface')
        ->and($output)->toContain('EloquentVideoBulkRepository');

    // Check for SoftDelete binding
    expect($output)->toContain('VideoSoftDeleteRepositoryInterface')
        ->and($output)->toContain('EloquentVideoSoftDeleteRepository');

    // Check for BulkSoftDelete binding
    expect($output)->toContain('VideoBulkSoftDeleteRepositoryInterface')
        ->and($output)->toContain('EloquentVideoBulkSoftDeleteRepository');
});
