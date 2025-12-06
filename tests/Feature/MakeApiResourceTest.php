<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $resourcesPath = ConfigHelper::basePath('resources');
    File::ensureDirectoryExists($resourcesPath);
});

test('creates resource file', function () {
    Artisan::call('make:api-resource', ['name' => 'Video']);

    $this->assertFileWasGenerated('resources', 'VideoResource.php');

    $content = $this->getGeneratedFileContent('resources', 'VideoResource.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('resources'))
        ->and($content)->toContain('class VideoResource')
        ->and($content)->toContain('extends JsonResource');
});

test('creates collection file', function () {
    Artisan::call('make:api-resource', ['name' => 'Video']);

    $this->assertFileWasGenerated('resources', 'VideoCollection.php');

    $content = $this->getGeneratedFileContent('resources', 'VideoCollection.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('resources'))
        ->and($content)->toContain('class VideoCollection')
        ->and($content)->toContain('extends ResourceCollection');
});

test('resource includes all schema fields', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'description', 'type' => 'text'],
        ['name' => 'duration', 'type' => 'integer'],
    ]);

    Artisan::call('make:api-resource', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('resources', 'VideoResource.php');

    expect($content)->toContain("'title' => \$this->title")
        ->and($content)->toContain("'description' => \$this->description")
        ->and($content)->toContain("'duration' => \$this->duration");
});

test('resource uses whenLoaded for foreign key relationships', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'user'],
    ]);

    Artisan::call('make:api-resource', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('resources', 'VideoResource.php');

    expect($content)->toContain('whenLoaded(\'user\')')
        ->and($content)->toContain('UserResource');
});

test('foreign keys generate nested resource with whenLoaded', function () {
    $schema = json_encode([
        ['name' => 'content', 'type' => 'text'],
        ['name' => 'post_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'post'],
        ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'user'],
    ]);

    Artisan::call('make:api-resource', [
        'name' => 'Comment',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('resources', 'CommentResource.php');

    expect($content)->toContain("'post' => new \\" . ConfigHelper::namespace('resources') . "\\PostResource(\$this->whenLoaded('post'))")
        ->and($content)->toContain("'user' => new \\" . ConfigHelper::namespace('resources') . "\\UserResource(\$this->whenLoaded('user'))");
});

test('collection includes pagination meta', function () {
    Artisan::call('make:api-resource', ['name' => 'Video']);

    $content = $this->getGeneratedFileContent('resources', 'VideoCollection.php');

    expect($content)->toContain('public function toArray')
        ->and($content)->toContain("'data' => \$this->collection")
        ->and($content)->toContain("'meta' =>")
        ->and($content)->toContain("'total' => \$this->total()")
        ->and($content)->toContain("'links' =>");
});

test('timestamps are included in resource', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string'],
    ]);

    Artisan::call('make:api-resource', [
        'name' => 'Article',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('resources', 'ArticleResource.php');

    expect($content)->toContain("'id' => \$this->id")
        ->and($content)->toContain("'created_at' => \$this->created_at")
        ->and($content)->toContain("'updated_at' => \$this->updated_at");
});

test('resource does not include foreign key id fields directly', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'user'],
    ]);

    Artisan::call('make:api-resource', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('resources', 'VideoResource.php');

    // Should have the relationship, not the raw ID
    expect($content)->toContain('whenLoaded(\'user\')')
        ->and($content)->not->toContain("'user_id' => \$this->user_id");
});

test('resource handles multiple relationships', function () {
    $schema = json_encode([
        ['name' => 'name', 'type' => 'string'],
        ['name' => 'category_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'category'],
        ['name' => 'brand_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'brand'],
        ['name' => 'supplier_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'supplier'],
    ]);

    Artisan::call('make:api-resource', [
        'name' => 'Product',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('resources', 'ProductResource.php');

    expect($content)->toContain('CategoryResource')
        ->and($content)->toContain('BrandResource')
        ->and($content)->toContain('SupplierResource')
        ->and($content)->toContain("whenLoaded('category')")
        ->and($content)->toContain("whenLoaded('brand')")
        ->and($content)->toContain("whenLoaded('supplier')");
});
