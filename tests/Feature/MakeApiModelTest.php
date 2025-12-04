<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $modelsPath = ConfigHelper::basePath('models');
    File::ensureDirectoryExists($modelsPath);
});

test('creates basic model file', function () {
    $modelName = 'Video';

    Artisan::call('make:api-model', ['name' => $modelName]);

    $this->assertFileWasGenerated('models', 'Video.php');

    $content = $this->getGeneratedFileContent('models', 'Video.php');

    expect($content)->toContain('class Video')
        ->and($content)->toContain('extends BaseModel')
        ->and($content)->toContain("protected \$table = 'videos'")
        ->and($content)->toContain('public $timestamps = true');
});

test('creates model with schema and fillable fields', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'required' => true],
        ['name' => 'description', 'type' => 'text', 'nullable' => true],
        ['name' => 'duration', 'type' => 'integer', 'required' => true],
    ]);

    Artisan::call('make:api-model', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $this->assertFileContains('models', 'Video.php', "'title'");
    $this->assertFileContains('models', 'Video.php', "'description'");
    $this->assertFileContains('models', 'Video.php', "'duration'");
    $this->assertFileContains('models', 'Video.php', 'protected $fillable');
});

test('creates model with foreign key relationship', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'user'],
    ]);

    Artisan::call('make:api-model', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $this->assertFileContains('models', 'Video.php', 'public function user()');
    $this->assertFileContains('models', 'Video.php', 'return $this->belongsTo');
});

test('relationship method name is singular table name', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'user'],
    ]);

    Artisan::call('make:api-model', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('models', 'Video.php');

    expect($content)->toContain('public function user()')
        ->and($content)->not->toContain('public function userVideo()')
        ->and($content)->not->toContain('public function userVideos()');
});

test('creates model with soft deletes', function () {
    Artisan::call('make:api-model', [
        'name' => 'Video',
        '--soft-deletes' => true
    ]);

    $this->assertFileContains('models', 'Video.php', 'use SoftDeletes');
    $this->assertFileContains('models', 'Video.php', 'use Illuminate\Database\Eloquent\SoftDeletes');
});

test('creates pivot model without timestamps', function () {
    Artisan::call('make:api-model', [
        'name' => 'tag_video',
        '--pivot' => 'true'
    ]);

    $this->assertFileWasGenerated('models', 'TagVideo.php');
    $this->assertFileContains('models', 'TagVideo.php', 'public $timestamps = false');
    $this->assertFileContains('models', 'TagVideo.php', "protected \$table = 'tag_video'");
});

test('creates model with proper casts', function () {
    $schema = json_encode([
        ['name' => 'is_active', 'type' => 'boolean'],
        ['name' => 'price', 'type' => 'decimal'],
        ['name' => 'published_at', 'type' => 'datetime'],
        ['name' => 'metadata', 'type' => 'json'],
    ]);

    Artisan::call('make:api-model', [
        'name' => 'Product',
        '--schema' => $schema
    ]);

    $this->assertFileContains('models', 'Product.php', "'is_active' => 'boolean'");
    $this->assertFileContains('models', 'Product.php', "'price' => 'decimal:2'");
    $this->assertFileContains('models', 'Product.php', "'published_at' => 'datetime'");
    $this->assertFileContains('models', 'Product.php', "'metadata' => 'array'");
    $this->assertFileContains('models', 'Product.php', 'protected $casts');
});
