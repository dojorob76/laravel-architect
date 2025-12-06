<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;

class MakeApiRepositoryCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-repository {name} {--schema=} {--driver=eloquent} {--composite-unique=}';
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

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $driver = $this->option('driver');
        $compositeUnique = $this->parseCompositeUnique($this->option('composite-unique'));
        $className = Str::studly($name);
        $tableName = $this->getTableName($name);

        $namespace = $this->getNamespace('repositories');
        $interfaceNamespace = $this->getNamespace('contracts');
        $modelNamespace = $this->getNamespace('models');

        $this->createInterface($files, $className, $interfaceNamespace);

        if ($driver === 'all' || $driver === 'eloquent') {
            $this->createEloquentRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace);
        }

        if ($driver === 'all' || $driver === 'mysql') {
            $this->createMySQLRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
        }

        if ($driver === 'all' || $driver === 'postgres') {
            $this->createPostgresRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
        }

        $this->info("Repositories created for {$className}");
        $this->newLine();
        $this->comment("📋 Don't forget to bind the interface in a service provider:");
        $this->comment("   \$this->app->bind(");
        $this->comment("       \\{$interfaceNamespace}\\{$className}RepositoryInterface::class,");
        $this->comment("       \\{$namespace}\\Eloquent{$className}Repository::class");
        $this->comment("   );");
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

    protected function createInterface(Filesystem $files, string $className, string $namespace): void
    {
        $content = $this->processStub('repository_interface', [
            'namespace' => $namespace,
            'class' => $className,
        ]);

        $path = $this->getRepositoryInterfacePath($className);
        $this->writeFile($files, $path, $content);
    }

    protected function createEloquentRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace): void
    {
        $content = $this->processStub('repository_eloquent', [
            'namespace' => $namespace,
            'interface_namespace' => $interfaceNamespace,
            'model_namespace' => $modelNamespace,
            'class' => $className,
        ]);

        $path = $this->getRepositoryPath($className, 'Eloquent');
        $this->writeFile($files, $path, $content);
    }

    protected function createMySQLRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace, string $tableName): void
    {
        $content = $this->processStub('repository_mysql', [
            'namespace' => $namespace,
            'interface_namespace' => $interfaceNamespace,
            'model_namespace' => $modelNamespace,
            'class' => $className,
            'table' => $tableName,
        ]);

        $path = $this->getRepositoryPath($className, 'MySQL');
        $this->writeFile($files, $path, $content);
    }

    protected function createPostgresRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace, string $tableName): void
    {
        $content = $this->processStub('repository_postgres', [
            'namespace' => $namespace,
            'interface_namespace' => $interfaceNamespace,
            'model_namespace' => $modelNamespace,
            'class' => $className,
            'table' => $tableName,
        ]);

        $path = $this->getRepositoryPath($className, 'Postgres');
        $this->writeFile($files, $path, $content);
    }
}
