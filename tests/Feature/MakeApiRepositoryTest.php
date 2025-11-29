<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $repoPath = base_path(config('api_scaffolding.paths.repositories'));
    $interfacePath = base_path(config('api_scaffolding.paths.repository_interfaces'));

    File::ensureDirectoryExists($repoPath);
    File::ensureDirectoryExists($interfacePath);
});

test('creates repository interface', function () {
    Artisan::call('make:api-repository', ['name' => 'Video']);

    $this->assertFileWasGenerated('repository_interfaces', 'VideoRepositoryInterface.php');
    $this->assertFileContains('repository_interfaces', 'VideoRepositoryInterface.php', 'interface VideoRepositoryInterface');
    $this->assertFileContains('repository_interfaces', 'VideoRepositoryInterface.php', 'extends BaseRepositoryInterface');
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
