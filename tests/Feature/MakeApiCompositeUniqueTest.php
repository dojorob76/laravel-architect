<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $migrationsPath = base_path(config('api_scaffolding.paths.migrations'));
    File::ensureDirectoryExists($migrationsPath);
});

test('creates single composite unique constraint', function () {
    // First create the Tenant model and migration
    Artisan::call('make:api-model', [
        'name' => 'Tenant',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Tenant',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    $schema = json_encode([
        ['name' => 'email', 'type' => 'string', 'required' => true],
        ['name' => 'tenant_id', 'type' => 'unsignedBigInteger', 'required' => true],
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'User',
        '--schema' => $schema,
        '--composite-unique' => '[["email","tenant_id"]]'
    ]);

    $content = $this->getMigrationContent('users');

    expect($content)->toContain("\$table->unique(['email', 'tenant_id'], 'users_composite_unique')");
});

test('creates multiple composite unique constraints with suffixes', function () {
    // First create the Warehouse model and migration
    Artisan::call('make:api-model', [
        'name' => 'Warehouse',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Warehouse',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    // Create the Supplier model and migration
    Artisan::call('make:api-model', [
        'name' => 'Supplier',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Supplier',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    $schema = json_encode([
        ['name' => 'sku', 'type' => 'string'],
        ['name' => 'warehouse_id', 'type' => 'unsignedBigInteger'],
        ['name' => 'barcode', 'type' => 'string'],
        ['name' => 'supplier_id', 'type' => 'unsignedBigInteger'],
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Product',
        '--schema' => $schema,
        '--composite-unique' => '[["sku","warehouse_id"],["barcode","supplier_id"]]'
    ]);

    $content = $this->getMigrationContent('products');

    expect($content)->toContain("\$table->unique(['sku', 'warehouse_id'], 'products_composite_unique')")
        ->and($content)->toContain("\$table->unique(['barcode', 'supplier_id'], 'products_composite_unique_a')");
});

test('creates multi-composite unique for 3+ columns', function () {
    // First create the Product model and migration
    Artisan::call('make:api-model', [
        'name' => 'Product',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Product',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    // Create the Warehouse model and migration
    Artisan::call('make:api-model', [
        'name' => 'Warehouse',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Warehouse',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    $schema = json_encode([
        ['name' => 'product_id', 'type' => 'unsignedBigInteger'],
        ['name' => 'warehouse_id', 'type' => 'unsignedBigInteger'],
        ['name' => 'batch_number', 'type' => 'string'],
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Inventory',
        '--schema' => $schema,
        '--composite-unique' => '[["product_id","warehouse_id","batch_number"]]'
    ]);

    $content = $this->getMigrationContent('inventories');

    expect($content)->toContain("\$table->unique(['product_id', 'warehouse_id', 'batch_number'], 'inventories_multi_composite_unique')");
});

test('pivot table automatically gets composite unique', function () {
    // First create the required models
    Artisan::call('make:api-model', [
        'name' => 'Post',
        '--schema' => json_encode([['name' => 'title', 'type' => 'string']])
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Post',
        '--schema' => json_encode([['name' => 'title', 'type' => 'string']])
    ]);

    Artisan::call('make:api-model', [
        'name' => 'Tag',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'Tag',
        '--schema' => json_encode([['name' => 'name', 'type' => 'string']])
    ]);

    $schema = json_encode([
        ['name' => 'post_id', 'type' => 'unsignedBigInteger'],
        ['name' => 'tag_id', 'type' => 'unsignedBigInteger'],
    ]);

    Artisan::call('make:api-migration', [
        'name' => 'post_tag',
        '--schema' => $schema,
        '--pivot' => 'true'
    ]);

    $content = $this->getMigrationContent('post_tag');

    expect($content)->toContain("\$table->primary(['post_id', 'tag_id'])")
        ->and($content)->toContain("\$table->unique(['post_id', 'tag_id'], 'post_tag_composite_unique')");
});

test('warns about multiple composite uniques', function () {
    $schema = json_encode([
        ['name' => 'field1', 'type' => 'string'],
        ['name' => 'field2', 'type' => 'string'],
        ['name' => 'field3', 'type' => 'string'],
        ['name' => 'field4', 'type' => 'string'],
    ]);

    $this->artisan('make:api-migration', [
        'name' => 'Test',
        '--schema' => $schema,
        '--composite-unique' => '[["field1","field2"],["field3","field4"]]'
    ])
        ->expectsOutput('⚠️  Multiple composite unique keys detected - using suffix notation (_a, _b, etc.)')
        ->assertSuccessful();
});
