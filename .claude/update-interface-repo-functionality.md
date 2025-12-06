# Update Interface & Repository Functionality

## Overview

This document outlines the refactoring required to enable **mixed repository driver support** — allowing users to use different database drivers (Eloquent, MySQL, PostgreSQL) for different operation types (CRUD, Bulk, SoftDelete, BulkSoftDelete) on the same model.

## Problem Statement

Currently, the `--driver` option in `make:api-bundle` is all-or-nothing per resource. Users cannot specify "use Eloquent for standard CRUD but PostgreSQL for bulk operations" because:

1. Generated interfaces only extend `BaseRepositoryInterface` — not the bulk/soft-delete interfaces
2. Generated repositories don't use the bulk/soft-delete traits
3. Services inject a single repository, not separate ones per concern
4. No config or CLI options exist to specify drivers per operation type

## Current Architecture (What Exists)

### Contracts (Already Separated - GOOD)
```
src/Base/Contracts/
├── BaseRepositoryInterface.php        # CRUD operations
├── BulkRepositoryInterface.php        # Bulk operations
├── SoftDeleteRepositoryInterface.php  # Soft delete operations
└── BulkSoftDeleteRepositoryInterface.php  # Bulk soft delete operations
```

### Traits (Already Separated by Driver - GOOD)
```
src/Base/Traits/
├── BulkEloquentRepository.php
├── BulkMySQLRepository.php
├── BulkPostgresRepository.php
├── SoftDeleteEloquentRepository.php
├── SoftDeleteMySQLRepository.php
├── SoftDeletePostgresRepository.php
├── BulkSoftDeleteEloquentRepository.php
├── BulkSoftDeleteMySQLRepository.php
├── BulkSoftDeletePostgresRepository.php
└── PostgresPlaceholderHelper.php
```

### What's Missing
1. **Config options** for per-operation-type drivers
2. **Separate generated interfaces** for each operation type
3. **Separate generated repositories** for each operation type + driver
4. **Updated BaseService** with dual/quad injection
5. **Updated stubs** for all the above
6. **Updated MakeApiRepositoryCommand** to support new options
7. **Updated MakeApiBundleCommand** to pass through new options

---

## Target Architecture

### Config Structure
```php
// config/api_scaffolding.php

// Directory paths - user configurable
'paths' => [
    // ... existing paths ...
    'contracts' => 'app/Contracts',           // Where interfaces are stored
    'repositories' => 'app/Repositories',     // Where repository implementations are stored
],

'namespaces' => [
    // ... existing namespaces ...
    'contracts' => 'App\\Contracts',          // Namespace for interfaces
    'repositories' => 'App\\Repositories',    // Namespace for repository implementations
],

// Driver configuration for each operation type
'drivers' => [
    'crud' => 'eloquent',           // eloquent | mysql | postgres
    'bulk' => 'eloquent',           // eloquent | mysql | postgres
    'soft_delete' => 'eloquent',    // eloquent | mysql | postgres
    'bulk_soft_delete' => 'eloquent', // eloquent | mysql | postgres
],
```

**Note:** The `contracts` and `repositories` paths are fully configurable. Users can set these to any directory structure they prefer (e.g., `app/Repositories/Interfaces` for contracts if they prefer the old convention).

### CLI Override
```bash
# Use config defaults (bulk IS included by default)
php artisan make:api-bundle Video --schema='...' --soft-deletes

# Override specific drivers
php artisan make:api-bundle Video --schema='...' --soft-deletes \
    --crud-driver=eloquent \
    --bulk-driver=postgres \
    --soft-delete-driver=eloquent \
    --bulk-soft-delete-driver=postgres

# Disable bulk operations entirely (only generates CRUD repository)
php artisan make:api-bundle Video --schema='...' --no-bulk

# Disable bulk but enable soft deletes (generates CRUD + SoftDelete, no Bulk/BulkSoftDelete)
php artisan make:api-bundle Video --schema='...' --no-bulk --soft-deletes
```

### Generation Logic

| Flag Combination | Generated Interfaces & Repos |
|------------------|------------------------------|
| (default) | CRUD, Bulk |
| `--soft-deletes` | CRUD, Bulk, SoftDelete, BulkSoftDelete |
| `--no-bulk` | CRUD only |
| `--no-bulk --soft-deletes` | CRUD, SoftDelete |

### Generated Interfaces (per model)
```
app/Contracts/
├── VideoRepositoryInterface.php               # extends BaseRepositoryInterface (always)
├── VideoBulkRepositoryInterface.php           # extends BulkRepositoryInterface (unless --no-bulk)
├── VideoSoftDeleteRepositoryInterface.php     # extends SoftDeleteRepositoryInterface (if --soft-deletes)
└── VideoBulkSoftDeleteRepositoryInterface.php # extends BulkSoftDeleteRepositoryInterface (if --soft-deletes AND NOT --no-bulk)
```

### Generated Repositories (per model, per driver)
```
app/Repositories/
├── EloquentVideoRepository.php                # CRUD via Eloquent (always)
├── PostgresVideoBulkRepository.php            # Bulk via PostgreSQL (unless --no-bulk)
├── EloquentVideoSoftDeleteRepository.php      # SoftDelete via Eloquent (if --soft-deletes)
└── PostgresVideoBulkSoftDeleteRepository.php  # BulkSoftDelete via PostgreSQL (if --soft-deletes AND NOT --no-bulk)
```

### Service Injection Pattern

The service constructor is generated **conditionally** based on command flags:

**Default (no flags):**
```php
class VideoService extends BaseService
{
    public function __construct(
        protected VideoRepositoryInterface $repository,
        protected VideoBulkRepositoryInterface $bulkRepository,
    ) {}
}
```

**With `--soft-deletes`:**
```php
class VideoService extends BaseService
{
    public function __construct(
        protected VideoRepositoryInterface $repository,
        protected VideoBulkRepositoryInterface $bulkRepository,
        protected VideoSoftDeleteRepositoryInterface $softDeleteRepository,
        protected VideoBulkSoftDeleteRepositoryInterface $bulkSoftDeleteRepository,
    ) {}
}
```

**With `--no-bulk`:**
```php
class VideoService extends BaseService
{
    public function __construct(
        protected VideoRepositoryInterface $repository,
    ) {}
}
```

**With `--no-bulk --soft-deletes`:**
```php
class VideoService extends BaseService
{
    public function __construct(
        protected VideoRepositoryInterface $repository,
        protected VideoSoftDeleteRepositoryInterface $softDeleteRepository,
    ) {}
}
```

**Key principle:** Only inject what's generated. No nullable parameters for "optional" repositories — if the flag wasn't used, the repository doesn't exist.

### Service Provider Bindings (generated guidance)
```php
// CRUD
$this->app->bind(VideoRepositoryInterface::class, EloquentVideoRepository::class);

// Bulk
$this->app->bind(VideoBulkRepositoryInterface::class, PostgresVideoBulkRepository::class);

// Soft Delete (if enabled)
$this->app->bind(VideoSoftDeleteRepositoryInterface::class, EloquentVideoSoftDeleteRepository::class);

// Bulk Soft Delete (if enabled)
$this->app->bind(VideoBulkSoftDeleteRepositoryInterface::class, PostgresVideoBulkSoftDeleteRepository::class);
```

---

## Implementation Plan

### Phase 1: Config Updates ✅ COMPLETED

- [x] **1.1** Update `config/api_scaffolding.php`:
  - Renamed `repository_interfaces` to `contracts` in both `paths` and `namespaces`
  - Added `drivers` section with defaults for all operation types
- [x] **1.2** Update `ConfigHelper` to add methods for retrieving driver settings:
  - `ConfigHelper::driver(string $type): string` — get driver for operation type
  - `ConfigHelper::drivers(): array` — get all driver settings

### Phase 2: New Stubs ✅ COMPLETED

**Interface stubs (3 files):**
- [x] **2.1** `stubs/repository_bulk_interface.stub`
- [x] **2.2** `stubs/repository_soft_delete_interface.stub`
- [x] **2.3** `stubs/repository_bulk_soft_delete_interface.stub`

**Consolidated implementation stubs (3 files, NOT 9):**

Instead of creating 9 driver-specific stubs, we created 3 consolidated driver-agnostic stubs:
- [x] **2.4** `stubs/repository_bulk.stub`
- [x] **2.5** `stubs/repository_soft_delete.stub`
- [x] **2.6** `stubs/repository_bulk_soft_delete.stub`

Each stub uses placeholders that the command must populate based on the selected driver.

### Phase 3: Update MakeApiRepositoryCommand

#### 3.1 Update Command Signature

```php
protected $signature = 'make:api-repository {name}
    {--schema=}
    {--soft-deletes}
    {--no-bulk}
    {--crud-driver=}
    {--bulk-driver=}
    {--soft-delete-driver=}
    {--bulk-soft-delete-driver=}
    {--composite-unique=}';
```

#### 3.2 Add Driver Resolution Method

Add to `MakeApiRepositoryCommand`:

```php
/**
 * Resolve the driver for an operation type.
 * CLI option takes precedence over config.
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

    if ($cliValue) {
        return $cliValue;
    }

    return ConfigHelper::driver($type);
}
```

#### 3.3 Add Driver Placeholder Generation Method

Add to `MakeApiRepositoryCommand`. This method returns the **exact literal strings** to substitute for each placeholder:

```php
/**
 * Get placeholder values for a specific driver.
 *
 * @param string $driver One of: 'eloquent', 'mysql', 'postgres'
 * @param string $className The model class name (e.g., 'Video')
 * @param string $tableName The table name (e.g., 'videos')
 * @param string $modelNamespace The model namespace from config
 * @return array Associative array of placeholder => value
 */
protected function getDriverPlaceholders(string $driver, string $className, string $tableName, string $modelNamespace): array
{
    $traitMap = [
        'bulk' => [
            'eloquent' => 'BulkEloquentRepository',
            'mysql' => 'BulkMySQLRepository',
            'postgres' => 'BulkPostgresRepository',
        ],
        'soft_delete' => [
            'eloquent' => 'SoftDeleteEloquentRepository',
            'mysql' => 'SoftDeleteMySQLRepository',
            'postgres' => 'SoftDeletePostgresRepository',
        ],
        'bulk_soft_delete' => [
            'eloquent' => 'BulkSoftDeleteEloquentRepository',
            'mysql' => 'BulkSoftDeleteMySQLRepository',
            'postgres' => 'BulkSoftDeletePostgresRepository',
        ],
    ];

    $driverPrefixMap = [
        'eloquent' => 'Eloquent',
        'mysql' => 'MySQL',
        'postgres' => 'Postgres',
    ];

    if ($driver === 'eloquent') {
        return [
            'driver' => 'Eloquent',
            'additional_imports' => 'use Illuminate\Database\Eloquent\Model;',
            'properties' => <<<'PHP'

    /**
     * The model instance.
     */
    protected Model $model;
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
        ];
    }

    // MySQL and Postgres use the same pattern
    $driverPrefix = $driverPrefixMap[$driver];

    return [
        'driver' => $driverPrefix,
        'additional_imports' => '',
        'properties' => <<<PHP

    /**
     * The table name.
     */
    protected string \$table = '{$tableName}';

    /**
     * The model class for hydration.
     */
    protected string \$modelClass = '{$modelNamespace}\\{$className}';
PHP,
        'constructor' => '',
    ];
}
```

#### 3.4 Add Trait Name Resolution Method

```php
/**
 * Get the trait name for an operation type and driver.
 *
 * @param string $operationType One of: 'bulk', 'soft_delete', 'bulk_soft_delete'
 * @param string $driver One of: 'eloquent', 'mysql', 'postgres'
 * @return string The trait name (e.g., 'BulkEloquentRepository')
 */
protected function getTraitName(string $operationType, string $driver): string
{
    $traitMap = [
        'bulk' => [
            'eloquent' => 'BulkEloquentRepository',
            'mysql' => 'BulkMySQLRepository',
            'postgres' => 'BulkPostgresRepository',
        ],
        'soft_delete' => [
            'eloquent' => 'SoftDeleteEloquentRepository',
            'mysql' => 'SoftDeleteMySQLRepository',
            'postgres' => 'SoftDeletePostgresRepository',
        ],
        'bulk_soft_delete' => [
            'eloquent' => 'BulkSoftDeleteEloquentRepository',
            'mysql' => 'BulkSoftDeleteMySQLRepository',
            'postgres' => 'BulkSoftDeletePostgresRepository',
        ],
    ];

    return $traitMap[$operationType][$driver];
}
```

#### 3.5 Add Repository Generation Methods

**For Bulk Repository:**

```php
protected function createBulkInterface(Filesystem $files, string $className, string $namespace): void
{
    $content = $this->processStub('repository_bulk_interface', [
        'namespace' => $namespace,
        'class' => $className,
    ]);

    $path = $this->getBulkRepositoryInterfacePath($className);
    $this->writeFile($files, $path, $content);
}

protected function createBulkRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace, string $tableName): void
{
    $driver = $this->resolveDriver('bulk');
    $driverPlaceholders = $this->getDriverPlaceholders($driver, $className, $tableName, $modelNamespace);

    $content = $this->processStub('repository_bulk', [
        'namespace' => $namespace,
        'interface_namespace' => $interfaceNamespace,
        'model_namespace' => $modelNamespace,
        'class' => $className,
        'trait' => $this->getTraitName('bulk', $driver),
        'driver' => $driverPlaceholders['driver'],
        'additional_imports' => $driverPlaceholders['additional_imports'],
        'properties' => $driverPlaceholders['properties'],
        'constructor' => $driverPlaceholders['constructor'],
    ]);

    $path = $this->getBulkRepositoryPath($className, $driverPlaceholders['driver']);
    $this->writeFile($files, $path, $content);
}
```

**For Soft Delete Repository:**

```php
protected function createSoftDeleteInterface(Filesystem $files, string $className, string $namespace): void
{
    $content = $this->processStub('repository_soft_delete_interface', [
        'namespace' => $namespace,
        'class' => $className,
    ]);

    $path = $this->getSoftDeleteRepositoryInterfacePath($className);
    $this->writeFile($files, $path, $content);
}

protected function createSoftDeleteRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace, string $tableName): void
{
    $driver = $this->resolveDriver('soft_delete');
    $driverPlaceholders = $this->getDriverPlaceholders($driver, $className, $tableName, $modelNamespace);

    $content = $this->processStub('repository_soft_delete', [
        'namespace' => $namespace,
        'interface_namespace' => $interfaceNamespace,
        'model_namespace' => $modelNamespace,
        'class' => $className,
        'trait' => $this->getTraitName('soft_delete', $driver),
        'driver' => $driverPlaceholders['driver'],
        'additional_imports' => $driverPlaceholders['additional_imports'],
        'properties' => $driverPlaceholders['properties'],
        'constructor' => $driverPlaceholders['constructor'],
    ]);

    $path = $this->getSoftDeleteRepositoryPath($className, $driverPlaceholders['driver']);
    $this->writeFile($files, $path, $content);
}
```

**For Bulk Soft Delete Repository:**

```php
protected function createBulkSoftDeleteInterface(Filesystem $files, string $className, string $namespace): void
{
    $content = $this->processStub('repository_bulk_soft_delete_interface', [
        'namespace' => $namespace,
        'class' => $className,
    ]);

    $path = $this->getBulkSoftDeleteRepositoryInterfacePath($className);
    $this->writeFile($files, $path, $content);
}

protected function createBulkSoftDeleteRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace, string $tableName): void
{
    $driver = $this->resolveDriver('bulk_soft_delete');
    $driverPlaceholders = $this->getDriverPlaceholders($driver, $className, $tableName, $modelNamespace);

    $content = $this->processStub('repository_bulk_soft_delete', [
        'namespace' => $namespace,
        'interface_namespace' => $interfaceNamespace,
        'model_namespace' => $modelNamespace,
        'class' => $className,
        'trait' => $this->getTraitName('bulk_soft_delete', $driver),
        'driver' => $driverPlaceholders['driver'],
        'additional_imports' => $driverPlaceholders['additional_imports'],
        'properties' => $driverPlaceholders['properties'],
        'constructor' => $driverPlaceholders['constructor'],
    ]);

    $path = $this->getBulkSoftDeleteRepositoryPath($className, $driverPlaceholders['driver']);
    $this->writeFile($files, $path, $content);
}
```

#### 3.6 Update handle() Method

```php
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

    // Always generate CRUD interface + implementation
    $this->createInterface($files, $className, $interfaceNamespace);
    $this->createCrudRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);

    // Generate Bulk unless --no-bulk
    if (!$noBulk) {
        $this->createBulkInterface($files, $className, $interfaceNamespace);
        $this->createBulkRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
    }

    // Generate SoftDelete if --soft-deletes
    if ($softDeletes) {
        $this->createSoftDeleteInterface($files, $className, $interfaceNamespace);
        $this->createSoftDeleteRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
    }

    // Generate BulkSoftDelete if --soft-deletes AND NOT --no-bulk
    if ($softDeletes && !$noBulk) {
        $this->createBulkSoftDeleteInterface($files, $className, $interfaceNamespace);
        $this->createBulkSoftDeleteRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
    }

    $this->outputBindingInstructions($className, $interfaceNamespace, $namespace, $softDeletes, $noBulk);
}
```

#### 3.7 Add Path Helper Methods to SchemaParserTrait

```php
protected function getBulkRepositoryInterfacePath(string $className): string
{
    return $this->getPath('contracts') . "/{$className}BulkRepositoryInterface.php";
}

protected function getSoftDeleteRepositoryInterfacePath(string $className): string
{
    return $this->getPath('contracts') . "/{$className}SoftDeleteRepositoryInterface.php";
}

protected function getBulkSoftDeleteRepositoryInterfacePath(string $className): string
{
    return $this->getPath('contracts') . "/{$className}BulkSoftDeleteRepositoryInterface.php";
}

protected function getBulkRepositoryPath(string $className, string $driverPrefix): string
{
    return $this->getPath('repositories') . "/{$driverPrefix}{$className}BulkRepository.php";
}

protected function getSoftDeleteRepositoryPath(string $className, string $driverPrefix): string
{
    return $this->getPath('repositories') . "/{$driverPrefix}{$className}SoftDeleteRepository.php";
}

protected function getBulkSoftDeleteRepositoryPath(string $className, string $driverPrefix): string
{
    return $this->getPath('repositories') . "/{$driverPrefix}{$className}BulkSoftDeleteRepository.php";
}
```

#### 3.8 Add Binding Instructions Output

```php
protected function outputBindingInstructions(string $className, string $interfaceNamespace, string $namespace, bool $softDeletes, bool $noBulk): void
{
    $this->info("Repositories created for {$className}");
    $this->newLine();
    $this->comment("📋 Add these bindings to a service provider:");
    $this->newLine();

    // CRUD binding
    $crudDriver = $this->resolveDriver('crud');
    $crudPrefix = $crudDriver === 'eloquent' ? 'Eloquent' : ($crudDriver === 'mysql' ? 'MySQL' : 'Postgres');
    $this->comment("   \$this->app->bind(\\{$interfaceNamespace}\\{$className}RepositoryInterface::class, \\{$namespace}\\{$crudPrefix}{$className}Repository::class);");

    // Bulk binding
    if (!$noBulk) {
        $bulkDriver = $this->resolveDriver('bulk');
        $bulkPrefix = $bulkDriver === 'eloquent' ? 'Eloquent' : ($bulkDriver === 'mysql' ? 'MySQL' : 'Postgres');
        $this->comment("   \$this->app->bind(\\{$interfaceNamespace}\\{$className}BulkRepositoryInterface::class, \\{$namespace}\\{$bulkPrefix}{$className}BulkRepository::class);");
    }

    // SoftDelete binding
    if ($softDeletes) {
        $softDeleteDriver = $this->resolveDriver('soft_delete');
        $softDeletePrefix = $softDeleteDriver === 'eloquent' ? 'Eloquent' : ($softDeleteDriver === 'mysql' ? 'MySQL' : 'Postgres');
        $this->comment("   \$this->app->bind(\\{$interfaceNamespace}\\{$className}SoftDeleteRepositoryInterface::class, \\{$namespace}\\{$softDeletePrefix}{$className}SoftDeleteRepository::class);");
    }

    // BulkSoftDelete binding
    if ($softDeletes && !$noBulk) {
        $bulkSoftDeleteDriver = $this->resolveDriver('bulk_soft_delete');
        $bulkSoftDeletePrefix = $bulkSoftDeleteDriver === 'eloquent' ? 'Eloquent' : ($bulkSoftDeleteDriver === 'mysql' ? 'MySQL' : 'Postgres');
        $this->comment("   \$this->app->bind(\\{$interfaceNamespace}\\{$className}BulkSoftDeleteRepositoryInterface::class, \\{$namespace}\\{$bulkSoftDeletePrefix}{$className}BulkSoftDeleteRepository::class);");
    }
}
```

#### 3.9 Example Generated Output

**Command:**
```bash
php artisan make:api-repository Video --soft-deletes --bulk-driver=postgres
```

**Generated Files:**

`app/Contracts/VideoBulkRepositoryInterface.php`:
```php
<?php

namespace App\Contracts;

use LaravelArchitect\Base\Contracts\BulkRepositoryInterface;

interface VideoBulkRepositoryInterface extends BulkRepositoryInterface
{
    //
}
```

`app/Repositories/PostgresVideoBulkRepository.php`:
```php
<?php

namespace App\Repositories;

use App\Models\Video;
use App\Contracts\VideoBulkRepositoryInterface;
use LaravelArchitect\Base\Traits\BulkPostgresRepository;

/**
 * Postgres implementation of Video bulk repository operations.
 */
class PostgresVideoBulkRepository implements VideoBulkRepositoryInterface
{
    use BulkPostgresRepository;

    /**
     * The table name.
     */
    protected string $table = 'videos';

    /**
     * The model class for hydration.
     */
    protected string $modelClass = 'App\Models\Video';
}
```

`app/Repositories/EloquentVideoSoftDeleteRepository.php` (using default eloquent driver):
```php
<?php

namespace App\Repositories;

use App\Models\Video;
use App\Contracts\VideoSoftDeleteRepositoryInterface;
use LaravelArchitect\Base\Traits\SoftDeleteEloquentRepository;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent implementation of Video soft delete repository operations.
 */
class EloquentVideoSoftDeleteRepository implements VideoSoftDeleteRepositoryInterface
{
    use SoftDeleteEloquentRepository;

    /**
     * The model instance.
     */
    protected Model $model;

    /**
     * Create a new repository instance.
     */
    public function __construct(Video $model)
    {
        $this->model = $model;
    }
}
```

### Phase 4: Update Service Stub & Generation

- [ ] **4.1** Create service stubs for each flag combination:
  - `stubs/service.stub` — CRUD + Bulk (default)
  - `stubs/service_no_bulk.stub` — CRUD only
  - `stubs/service_soft_deletes.stub` — CRUD + Bulk + SoftDelete + BulkSoftDelete
  - `stubs/service_soft_deletes_no_bulk.stub` — CRUD + SoftDelete
- [ ] **4.2** Update `MakeApiServiceCommand` to:
  - Accept `--soft-deletes` flag
  - Accept `--no-bulk` flag
  - Select appropriate stub based on flags
  - Include only relevant repository interfaces in constructor
- [ ] **4.3** Update `BaseService` to provide base methods that delegate to appropriate repositories

### Phase 5: Update MakeApiBundleCommand

- [ ] **5.1** Update signature to include new driver and bulk options:
  ```php
  protected $signature = 'make:api-bundle {name}
      {--schema=}
      {--soft-deletes}
      {--no-bulk}
      {--composite-unique=}
      {--crud-driver=}
      {--bulk-driver=}
      {--soft-delete-driver=}
      {--bulk-soft-delete-driver=}';
  ```
- [ ] **5.2** Update `handle()` to pass all flags to `make:api-repository` and `make:api-service`
- [ ] **5.3** Update final output to show complete service provider binding instructions (only for generated repos)

### Phase 6: Update Controller & Routes

- [ ] **6.1** Verify `MakeApiControllerCommand` works with new service structure (likely no changes needed)
- [ ] **6.2** Verify `MakeApiRoutesCommand` works with new structure (likely no changes needed)

### Phase 7: Update Tests

- [ ] **7.1** Update `MakeApiTestCommand` to handle new repository structure
- [ ] **7.2** Add tests for new driver options in `MakeApiRepositoryCommand`
- [ ] **7.3** Add tests for config-based driver resolution
- [ ] **7.4** Add tests for CLI override of config defaults
- [ ] **7.5** Add integration tests for mixed-driver scenarios

### Phase 8: Documentation

- [ ] **8.1** Update README with new driver configuration options
- [ ] **8.2** Add examples for common mixed-driver scenarios
- [ ] **8.3** Update CLAUDE.md with new architecture patterns

---

## DRY/SOLID Compliance Checklist

### Single Responsibility Principle (SRP)
- [ ] Each interface handles ONE operation type (CRUD, Bulk, SoftDelete, BulkSoftDelete)
- [ ] Each repository class implements ONE interface
- [ ] Each trait provides implementation for ONE operation type + driver combination
- [ ] Service delegates to appropriate repository based on operation type

### Open-Closed Principle (OCP)
- [ ] New drivers can be added without modifying existing code (just add new traits/stubs)
- [ ] New operation types can be added without modifying existing interfaces

### Liskov Substitution Principle (LSP)
- [ ] All repository implementations are interchangeable within their interface contract
- [ ] Eloquent, MySQL, PostgreSQL implementations all satisfy the same interface

### Interface Segregation Principle (ISP)
- [ ] Clients only depend on interfaces they use
- [ ] Services without soft-deletes don't have soft-delete repository dependencies
- [ ] Each interface is focused and minimal

### Dependency Inversion Principle (DIP)
- [ ] Services depend on interfaces, not concrete implementations
- [ ] Driver selection happens at binding time, not in business logic
- [ ] Config drives behavior, code remains abstract

### DRY Compliance
- [ ] All driver resolution logic in ONE helper method (`resolveDriver()`)
- [ ] All path generation logic in SchemaParserTrait (no duplication in commands)
- [ ] All naming conventions use NamingConventionHelper
- [ ] Stub processing uses existing `processStub()` method
- [ ] No repeated inline logic across commands

---

## File Changes Summary

### Completed (Phase 1-2)
```
config/api_scaffolding.php (updated) ✅
src/Console/Commands/Support/ConfigHelper.php (updated) ✅

# Repository interface stubs (3 new) ✅
stubs/repository_bulk_interface.stub (new)
stubs/repository_soft_delete_interface.stub (new)
stubs/repository_bulk_soft_delete_interface.stub (new)

# Repository implementation stubs (3 consolidated, driver-agnostic) ✅
stubs/repository_bulk.stub (new)
stubs/repository_soft_delete.stub (new)
stubs/repository_bulk_soft_delete.stub (new)
```

### Pending (Phase 3+)
```
src/Console/Commands/Traits/SchemaParserTrait.php (update needed)
src/Console/Commands/MakeApiRepositoryCommand.php (update needed)
src/Console/Commands/MakeApiServiceCommand.php (update needed)
src/Console/Commands/MakeApiBundleCommand.php (update needed)
src/Base/BaseService.php (update needed)

# Service stubs (4 variants based on flags)
stubs/service.stub (update - default: CRUD + Bulk)
stubs/service_no_bulk.stub (new - CRUD only)
stubs/service_soft_deletes.stub (new - CRUD + Bulk + SoftDelete + BulkSoftDelete)
stubs/service_soft_deletes_no_bulk.stub (new - CRUD + SoftDelete)
```

### Test Files
```
tests/Feature/MakeApiRepositoryTest.php (update)
tests/Feature/MakeApiServiceTest.php (update)
tests/Feature/MakeApiBundleTest.php (update)
tests/Feature/DriverConfigurationTest.php (new)
```

---

## Example Usage After Implementation

### Config-Based (Default)
```php
// config/api_scaffolding.php
'drivers' => [
    'crud' => 'eloquent',
    'bulk' => 'postgres',
    'soft_delete' => 'eloquent',
    'bulk_soft_delete' => 'postgres',
],
```

```bash
# Uses config defaults
php artisan make:api-bundle Video --schema='[{"name":"title","type":"string","required":true}]' --soft-deletes
```

**Generated:**
- `VideoRepositoryInterface` → `EloquentVideoRepository`
- `VideoBulkRepositoryInterface` → `PostgresVideoBulkRepository`
- `VideoSoftDeleteRepositoryInterface` → `EloquentVideoSoftDeleteRepository`
- `VideoBulkSoftDeleteRepositoryInterface` → `PostgresVideoBulkSoftDeleteRepository`

### CLI Override
```bash
# Override bulk to use MySQL instead of config's postgres
php artisan make:api-bundle Video --schema='...' --soft-deletes --bulk-driver=mysql
```

**Generated:**
- `VideoRepositoryInterface` → `EloquentVideoRepository`
- `VideoBulkRepositoryInterface` → `MySQLVideoBulkRepository` (overridden)
- `VideoSoftDeleteRepositoryInterface` → `EloquentVideoSoftDeleteRepository`
- `VideoBulkSoftDeleteRepositoryInterface` → `PostgresVideoBulkSoftDeleteRepository`

### Disable Bulk Operations
```bash
# Generate only CRUD repository (no bulk operations)
php artisan make:api-bundle Video --schema='...' --no-bulk
```

**Generated:**
- `VideoRepositoryInterface` → `EloquentVideoRepository`

### Soft Deletes Without Bulk
```bash
# Generate CRUD + SoftDelete, but no bulk operations
php artisan make:api-bundle Video --schema='...' --no-bulk --soft-deletes
```

**Generated:**
- `VideoRepositoryInterface` → `EloquentVideoRepository`
- `VideoSoftDeleteRepositoryInterface` → `EloquentVideoSoftDeleteRepository`

---

## Notes for Implementation

1. **Start with Phase 1-2** (config + stubs) as foundation
2. **Phase 3** is the core logic change
3. **Phase 4-5** integrate the changes into the full bundle flow
4. **Phase 6** should require minimal changes (verify only)
5. **Phase 7-8** ensure quality and documentation

Each phase should include running existing tests to ensure no regressions before moving to the next phase.

---

## Implementation Clarifications

### Repository Stub Implementation Pattern

**Use 3 consolidated driver-agnostic stubs** (not 9 driver-specific ones). Each stub uses placeholders that the command populates based on the selected driver.

The 3 consolidated stubs are:
- `stubs/repository_bulk.stub`
- `stubs/repository_soft_delete.stub`
- `stubs/repository_bulk_soft_delete.stub`

Each stub uses these driver-specific traits via the `{{ trait }}` placeholder:
- Bulk: `BulkEloquentRepository`, `BulkMySQLRepository`, `BulkPostgresRepository`
- SoftDelete: `SoftDeleteEloquentRepository`, `SoftDeleteMySQLRepository`, `SoftDeletePostgresRepository`
- BulkSoftDelete: `BulkSoftDeleteEloquentRepository`, `BulkSoftDeleteMySQLRepository`, `BulkSoftDeletePostgresRepository`

See **Phase 3.3** for the exact placeholder values for each driver.

### BaseService Remains Empty

**BaseService stays as an empty abstract class.** It exists only for type consistency and potential future extension.

The key principle is: **"Only inject what's generated. No nullable parameters for 'optional' repositories — if the flag wasn't used, the repository doesn't exist."**

This means:
- There are **4 different service stubs**, each containing only the methods relevant to its injected repositories
- Service methods are generated in the stub itself, NOT inherited from BaseService
- No runtime null checking is needed — the generated code matches exactly what exists

| Flags | Stub Used | Constructor Parameters | Methods Included |
|-------|-----------|------------------------|------------------|
| (default) | `service.stub` | `$repository`, `$bulkRepository` | CRUD + Bulk methods |
| `--no-bulk` | `service_no_bulk.stub` | `$repository` | CRUD methods only |
| `--soft-deletes` | `service_soft_deletes.stub` | `$repository`, `$bulkRepository`, `$softDeleteRepository`, `$bulkSoftDeleteRepository` | All methods |
| `--no-bulk --soft-deletes` | `service_soft_deletes_no_bulk.stub` | `$repository`, `$softDeleteRepository` | CRUD + SoftDelete methods |

For example, `service_no_bulk.stub` does NOT include `createMany()`, `updateMany()`, `deleteMany()` methods because the bulk repository isn't generated.

### Config Migration (v0.1 - Never Published)

Per CLAUDE.md guidance:
> "There is NO such thing as a 'backwards-breaking change'"

When renaming `repository_interfaces` to `contracts` in config:
- **Just rename it** — no deprecated fallbacks
- **Delete all old code paths** — no parallel implementations
- Update all references across the codebase