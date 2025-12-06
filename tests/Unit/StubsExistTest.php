<?php

use Illuminate\Support\Facades\File;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;

/**
 * Tests to verify all required stub files exist in the package.
 */
test('all required stub files exist', function () {
    $stubsPath = __DIR__ . '/../../stubs';

    $requiredStubs = [
        // Core stubs
        'controller.stub',
        'factory.stub',
        'migration.stub',
        'model.stub',
        'repository.stub', // Phase 2.5: Consolidated CRUD stub (replaces repository_eloquent/mysql/postgres)
        'repository_interface.stub',
        'request.stub',
        'resource.stub',
        'resource_collection.stub',
        'service.stub',
        'test.stub',

        // Phase 2 stubs - Interfaces (one per operation type)
        'repository_bulk_interface.stub',
        'repository_soft_delete_interface.stub',
        'repository_bulk_soft_delete_interface.stub',

        // Phase 2 stubs - Consolidated implementations (one per operation type, driver-agnostic)
        'repository_bulk.stub',
        'repository_soft_delete.stub',
        'repository_bulk_soft_delete.stub',
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

test('consolidated bulk repository stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}BulkRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\{{ trait }}')
        ->toContain('{{ additional_imports }}')
        ->toContain('{{ driver }} implementation of {{ class }} bulk repository operations')
        ->toContain('class {{ driver }}{{ class }}BulkRepository implements {{ class }}BulkRepositoryInterface')
        ->toContain('use {{ trait }}')
        ->toContain('{{ properties }}')
        ->toContain('{{ constructor }}');
});

test('consolidated soft delete repository stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_soft_delete.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}SoftDeleteRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\{{ trait }}')
        ->toContain('{{ additional_imports }}')
        ->toContain('{{ driver }} implementation of {{ class }} soft delete repository operations')
        ->toContain('class {{ driver }}{{ class }}SoftDeleteRepository implements {{ class }}SoftDeleteRepositoryInterface')
        ->toContain('use {{ trait }}')
        ->toContain('{{ properties }}')
        ->toContain('{{ constructor }}');
});

test('consolidated bulk soft delete repository stub has correct structure', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_soft_delete.stub';
    $content = File::get($stubPath);

    expect($content)
        ->toContain('namespace {{ namespace }}')
        ->toContain('use {{ model_namespace }}\{{ class }}')
        ->toContain('use {{ interface_namespace }}\{{ class }}BulkSoftDeleteRepositoryInterface')
        ->toContain('use LaravelArchitect\Base\Traits\{{ trait }}')
        ->toContain('{{ additional_imports }}')
        ->toContain('{{ driver }} implementation of {{ class }} bulk soft delete repository operations')
        ->toContain('class {{ driver }}{{ class }}BulkSoftDeleteRepository implements {{ class }}BulkSoftDeleteRepositoryInterface')
        ->toContain('use {{ trait }}')
        ->toContain('{{ properties }}')
        ->toContain('{{ constructor }}');
});

test('all phase 2 stubs use placeholders not hardcoded values', function () {
    $stubsPath = __DIR__ . '/../../stubs';
    $phase2Stubs = [
        'repository_bulk_interface.stub',
        'repository_soft_delete_interface.stub',
        'repository_bulk_soft_delete_interface.stub',
        'repository_bulk.stub',
        'repository_soft_delete.stub',
        'repository_bulk_soft_delete.stub',
    ];

    foreach ($phase2Stubs as $stub) {
        $content = File::get("{$stubsPath}/{$stub}");

        // Should NOT contain hardcoded values
        expect($content)
            ->not->toContain('App\\')
            ->not->toContain('app/')
            ->not->toContain('Video')
            ->not->toContain('videos')
            ->not->toContain('Eloquent')
            ->not->toContain('MySQL')
            ->not->toContain('Postgres');

        // Should contain placeholders
        expect($content)->toContain('{{ namespace }}');
    }
});

/**
 * Tests for placeholder substitution to verify correct output per driver.
 * Uses ConfigHelper to get actual configured namespace values.
 */
test('bulk repository stub generates correct eloquent output', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk.stub';
    $content = File::get($stubPath);

    $repositoriesNamespace = ConfigHelper::namespace('repositories');
    $modelsNamespace = ConfigHelper::namespace('models');
    $contractsNamespace = ConfigHelper::namespace('contracts');

    // Simulate Eloquent placeholder substitution
    $output = str_replace([
        '{{ namespace }}',
        '{{ model_namespace }}',
        '{{ interface_namespace }}',
        '{{ class }}',
        '{{ trait }}',
        '{{ additional_imports }}',
        '{{ driver }}',
        '{{ properties }}',
        '{{ constructor }}',
    ], [
        $repositoriesNamespace,
        $modelsNamespace,
        $contractsNamespace,
        'Video',
        'BulkEloquentRepository',
        'use Illuminate\Database\Eloquent\Model;',
        'Eloquent',
        "\n    /**\n     * The model instance.\n     */\n    protected Model \$model;\n",
        "\n    /**\n     * Create a new repository instance.\n     */\n    public function __construct(Video \$model)\n    {\n        \$this->model = \$model;\n    }\n",
    ], $content);

    expect($output)
        ->toContain("namespace {$repositoriesNamespace};")
        ->toContain("use {$modelsNamespace}\\Video;")
        ->toContain("use {$contractsNamespace}\\VideoBulkRepositoryInterface;")
        ->toContain('use LaravelArchitect\Base\Traits\BulkEloquentRepository;')
        ->toContain('use Illuminate\Database\Eloquent\Model;')
        ->toContain('Eloquent implementation of Video bulk repository operations')
        ->toContain('class EloquentVideoBulkRepository implements VideoBulkRepositoryInterface')
        ->toContain('use BulkEloquentRepository;')
        ->toContain('protected Model $model;')
        ->toContain('public function __construct(Video $model)');
});

test('bulk repository stub generates correct mysql output', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk.stub';
    $content = File::get($stubPath);

    $repositoriesNamespace = ConfigHelper::namespace('repositories');
    $modelsNamespace = ConfigHelper::namespace('models');
    $contractsNamespace = ConfigHelper::namespace('contracts');

    // Simulate MySQL placeholder substitution
    $output = str_replace([
        '{{ namespace }}',
        '{{ model_namespace }}',
        '{{ interface_namespace }}',
        '{{ class }}',
        '{{ trait }}',
        '{{ additional_imports }}',
        '{{ driver }}',
        '{{ properties }}',
        '{{ constructor }}',
    ], [
        $repositoriesNamespace,
        $modelsNamespace,
        $contractsNamespace,
        'Video',
        'BulkMySQLRepository',
        '',
        'MySQL',
        "\n    /**\n     * The table name.\n     */\n    protected string \$table = 'videos';\n\n    /**\n     * The model class for hydration.\n     */\n    protected string \$modelClass = '{$modelsNamespace}\\Video';\n",
        '',
    ], $content);

    expect($output)
        ->toContain("namespace {$repositoriesNamespace};")
        ->toContain("use {$modelsNamespace}\\Video;")
        ->toContain("use {$contractsNamespace}\\VideoBulkRepositoryInterface;")
        ->toContain('use LaravelArchitect\Base\Traits\BulkMySQLRepository;')
        ->toContain('MySQL implementation of Video bulk repository operations')
        ->toContain('class MySQLVideoBulkRepository implements VideoBulkRepositoryInterface')
        ->toContain('use BulkMySQLRepository;')
        ->toContain("protected string \$table = 'videos';")
        ->toContain("protected string \$modelClass = '{$modelsNamespace}\\Video';")
        ->not->toContain('use Illuminate\Database\Eloquent\Model;')
        ->not->toContain('__construct');
});

test('bulk repository stub generates correct postgres output', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk.stub';
    $content = File::get($stubPath);

    $repositoriesNamespace = ConfigHelper::namespace('repositories');
    $modelsNamespace = ConfigHelper::namespace('models');
    $contractsNamespace = ConfigHelper::namespace('contracts');

    // Simulate PostgreSQL placeholder substitution
    $output = str_replace([
        '{{ namespace }}',
        '{{ model_namespace }}',
        '{{ interface_namespace }}',
        '{{ class }}',
        '{{ trait }}',
        '{{ additional_imports }}',
        '{{ driver }}',
        '{{ properties }}',
        '{{ constructor }}',
    ], [
        $repositoriesNamespace,
        $modelsNamespace,
        $contractsNamespace,
        'Video',
        'BulkPostgresRepository',
        '',
        'Postgres',
        "\n    /**\n     * The table name.\n     */\n    protected string \$table = 'videos';\n\n    /**\n     * The model class for hydration.\n     */\n    protected string \$modelClass = '{$modelsNamespace}\\Video';\n",
        '',
    ], $content);

    expect($output)
        ->toContain("namespace {$repositoriesNamespace};")
        ->toContain("use {$modelsNamespace}\\Video;")
        ->toContain("use {$contractsNamespace}\\VideoBulkRepositoryInterface;")
        ->toContain('use LaravelArchitect\Base\Traits\BulkPostgresRepository;')
        ->toContain('Postgres implementation of Video bulk repository operations')
        ->toContain('class PostgresVideoBulkRepository implements VideoBulkRepositoryInterface')
        ->toContain('use BulkPostgresRepository;')
        ->toContain("protected string \$table = 'videos';")
        ->toContain("protected string \$modelClass = '{$modelsNamespace}\\Video';")
        ->not->toContain('use Illuminate\Database\Eloquent\Model;')
        ->not->toContain('__construct');
});

test('soft delete repository stub generates correct eloquent output', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_soft_delete.stub';
    $content = File::get($stubPath);

    $repositoriesNamespace = ConfigHelper::namespace('repositories');
    $modelsNamespace = ConfigHelper::namespace('models');
    $contractsNamespace = ConfigHelper::namespace('contracts');

    // Simulate Eloquent placeholder substitution
    $output = str_replace([
        '{{ namespace }}',
        '{{ model_namespace }}',
        '{{ interface_namespace }}',
        '{{ class }}',
        '{{ trait }}',
        '{{ additional_imports }}',
        '{{ driver }}',
        '{{ properties }}',
        '{{ constructor }}',
    ], [
        $repositoriesNamespace,
        $modelsNamespace,
        $contractsNamespace,
        'Video',
        'SoftDeleteEloquentRepository',
        'use Illuminate\Database\Eloquent\Model;',
        'Eloquent',
        "\n    /**\n     * The model instance.\n     */\n    protected Model \$model;\n",
        "\n    /**\n     * Create a new repository instance.\n     */\n    public function __construct(Video \$model)\n    {\n        \$this->model = \$model;\n    }\n",
    ], $content);

    expect($output)
        ->toContain("namespace {$repositoriesNamespace};")
        ->toContain("use {$contractsNamespace}\\VideoSoftDeleteRepositoryInterface;")
        ->toContain('use LaravelArchitect\Base\Traits\SoftDeleteEloquentRepository;')
        ->toContain('Eloquent implementation of Video soft delete repository operations')
        ->toContain('class EloquentVideoSoftDeleteRepository implements VideoSoftDeleteRepositoryInterface')
        ->toContain('use SoftDeleteEloquentRepository;')
        ->toContain('protected Model $model;')
        ->toContain('public function __construct(Video $model)');
});

test('soft delete repository stub generates correct mysql output', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_soft_delete.stub';
    $content = File::get($stubPath);

    $repositoriesNamespace = ConfigHelper::namespace('repositories');
    $modelsNamespace = ConfigHelper::namespace('models');
    $contractsNamespace = ConfigHelper::namespace('contracts');

    // Simulate MySQL placeholder substitution
    $output = str_replace([
        '{{ namespace }}',
        '{{ model_namespace }}',
        '{{ interface_namespace }}',
        '{{ class }}',
        '{{ trait }}',
        '{{ additional_imports }}',
        '{{ driver }}',
        '{{ properties }}',
        '{{ constructor }}',
    ], [
        $repositoriesNamespace,
        $modelsNamespace,
        $contractsNamespace,
        'Video',
        'SoftDeleteMySQLRepository',
        '',
        'MySQL',
        "\n    /**\n     * The table name.\n     */\n    protected string \$table = 'videos';\n\n    /**\n     * The model class for hydration.\n     */\n    protected string \$modelClass = '{$modelsNamespace}\\Video';\n",
        '',
    ], $content);

    expect($output)
        ->toContain("namespace {$repositoriesNamespace};")
        ->toContain("use {$contractsNamespace}\\VideoSoftDeleteRepositoryInterface;")
        ->toContain('use LaravelArchitect\Base\Traits\SoftDeleteMySQLRepository;')
        ->toContain('MySQL implementation of Video soft delete repository operations')
        ->toContain('class MySQLVideoSoftDeleteRepository implements VideoSoftDeleteRepositoryInterface')
        ->toContain('use SoftDeleteMySQLRepository;')
        ->toContain("protected string \$table = 'videos';");
});

test('bulk soft delete repository stub generates correct eloquent output', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_soft_delete.stub';
    $content = File::get($stubPath);

    $repositoriesNamespace = ConfigHelper::namespace('repositories');
    $modelsNamespace = ConfigHelper::namespace('models');
    $contractsNamespace = ConfigHelper::namespace('contracts');

    // Simulate Eloquent placeholder substitution
    $output = str_replace([
        '{{ namespace }}',
        '{{ model_namespace }}',
        '{{ interface_namespace }}',
        '{{ class }}',
        '{{ trait }}',
        '{{ additional_imports }}',
        '{{ driver }}',
        '{{ properties }}',
        '{{ constructor }}',
    ], [
        $repositoriesNamespace,
        $modelsNamespace,
        $contractsNamespace,
        'Video',
        'BulkSoftDeleteEloquentRepository',
        'use Illuminate\Database\Eloquent\Model;',
        'Eloquent',
        "\n    /**\n     * The model instance.\n     */\n    protected Model \$model;\n",
        "\n    /**\n     * Create a new repository instance.\n     */\n    public function __construct(Video \$model)\n    {\n        \$this->model = \$model;\n    }\n",
    ], $content);

    expect($output)
        ->toContain("namespace {$repositoriesNamespace};")
        ->toContain("use {$contractsNamespace}\\VideoBulkSoftDeleteRepositoryInterface;")
        ->toContain('use LaravelArchitect\Base\Traits\BulkSoftDeleteEloquentRepository;')
        ->toContain('Eloquent implementation of Video bulk soft delete repository operations')
        ->toContain('class EloquentVideoBulkSoftDeleteRepository implements VideoBulkSoftDeleteRepositoryInterface')
        ->toContain('use BulkSoftDeleteEloquentRepository;')
        ->toContain('protected Model $model;')
        ->toContain('public function __construct(Video $model)');
});

test('bulk soft delete repository stub generates correct postgres output', function () {
    $stubPath = __DIR__ . '/../../stubs/repository_bulk_soft_delete.stub';
    $content = File::get($stubPath);

    $repositoriesNamespace = ConfigHelper::namespace('repositories');
    $modelsNamespace = ConfigHelper::namespace('models');
    $contractsNamespace = ConfigHelper::namespace('contracts');

    // Simulate PostgreSQL placeholder substitution
    $output = str_replace([
        '{{ namespace }}',
        '{{ model_namespace }}',
        '{{ interface_namespace }}',
        '{{ class }}',
        '{{ trait }}',
        '{{ additional_imports }}',
        '{{ driver }}',
        '{{ properties }}',
        '{{ constructor }}',
    ], [
        $repositoriesNamespace,
        $modelsNamespace,
        $contractsNamespace,
        'Video',
        'BulkSoftDeletePostgresRepository',
        '',
        'Postgres',
        "\n    /**\n     * The table name.\n     */\n    protected string \$table = 'videos';\n\n    /**\n     * The model class for hydration.\n     */\n    protected string \$modelClass = '{$modelsNamespace}\\Video';\n",
        '',
    ], $content);

    expect($output)
        ->toContain("namespace {$repositoriesNamespace};")
        ->toContain("use {$contractsNamespace}\\VideoBulkSoftDeleteRepositoryInterface;")
        ->toContain('use LaravelArchitect\Base\Traits\BulkSoftDeletePostgresRepository;')
        ->toContain('Postgres implementation of Video bulk soft delete repository operations')
        ->toContain('class PostgresVideoBulkSoftDeleteRepository implements VideoBulkSoftDeleteRepositoryInterface')
        ->toContain('use BulkSoftDeletePostgresRepository;')
        ->toContain("protected string \$table = 'videos';")
        ->toContain("protected string \$modelClass = '{$modelsNamespace}\\Video';");
});