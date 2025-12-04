<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $migrationsPath = ConfigHelper::basePath('migrations');
    File::ensureDirectoryExists($migrationsPath);
});

test('creates migration file', function () {
    Artisan::call('make:api-migration', ['name' => 'Video']);

    $this->assertMigrationExists('videos');
});

test('migration has safety check for existing table', function () {
    Artisan::call('make:api-migration', ['name' => 'Video']);

    $content = $this->getMigrationContent('videos');

    expect($content)->toContain('Schema::hasTable')
        ->and($content)->toContain('if (Schema::hasTable');
});

test('migration has dropIfExists in down method', function () {
    Artisan::call('make:api-migration', ['name' => 'Video']);

    $content = $this->getMigrationContent('videos');

    expect($content)->toContain('Schema::dropIfExists')
        ->and($content)->toContain('public function down()');
});

test('migration creates proper columns from schema', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string', 'nullable' => false, 'required' => true],
        ['name' => 'description', 'type' => 'text', 'nullable' => true],
        ['name' => 'views', 'type' => 'integer', 'default' => 0, 'required' => true],
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getMigrationContent('videos');

    expect($content)->toContain("\$table->string('title')")
        ->and($content)->toContain("\$table->text('description')->nullable()")
        ->and($content)->toContain("\$table->integer('views')->default(0)");
});

test('makes fields nullable when required is not set', function () {
    $schema = json_encode([
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'description', 'type' => 'text', 'required' => true],
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getMigrationContent('videos');

    expect($content)->toContain("\$table->string('title')->nullable()")
        ->and($content)->toContain("\$table->text('description')")
        ->and($content)->not->toContain("\$table->text('description')->nullable()");
});

test('migration creates foreign key constraints', function () {
    // First create the user model/migration
    Artisan::call('make:api-model', ['name' => 'User']);
    Artisan::call('make:api-migration', ['name' => 'User', '--schema' => json_encode([['name' => 'name', 'type' => 'string']])]);

    $schema = json_encode([
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'user_id', 'type' => 'unsignedBigInteger', 'isForeignKey' => true, 'foreignTable' => 'user'],
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getMigrationContent('videos');

    expect($content)->toContain("\$table->foreign('user_id')")
        ->and($content)->toContain("->references('id')->on('users')")
        ->and($content)->toContain("->onDelete('cascade')");
});

test('migration adds timestamps with proper defaults', function () {
    Artisan::call('make:api-migration', ['name' => 'Video']);

    $content = $this->getMigrationContent('videos');

    expect($content)->toContain("->useCurrent()")
        ->and($content)->toContain("->useCurrentOnUpdate()");
});

test('migration adds soft deletes column when requested', function () {
    Artisan::call('make:api-migration', [
        'name' => 'Video',
        '--soft-deletes' => true
    ]);

    $content = $this->getMigrationContent('videos');

    expect($content)->toContain('$table->softDeletes()');
});

test('pivot migration has no timestamps and has composite primary key', function () {
    // Create required models first
    Artisan::call('make:api-model', ['name' => 'Post']);
    Artisan::call('make:api-migration', ['name' => 'Post', '--schema' => json_encode([['name' => 'title', 'type' => 'string']])]);

    Artisan::call('make:api-model', ['name' => 'Tag']);
    Artisan::call('make:api-migration', ['name' => 'Tag', '--schema' => json_encode([['name' => 'name', 'type' => 'string']])]);

    // Use new model-centric pivot syntax
    Artisan::call('make:api-migration', [
        '--pivot' => 'true',
        '--models' => 'Post,Tag',
        '--update-models' => 'false'
    ]);

    $content = $this->getMigrationContent('post_tag');

    expect($content)->not->toContain('$table->timestamps()')
        ->and($content)->not->toContain('$table->id()')
        ->and($content)->toContain("\$table->primary(['post_id', 'tag_id'])");
});

test('creates composite unique constraint', function () {
    // First create the Category model and migration
    Artisan::call('make:api-model', [
        'name' => 'Category',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Category',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    $schema = json_encode([
        ['name' => 'email', 'type' => 'string', 'required' => true],
        ['name' => 'category_id', 'type' => 'unsignedBigInteger', 'required' => true],
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'User',
        '--schema' => $schema,
        '--composite-unique' => '[["email","category_id"]]'
    ]);

    $content = $this->getMigrationContent('users');

    expect($content)->toContain("\$table->unique(['email', 'category_id'], 'users_composite_unique')");
});
