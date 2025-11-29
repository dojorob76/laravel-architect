<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Ensure all configured directories exist
    $pathKeys = ['models', 'repositories', 'repository_interfaces', 'services', 'controllers',
                 'requests', 'resources', 'migrations', 'factories', 'tests'];

    foreach ($pathKeys as $key) {
        $path = base_path(config("api_scaffolding.paths.{$key}"));
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

test('pivot bundle does not create service or controller', function () {
    $schema = json_encode([
        ['name' => 'tag_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'tag'],
        ['name' => 'video_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'video'],
    ]);

    Artisan::call('make:api-bundle', [
        'name' => 'tag_video',
        '--schema' => $schema,
        '--pivot' => 'true'
    ]);

    // Model and migration should exist
    $this->assertFileWasGenerated('models', 'TagVideo.php');
    $this->assertMigrationExists('tag_video');

    // But not service, controller, etc
    $servicePath = $this->getGeneratedFilePath('services', 'TagVideoService.php');
    $controllerPath = $this->getGeneratedFilePath('controllers', 'TagVideoController.php');

    expect(File::exists($servicePath))->toBeFalse()
        ->and(File::exists($controllerPath))->toBeFalse();
});
