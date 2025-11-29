<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $controllersPath = base_path(config('api_scaffolding.paths.controllers'));
    File::ensureDirectoryExists($controllersPath);
});

test('creates controller file', function () {
    Artisan::call('make:api-controller', ['name' => 'Video']);

    $this->assertFileWasGenerated('controllers', 'VideoController.php');
    $this->assertFileContains('controllers', 'VideoController.php', 'class VideoController');
    $this->assertFileContains('controllers', 'VideoController.php', 'extends Controller');
});

test('controller injects service', function () {
    Artisan::call('make:api-controller', ['name' => 'Video']);

    $this->assertFileContains('controllers', 'VideoController.php', 'VideoService $service');
    $this->assertFileContains('controllers', 'VideoController.php', 'protected VideoService $service');
});

test('controller has all REST methods', function () {
    Artisan::call('make:api-controller', ['name' => 'Video']);

    $content = $this->getGeneratedFileContent('controllers', 'VideoController.php');

    expect($content)->toContain('public function index')
        ->and($content)->toContain('public function store')
        ->and($content)->toContain('public function show')
        ->and($content)->toContain('public function update')
        ->and($content)->toContain('public function destroy');
});

test('controller has batch operation methods', function () {
    Artisan::call('make:api-controller', ['name' => 'Video']);

    $content = $this->getGeneratedFileContent('controllers', 'VideoController.php');

    expect($content)->toContain('public function batchShow')
        ->and($content)->toContain('public function batchStore')
        ->and($content)->toContain('public function batchUpdate')
        ->and($content)->toContain('public function batchDestroy');
});

test('controller uses config for status codes', function () {
    Artisan::call('make:api-controller', ['name' => 'Video']);

    $content = $this->getGeneratedFileContent('controllers', 'VideoController.php');

    expect($content)->toContain("config('api_scaffolding.status_codes")
        ->and($content)->not->toContain('->setStatusCode(200)')
        ->and($content)->not->toContain('->setStatusCode(201)');
});

test('controller uses resource transformers', function () {
    Artisan::call('make:api-controller', ['name' => 'Video']);

    $content = $this->getGeneratedFileContent('controllers', 'VideoController.php');

    expect($content)->toContain('VideoResource')
        ->and($content)->toContain('VideoCollection')
        ->and($content)->toContain('new VideoResource')
        ->and($content)->toContain('new VideoCollection');
});
