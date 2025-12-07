# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel Architect is a code generator package for Laravel 12+ / PHP 8.4+ that scaffolds production-ready RESTful APIs using the Repository Pattern. It generates models, migrations, controllers, services, repositories, form requests, API resources, factories, PEST tests, and routes.

The purpose of this workflow is to extend this package after a full refactor implementing the DEVELOPMENT GUIDELINES below.

## CRITICAL: Package Version Status

**This package is version 0.1 and has NEVER been published or released.**

- There is NO such thing as a "backwards-breaking change"
- There is NO need to maintain deprecated fallbacks
- There is NO need to support legacy code paths
- When refactoring, **DELETE all code that becomes irrelevant** — do not comment it out, do not add deprecation warnings, do not maintain parallel implementations
- Treat every change as if you're writing it for the first time

## DEVELOPMENT GUIDELINES - MUST FOLLOW

It is CRITICAL that all development work on this repository follow DRY and SOLID programming principles.

DRY: ALL repetitive code logic (anything that contains at least one logic check and is used in more than ONE part of the code) MUST be abstracted to its own method, and that method MUST be available to ANY/EVERY class that makes use of it.

SOLID:
Single Responsibility Principle (SRP): A class should have only one reason to change, meaning it should have only one job or responsibility.
Open-Closed Principle (OCP): Software entities (classes, modules, functions, etc.) should be open for extension but closed for modification. This means you can add new functionality without changing the existing code.
Liskov Substitution Principle (LSP): Objects of a superclass should be replaceable with objects of a subclass without affecting the correctness of the program.
Interface Segregation Principle (ISP): Clients should not be forced to depend on interfaces they do not use. It's better to have many small, specific interfaces than one large, general-purpose one.
Dependency Inversion Principle (DIP): High-level modules should not depend on low-level modules. Both should depend on abstractions (e.g., interfaces). Abstractions should not depend on details. Details should depend on abstractions.

NEVER write a line of code that would violate either one of these principles.

Finally, ALL methods should be fully tested, and ALL new/refactored code MUST pass ALL tests that relate to it.

### DRY Violation Examples

**BAD - Inline logic repeated:**
```php
// In MakeApiTestCommand.php line 102:
$regularFields = array_filter($schema, fn($col) => $this->isRegularDataField($col));
// In MakeApiTestCommand.php line 145:
$regularFields = array_filter($schema, fn($col) => $this->isRegularDataField($col));
// In MakeApiTestCommand.php line 189:
$regularFields = array_filter($schema, fn($col) => $this->isRegularDataField($col));
```

**GOOD - Abstracted to method:**
```php
// In SchemaParserTrait (one location):
protected function getRegularDataFields(array $schema): array {
    return array_filter($schema, fn($col) => $this->isRegularDataField($col));
}
// In all commands:
$regularFields = $this->getRegularDataFields($schema);
```

**BAD - Using Laravel helpers directly when project helper exists:**
```php
$tableName = Str::snake(Str::plural($modelName));
```

**GOOD - Using project helper:**
```php
$tableName = NamingConventionHelper::modelToTableName($modelName);
```

### Pre-Commit DRY Checklist

Before committing, verify:
- [ ] No inline logic appears more than once across all files
- [ ] All class metadata (names, paths, imports, FQCNs) use ClassMetaBuilderHelper
- [ ] All naming transformations use NamingConventionHelper
- [ ] All type mappings use TypeMappingHelper
- [ ] All config access uses ConfigHelper
- [ ] No `Str::snake()`, `Str::plural()`, `Str::singular()` used directly in commands (use helpers)
- [ ] No `array_filter()` with callbacks that duplicate existing trait/helper methods
- [ ] No hardcoded driver name mappings (use `ClassMetaBuilderHelper::driverName()`)
- [ ] No hardcoded operation type prefixes (use `ClassMetaBuilderHelper::operationPrefix()`)

### Helper Method Inventory (MUST USE)

**PRIORITY ORDER:** When a method exists in `SchemaParserTrait`, USE THE TRAIT METHOD. Only fall back to `NamingConventionHelper` when no trait method exists. This keeps command code clean and maintains a single point of abstraction.

#### Trait Methods (PREFER THESE in commands that use SchemaParserTrait)
| Instead of | Use |
|------------|-----|
| `Str::snake(Str::plural($model))` | `$this->getTableName($model)` |
| `Str::snake($model) . '_id'` | `$this->modelToForeignKeyColumn($model)` |
| `str_replace('_id', '', $fk)` | `$this->extractForeignEntityName($fk)` |
| `Str::studly(str_replace('_id', '', $fk))` | `$this->getForeignModelName($fk)` |
| `config('api_scaffolding.paths.x')` | `$this->getPath('x')` |
| `config('api_scaffolding.namespaces.x')` | `$this->getNamespace('x')` |
| `array_filter($schema, fn($col) => $this->isRegularDataField($col))` | `$this->getRegularDataFields($schema)` |
| `array_filter($schema, fn($col) => !$this->isForeignKey($col))` | `$this->getNonForeignKeyFields($schema)` |
| `array_filter($schema, fn($col) => $this->isForeignKey($col))` | `$this->getForeignKeyFields($schema)` |
| `$col['name']` or `$col['field']` | `$this->getFieldName($col)` |

#### NamingConventionHelper (ONLY when no trait method exists)
| Instead of | Use |
|------------|-----|
| `Str::singular(Str::studly($table))` | `NamingConventionHelper::tableToModelName($table)` |
| `Str::snake($model)` | `NamingConventionHelper::modelToSingularSnake($model)` |
| `Str::camel(Str::plural($model))` | `NamingConventionHelper::modelToBelongsToManyMethod($model)` |
| `Str::camel($model)` | `NamingConventionHelper::modelToBelongsToMethod($model)` |
| Pivot table name from models | `NamingConventionHelper::generatePivotTableName($m1, $m2)` |

#### ClassMetaBuilderHelper (MUST USE for class metadata)

`ClassMetaBuilderHelper` is the **single source of truth** for all generated class metadata. Use it whenever you need class names, file paths, namespaces, imports, or FQCNs.

**Location:** `src/Console/Commands/Support/ClassMetaBuilderHelper.php`

**Usage:**
```php
use LaravelArchitect\Console\Commands\Support\ClassMetaBuilderHelper;

// Build metadata for any file type
$meta = ClassMetaBuilderHelper::build('repository', 'Video', 'crud', 'eloquent');
// Returns: ['class_name', 'file_name', 'path', 'namespace', 'fqcn', 'extends', 'extends_import', ...]
```

| Instead of | Use |
|------------|-----|
| Hardcoded class names | `ClassMetaBuilderHelper::build($type, $model)['class_name']` |
| Hardcoded file paths | `ClassMetaBuilderHelper::build($type, $model)['path']` |
| Hardcoded namespaces | `ClassMetaBuilderHelper::build($type, $model)['namespace']` |
| Hardcoded FQCNs | `ClassMetaBuilderHelper::build($type, $model)['fqcn']` |
| Hardcoded base class imports | `ClassMetaBuilderHelper::build($type, $model)['extends_import']` |
| Driver name mapping arrays | `ClassMetaBuilderHelper::driverName('mysql')` → `'MySQL'` |
| Operation type prefix arrays | `ClassMetaBuilderHelper::operationPrefix('soft_delete')` → `'SoftDelete'` |

**Supported file types:** `model`, `service`, `controller`, `request`, `resource`, `factory`, `test`, `interface`, `repository`

**Return array keys:**
- `class_name` - Short class name (e.g., `EloquentVideoRepository`)
- `file_name` - File name (e.g., `EloquentVideoRepository.php`)
- `path` - Full file path
- `namespace` - Class namespace (from config)
- `fqcn` - Fully qualified class name
- `extends` - Parent class name (or null)
- `extends_import` - Parent class import (or null)
- `implements` - Interface name (or null, repositories only)
- `implements_import` - Interface import (or null)
- `uses_trait` - Trait name (or null, operation-type repositories only)
- `trait_import` - Trait import (or null)

**Static helper methods:**
```php
ClassMetaBuilderHelper::driverName('postgres');     // 'Postgres'
ClassMetaBuilderHelper::operationPrefix('bulk');    // 'Bulk'
ClassMetaBuilderHelper::operationTypes();           // ['crud', 'bulk', 'soft_delete', 'bulk_soft_delete']
ClassMetaBuilderHelper::drivers();                  // ['eloquent', 'mysql', 'postgres']
```

**Tests:** `tests/Unit/ClassMetaBuilderHelperTest.php`

#### Other Helpers (ConfigHelper, TypeMappingHelper)
| Instead of | Use |
|------------|-----|
| `base_path(config('api_scaffolding.paths.x'))` | `ConfigHelper::basePath('x')` |
| `config('api_scaffolding.drivers.x')` | `ConfigHelper::driver('x')` |
| Manual cast mapping switch/array | `TypeMappingHelper::getCast($type)` |
| Manual validation rule mapping | `TypeMappingHelper::getValidationRule($type)` |
| Manual faker method mapping | `TypeMappingHelper::getFakerMethod($type, $name)` |
| Manual test value mapping | `TypeMappingHelper::getTestValue($type)` |

## Development Commands

### Git Workflow
```bash
# IMPORTANT: Only the main branch should be set to track upstream
# For all other branches, use explicit origin push/pull commands

# Push non-main branch
git push origin branch-name

# Pull non-main branch
git pull origin branch-name

# DO NOT use --set-upstream for non-main branches
```

### Testing Commands (Docker)
```bash
# Build and start containers (first time setup)
./docker.sh build && ./docker.sh up

# Install dependencies
./docker.sh composer-install

# Run tests with SQLite (default, fastest)
./docker.sh test

# Run tests with all databases (required before PR)
./docker.sh test-all

# Individual database tests
./docker.sh test-mysql    # MySQL only
./docker.sh test-postgres # PostgreSQL only

# Run a specific test file
./docker.sh test-all tests/Unit/ClassMetaBuilderHelperTest.php

# Run tests matching a filter
./docker.sh test-all --filter="builds model metadata"
```

## Testing Guidelines - MUST FOLLOW

All tests in this package use PEST and follow specific patterns for consistency and maintainability.

### Test Assertion Rules: Hardcoded vs ConfigHelper

When writing test assertions, you MUST distinguish between **package constants** (hardcode) and **user-configurable values** (use ConfigHelper).

#### HARDCODE These Values (Package Constants)

These are internal to Laravel Architect and users CANNOT change them:

| Value Type | Examples | Why Hardcode |
|------------|----------|--------------|
| Package base class imports | `LaravelArchitect\Base\BaseEloquentRepository` | Package internal |
| Package trait imports | `LaravelArchitect\Base\Traits\BulkEloquentRepository` | Package internal |
| Package interface imports | `LaravelArchitect\Base\Contracts\BaseRepositoryInterface` | Package internal |
| Laravel framework imports | `Illuminate\Database\Eloquent\SoftDeletes` | Framework constant |
| Laravel base classes | `extends JsonResource`, `extends Factory` | Framework constant |
| Class names derived from model | `EloquentVideoRepository`, `VideoController` | Derived from input |
| Method names | `public function index`, `public function store` | Package logic |
| Property names | `protected $table`, `protected $fillable` | Package logic |
| Column/field generation | `$table->string('title')` | Package logic |

**Example - CORRECT (hardcoded package constant):**
```php
expect($result['import'])->toBe('LaravelArchitect\\Base\\BaseEloquentRepository');
expect($content)->toContain('extends BaseFormRequest');
expect($content)->toContain('class VideoController');
```

#### USE ConfigHelper For These Values (User-Configurable)

These are configured in `api_scaffolding.php` and users CAN change them:

| Config Key | Default Value | Usage |
|------------|---------------|-------|
| `paths.repositories` | `app/Repositories` | File generation location |
| `paths.contracts` | `app/Contracts` | File generation location |
| `paths.controllers` | `app/Http/Controllers/Api` | File generation location |
| `namespaces.requests` | `App\Http\Requests` | Generated namespace |
| `namespaces.resources` | `App\Http\Resources` | Generated namespace |
| `namespaces.factories` | `Database\Factories` | Generated namespace |
| `namespaces.controllers` | `App\Http\Controllers\Api` | Generated namespace |
| `namespaces.models` | `App\Models` | Generated namespace |

**Example - CORRECT (using ConfigHelper):**
```php
// Path assertions
$repoPath = ConfigHelper::path('repositories');
expect($result['path'])->toContain($repoPath . '/EloquentVideoRepository.php');

// Namespace assertions in generated content
$namespace = ConfigHelper::namespace('requests');
expect($content)->toContain('namespace ' . $namespace);

// Controller use statement
expect($content)->toContain('use ' . ConfigHelper::namespace('controllers') . '\\ProductController');
```

**Example - WRONG (hardcoding configurable values):**
```php
// BAD - path is user-configurable
expect($result['path'])->toContain('app/Repositories/EloquentVideoRepository.php');

// BAD - namespace is user-configurable
expect($content)->toContain('namespace App\Http\Requests');
```

### Test File Structure

All feature tests follow this pattern:

```php
<?php

use Illuminate\Support\Facades\Artisan;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Ensure directory exists using ConfigHelper
    $path = ConfigHelper::basePath('component_type');
    File::ensureDirectoryExists($path);
});

test('descriptive test name', function () {
    // Arrange: Set up test data
    $schema = json_encode([...]);

    // Act: Run the command
    Artisan::call('make:api-component', ['name' => 'Model', '--schema' => $schema]);

    // Assert: Use TestCase helpers (they use ConfigHelper internally)
    $this->assertFileWasGenerated('component_type', 'Filename.php');
    $this->assertFileContains('component_type', 'Filename.php', 'expected content');

    // For content checks, get content and use expect()
    $content = $this->getGeneratedFileContent('component_type', 'Filename.php');
    expect($content)->toContain('expected string');
});
```

### TestCase Helper Methods

The `TestCase.php` provides these helper methods that already use ConfigHelper internally:

| Method | Purpose |
|--------|---------|
| `$this->assertFileWasGenerated('type', 'file.php')` | Assert file exists at configured path |
| `$this->assertFileContains('type', 'file.php', 'needle')` | Assert file contains string |
| `$this->assertFileDoesNotContain('type', 'file.php', 'needle')` | Assert file does NOT contain string |
| `$this->getGeneratedFileContent('type', 'file.php')` | Get file contents for custom assertions |
| `$this->assertMigrationExists('table_name')` | Assert migration file exists |
| `$this->getMigrationContent('table_name')` | Get migration file contents |

**ALWAYS use these helpers** instead of manually building paths with `base_path()`.

### Testing ConfigHelper Itself

The `tests/Unit/ConfigHelperTest.php` file tests ConfigHelper. In this file ONLY:
- Assertions MUST be hardcoded (you cannot test ConfigHelper using ConfigHelper)
- Values should match what's set in `TestCase::getEnvironmentSetUp()`

### Quick Reference: What to Check

Before writing a test assertion, ask:
1. **Is this a package-internal value?** → Hardcode it
2. **Is this from `api_scaffolding.php` config?** → Use ConfigHelper
3. **Is this derived from user input (model name)?** → Hardcode it
4. **Is this a Laravel framework constant?** → Hardcode it

For detailed examples and the complete list of tests requiring updates, see `.claude/update-test-path-config.md`.

## (CURRENT) Architecture

### (CURRENT) Directory Structure
```
src/
├── Base/                    # Base classes for generated code
│   ├── BaseModel.php
│   ├── BaseService.php
│   ├── BaseFormRequest.php
│   ├── BaseRepositoryInterface.php
│   ├── BaseEloquentRepository.php
│   ├── BaseMySQLRepository.php
│   └── BasePostgresRepository.php
├── Console/Commands/        # Artisan generator commands
│   ├── MakeApiBundleCommand.php      # Generates complete API resource
│   ├── MakeApiModelCommand.php
│   ├── MakeApiMigrationCommand.php
│   ├── MakeApiControllerCommand.php
│   ├── MakeApiServiceCommand.php
│   ├── MakeApiRepositoryCommand.php
│   ├── MakeApiRequestCommand.php
│   ├── MakeApiResourceCommand.php
│   ├── MakeApiFactoryCommand.php
│   ├── MakeApiTestCommand.php
│   ├── MakeApiRoutesCommand.php
│   ├── Support/
│   │   ├── ConfigHelper.php          # Config access utilities
│   │   ├── ClassMetaBuilderHelper.php # Class metadata generation (names, paths, imports)
│   │   ├── NamingConventionHelper.php # Naming transformations
│   │   └── TypeMappingHelper.php     # Type to validation/faker/cast mappings
│   └── Traits/SchemaParserTrait.php  # Schema JSON parsing and validation
├── Traits/                  # Traits for generated models
└── LaravelArchitectServiceProvider.php
```

### Key Patterns

**Repository Pattern Flow:**
Controller → Service → Repository Interface → Implementation (Eloquent/MySQL/PostgreSQL)

**Schema Parsing:** CURRENTLY, All commands use `SchemaParserTrait` for parsing `--schema` JSON option. CURRENT Schema fields support:
- `name`, `type`, `required`, `unique`, `default`
- Foreign keys: `isForeignKey`, `foreignTable`, `onDelete`, `onUpdate`
- Or simplified: `parentModel` (auto-generates `{snake}_id` column)
Our MAIN GOAL for this repository is to REFACTOR this behavior, so some of the bullet points above (in Schema Parsing section) WILL be updated/appended to/removed as part of this work.

**Configuration:** Commands use `ConfigHelper` to read paths/namespaces from `config/api_scaffolding.php`.

## Coding Standards (Critical)

!!IMPORTANT!! As part of the refactor, the naming conventions and database rules outline below will be DEFAULTS for the package. They will be adjustable by package users according to their own project naming conventions, which we will be allowing via config variables. The list below is for package defaults only.

### Naming Conventions
| Element | Convention | Example |
|---------|-----------|---------|
| Model | Singular, StudlyCase | `Video`, `BlogPost` |
| Table | Plural, snake_case | `videos`, `blog_posts` |
| Foreign Key | `{singular_table}_id` | `user_id`, `blog_post_id` |
| Pivot Table | Alphabetical, singular | `post_tag` (not `tag_post`) |
| BelongsTo method | Singular table name | `user()` |
| HasMany method | Plural table name | `videos()` |

### Database Rules
- Primary keys: Always `id` (auto-increment integer)
- Pivot tables: No `id`, composite primary key, no timestamps, use `make:api-migration --pivot=true`
- Foreign keys: Must end with `_id`, referenced model must exist
- Timestamps: `useCurrent()` and `useCurrentOnUpdate()`
- `"required": true` → NOT NULL; absent/false → nullable

### Code Generation
- Use modern PHP 8.4+ features (constructor promotion, typed properties)
- Full type hints on all methods
- Generated code extends Base classes from this package
- Controllers: No business logic, only validate → call service → return response
- Services: All business logic
- Repositories: Data access only

## CURRENT Schema Format

!!IMPORTANT!! This is part of what we will be refactoring. Some key names will change, some will be updated/appended to/removed as part of this work. The example below contains the CURRENT codebase (which we are refactoring in this project) set up ONLY.

```json
[
  {"name": "title", "type": "string", "required": true},
  {"name": "user_id", "type": "unsignedBigInteger", "required": true, "isForeignKey": true, "foreignTable": "user"},
  {"parentModel": "Category"}  // Simplified FK syntax
]
```

Types: `string`, `text`, `integer`, `unsignedBigInteger`, `boolean`, `decimal`, `float`, `date`, `datetime`, `timestamp`, `json`, `email`

## Important Validations

!! IMPORTANT !! These will ultimately be based off of the package user's config options. The list below is for the package DEFAULT options ONLY.

The package validates:
- Foreign keys must end with `_id`
- Referenced tables/models must exist
- Pivot table names must be alphabetical
- Pivot tables need exactly 2 foreign keys
- Composite unique columns must exist in schema