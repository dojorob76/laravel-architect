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

test('service has soft delete methods when --soft-deletes flag is used', function () {
    Artisan::call('make:api-service', ['name' => 'Video', '--soft-deletes' => true]);

    $content = $this->getGeneratedFileContent('services', 'VideoService.php');

    expect($content)->toContain('public function restore')
        ->and($content)->toContain('public function forceDelete');
});

test('service has bulk soft delete methods when --soft-deletes flag is used', function () {
    Artisan::call('make:api-service', ['name' => 'Video', '--soft-deletes' => true]);

    $content = $this->getGeneratedFileContent('services', 'VideoService.php');

    expect($content)->toContain('public function restoreMany')
        ->and($content)->toContain('public function forceDeleteMany');
});

test('service does not have bulk methods when --no-bulk flag is used', function () {
    Artisan::call('make:api-service', ['name' => 'Video', '--no-bulk' => true]);

    $content = $this->getGeneratedFileContent('services', 'VideoService.php');

    expect($content)->not->toContain('public function findMany')
        ->and($content)->not->toContain('public function createMany')
        ->and($content)->not->toContain('public function updateMany')
        ->and($content)->not->toContain('public function deleteMany')
        ->and($content)->not->toContain('VideoBulkRepositoryInterface');
});

test('service injects correct repositories based on flags', function () {
    Artisan::call('make:api-service', ['name' => 'Video', '--soft-deletes' => true]);

    $content = $this->getGeneratedFileContent('services', 'VideoService.php');

    expect($content)->toContain('VideoRepositoryInterface $repository')
        ->and($content)->toContain('VideoBulkRepositoryInterface $bulkRepository')
        ->and($content)->toContain('VideoSoftDeleteRepositoryInterface $softDeleteRepository')
        ->and($content)->toContain('VideoBulkSoftDeleteRepositoryInterface $bulkSoftDeleteRepository');
});

test('service with --no-bulk --soft-deletes only injects CRUD and soft delete repos', function () {
    Artisan::call('make:api-service', ['name' => 'Video', '--no-bulk' => true, '--soft-deletes' => true]);

    $content = $this->getGeneratedFileContent('services', 'VideoService.php');

    expect($content)->toContain('VideoRepositoryInterface $repository')
        ->and($content)->toContain('VideoSoftDeleteRepositoryInterface $softDeleteRepository')
        ->and($content)->not->toContain('VideoBulkRepositoryInterface')
        ->and($content)->not->toContain('VideoBulkSoftDeleteRepositoryInterface');
});
