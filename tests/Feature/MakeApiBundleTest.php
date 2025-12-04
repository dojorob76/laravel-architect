<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Ensure all configured directories exist
    $pathKeys = ['models', 'repositories', 'repository_interfaces', 'services', 'controllers',
                 'requests', 'resources', 'migrations', 'factories', 'tests'];

    foreach ($pathKeys as $key) {
        $path = ConfigHelper::basePath($key);
        File::ensureDirectoryExists($path);
    }
});

test('scaffolds complete bundle', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-bundle', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    // Check all files were created using config
    $this->assertFileWasGenerated('models', 'Video.php');
    $this->assertFileWasGenerated('repository_interfaces', 'VideoRepositoryInterface.php');
    $this->assertFileWasGenerated('repositories', 'EloquentVideoRepository.php');
    $this->assertFileWasGenerated('services', 'VideoService.php');
    $this->assertFileWasGenerated('controllers', 'VideoController.php');
    $this->assertFileWasGenerated('requests', 'VideoRequest.php');
    $this->assertFileWasGenerated('resources', 'VideoResource.php');
    $this->assertFileWasGenerated('factories', 'VideoFactory.php');
    $this->assertFileWasGenerated('tests', 'VideoTest.php');

    // Check migration was created
    $this->assertMigrationExists('videos');
});

test('bundle with soft deletes includes soft delete features', function () {
    Artisan::call('make:api-bundle', [
        'name' => 'Video',
        '--soft-deletes' => true
    ]);

    // Check model has SoftDeletes
    $this->assertFileContains('models', 'Video.php', 'use SoftDeletes');

    // Check migration has softDeletes
    $content = $this->getMigrationContent('videos');
    expect($content)->toContain('$table->softDeletes()');

    // Check controller has restore methods
    $this->assertFileContains('controllers', 'VideoController.php', 'public function restore');
    $this->assertFileContains('controllers', 'VideoController.php', 'public function forceDelete');
});
