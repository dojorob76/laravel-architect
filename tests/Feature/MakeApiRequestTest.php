<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $requestsPath = ConfigHelper::basePath('requests');
    File::ensureDirectoryExists($requestsPath);
});

test('creates request file with proper namespace', function () {
    Artisan::call('make:api-request', ['name' => 'Video']);

    $this->assertFileWasGenerated('requests', 'VideoRequest.php');

    $content = $this->getGeneratedFileContent('requests', 'VideoRequest.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('requests'))
        ->and($content)->toContain('class VideoRequest')
        ->and($content)->toContain('extends BaseFormRequest');
});

test('generates validation rules from schema', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
        ['name' => 'description', 'type' => 'text'],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('requests', 'VideoRequest.php');

    expect($content)->toContain("'title' => ['required', 'string']")
        ->and($content)->toContain("'description' => ['nullable', 'string']");
});

test('required fields have required rule', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
        ['name' => 'price', 'type' => 'decimal', 'required' => true],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'Product',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('requests', 'ProductRequest.php');

    expect($content)->toContain("'title' => ['required', 'string']")
        ->and($content)->toContain("'price' => ['required', 'numeric']");
});

test('nullable fields have nullable rule', function () {
    $schema = json_encode([
        ['name' => 'description', 'type' => 'text'],
        ['name' => 'notes', 'type' => 'text', 'required' => false],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'Article',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('requests', 'ArticleRequest.php');

    expect($content)->toContain("'description' => ['nullable', 'string']")
        ->and($content)->toContain("'notes' => ['nullable', 'string']");
});

test('patch put makes required fields sometimes instead of required', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
        ['name' => 'content', 'type' => 'text', 'required' => true],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'Post',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('requests', 'PostRequest.php');

    expect($content)->toContain("if (\$this->isMethod('PATCH') || \$this->isMethod('PUT'))")
        ->and($content)->toContain("\$rules['title'] = array_diff(\$rules['title'], ['required']);")
        ->and($content)->toContain("\$rules['title'][] = 'sometimes';")
        ->and($content)->toContain("\$rules['content'] = array_diff(\$rules['content'], ['required']);")
        ->and($content)->toContain("\$rules['content'][] = 'sometimes';");
});

test('email fields have email validation', function () {
    $schema = json_encode([
        ['name' => 'email', 'type' => 'email', 'required' => true],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'User',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('requests', 'UserRequest.php');

    expect($content)->toContain("'email' => ['required', 'email']");
});

test('unique fields have unique validation', function () {
    $schema = json_encode([
        ['name' => 'slug', 'type' => 'string', 'required' => true, 'unique' => true],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'Article',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('requests', 'ArticleRequest.php');

    expect($content)->toContain("'slug' => ['required', 'string', 'unique:");
});

test('foreign keys have exists validation', function () {
    $schema = json_encode([
        ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'required' => true, 'isForeignKey' => true, 'foreignTable' => 'user'],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('requests', 'VideoRequest.php');

    expect($content)->toContain("'user_id' => ['required', 'integer', 'exists:users,id']");
});

test('generates type specific validation', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'count', 'type' => 'integer'],
        ['name' => 'is_active', 'type' => 'boolean'],
        ['name' => 'price', 'type' => 'decimal'],
        ['name' => 'birth_date', 'type' => 'date'],
        ['name' => 'published_at', 'type' => 'datetime'],
        ['name' => 'metadata', 'type' => 'json'],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'Product',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('requests', 'ProductRequest.php');

    expect($content)->toContain("'title' => ['nullable', 'string']")
        ->and($content)->toContain("'count' => ['nullable', 'integer']")
        ->and($content)->toContain("'is_active' => ['nullable', 'boolean']")
        ->and($content)->toContain("'price' => ['nullable', 'numeric']")
        ->and($content)->toContain("'birth_date' => ['nullable', 'date']")
        ->and($content)->toContain("'published_at' => ['nullable', 'date']")
        ->and($content)->toContain("'metadata' => ['nullable', 'array']");
});

test('generates composite unique validation closure', function () {
    $schema = json_encode([
        ['name' => 'email', 'type' => 'string', 'required' => true],
        ['name' => 'category_id', 'type' => 'unsignedBigInteger', 'required' => true],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'User',
        '--schema' => $schema,
        '--composite-unique' => '[["email","category_id"]]'
    ]);

    $content = $this->getGeneratedFileContent('requests', 'UserRequest.php');

    expect($content)->toContain('function ($attribute, $value, $fail)')
        ->and($content)->toContain('composite_unique_0')
        ->and($content)->toContain("\\DB::table('users')")
        ->and($content)->toContain("->where('email', \$this->input('email'))")
        ->and($content)->toContain("->where('category_id', \$this->input('category_id'))");
});

test('composite unique validation handles multiple constraints', function () {
    $schema = json_encode([
        ['name' => 'sku', 'type' => 'string', 'required' => true],
        ['name' => 'barcode', 'type' => 'string', 'required' => true],
        ['name' => 'warehouse_id', 'type' => 'unsignedBigInteger', 'required' => true],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'Product',
        '--schema' => $schema,
        '--composite-unique' => '[["sku","warehouse_id"],["barcode","warehouse_id"]]'
    ]);

    $content = $this->getGeneratedFileContent('requests', 'ProductRequest.php');

    expect($content)->toContain('composite_unique_0')
        ->and($content)->toContain('composite_unique_1')
        ->and($content)->toContain("->where('sku', \$this->input('sku'))")
        ->and($content)->toContain("->where('barcode', \$this->input('barcode'))");
});

test('composite unique validation excludes current record on update', function () {
    $schema = json_encode([
        ['name' => 'email', 'type' => 'string', 'required' => true],
        ['name' => 'category_id', 'type' => 'unsignedBigInteger', 'required' => true],
    ]);

    Artisan::call('make:api-request', [
        'name' => 'User',
        '--schema' => $schema,
        '--composite-unique' => '[["email","category_id"]]'
    ]);

    $content = $this->getGeneratedFileContent('requests', 'UserRequest.php');

    expect($content)->toContain("if (\$this->route('user'))")
        ->and($content)->toContain("->where('id', '!=', \$this->route('user')->id ?? \$this->route('user'))");
});
