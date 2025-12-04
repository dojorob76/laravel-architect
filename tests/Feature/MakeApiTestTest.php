<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $testsPath = base_path(config('api_scaffolding.paths.tests'));
    File::ensureDirectoryExists($testsPath);
});

test('creates pest test file', function () {
    Artisan::call('make:api-test', ['name' => 'Video']);

    $this->assertFileWasGenerated('tests', 'VideoTest.php');

    $content = $this->getGeneratedFileContent('tests', 'VideoTest.php');

    expect($content)->toContain('<?php')
        ->and($content)->toContain('use Illuminate\Foundation\Testing\RefreshDatabase');
});

test('generates crud tests', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'VideoTest.php');

    expect($content)->toContain('can list videos')
        ->and($content)->toContain('can create video')
        ->and($content)->toContain('can show single video')
        ->and($content)->toContain('can update video')
        ->and($content)->toContain('can delete video');
});

test('includes tests for index listing', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Article',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'ArticleTest.php');

    expect($content)->toContain("test('can list articles'")
        ->and($content)->toContain("getJson('/api/articles')")
        ->and($content)->toContain('assertStatus');
});

test('includes tests for store create', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Post',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'PostTest.php');

    expect($content)->toContain("test('can create post'")
        ->and($content)->toContain("postJson('/api/posts'")
        ->and($content)->toContain('assertStatus');
});

test('includes tests for show read', function () {
    $schema = json_encode([
        ['name' => 'name', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Product',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'ProductTest.php');

    expect($content)->toContain("test('can show single product'")
        ->and($content)->toContain('getJson("/api/products/');
});

test('includes tests for update patch', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'VideoTest.php');

    expect($content)->toContain("test('can update video'")
        ->and($content)->toContain('patchJson("/api/videos/');
});

test('includes tests for destroy delete', function () {
    $schema = json_encode([
        ['name' => 'name', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Category',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'CategoryTest.php');

    expect($content)->toContain("test('can delete category'")
        ->and($content)->toContain('deleteJson("/api/categories/');
});

test('includes batch operation tests', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'VideoTest.php');

    expect($content)->toContain('can batch show')
        ->and($content)->toContain('can batch create')
        ->and($content)->toContain('can batch update')
        ->and($content)->toContain('can batch delete');
});

test('includes soft delete tests when flag provided', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Video',
        '--schema' => $schema,
        '--soft-deletes' => true
    ]);

    $content = $this->getGeneratedFileContent('tests', 'VideoTest.php');

    expect($content)->toContain('can restore')
        ->and($content)->toContain('can force delete')
        ->and($content)->toContain('assertSoftDeleted');
});

test('includes batch soft delete tests when flag provided', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Post',
        '--schema' => $schema,
        '--soft-deletes' => true
    ]);

    $content = $this->getGeneratedFileContent('tests', 'PostTest.php');

    expect($content)->toContain('can batch restore')
        ->and($content)->toContain('can batch force delete');
});

test('test data generated from schema', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
        ['name' => 'price', 'type' => 'decimal', 'required' => true],
        ['name' => 'is_active', 'type' => 'boolean', 'required' => false],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Product',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'ProductTest.php');

    expect($content)->toContain("'title' =>")
        ->and($content)->toContain("'price' =>")
        ->and($content)->toContain("'is_active' =>");
});

test('validation error tests for required fields', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
        ['name' => 'content', 'type' => 'text', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Article',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'ArticleTest.php');

    expect($content)->toContain('validates required fields')
        ->and($content)->toContain('unprocessable_entity');
});

test('returns 404 tests for non existent resources', function () {
    $schema = json_encode([
        ['name' => 'name', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'VideoTest.php');

    expect($content)->toContain('not found')
        ->and($content)->toContain('assertStatus');
});

test('partial update tests for patch method', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
        ['name' => 'description', 'type' => 'text', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'Post',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'PostTest.php');

    expect($content)->toContain('can partially update');
});

test('uses correct route names', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
    ]);

    Artisan::call('make:api-test', [
        'name' => 'BlogPost',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('tests', 'BlogPostTest.php');

    expect($content)->toContain('/api/blog_posts')
        ->and($content)->toContain('blog_posts');
});
