<?php

namespace LaravelArchitect\Console\Commands\Support;

class ClassMetaBuilderHelper
{
    /**
     * Package base namespace.
     */
    public const PACKAGE_NAMESPACE = 'LaravelArchitect\\Base';

    /**
     * Package subdirectory names.
     */
    protected const SUBDIR_TRAITS = 'Traits';
    protected const SUBDIR_CONTRACTS = 'Contracts';

    /**
     * Operation type prefix mapping.
     *
     * Used for:
     * - Generated class names (bulk, soft_delete, bulk_soft_delete only)
     * - Package base class/interface references (all, including crud → 'Base')
     */
    protected const OPERATION_TYPE_PREFIX = [
        'crud' => 'Base',
        'bulk' => 'Bulk',
        'soft_delete' => 'SoftDelete',
        'bulk_soft_delete' => 'BulkSoftDelete',
    ];

    /**
     * Driver name mapping.
     */
    protected const DRIVER_NAME = [
        'eloquent' => 'Eloquent',
        'mysql' => 'MySQL',
        'postgres' => 'Postgres',
    ];

    /**
     * Build all class metadata for a generated file.
     *
     * @param string $fileType One of: 'model', 'service', 'controller', 'request', 'interface', 'repository', 'resource', 'test', 'factory'
     * @param string $model The model name (e.g., 'Video')
     * @param string|null $operationType For repos/interfaces: 'crud', 'bulk', 'soft_delete', 'bulk_soft_delete'
     * @param string|null $driver For repos: 'eloquent', 'mysql', 'postgres'
     * @return array{
     *     class_name: string,
     *     file_name: string,
     *     path: string,
     *     namespace: string|null,
     *     extends: string|null,
     *     extends_import: string|null,
     *     implements: string|null,
     *     implements_import: string|null,
     *     uses_trait: string|null,
     *     trait_import: string|null
     * }
     */
    public static function build(
        string $fileType,
        string $model,
        ?string $operationType = null,
        ?string $driver = null
    ): array {
        return match ($fileType) {
            'model' => self::buildModel($model),
            'service' => self::buildService($model),
            'controller' => self::buildController($model),
            'request' => self::buildRequest($model),
            'resource' => self::buildResource($model),
            'factory' => self::buildFactory($model),
            'test' => self::buildTest($model),
            'interface' => self::buildInterface($model, $operationType ?? 'crud'),
            'repository' => self::buildRepository($model, $operationType ?? 'crud', $driver ?? 'eloquent'),
            default => throw new \InvalidArgumentException("Unknown file type: {$fileType}"),
        };
    }

    /**
     * Build common return values for a class with standard naming.
     *
     * @param string $className The class name
     * @param string $configKey The config key for path/namespace lookup
     */
    protected static function buildCommon(string $className, string $configKey): array
    {
        $fileName = $className . '.php';
        $namespace = ConfigHelper::namespace($configKey);

        return [
            'class_name' => $className,
            'file_name' => $fileName,
            'path' => ConfigHelper::basePath($configKey) . '/' . $fileName,
            'namespace' => $namespace,
            'fqcn' => $namespace ? $namespace . '\\' . $className : $className,
            'extends' => null,
            'extends_import' => null,
            'implements' => null,
            'implements_import' => null,
            'uses_trait' => null,
            'trait_import' => null,
        ];
    }

    /**
     * Build metadata for a repository interface.
     */
    protected static function buildInterface(string $model, string $operationType): array
    {
        $prefix = self::OPERATION_TYPE_PREFIX[$operationType];

        // Generated class name: omit prefix for crud, include for others
        $generatedPrefix = $operationType === 'crud' ? '' : $prefix;
        $className = $model . $generatedPrefix . 'RepositoryInterface';

        // Package base interface: always use prefix
        $baseInterface = $prefix . 'RepositoryInterface';

        return array_merge(self::buildCommon($className, 'contracts'), [
            'extends' => $baseInterface,
            'extends_import' => self::PACKAGE_NAMESPACE . '\\' . self::SUBDIR_CONTRACTS . '\\' . $baseInterface,
        ]);
    }

    /**
     * Build metadata for a repository implementation.
     */
    protected static function buildRepository(string $model, string $operationType, string $driver): array
    {
        $driverName = self::DRIVER_NAME[$driver] ?? throw new \InvalidArgumentException("Unknown driver: {$driver}");
        $prefix = self::OPERATION_TYPE_PREFIX[$operationType];

        // Generated class name: omit prefix for crud, include for others
        $generatedPrefix = $operationType === 'crud' ? '' : $prefix;
        $className = $driverName . $model . $generatedPrefix . 'Repository';

        // Interface name follows same pattern
        $interfaceName = $model . $generatedPrefix . 'RepositoryInterface';
        $interfaceImport = ConfigHelper::namespace('contracts') . '\\' . $interfaceName;

        // Package class name: {Prefix}{Driver}Repository (e.g., BaseEloquentRepository, BulkPostgresRepository)
        $packageClassName = $prefix . $driverName . 'Repository';

        $result = array_merge(self::buildCommon($className, 'repositories'), [
            'implements' => $interfaceName,
            'implements_import' => $interfaceImport,
        ]);

        if ($operationType === 'crud') {
            // CRUD repos extend base class
            $result['extends'] = $packageClassName;
            $result['extends_import'] = self::PACKAGE_NAMESPACE . '\\' . $packageClassName;
        } else {
            // Operation-type repos use traits
            $result['uses_trait'] = $packageClassName;
            $result['trait_import'] = self::PACKAGE_NAMESPACE . '\\' . self::SUBDIR_TRAITS . '\\' . $packageClassName;
        }

        return $result;
    }

    /**
     * Build metadata for a service.
     */
    protected static function buildService(string $model): array
    {
        return array_merge(self::buildCommon($model . 'Service', 'services'), [
            'extends' => 'BaseService',
            'extends_import' => self::PACKAGE_NAMESPACE . '\\BaseService',
        ]);
    }

    /**
     * Build metadata for a model.
     */
    protected static function buildModel(string $model): array
    {
        return array_merge(self::buildCommon($model, 'models'), [
            'extends' => 'BaseModel',
            'extends_import' => self::PACKAGE_NAMESPACE . '\\BaseModel',
        ]);
    }

    /**
     * Build metadata for a controller.
     */
    protected static function buildController(string $model): array
    {
        return array_merge(self::buildCommon($model . 'Controller', 'controllers'), [
            'extends' => 'Controller',
            'extends_import' => ConfigHelper::namespace('base_controller') . '\\Controller',
        ]);
    }

    /**
     * Build metadata for a form request.
     */
    protected static function buildRequest(string $model): array
    {
        return array_merge(self::buildCommon($model . 'Request', 'requests'), [
            'extends' => 'BaseFormRequest',
            'extends_import' => self::PACKAGE_NAMESPACE . '\\BaseFormRequest',
        ]);
    }

    /**
     * Build metadata for a resource.
     */
    protected static function buildResource(string $model): array
    {
        return array_merge(self::buildCommon($model . 'Resource', 'resources'), [
            'extends' => 'JsonResource',
            'extends_import' => 'Illuminate\\Http\\Resources\\Json\\JsonResource',
        ]);
    }

    /**
     * Build metadata for a factory.
     */
    protected static function buildFactory(string $model): array
    {
        return array_merge(self::buildCommon($model . 'Factory', 'factories'), [
            'extends' => 'Factory',
            'extends_import' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
        ]);
    }

    /**
     * Build metadata for a test.
     */
    protected static function buildTest(string $model): array
    {
        return self::buildCommon($model . 'Test', 'tests');
    }

    /**
     * Get the StudlyCase driver name.
     */
    public static function driverName(string $driver): string
    {
        return self::DRIVER_NAME[$driver] ?? throw new \InvalidArgumentException("Unknown driver: {$driver}");
    }

    /**
     * Get the operation type prefix.
     */
    public static function operationPrefix(string $operationType): string
    {
        return self::OPERATION_TYPE_PREFIX[$operationType] ?? throw new \InvalidArgumentException("Unknown operation type: {$operationType}");
    }

    /**
     * Get all valid operation types.
     */
    public static function operationTypes(): array
    {
        return array_keys(self::OPERATION_TYPE_PREFIX);
    }

    /**
     * Get all valid drivers.
     */
    public static function drivers(): array
    {
        return array_keys(self::DRIVER_NAME);
    }
}
