<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;

class MakeApiRepositoryCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-repository {name}
        {--schema=}
        {--soft-deletes}
        {--no-bulk}
        {--crud-driver=}
        {--bulk-driver=}
        {--soft-delete-driver=}
        {--bulk-soft-delete-driver=}
        {--composite-unique=}';
    protected $description = 'Create repository interface and implementations (eloquent|mysql|postgres|all)';

    /**
     * Package base namespace.
     * All package classes live under this namespace.
     */
    protected const PACKAGE_NAMESPACE = 'LaravelArchitect\\Base';

    /**
     * Common class suffixes.
     */
    protected const SUFFIX_REPOSITORY = 'Repository';
    protected const SUFFIX_INTERFACE = 'RepositoryInterface';

    /**
     * Package subdirectory names.
     * These are subdirectories under PACKAGE_NAMESPACE.
     */
    protected const SUBDIR_TRAITS = 'Traits';
    protected const SUBDIR_CONTRACTS = 'Contracts';

    /**
     * Operation type prefix mapping.
     *
     * Maps internal operation type keys to their StudlyCase prefixes
     * used in class/trait naming. Empty string for 'crud' means no prefix.
     *
     * Examples:
     *   'crud' => '' (EloquentVideoRepository, not EloquentVideoCrudRepository)
     *   'bulk' => 'Bulk' (PostgresVideoBulkRepository)
     *   'soft_delete' => 'SoftDelete' (MySQLVideoSoftDeleteRepository)
     *   'bulk_soft_delete' => 'BulkSoftDelete' (EloquentVideoBulkSoftDeleteRepository)
     */
    protected const OPERATION_TYPE_PREFIX = [
        'crud' => '',
        'bulk' => 'Bulk',
        'soft_delete' => 'SoftDelete',
        'bulk_soft_delete' => 'BulkSoftDelete',
    ];

    /**
     * Driver name mapping.
     *
     * Maps internal driver keys (lowercase) to their StudlyCase names
     * used in class/trait naming.
     */
    protected const DRIVER_NAME = [
        'eloquent' => 'Eloquent',
        'mysql' => 'MySQL',
        'postgres' => 'Postgres',
    ];

    /**
     * Resolve the driver for an operation type.
     * CLI option takes precedence over config.
     *
     * @param string $type One of: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @return string One of: 'eloquent', 'mysql', 'postgres'
     * @throws \InvalidArgumentException If driver is not valid
     */
    protected function resolveDriver(string $type): string
    {
        $optionMap = [
            'crud' => 'crud-driver',
            'bulk' => 'bulk-driver',
            'soft_delete' => 'soft-delete-driver',
            'bulk_soft_delete' => 'bulk-soft-delete-driver',
        ];

        $cliValue = $this->option($optionMap[$type]);

        $driver = $cliValue ?: \LaravelArchitect\Console\Commands\Support\ConfigHelper::driver($type);

        // Validate driver using existing DRIVER_NAME constant (DRY)
        if (!isset(self::DRIVER_NAME[$driver])) {
            $validDrivers = implode(', ', array_keys(self::DRIVER_NAME));
            throw new \InvalidArgumentException(
                "Invalid driver '{$driver}' for {$type}. Must be one of: {$validDrivers}"
            );
        }

        return $driver;
    }

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $softDeletes = $this->option('soft-deletes');
        $noBulk = $this->option('no-bulk');
        $compositeUnique = $this->parseCompositeUnique($this->option('composite-unique'));
        $className = Str::studly($name);
        $tableName = $this->getTableName($name);

        $namespace = $this->getNamespace('repositories');
        $interfaceNamespace = $this->getNamespace('contracts');
        $modelNamespace = $this->getNamespace('models');

        // Always generate CRUD interface
        $this->createInterface($files, $className, $interfaceNamespace);

        // Generate CRUD implementations - supports "all" for multiple drivers
        $this->generateRepositoriesForOperationType($files, 'crud', $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);

        // Generate Bulk unless --no-bulk
        if (!$noBulk) {
            $this->createBulkInterface($files, $className, $interfaceNamespace);
            $this->generateRepositoriesForOperationType($files, 'bulk', $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
        }

        // Generate SoftDelete if --soft-deletes
        if ($softDeletes) {
            $this->createSoftDeleteInterface($files, $className, $interfaceNamespace);
            $this->generateRepositoriesForOperationType($files, 'soft_delete', $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
        }

        // Generate BulkSoftDelete if --soft-deletes AND NOT --no-bulk
        if ($softDeletes && !$noBulk) {
            $this->createBulkSoftDeleteInterface($files, $className, $interfaceNamespace);
            $this->generateRepositoriesForOperationType($files, 'bulk_soft_delete', $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
        }

        $this->outputBindingInstructions($className, $interfaceNamespace, $namespace, $softDeletes, $noBulk);
    }

    /**
     * Generate repository implementations for an operation type.
     * Supports "all" driver option to generate all three driver implementations.
     *
     * @param Filesystem $files
     * @param string $operationType One of: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @param string $className
     * @param string $namespace
     * @param string $interfaceNamespace
     * @param string $modelNamespace
     * @param string $tableName
     */
    protected function generateRepositoriesForOperationType(
        Filesystem $files,
        string $operationType,
        string $className,
        string $namespace,
        string $interfaceNamespace,
        string $modelNamespace,
        string $tableName
    ): void {
        $optionMap = [
            'crud' => 'crud-driver',
            'bulk' => 'bulk-driver',
            'soft_delete' => 'soft-delete-driver',
            'bulk_soft_delete' => 'bulk-soft-delete-driver',
        ];

        $cliValue = $this->option($optionMap[$operationType]);

        // If user specified "all", generate for all drivers
        if ($cliValue === 'all') {
            foreach (array_keys(self::DRIVER_NAME) as $driver) {
                $this->generateSingleRepository($files, $operationType, $driver, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
            }
        } else {
            // Use resolveDriver to get single driver (from CLI or config)
            $driver = $this->resolveDriver($operationType);
            $this->generateSingleRepository($files, $operationType, $driver, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
        }
    }

    /**
     * Generate a single repository implementation for a specific driver and operation type.
     *
     * @param Filesystem $files
     * @param string $operationType One of: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @param string $driver One of: 'eloquent', 'mysql', 'postgres'
     * @param string $className
     * @param string $namespace
     * @param string $interfaceNamespace
     * @param string $modelNamespace
     * @param string $tableName
     */
    protected function generateSingleRepository(
        Filesystem $files,
        string $operationType,
        string $driver,
        string $className,
        string $namespace,
        string $interfaceNamespace,
        string $modelNamespace,
        string $tableName
    ): void {
        // Use buildClassInfo() for all naming - NO hardcoded values
        $repoInfo = $this->buildClassInfo($driver, $className, $operationType, 'repository');
        $interfaceInfo = $this->buildClassInfo($driver, $className, $operationType, 'interface');

        // Get driver-specific properties/constructor/imports
        $driverPlaceholders = $this->getDriverSpecificPlaceholders($driver, $className, $tableName, $modelNamespace, $operationType);

        if ($operationType === 'crud') {
            // CRUD repositories extend base class
            $baseInfo = $this->buildClassInfo($driver, $className, 'crud', 'base');

            $content = $this->processStub('repository', [
                'namespace' => $namespace,
                'interface_namespace' => $interfaceNamespace,
                'model_namespace' => $modelNamespace,
                'class' => $className,
                'table' => $tableName,
                'driver' => self::DRIVER_NAME[$driver],
                'base_class_import' => $baseInfo['import'],
                'base_class' => $baseInfo['name'],
                'properties' => $driverPlaceholders['properties'],
                'constructor' => $driverPlaceholders['constructor'],
            ]);
        } else {
            // Operation-type repositories use traits
            $traitInfo = $this->buildClassInfo($driver, $className, $operationType, 'trait');

            // Determine the stub name based on operation type
            $stubMap = [
                'bulk' => 'repository_bulk',
                'soft_delete' => 'repository_soft_delete',
                'bulk_soft_delete' => 'repository_bulk_soft_delete',
            ];

            $content = $this->processStub($stubMap[$operationType], [
                'namespace' => $namespace,
                'interface_namespace' => $interfaceNamespace,
                'model_namespace' => $modelNamespace,
                'class' => $className,
                'driver' => self::DRIVER_NAME[$driver],
                'trait_import' => $traitInfo['import'],
                'trait' => $traitInfo['name'],
                'properties' => $driverPlaceholders['properties'],
                'constructor' => $driverPlaceholders['constructor'],
                'additional_imports' => $driverPlaceholders['additional_imports'],
            ]);
        }

        $this->writeFile($files, $repoInfo['path'], $content);
    }

    /**
     * Build class name, import, and path information for any generated file type.
     *
     * This method is the SINGLE SOURCE OF TRUTH for all class naming conventions.
     * Do NOT hardcode class names elsewhere - always use this method.
     *
     * Returns an array with:
     * - 'name': The short class name (e.g., 'BaseEloquentRepository')
     * - 'import': Full namespace for use statements, or null if not applicable
     * - 'path': File path for generation, or null if not applicable
     *
     * Import is provided for:
     * - 'repository': Base class (CRUD) or trait (operation-types) to extend/use
     * - 'interface': Base interface from package that generated interface extends
     * - 'trait': Full import path for the trait
     * - 'base': Full import path for the base class
     *
     * Path is provided for classes that are generated as new files:
     * - 'repository': Generated repository implementation files
     * - 'interface': Generated repository interface files
     *
     * Path is null for package classes (traits, base classes) since we don't generate those.
     *
     * @param string $driver One of: 'eloquent', 'mysql', 'postgres'
     * @param string $model The model name (e.g., 'Video') - ignored for 'trait' and 'base' types
     * @param string $operationType One of: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @param string $classType One of: 'repository', 'interface', 'trait', 'base'
     * @return array{name: string, import: string|null, path: string|null}
     *
     * @example Get CRUD repository info:
     *   $info = $this->buildClassInfo('eloquent', 'Video', 'crud', 'repository');
     *   // Returns: [
     *   //     'name' => 'EloquentVideoRepository',
     *   //     'import' => 'LaravelArchitect\Base\BaseEloquentRepository',
     *   //     'path' => 'app/Repositories/EloquentVideoRepository.php',
     *   // ]
     *
     * @example Get bulk interface info:
     *   $info = $this->buildClassInfo('postgres', 'Video', 'bulk', 'interface');
     *   // Returns: [
     *   //     'name' => 'VideoBulkRepositoryInterface',
     *   //     'import' => 'LaravelArchitect\Base\Contracts\BulkRepositoryInterface',
     *   //     'path' => 'app/Contracts/VideoBulkRepositoryInterface.php',
     *   // ]
     *
     * @example Get soft delete trait info:
     *   $info = $this->buildClassInfo('mysql', 'Video', 'soft_delete', 'trait');
     *   // Returns: [
     *   //     'name' => 'SoftDeleteMySQLRepository',
     *   //     'import' => 'LaravelArchitect\Base\Traits\SoftDeleteMySQLRepository',
     *   //     'path' => null,
     *   // ]
     *
     * @example Get base class info:
     *   $info = $this->buildClassInfo('postgres', 'Video', 'crud', 'base');
     *   // Returns: [
     *   //     'name' => 'BasePostgresRepository',
     *   //     'import' => 'LaravelArchitect\Base\BasePostgresRepository',
     *   //     'path' => null,
     *   // ]
     */
    protected function buildClassInfo(string $driver, string $model, string $operationType, string $classType): array
    {
        $driverName = self::DRIVER_NAME[$driver];
        $operationPrefix = self::OPERATION_TYPE_PREFIX[$operationType];
        $basePrefix = $operationType === 'crud' ? 'Base' : $operationPrefix;
        $pkg = self::PACKAGE_NAMESPACE;
        $repo = self::SUFFIX_REPOSITORY;
        $iface = self::SUFFIX_INTERFACE;

        switch ($classType) {
            case 'repository':
                // Pattern: {Driver}{Model}{OperationType}Repository
                // Examples: EloquentVideoRepository, PostgresVideoBulkRepository
                $shortName = $driverName . $model . $operationPrefix . $repo;

                if ($operationType === 'crud') {
                    // CRUD repos extend base class: Base{Driver}Repository
                    $importName = 'Base' . $driverName . $repo;
                    $import = $pkg . '\\' . $importName;
                } else {
                    // Operation-type repos use trait: {OperationType}{Driver}Repository
                    $importName = $operationPrefix . $driverName . $repo;
                    $import = $pkg . '\\' . self::SUBDIR_TRAITS . '\\' . $importName;
                }

                return [
                    'name' => $shortName,
                    'import' => $import,
                    'path' => $this->getPath('repositories') . '/' . $shortName . '.php',
                ];

            case 'interface':
                // Pattern: {Model}{OperationType}RepositoryInterface
                // Examples: VideoRepositoryInterface, VideoBulkRepositoryInterface
                $shortName = $model . $operationPrefix . $iface;
                $baseName = $basePrefix . $iface;
                return [
                    'name' => $shortName,
                    'import' => $pkg . '\\' . self::SUBDIR_CONTRACTS . '\\' . $baseName,
                    'path' => $this->getPath('contracts') . '/' . $shortName . '.php',
                ];

            case 'trait':
                // Pattern: {OperationType}{Driver}Repository
                // Examples: BulkEloquentRepository, SoftDeleteMySQLRepository
                // Note: $model parameter is ignored for traits
                $shortName = $operationPrefix . $driverName . $repo;
                return [
                    'name' => $shortName,
                    'import' => $pkg . '\\' . self::SUBDIR_TRAITS . '\\' . $shortName,
                    'path' => null,
                ];

            case 'base':
                // Pattern: Base{Driver}Repository
                // Examples: BaseEloquentRepository, BaseMySQLRepository
                // Note: $model and $operationType parameters are ignored for base classes
                $shortName = 'Base' . $driverName . $repo;
                return [
                    'name' => $shortName,
                    'import' => $pkg . '\\' . $shortName,
                    'path' => null,
                ];

            default:
                throw new \InvalidArgumentException("Unknown class type: {$classType}");
        }
    }

    /**
     * Create the CRUD repository interface.
     *
     * Uses buildClassInfo() for path consistency with all other generation methods.
     */
    protected function createInterface(Filesystem $files, string $className, string $namespace): void
    {
        // Use buildClassInfo() for path - driver doesn't matter for CRUD interface path
        $interfaceInfo = $this->buildClassInfo('eloquent', $className, 'crud', 'interface');

        $content = $this->processStub('repository_interface', [
            'namespace' => $namespace,
            'class' => $className,
            'base_interface_import' => $interfaceInfo['import'],
        ]);

        $this->writeFile($files, $interfaceInfo['path'], $content);
    }

    protected function createBulkInterface(Filesystem $files, string $className, string $namespace): void
    {
        $driver = $this->resolveDriver('bulk');
        $interfaceInfo = $this->buildClassInfo($driver, $className, 'bulk', 'interface');

        $content = $this->processStub('repository_bulk_interface', [
            'namespace' => $namespace,
            'class' => $className,
            'base_interface_import' => $interfaceInfo['import'],
        ]);

        $this->writeFile($files, $interfaceInfo['path'], $content);
    }

    protected function createSoftDeleteInterface(Filesystem $files, string $className, string $namespace): void
    {
        $driver = $this->resolveDriver('soft_delete');
        $interfaceInfo = $this->buildClassInfo($driver, $className, 'soft_delete', 'interface');

        $content = $this->processStub('repository_soft_delete_interface', [
            'namespace' => $namespace,
            'class' => $className,
            'base_interface_import' => $interfaceInfo['import'],
        ]);

        $this->writeFile($files, $interfaceInfo['path'], $content);
    }

    protected function createBulkSoftDeleteInterface(Filesystem $files, string $className, string $namespace): void
    {
        $driver = $this->resolveDriver('bulk_soft_delete');
        $interfaceInfo = $this->buildClassInfo($driver, $className, 'bulk_soft_delete', 'interface');

        $content = $this->processStub('repository_bulk_soft_delete_interface', [
            'namespace' => $namespace,
            'class' => $className,
            'base_interface_import' => $interfaceInfo['import'],
        ]);

        $this->writeFile($files, $interfaceInfo['path'], $content);
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
        $optionMap = [
            'crud' => 'crud-driver',
            'bulk' => 'bulk-driver',
            'soft_delete' => 'soft-delete-driver',
            'bulk_soft_delete' => 'bulk-soft-delete-driver',
        ];

        $cliValue = $this->option($optionMap[$operationType]);

        if ($cliValue === 'all') {
            return array_keys(self::DRIVER_NAME);
        }

        return [$this->resolveDriver($operationType)];
    }

    protected function outputBindingInstructions(string $className, string $interfaceNamespace, string $namespace, bool $softDeletes, bool $noBulk): void
    {
        $this->info("Repositories created for {$className}");
        $this->newLine();
        $this->comment("Add these bindings to a service provider:");
        $this->newLine();

        // CRUD bindings
        foreach ($this->getDriversForBindingOutput('crud') as $driver) {
            $prefix = self::DRIVER_NAME[$driver];
            $this->comment("   \$this->app->bind(\\{$interfaceNamespace}\\{$className}RepositoryInterface::class, \\{$namespace}\\{$prefix}{$className}Repository::class);");
        }

        // Bulk bindings
        if (!$noBulk) {
            foreach ($this->getDriversForBindingOutput('bulk') as $driver) {
                $prefix = self::DRIVER_NAME[$driver];
                $this->comment("   \$this->app->bind(\\{$interfaceNamespace}\\{$className}BulkRepositoryInterface::class, \\{$namespace}\\{$prefix}{$className}BulkRepository::class);");
            }
        }

        // SoftDelete bindings
        if ($softDeletes) {
            foreach ($this->getDriversForBindingOutput('soft_delete') as $driver) {
                $prefix = self::DRIVER_NAME[$driver];
                $this->comment("   \$this->app->bind(\\{$interfaceNamespace}\\{$className}SoftDeleteRepositoryInterface::class, \\{$namespace}\\{$prefix}{$className}SoftDeleteRepository::class);");
            }
        }

        // BulkSoftDelete bindings
        if ($softDeletes && !$noBulk) {
            foreach ($this->getDriversForBindingOutput('bulk_soft_delete') as $driver) {
                $prefix = self::DRIVER_NAME[$driver];
                $this->comment("   \$this->app->bind(\\{$interfaceNamespace}\\{$className}BulkSoftDeleteRepositoryInterface::class, \\{$namespace}\\{$prefix}{$className}BulkSoftDeleteRepository::class);");
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
