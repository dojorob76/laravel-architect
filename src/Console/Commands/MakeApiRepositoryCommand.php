<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Support\ClassMetaBuilderHelper;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use LaravelArchitect\Console\Commands\Support\NamingConventionHelper;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;

class MakeApiRepositoryCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-repository {name}
        {--soft-deletes}
        {--no-bulk}
        {--crud-driver=}
        {--bulk-driver=}
        {--soft-delete-driver=}
        {--bulk-soft-delete-driver=}';
    protected $description = 'Create repository interface and implementations (eloquent|mysql|postgres|all)';

    /**
     * Maps operation types to their CLI option names.
     */
    protected const OPERATION_TYPE_OPTION = [
        'crud' => 'crud-driver',
        'bulk' => 'bulk-driver',
        'soft_delete' => 'soft-delete-driver',
        'bulk_soft_delete' => 'bulk-soft-delete-driver',
    ];

    /**
     * Resolve the driver for an operation type.
     * CLI option takes precedence over config.
     *
     * @param string $operationType One of: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @return string One of: 'eloquent', 'mysql', 'postgres'
     * @throws \InvalidArgumentException If driver is not valid
     */
    protected function resolveDriver(string $operationType): string
    {
        $cliValue = $this->option(self::OPERATION_TYPE_OPTION[$operationType]);

        // ClassMetaBuilderHelper::buildRepository() validates and throws InvalidArgumentException for invalid drivers
        return $cliValue ?: ConfigHelper::driver($operationType);
    }

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $softDeletes = $this->option('soft-deletes');
        $noBulk = $this->option('no-bulk');
        $className = NamingConventionHelper::modelToClassName($name);
        $tableName = $this->getTableName($name);

        // Always generate CRUD interface and repositories
        $this->createInterface($files, $className, 'crud');
        $this->generateRepositoriesForOperationType($files, 'crud', $className, $tableName);

        // Generate Bulk unless --no-bulk
        if (!$noBulk) {
            $this->createInterface($files, $className, 'bulk');
            $this->generateRepositoriesForOperationType($files, 'bulk', $className, $tableName);
        }

        // Generate SoftDelete if --soft-deletes
        if ($softDeletes) {
            $this->createInterface($files, $className, 'soft_delete');
            $this->generateRepositoriesForOperationType($files, 'soft_delete', $className, $tableName);
        }

        // Generate BulkSoftDelete if --soft-deletes AND NOT --no-bulk
        if ($softDeletes && !$noBulk) {
            $this->createInterface($files, $className, 'bulk_soft_delete');
            $this->generateRepositoriesForOperationType($files, 'bulk_soft_delete', $className, $tableName);
        }

        $this->outputBindingInstructions($className, $softDeletes, $noBulk);
    }

    /**
     * Generate repository implementations for an operation type.
     * Supports "all" driver option to generate all three driver implementations.
     *
     * @param Filesystem $files
     * @param string $operationType One of: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @param string $className
     * @param string $tableName
     */
    protected function generateRepositoriesForOperationType(
        Filesystem $files,
        string $operationType,
        string $className,
        string $tableName
    ): void {
        $cliValue = $this->option(self::OPERATION_TYPE_OPTION[$operationType]);

        // If user specified "all", generate for all drivers
        if ($cliValue === 'all') {
            foreach (ClassMetaBuilderHelper::drivers() as $driver) {
                $this->generateSingleRepository($files, $operationType, $driver, $className, $tableName);
            }
        } else {
            // Use resolveDriver to get single driver (from CLI or config)
            $driver = $this->resolveDriver($operationType);
            $this->generateSingleRepository($files, $operationType, $driver, $className, $tableName);
        }
    }

    /**
     * Generate a single repository implementation for a specific driver and operation type.
     *
     * @param Filesystem $files
     * @param string $operationType One of: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @param string $driver One of: 'eloquent', 'mysql', 'postgres'
     * @param string $className
     * @param string $tableName
     */
    protected function generateSingleRepository(
        Filesystem $files,
        string $operationType,
        string $driver,
        string $className,
        string $tableName
    ): void {
        // Get all metadata from ClassMetaBuilderHelper
        $repoMeta = ClassMetaBuilderHelper::build('repository', $className, $operationType, $driver);
        $interfaceMeta = ClassMetaBuilderHelper::build('interface', $className, $operationType);
        $modelMeta = ClassMetaBuilderHelper::build('model', $className);

        // Get driver-specific properties/constructor/imports
        $driverPlaceholders = $this->getDriverSpecificPlaceholders($driver, $className, $tableName, $modelMeta['namespace'], $operationType);

        // Stub name: 'repository' for crud, 'repository_{type}' for others
        $stubName = $operationType === 'crud'
            ? 'repository'
            : 'repository_' . $operationType;

        // Build common placeholders
        $placeholders = [
            'namespace' => $repoMeta['namespace'],
            'interface_namespace' => $interfaceMeta['namespace'],
            'model_namespace' => $modelMeta['namespace'],
            'class' => $className,
            'driver' => ClassMetaBuilderHelper::driverName($driver),
            'properties' => $driverPlaceholders['properties'],
            'constructor' => $driverPlaceholders['constructor'],
        ];

        if ($operationType === 'crud') {
            // CRUD repositories extend base class
            $placeholders['table'] = $tableName;
            $placeholders['base_class_import'] = $repoMeta['extends_import'];
            $placeholders['base_class'] = $repoMeta['extends'];
        } else {
            // Operation-type repositories use traits
            $placeholders['trait_import'] = $repoMeta['trait_import'];
            $placeholders['trait'] = $repoMeta['uses_trait'];
            $placeholders['additional_imports'] = $driverPlaceholders['additional_imports'];
        }

        $content = $this->processStub($stubName, $placeholders);
        $this->writeFile($files, $repoMeta['path'], $content);
    }

    /**
     * Create a repository interface for the given operation type.
     */
    protected function createInterface(Filesystem $files, string $className, string $operationType = 'crud'): void
    {
        $interfaceMeta = ClassMetaBuilderHelper::build('interface', $className, $operationType);

        // Stub name: 'repository_interface' for crud, 'repository_{type}_interface' for others
        $stubName = $operationType === 'crud'
            ? 'repository_interface'
            : 'repository_' . $operationType . '_interface';

        $content = $this->processStub($stubName, [
            'namespace' => $interfaceMeta['namespace'],
            'class' => $className,
            'base_interface_import' => $interfaceMeta['extends_import'],
        ]);

        $this->writeFile($files, $interfaceMeta['path'], $content);
    }

    /**
     * Get the drivers for binding output for an operation type.
     * Returns an array of drivers. If "all" was specified, returns all three drivers.
     *
     * @param string $operationType One of: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @return array<string> Array of driver keys (e.g., ['eloquent'] or ['eloquent', 'mysql', 'postgres'])
     */
    protected function getDriversForBindingOutput(string $operationType): array
    {
        $cliValue = $this->option(self::OPERATION_TYPE_OPTION[$operationType]);

        if ($cliValue === 'all') {
            return ClassMetaBuilderHelper::drivers();
        }

        return [$this->resolveDriver($operationType)];
    }

    protected function outputBindingInstructions(string $className, bool $softDeletes, bool $noBulk): void
    {
        $this->info("Repositories created for {$className}");
        $this->newLine();
        $this->comment("Add these bindings to a service provider:");
        $this->newLine();

        // Define operation types with their enabled state
        $operationTypes = [
            'crud' => true,
            'bulk' => !$noBulk,
            'soft_delete' => $softDeletes,
            'bulk_soft_delete' => $softDeletes && !$noBulk,
        ];

        foreach ($operationTypes as $operationType => $enabled) {
            if (!$enabled) {
                continue;
            }

            foreach ($this->getDriversForBindingOutput($operationType) as $driver) {
                $interfaceMeta = ClassMetaBuilderHelper::build('interface', $className, $operationType);
                $repoMeta = ClassMetaBuilderHelper::build('repository', $className, $operationType, $driver);

                $this->comment("   \$this->app->bind(\\{$interfaceMeta['fqcn']}::class, \\{$repoMeta['fqcn']}::class);");
            }
        }
    }

    /**
     * Get driver-specific placeholder values for properties, constructor, and additional imports.
     *
     * This method handles the structural differences between Eloquent (uses constructor)
     * and MySQL/Postgres (use properties). It also handles the difference between
     * CRUD Eloquent (extends base class) and operation-type Eloquent (uses trait).
     *
     * @param string $driver One of: 'eloquent', 'mysql', 'postgres'
     * @param string $className The model class name
     * @param string $tableName The database table name
     * @param string $modelNamespace The model namespace from config
     * @param string $operationType One of: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete' (default: 'crud')
     * @return array{properties: string, constructor: string, additional_imports: string}
     */
    protected function getDriverSpecificPlaceholders(
        string $driver,
        string $className,
        string $tableName,
        string $modelNamespace,
        string $operationType = 'crud'
    ): array {
        if ($driver === 'eloquent') {
            if ($operationType === 'crud') {
                // CRUD Eloquent: extends base class, calls parent constructor
                return [
                    'properties' => '',
                    'constructor' => <<<PHP

    /**
     * Create a new repository instance.
     */
    public function __construct({$className} \$model)
    {
        parent::__construct(\$model);
    }
PHP,
                    'additional_imports' => '',
                ];
            }

            // Operation-type Eloquent: uses trait, needs $model property, simple assignment, and Model import
            return [
                'properties' => <<<PHP

    /**
     * The model instance.
     */
    protected Model \$model;
PHP,
                'constructor' => <<<PHP

    /**
     * Create a new repository instance.
     */
    public function __construct({$className} \$model)
    {
        \$this->model = \$model;
    }
PHP,
                'additional_imports' => "\nuse Illuminate\\Database\\Eloquent\\Model;",
            ];
        }

        // MySQL and PostgreSQL use properties, no constructor, no additional imports
        return [
            'properties' => <<<PHP

    /**
     * The database table name.
     */
    protected string \$table = '{$tableName}';

    /**
     * The model class for hydration.
     */
    protected string \$modelClass = '{$modelNamespace}\\{$className}';
PHP,
            'constructor' => '',
            'additional_imports' => '',
        ];
    }
}
