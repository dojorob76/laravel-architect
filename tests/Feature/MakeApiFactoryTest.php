<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $factoriesPath = ConfigHelper::basePath('factories');
    File::ensureDirectoryExists($factoriesPath);
});

test('creates factory file', function () {
    Artisan::call('make:api-factory', ['name' => 'Video']);

    $this->assertFileWasGenerated('factories', 'VideoFactory.php');

    $content = $this->getGeneratedFileContent('factories', 'VideoFactory.php');

    expect($content)->toContain('namespace ' . ConfigHelper::namespace('factories'))
        ->and($content)->toContain('class VideoFactory')
        ->and($content)->toContain('extends Factory');
});

test('generates definition method', function () {
    $schema = json_encode([
        ['field' => 'title', 'type' => 'string'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('factories', 'VideoFactory.php');

    expect($content)->toContain('public function definition(): array')
        ->and($content)->toContain('return [');
});

test('factory generates intelligent fake data', function () {
    $schema = json_encode([
        ['field' => 'email', 'type' => 'string'],
        ['field' => 'age', 'type' => 'integer'],
        ['field' => 'is_active', 'type' => 'boolean'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'User',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('factories', 'UserFactory.php');

    expect($content)->toContain("'email' => fake()->safeEmail()")
        ->and($content)->toContain("'age' => fake()->numberBetween(")
        ->and($content)->toContain("'is_active' => fake()->boolean()");
});

test('type appropriate fakers for string fields', function () {
    $schema = json_encode([
        ['field' => 'title', 'type' => 'string'],
        ['field' => 'description', 'type' => 'text'],
        ['field' => 'url', 'type' => 'string'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'Article',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('factories', 'ArticleFactory.php');

    expect($content)->toContain("'title' => fake()->words(3, true)")
        ->and($content)->toContain("'description' => fake()->paragraph()")
        ->and($content)->toContain("'url' => fake()->url()");
});

test('type appropriate fakers for numeric fields', function () {
    $schema = json_encode([
        ['field' => 'count', 'type' => 'integer'],
        ['field' => 'price', 'type' => 'decimal'],
        ['field' => 'rating', 'type' => 'float'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'Product',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('factories', 'ProductFactory.php');

    expect($content)->toContain("'count' => fake()->numberBetween(")
        ->and($content)->toContain("'price' => fake()->randomFloat(")
        ->and($content)->toContain("'rating' => fake()->randomFloat(");
});

test('boolean fields default to fake boolean', function () {
    $schema = json_encode([
        ['field' => 'is_published', 'type' => 'boolean'],
        ['field' => 'is_active', 'type' => 'boolean'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'Post',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('factories', 'PostFactory.php');

    expect($content)->toContain("'is_published' => fake()->boolean()")
        ->and($content)->toContain("'is_active' => fake()->boolean()");
});

test('date fields use fake date', function () {
    $schema = json_encode([
        ['field' => 'birth_date', 'type' => 'date'],
        ['field' => 'published_at', 'type' => 'datetime'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'User',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('factories', 'UserFactory.php');

    expect($content)->toContain("'birth_date' => fake()->date()")
        ->and($content)->toContain("'published_at' => fake()->dateTime()");
});

test('foreign keys use model factory', function () {
    // Create User model first (required for foreign key validation)
    Artisan::call('make:api-model', ['name' => 'User']);

    $schema = json_encode([
        ['parentModel' => 'User'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'Video',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('factories', 'VideoFactory.php');

    expect($content)->toContain("'user_id' => \\" . ConfigHelper::namespace('models') . "\\User::factory()");
});

test('composite unique fields marked as unique', function () {
    $schema = json_encode([
        ['field' => 'email', 'type' => 'string'],
        ['field' => 'category_id', 'type' => 'unsignedBigInteger'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'User',
        '--schema' => $schema,
        '--composite-unique' => '[["email","category_id"]]'
    ]);

    $content = $this->getGeneratedFileContent('factories', 'UserFactory.php');

    expect($content)->toContain('fake()->unique()->');
});

test('comments added warning about composite unique constraints', function () {
    $schema = json_encode([
        ['field' => 'sku', 'type' => 'string'],
        ['field' => 'warehouse_id', 'type' => 'unsignedBigInteger'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'Product',
        '--schema' => $schema,
        '--composite-unique' => '[["sku","warehouse_id"]]'
    ]);

    $content = $this->getGeneratedFileContent('factories', 'ProductFactory.php');

    expect($content)->toContain('// NOTE: Composite unique constraints exist on:')
        ->and($content)->toContain('// - sku, warehouse_id')
        ->and($content)->toContain('// Ensure these field combinations are unique in your tests');
});

test('multiple composite unique constraints listed in comments', function () {
    $schema = json_encode([
        ['field' => 'sku', 'type' => 'string'],
        ['field' => 'barcode', 'type' => 'string'],
        ['field' => 'warehouse_id', 'type' => 'unsignedBigInteger'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'Product',
        '--schema' => $schema,
        '--composite-unique' => '[["sku","warehouse_id"],["barcode","warehouse_id"]]'
    ]);

    $content = $this->getGeneratedFileContent('factories', 'ProductFactory.php');

    expect($content)->toContain('// - sku, warehouse_id')
        ->and($content)->toContain('// - barcode, warehouse_id');
});

test('intelligent fakers for common field names', function () {
    $schema = json_encode([
        ['field' => 'email', 'type' => 'string'],
        ['field' => 'phone', 'type' => 'string'],
        ['field' => 'address', 'type' => 'string'],
        ['field' => 'city', 'type' => 'string'],
        ['field' => 'country', 'type' => 'string'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'Contact',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('factories', 'ContactFactory.php');

    expect($content)->toContain("'email' => fake()->safeEmail()")
        ->and($content)->toContain("'phone' => fake()->phoneNumber()")
        ->and($content)->toContain("'address' => fake()->address()")
        ->and($content)->toContain("'city' => fake()->city()")
        ->and($content)->toContain("'country' => fake()->country()");
});

test('json fields get array structure', function () {
    $schema = json_encode([
        ['field' => 'metadata', 'type' => 'json'],
        ['field' => 'settings', 'type' => 'json'],
    ]);

    Artisan::call('make:api-factory', [
        'name' => 'Product',
        '--schema' => $schema
    ]);

    $content = $this->getGeneratedFileContent('factories', 'ProductFactory.php');

    expect($content)->toContain("'metadata' => ['key' => fake()->word()]")
        ->and($content)->toContain("'settings' => ['key' => fake()->word()]");
});
