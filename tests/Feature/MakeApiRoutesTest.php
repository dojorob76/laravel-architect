<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $routesPath = base_path('routes');
    File::ensureDirectoryExists($routesPath);

    // Create a minimal api.php file for testing
    File::put(base_path('routes/api.php'), "<?php\n\n");
});

afterEach(function () {
    // Clean up routes file
    if (File::exists(base_path('routes/api.php'))) {
        File::delete(base_path('routes/api.php'));
    }
});

test('appends routes to api file', function () {
    Artisan::call('make:api-routes', ['name' => 'Video']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::apiResource('videos', VideoController::class)")
        ->and($content)->toContain("Route::post('videos/batch'");
});

test('includes apiResource route', function () {
    Artisan::call('make:api-routes', ['name' => 'Article']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::apiResource('articles', ArticleController::class)");
});

test('includes batch routes', function () {
    Artisan::call('make:api-routes', ['name' => 'Post']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::post('posts/batch/show'")
        ->and($content)->toContain("Route::post('posts/batch'")
        ->and($content)->toContain("Route::patch('posts/batch'")
        ->and($content)->toContain("Route::delete('posts/batch'");
});

test('includes restore and force delete routes when soft deletes enabled', function () {
    Artisan::call('make:api-routes', [
        'name' => 'Video',
        '--soft-deletes' => true
    ]);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::post('videos/{id}/restore'")
        ->and($content)->toContain("Route::delete('videos/{id}/force'");
});

test('includes batch restore and batch force delete when soft deletes enabled', function () {
    Artisan::call('make:api-routes', [
        'name' => 'Post',
        '--soft-deletes' => true
    ]);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::post('posts/batch/restore'")
        ->and($content)->toContain("Route::delete('posts/batch/force'");
});

test('multiple resources can be added', function () {
    Artisan::call('make:api-routes', ['name' => 'Video']);
    Artisan::call('make:api-routes', ['name' => 'Article', '--no-interaction' => true]);
    Artisan::call('make:api-routes', ['name' => 'Post', '--no-interaction' => true]);

    $content = File::get(base_path('routes/api.php'));

    // Check that all three resources are present
    expect($content)->toContain('articles')
        ->and($content)->toContain('posts')
        ->and($content)->toContain('videos')
        ->and($content)->toContain('ArticleController')
        ->and($content)->toContain('PostController')
        ->and($content)->toContain('VideoController');
});

test('proper controller namespacing', function () {
    Artisan::call('make:api-routes', ['name' => 'Product']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain('use ' . ConfigHelper::namespace('controllers') . '\ProductController');
});

test('adds use statement for controller', function () {
    Artisan::call('make:api-routes', ['name' => 'Category']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain('use ' . ConfigHelper::namespace('controllers') . '\CategoryController;');
});

test('uses snake case for multi word models', function () {
    Artisan::call('make:api-routes', ['name' => 'BlogPost']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::apiResource('blog_posts', BlogPostController::class)");
});

test('includes batch show route', function () {
    Artisan::call('make:api-routes', ['name' => 'User']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::post('users/batch/show'")
        ->and($content)->toContain('batchShow');
});

test('includes batch store route', function () {
    Artisan::call('make:api-routes', ['name' => 'Video']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::post('videos/batch'")
        ->and($content)->toContain('batchStore');
});

test('includes batch update route', function () {
    Artisan::call('make:api-routes', ['name' => 'Article']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::patch('articles/batch'")
        ->and($content)->toContain('batchUpdate');
});

test('includes batch destroy route', function () {
    Artisan::call('make:api-routes', ['name' => 'Post']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain("Route::delete('posts/batch'")
        ->and($content)->toContain('batchDestroy');
});

test('route comments are added', function () {
    Artisan::call('make:api-routes', ['name' => 'Category']);

    $content = File::get(base_path('routes/api.php'));

    expect($content)->toContain('// Category routes');
});
