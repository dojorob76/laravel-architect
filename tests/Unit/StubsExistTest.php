<?php

use Illuminate\Support\Facades\File;

/**
 * Tests to verify all required stub files exist in the package.
 */
test('all required stub files exist', function () {
    $stubsPath = __DIR__ . '/../../stubs';

    $requiredStubs = [
        // Existing stubs
        'controller.stub',
        'factory.stub',
        'migration.stub',
        'model.stub',
        'repository_eloquent.stub',
        'repository_interface.stub',
        'repository_mysql.stub',
        'repository_postgres.stub',
        'request.stub',
        'resource.stub',
        'resource_collection.stub',
        'service.stub',
        'test.stub',

        // New Phase 2 stubs - Interfaces
        'repository_bulk_interface.stub',
        'repository_soft_delete_interface.stub',
        'repository_bulk_soft_delete_interface.stub',

        // New Phase 2 stubs - Bulk implementations
        'repository_bulk_eloquent.stub',
        'repository_bulk_mysql.stub',
        'repository_bulk_postgres.stub',

        // New Phase 2 stubs - SoftDelete implementations
        'repository_soft_delete_eloquent.stub',
        'repository_soft_delete_mysql.stub',
        'repository_soft_delete_postgres.stub',

        // New Phase 2 stubs - BulkSoftDelete implementations
        'repository_bulk_soft_delete_eloquent.stub',
        'repository_bulk_soft_delete_mysql.stub',
        'repository_bulk_soft_delete_postgres.stub',
    ];

    foreach ($requiredStubs as $stub) {
        $stubPath = "{$stubsPath}/{$stub}";
        expect(File::exists($stubPath))
            ->toBeTrue("Stub file missing: {$stub}");
    }
});

test('bulk interface stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_interface.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use LaravelArchitect\Base\Contracts\BulkRepositoryInterface')
        ->toContain('interface {{ class }}BulkRepositoryInterface extends BulkRepositoryInterface');
});

test('soft delete interface stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_soft_delete_interface.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use LaravelArchitect\Base\Contracts\SoftDeleteRepositoryInterface')
        ->toContain('interface {{ class }}SoftDeleteRepositoryInterface extends SoftDeleteRepositoryInterface');
});

test('bulk soft delete interface stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_soft_delete_interface.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use LaravelArchitect\Base\Contracts\BulkSoftDeleteRepositoryInterface')
        ->toContain('interface {{ class }}BulkSoftDeleteRepositoryInterface extends BulkSoftDeleteRepositoryInterface');
});

test('bulk eloquent stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_eloquent.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}BulkRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\BulkEloquentRepository')
        ->toContain('use Illuminate\Database\Eloquent\Model')
        ->toContain('class Eloquent{{ class }}BulkRepository implements {{ class }}BulkRepositoryInterface')
        ->toContain('use BulkEloquentRepository')
        ->toContain('protected Model $model')
        ->toContain('public function __construct({{ class }} $model)');
});

test('soft delete eloquent stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_soft_delete_eloquent.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}SoftDeleteRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\SoftDeleteEloquentRepository')
        ->toContain('use Illuminate\Database\Eloquent\Model')
        ->toContain('class Eloquent{{ class }}SoftDeleteRepository implements {{ class }}SoftDeleteRepositoryInterface')
        ->toContain('use SoftDeleteEloquentRepository')
        ->toContain('protected Model $model')
        ->toContain('public function __construct({{ class }} $model)');
});

test('bulk soft delete eloquent stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_soft_delete_eloquent.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}BulkSoftDeleteRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\BulkSoftDeleteEloquentRepository')
        ->toContain('use Illuminate\Database\Eloquent\Model')
        ->toContain('class Eloquent{{ class }}BulkSoftDeleteRepository implements {{ class }}BulkSoftDeleteRepositoryInterface')
        ->toContain('use BulkSoftDeleteEloquentRepository')
        ->toContain('protected Model $model')
        ->toContain('public function __construct({{ class }} $model)');
});

test('bulk mysql stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_mysql.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}BulkRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\BulkMySQLRepository')
        ->toContain('class MySQL{{ class }}BulkRepository implements {{ class }}BulkRepositoryInterface')
        ->toContain('use BulkMySQLRepository')
        ->toContain("protected string \$table = '{{ table }}'")
        ->toContain("protected string \$modelClass = '{{ model_namespace }}\\{{ class }}'");
});

test('soft delete mysql stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_soft_delete_mysql.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}SoftDeleteRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\SoftDeleteMySQLRepository')
        ->toContain('class MySQL{{ class }}SoftDeleteRepository implements {{ class }}SoftDeleteRepositoryInterface')
        ->toContain('use SoftDeleteMySQLRepository')
        ->toContain("protected string \$table = '{{ table }}'")
        ->toContain("protected string \$modelClass = '{{ model_namespace }}\\{{ class }}'");
});

test('bulk soft delete mysql stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_soft_delete_mysql.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}BulkSoftDeleteRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\BulkSoftDeleteMySQLRepository')
        ->toContain('class MySQL{{ class }}BulkSoftDeleteRepository implements {{ class }}BulkSoftDeleteRepositoryInterface')
        ->toContain('use BulkSoftDeleteMySQLRepository')
        ->toContain("protected string \$table = '{{ table }}'")
        ->toContain("protected string \$modelClass = '{{ model_namespace }}\\{{ class }}'");
});

test('bulk postgres stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_postgres.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}BulkRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\BulkPostgresRepository')
        ->toContain('class Postgres{{ class }}BulkRepository implements {{ class }}BulkRepositoryInterface')
        ->toContain('use BulkPostgresRepository')
        ->toContain("protected string \$table = '{{ table }}'")
        ->toContain("protected string \$modelClass = '{{ model_namespace }}\\{{ class }}'");
});

test('soft delete postgres stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_soft_delete_postgres.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}SoftDeleteRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\SoftDeletePostgresRepository')
        ->toContain('class Postgres{{ class }}SoftDeleteRepository implements {{ class }}SoftDeleteRepositoryInterface')
        ->toContain('use SoftDeletePostgresRepository')
        ->toContain("protected string \$table = '{{ table }}'")
        ->toContain("protected string \$modelClass = '{{ model_namespace }}\\{{ class }}'");
});

test('bulk soft delete postgres stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_soft_delete_postgres.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}BulkSoftDeleteRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\BulkSoftDeletePostgresRepository')
        ->toContain('class Postgres{{ class }}BulkSoftDeleteRepository implements {{ class }}BulkSoftDeleteRepositoryInterface')
        ->toContain('use BulkSoftDeletePostgresRepository')
        ->toContain("protected string \$table = '{{ table }}'")
        ->toContain("protected string \$modelClass = '{{ model_namespace }}\\{{ class }}'");
});

test('all stubs use placeholders not hardcoded values', function () {
    $stubsPath = __DIR__ . '/../../stubs';
    $newStubs = [
        'repository_bulk_interface.stub',
        'repository_soft_delete_interface.stub',
        'repository_bulk_soft_delete_interface.stub',
        'repository_bulk_eloquent.stub',
        'repository_bulk_mysql.stub',
        'repository_bulk_postgres.stub',
        'repository_soft_delete_eloquent.stub',
        'repository_soft_delete_mysql.stub',
        'repository_soft_delete_postgres.stub',
        'repository_bulk_soft_delete_eloquent.stub',
        'repository_bulk_soft_delete_mysql.stub',
        'repository_bulk_soft_delete_postgres.stub',
    ];

    foreach ($newStubs as $stub) {
        $content = File::get("{$stubsPath}/{$stub}");

        // Should NOT contain hardcoded values
        expect($content)
            ->not->toContain('App\\')
            ->not->toContain('app/')
            ->not->toContain('Video')
            ->not->toContain('videos');

        // Should contain placeholders
        expect($content)->toContain('{{ namespace }}');
    }
});