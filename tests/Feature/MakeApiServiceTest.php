<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $servicesPath = ConfigHelper::basePath('services');
    File::ensureDirectoryExists($servicesPath);
});

test('creates service file', function () {
    Artisan::call('make:api-service', ['name' => 'Video']);

    $this->assertFileWasGenerated('services', 'VideoService.php');
    $this->assertFileContains('services', 'VideoService.php', 'class VideoService');
    $this->assertFileContains('services', 'VideoService.php', 'extends BaseService');
});

test('service injects repository interface', function () {
    Artisan::call('make:api-service', ['name' => 'Video']);

    $this->assertFileContains('services', 'VideoService.php', 'VideoRepositoryInterface $repository');
    $this->assertFileContains('services', 'VideoService.php', 'protected VideoRepositoryInterface $repository');
});

test('service has all CRUD methods', function () {
    Artisan::call('make:api-service', ['name' => 'Video']);

    $content = $this->getGeneratedFileContent('services', 'VideoService.php');

    expect($content)->toContain('public function getAll')
        ->and($content)->toContain('public function getPaginated')
        ->and($content)->toContain('public function findById')
        ->and($content)->toContain('public function create')
        ->and($content)->toContain('public function update')
        ->and($content)->toContain('public function delete');
});

test('service has batch operation methods', function () {
    Artisan::call('make:api-service', ['name' => 'Video']);

    $content = $this->getGeneratedFileContent('services', 'VideoService.php');

    expect($content)->toContain('public function findMany')
        ->and($content)->toContain('public function createMany')
        ->and($content)->toContain('public function updateMany')
        ->and($content)->toContain('public function deleteMany');
});

test('service has soft delete methods', function () {
    Artisan::call('make:api-service', ['name' => 'Video']);

    $content = $this->getGeneratedFileContent('services', 'VideoService.php');

    expect($content)->toContain('public function restore')
        ->and($content)->toContain('public function forceDelete')
        ->and($content)->toContain('public function restoreMany')
        ->and($content)->toContain('public function forceDeleteMany');
});
