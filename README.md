# Laravel Architect

> **A strict, opinionated scaffolding package for Laravel 12+ and PHP 8.4+ that enforces the Repository Pattern and REST best practices.**

Laravel Architect generates production-ready API scaffolding with complete CRUD operations, batch processing, soft deletes, multi-tenancy support, composite unique keys, and comprehensive PEST tests—all while enforcing your company's coding standards.

---

## 📑 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Docker Development Environment](#-docker-development-environment)
- [Configuration](#configuration)
- [Coding Standards](#coding-standards)
- [Usage](#usage)
  - [Quick Start](#quick-start)
  - [Command Reference](#command-reference)
  - [Schema Format](#schema-format)
  - [Composite Unique Keys](#composite-unique-keys)
  - [Examples](#examples)
- [Generated Files](#generated-files)
- [Testing](#testing)
- [Advanced Features](#advanced-features)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

---

## ✨ Features

- 🏗️ **Complete API Scaffolding** - Models, Migrations, Controllers, Services, Repositories
- 🔄 **Repository Pattern** - Interface-driven architecture with Eloquent, MySQL, and PostgreSQL implementations
- 📦 **Batch Operations** - Built-in support for bulk create, update, delete, and restore
- 🔍 **Search & Filtering** - Automatic query filtering and search capabilities
- 🗑️ **Soft Deletes** - Optional soft delete support with restore functionality
- 🏢 **Multi-Tenancy** - Built-in tenant isolation support
- 🔑 **Composite Unique Keys** - Support for multi-column unique constraints with smart naming
- 🎨 **API Resources** - Laravel Resource transformers for consistent JSON responses
- ✅ **Form Validation** - Auto-generated Form Request classes with smart validation rules
- 🧪 **PEST Tests** - Comprehensive test suite for all CRUD operations
- 🏭 **Factories** - Model factories with intelligent fake data generation
- 📋 **Strict Standards** - Enforces company coding conventions automatically
- ⚙️ **Fully Configurable** - All paths, namespaces, and responses configurable

---

## 📋 Requirements

- PHP **8.4+**
- Laravel **12.0+**
- Composer

---

## 📦 Installation

### Step 1: Install via Composer

```bash
composer require vendor/laravel-architect
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=api-scaffolding-config
```

This creates `config/api_scaffolding.php` where you can customize paths, namespaces, and responses.

### Step 3: Create Repository Service Provider (Recommended)

Create a service provider to bind repository interfaces:

```bash
php artisan make:provider RepositoryServiceProvider
```

```php
// app/Providers/RepositoryServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Add repository bindings here
        // Example:
        // $this->app->bind(
        //     \App\Repositories\Interfaces\VideoRepositoryInterface::class,
        //     \App\Repositories\EloquentVideoRepository::class
        // );
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\RepositoryServiceProvider::class,
],
```

ℹ️ - The `boost-application-standards-helper.md` can be copy/pasted into your `.ai/guidelines` directory (and edited as desired) if you are using [Laravel Boost](https://github.com/laravel/boost).

---

## 🐳 Docker Development Environment

**⚠️ For Package Contributors Only**

The Docker setup is for **developing and testing the Laravel Architect package itself**. If you're using Laravel Architect in your Laravel application, you do NOT need this—use your application's own database configuration.

### What is This For?

The Docker environment provides:
- 🧪 **Multi-database testing** - Test package generators against SQLite, MySQL, and PostgreSQL
- 🏗️ **Package development** - Isolated environment for contributing to Laravel Architect
- ⚡ **Quick setup** - Everything needed for package testing in one command

### Quick Start for Contributors

```bash
# Build and start containers
./docker.sh build && ./docker.sh up

# Install dependencies
./docker.sh composer-install

# Run tests with all databases
./docker.sh test-all
```

### Important Notes

- ✅ **Test credentials are simple by design** - These databases only exist for package testing
- ✅ **Ports are localhost-only** - Database ports bound to 127.0.0.1 for security
- ✅ **No .env required** - Works with sensible defaults; `.env.example` available for customization
- ❌ **Not for production** - This setup is purely for package development and testing

For complete Docker documentation, commands, and troubleshooting, see [DOCKER.md](DOCKER.md).

---

## ⚙️ Configuration

Edit `config/api_scaffolding.php` to customize:

```php
return [
    // File paths for generated code
    'paths' => [
        'models' => 'app/Models',
        'repositories' => 'app/Repositories',
        'repository_interfaces' => 'app/Repositories/Interfaces',
        'services' => 'app/Services',
        'controllers' => 'app/Http/Controllers/Api',
        'requests' => 'app/Http/Requests',
        'resources' => 'app/Http/Resources',
        'migrations' => 'database/migrations',
        'tests' => 'tests/Feature',
        'factories' => 'database/factories',
        'routes' => 'routes/api.php',
    ],
    
    // Namespaces for generated classes
    'namespaces' => [
        'models' => 'App\Models',
        'repositories' => 'App\Repositories',
        'repository_interfaces' => 'App\Repositories\Interfaces',
        'services' => 'App\Services',
        'controllers' => 'App\Http\Controllers\Api',
        'base_controller' => 'App\Http\Controllers\Controller',
        'requests' => 'App\Http\Requests',
        'resources' => 'App\Http\Resources',
        'factories' => 'Database\Factories',
    ],
    
    // API response configuration
    'response_settings' => [
        'json_key' => 'data',
        'pagination_limit' => 15,
    ],
    
    // HTTP status codes
    'status_codes' => [
        'success' => 200,
        'created' => 201,
        'no_content' => 204,
        'not_found' => 404,
        'unprocessable_entity' => 422,
        'server_error' => 500,
    ],
    
    // Response messages
    'messages' => [
        'created' => 'Resource created successfully.',
        'updated' => 'Resource updated successfully.',
        'deleted' => 'Resource deleted successfully.',
        'not_found' => 'Resource not found.',
    ],
];
```

---

## 📏 Coding Standards

Laravel Architect enforces strict coding conventions:

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| **Model Names** | Singular, StudlyCase | `Video`, `BlogPost`, `User` |
| **Table Names** | Plural, snake_case | `videos`, `blog_posts`, `users` |
| **Pivot Tables** | Singular, alphabetical, snake_case | `post_tag`, `role_user`, `tag_video` |
| **Foreign Keys** | `{singular_table}_id` | `user_id`, `blog_post_id`, `tag_id` |
| **Primary Keys** | Always `id` (auto-increment integer) | `id` |
| **Relationships** | Singular (belongsTo) or Plural (hasMany/belongsToMany) | `user()`, `videos()`, `tags()` |

### Relationship Naming Rules

**BelongsTo (Many-to-One):**
- Method name is the **singular** foreign table name
- Example: `user_id` foreign key → `user()` method

```php
// Video model with user_id foreign key
public function user()
{
    return $this->belongsTo(User::class);
}
```

**HasMany (One-to-Many):**
- Method name is the **plural** of the child table
- Example: User has many Videos → `videos()` method

```php
// User model (inverse of the above)
public function videos()
{
    return $this->hasMany(Video::class);
}
```

**BelongsToMany (Many-to-Many via Pivot):**
- Method name is the **plural** of the related table
- Example: Post has many Tags → `tags()` method

```php
// Post model
public function tags()
{
    return $this->belongsToMany(Tag::class, 'post_tag');
}

// Tag model
public function posts()
{
    return $this->belongsToMany(Post::class, 'post_tag');
}
```

**❌ WRONG Relationship Names:**
```php
// DON'T DO THIS:
public function userVideo() { }     // Wrong!
public function userVideos() { }    // Wrong!
public function video() { }         // Wrong for hasMany!
```

**✅ CORRECT Relationship Names:**
```php
// DO THIS:
public function user() { }          // belongsTo
public function videos() { }        // hasMany
public function tags() { }          // belongsToMany
```

### Database Rules

1. ✅ **Primary Keys**: Always `id` (integer, auto-increment)
2. ✅ **Pivot Tables**:
    - **Do NOT use `make:api-bundle`** - Use `make:api-migration --pivot=true` instead
    - No `id` column
    - Composite primary key from foreign keys
    - Composite unique key (automatically added)
    - No timestamps
    - Named alphabetically: `post_tag` not `tag_post`
    - Both foreign key tables MUST exist before creating pivot
3. ✅ **Foreign Keys**:
    - Always named `{table_name}_id`
    - Referenced table/model MUST exist before creation
    - Automatic validation on generation
    - Include constraints with cascade behavior
4. ✅ **Timestamps**:
    - Use `useCurrent()` and `useCurrentOnUpdate()`
    - All tables except pivots have `created_at` and `updated_at`
5. ✅ **Migrations**:
    - Must check `Schema::hasTable()` before creating
    - Must use `Schema::dropIfExists()` in `down()` method
6. ✅ **Required vs Nullable**:
    - If `"required": true` in schema → field is required (NOT NULL)
    - If `"required"` is absent or false → field is nullable (NULL allowed)

### Architecture Rules

1. ✅ **Controllers**:
    - NO business logic
    - Only validate request → call service → return response
    - All HTTP status codes from config
2. ✅ **Services**:
    - All business logic lives here
    - Inject repository interface
3. ✅ **Repositories**:
    - Data access only
    - Implement interface
    - Support Eloquent, MySQL, and PostgreSQL
4. ✅ **Form Requests**:
    - All validation rules
    - Different rules for POST vs PATCH

### Code Quality

- ✅ Modern PHP 8.4+ features (constructor property promotion, typed properties, etc.)
- ✅ Laravel 12+ best practices
- ✅ Full type hints on all methods
- ✅ DocBlocks for public methods
- ✅ Comprehensive PEST tests for all functionality

---

## 🚀 Usage

### Quick Start

Generate a complete API resource in one command:

```bash
php artisan make:api-bundle Video \
  --schema='[{"name":"title","type":"string","required":true},{"name":"description","type":"text","nullable":true},{"name":"duration","type":"integer","required":true}]'
```

This generates:
- ✅ Migration with safety checks
- ✅ Model with relationships and casts
- ✅ Repository (interface + implementation)
- ✅ Service layer
- ✅ Controller with all CRUD + batch operations
- ✅ Form Request validation
- ✅ API Resources (Resource + Collection)
- ✅ Factory with smart fake data
- ✅ Comprehensive PEST tests
- ✅ API routes

---

### Command Reference

#### 1. `make:api-bundle` - Generate Complete Resource

**Generates everything for a complete API resource.**

```bash
php artisan make:api-bundle {name} [options]
```

**Arguments:**
- `name` - Singular model name (e.g., `Video`, `BlogPost`)

**Options:**
- `--schema=JSON` - Schema definition (see [Schema Format](#schema-format))
- `--pivot=true` - **DEPRECATED**: Use `make:api-migration --pivot=true` instead
- `--soft-deletes` - Include soft delete support
- `--multi-tenant` - Include multi-tenancy support
- `--composite-unique=JSON` - Add composite unique constraints
- `--driver=eloquent` - Repository driver: `eloquent`, `mysql`, `postgres`, or `all`

**Examples:**

```bash
# Basic resource
php artisan make:api-bundle Article

# With schema
php artisan make:api-bundle Video \
  --schema='[{"name":"title","type":"string","required":true}]'

# With soft deletes
php artisan make:api-bundle Product --soft-deletes

# With multi-tenancy
php artisan make:api-bundle Order --multi-tenant

# With composite unique (multi-tenant users)
php artisan make:api-bundle User \
  --schema='[{"name":"email","type":"string","required":true},{"name":"tenant_id","type":"unsignedBigInteger","required":true}]' \
  --composite-unique='[["email","tenant_id"]]' \
  --multi-tenant

# Generate all repository types
php artisan make:api-bundle Video --driver=all
```

**⚠️ Important:** For pivot tables, use `make:api-migration` instead:

```bash
# WRONG - Don't use make:api-bundle for pivots
php artisan make:api-bundle post_tag --pivot=true

# CORRECT - Use make:api-migration
php artisan make:api-migration post_tag \
  --schema='[{"name":"post_id","type":"unsignedBigInteger"},{"name":"tag_id","type":"unsignedBigInteger"}]' \
  --pivot=true
```

---

#### 2. `make:api-model` - Generate Model

```bash
php artisan make:api-model {name} [options]
```

**Options:**
- `--schema=JSON` - Field definitions
- `--pivot=true` - Create pivot model (no timestamps)
- `--soft-deletes` - Add SoftDeletes trait
- `--multi-tenant` - Add MultiTenantTrait

**Example:**

```bash
php artisan make:api-model Video \
  --schema='[{"name":"title","type":"string","required":true},{"name":"user_id","type":"unsignedBigInteger","isForeignKey":true,"foreignTable":"user"}]' \
  --soft-deletes
```

**Generates:**

```php
<?php

namespace App\Models;

use LaravelArchitect\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends BaseModel
{
    use SoftDeletes;

    protected $table = 'videos';

    protected $fillable = [
        'title',
        'user_id'
    ];

    public $timestamps = true;

    protected $casts = [];

    /**
     * Get the user that owns this video.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
```

---

#### 3. `make:api-migration` - Generate Migration

```bash
php artisan make:api-migration {name} [options]
```

**Options:**
- `--schema=JSON` - Column definitions
- `--pivot=true` - Create pivot migration (no id, no timestamps, composite keys)
- `--soft-deletes` - Add `deleted_at` column
- `--multi-tenant` - Add `tenant_id` column with foreign key
- `--composite-unique=JSON` - Add composite unique constraints

**Example:**

```bash
php artisan make:api-migration Video \
  --schema='[{"name":"title","type":"string","required":true},{"name":"duration","type":"integer","default":0}]' \
  --soft-deletes
```

**Generates:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('videos')) {
            return;
        }

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('duration')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
```

**Pivot Table Example:**

```bash
# Step 1: Ensure both models exist first
php artisan make:api-bundle Post --schema='[...]'
php artisan make:api-bundle Tag --schema='[...]'

# Step 2: Create pivot migration
php artisan make:api-migration post_tag \
  --schema='[{"name":"post_id","type":"unsignedBigInteger"},{"name":"tag_id","type":"unsignedBigInteger"}]' \
  --pivot=true
```

**Pivot Migration Output:**

```php
Schema::create('post_tag', function (Blueprint $table) {
    $table->unsignedBigInteger('post_id');
    $table->unsignedBigInteger('tag_id');
    $table->primary(['post_id', 'tag_id']);
    $table->unique(['post_id', 'tag_id'], 'post_tag_composite_unique');
    $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
    $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
});
```

**Validation:**
- ✅ Foreign keys must end with `_id`
- ✅ Referenced tables/models must exist
- ✅ Pivot tables must have exactly 2 foreign keys
- ✅ Pivot table names must be alphabetical

---

#### 4. `make:api-repository` - Generate Repository

```bash
php artisan make:api-repository {name} [options]
```

**Options:**
- `--schema=JSON` - Schema (used for table name)
- `--driver=eloquent` - Which implementation(s) to generate:
    - `eloquent` (default) - Eloquent ORM
    - `mysql` - Raw MySQL queries
    - `postgres` - Raw PostgreSQL queries
    - `all` - Generate all three

**Example:**

```bash
php artisan make:api-repository Video --driver=all
```

**Generates:**
- `app/Repositories/Interfaces/VideoRepositoryInterface.php`
- `app/Repositories/EloquentVideoRepository.php`
- `app/Repositories/MySQLVideoRepository.php`
- `app/Repositories/PostgresVideoRepository.php`

**Remember to bind in service provider:**

```php
$this->app->bind(
    \App\Repositories\Interfaces\VideoRepositoryInterface::class,
    \App\Repositories\EloquentVideoRepository::class // or MySQL/Postgres
);
```

---

#### 5. `make:api-service` - Generate Service

```bash
php artisan make:api-service {name}
```

**Example:**

```bash
php artisan make:api-service Video
```

**Generates:**

```php
<?php

namespace App\Services;

use App\Repositories\Interfaces\VideoRepositoryInterface;
use LaravelArchitect\Base\BaseService;

class VideoService extends BaseService
{
    public function __construct(
        protected VideoRepositoryInterface $repository
    ) {}

    public function getAll(): Collection { /* ... */ }
    public function create(array $data): object { /* ... */ }
    // ... all CRUD + batch operations
}
```

---

#### 6. `make:api-controller` - Generate Controller

```bash
php artisan make:api-controller {name}
```

**Example:**

```bash
php artisan make:api-controller Video
```

**Generates controller with:**
- ✅ Standard REST methods: `index`, `store`, `show`, `update`, `destroy`
- ✅ Soft delete methods: `restore`, `forceDelete`
- ✅ Batch operations: `batchShow`, `batchStore`, `batchUpdate`, `batchDestroy`, `batchRestore`, `batchForceDelete`
- ✅ Proper status codes from config
- ✅ API Resource transformers

---

#### 7. `make:api-request` - Generate Form Request

```bash
php artisan make:api-request {name} [--schema=JSON]
```

**Example:**

```bash
php artisan make:api-request Video \
  --schema='[{"name":"title","type":"string","required":true},{"name":"url","type":"string"}]'
```

**Generates:**

```php
<?php

namespace App\Http\Requests;

use LaravelArchitect\Base\BaseFormRequest;

class VideoRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string'],
            'url' => ['nullable', 'string'],
        ];

        // Make required fields optional for PATCH
        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['title'] = array_diff($rules['title'], ['required']);
            $rules['title'][] = 'sometimes';
        }

        return $rules;
    }
}
```

**Required Field Logic:**
- `"required": true` → `'required'` validation rule
- `"required"` absent or `false` → `'nullable'` validation rule

---

#### 8. `make:api-resource` - Generate API Resources

```bash
php artisan make:api-resource {name} [--schema=JSON]
```

**Generates:**
- `{Name}Resource.php` - Single resource transformer
- `{Name}Collection.php` - Collection transformer with pagination

**Example:**

```bash
php artisan make:api-resource Video \
  --schema='[{"name":"title","type":"string"},{"name":"user_id","type":"unsignedBigInteger","isForeignKey":true,"foreignTable":"user"}]'
```

**Generated Resource:**

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'user' => new \App\Http\Resources\UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

---

#### 9. `make:api-factory` - Generate Factory

```bash
php artisan make:api-factory {name} [--schema=JSON]
```

**Example:**

```bash
php artisan make:api-factory Video \
  --schema='[{"name":"title","type":"string"},{"name":"duration","type":"integer"}]'
```

**Generates factory with intelligent fake data based on field names and types.**

---

#### 10. `make:api-test` - Generate PEST Tests

```bash
php artisan make:api-test {name} [options]
```

**Options:**
- `--schema=JSON` - Schema for test data
- `--soft-deletes` - Include soft delete tests
- `--multi-tenant` - Include multi-tenancy tests

**Generates comprehensive PEST tests covering:**
- ✅ Listing with pagination
- ✅ Search and filtering
- ✅ Create with validation
- ✅ Read single record
- ✅ Update (full and partial)
- ✅ Delete
- ✅ Batch operations (all CRUD)
- ✅ Soft deletes (if enabled)
- ✅ Multi-tenancy (if enabled)
- ✅ Edge cases and error handling

---

#### 11. `make:api-routes` - Generate Routes

```bash
php artisan make:api-routes {name} [--soft-deletes]
```

**Example:**

```bash
php artisan make:api-routes Video --soft-deletes
```

**Adds to `routes/api.php` in alphabetical order:**

```php
// Video routes
Route::apiResource('videos', VideoController::class);
Route::post('videos/batch/show', [VideoController::class, 'batchShow']);
Route::post('videos/batch', [VideoController::class, 'batchStore']);
Route::patch('videos/batch', [VideoController::class, 'batchUpdate']);
Route::delete('videos/batch', [VideoController::class, 'batchDestroy']);
Route::post('videos/{id}/restore', [VideoController::class, 'restore']);
Route::delete('videos/{id}/force', [VideoController::class, 'forceDelete']);
Route::post('videos/batch/restore', [VideoController::class, 'batchRestore']);
Route::delete('videos/batch/force', [VideoController::class, 'batchForceDelete']);
```

---

### Schema Format

The `--schema` option accepts JSON array of column definitions:

```json
[
  {
    "name": "title",
    "type": "string",
    "required": true,
    "nullable": false,
    "unique": false,
    "default": null
  },
  {
    "name": "user_id",
    "type": "unsignedBigInteger",
    "required": true,
    "isForeignKey": true,
    "foreignTable": "user",
    "onDelete": "cascade",
    "onUpdate": "cascade"
  }
]
```

**Field Types:**

| Type | MySQL | Use For |
|------|-------|---------|
| `string` | VARCHAR(255) | Short text |
| `text` | TEXT | Long text |
| `integer` | INT | Whole numbers |
| `unsignedBigInteger` | BIGINT UNSIGNED | IDs, foreign keys |
| `bigInteger` | BIGINT | Large numbers |
| `boolean` | TINYINT(1) | True/false |
| `decimal` | DECIMAL | Money, precise decimals |
| `float` | FLOAT | Decimals |
| `double` | DOUBLE | Large decimals |
| `date` | DATE | Date only |
| `datetime` | DATETIME | Date and time |
| `timestamp` | TIMESTAMP | Unix timestamp |
| `json` | JSON | JSON data |
| `email` | VARCHAR(255) | Email addresses |

**Field Properties:**

- `name` (required) - Column name
- `type` (required) - Data type
- `required` - If `true`, adds NOT NULL and 'required' validation. If absent/false, adds NULL and 'nullable' validation
- `nullable` - **DEPRECATED**: Use `required` instead
- `unique` - Add unique constraint (single column)
- `default` - Default value
- `isForeignKey` - Mark as foreign key
- `foreignTable` - Related table (singular)
- `onDelete` - Cascade behavior (default: 'cascade')
- `onUpdate` - Update behavior (default: 'cascade')

**Required vs Nullable:**

```json
// Field is REQUIRED (NOT NULL)
{
  "name": "title",
  "type": "string",
  "required": true
}

// Field is NULLABLE (NULL allowed)
{
  "name": "description",
  "type": "text"
  // No 'required' key = nullable
}

// Also nullable
{
  "name": "notes",
  "type": "text",
  "required": false
}
```

---

### Composite Unique Keys

Create unique constraints across multiple columns using the `--composite-unique` option.

**Format:**

```json
[
  ["column1", "column2"],           // First composite unique
  ["column3", "column4", "column5"] // Second composite unique
]
```

**Naming Convention:**

| Column Count | First Composite | Additional Composites |
|--------------|----------------|----------------------|
| 2 columns | `{table}_composite_unique` | `{table}_composite_unique_a`, `_b`, etc. |
| 3+ columns | `{table}_multi_composite_unique` | `{table}_multi_composite_unique_a`, `_b`, etc. |

**Example 1: Multi-Tenant Users (Single Composite)**

```bash
php artisan make:api-bundle User \
  --schema='[
    {"name":"email","type":"string","required":true},
    {"name":"username","type":"string","required":true},
    {"name":"tenant_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"tenant"}
  ]' \
  --composite-unique='[["email","tenant_id"]]' \
  --multi-tenant
```

**Generates:**
```sql
-- Unique constraint name: users_composite_unique
ALTER TABLE users ADD UNIQUE users_composite_unique (email, tenant_id);
```

**Example 2: Multiple Composites**

```bash
php artisan make:api-bundle Product \
  --schema='[
    {"name":"sku","type":"string","required":true},
    {"name":"barcode","type":"string","required":true},
    {"name":"warehouse_id","type":"unsignedBigInteger","required":true}
  ]' \
  --composite-unique='[["sku","warehouse_id"],["barcode","warehouse_id"]]'
```

**Generates:**
```sql
-- First composite: products_composite_unique
ALTER TABLE products ADD UNIQUE products_composite_unique (sku, warehouse_id);

-- Second composite: products_composite_unique_a
ALTER TABLE products ADD UNIQUE products_composite_unique_a (barcode, warehouse_id);
```

**Output:**
```
Migration created: 2024_01_15_120000_create_products_table.php

📋 Composite Unique Keys Created:
   • products_composite_unique: [sku, warehouse_id]
   • products_composite_unique_a: [barcode, warehouse_id]
   ⚠️  Multiple composite unique keys detected - using suffix notation (_a, _b, etc.)
```

**Example 3: Multi-Composite (3+ columns)**

```bash
php artisan make:api-bundle Inventory \
  --schema='[
    {"name":"product_id","type":"unsignedBigInteger","required":true},
    {"name":"warehouse_id","type":"unsignedBigInteger","required":true},
    {"name":"batch_number","type":"string","required":true}
  ]' \
  --composite-unique='[["product_id","warehouse_id","batch_number"]]'
```

**Generates:**
```sql
-- Unique constraint name: inventories_multi_composite_unique
ALTER TABLE inventories ADD UNIQUE inventories_multi_composite_unique 
  (product_id, warehouse_id, batch_number);
```

**Pivot Tables (Automatic):**

Pivot tables automatically get composite unique constraints on their foreign keys:

```bash
php artisan make:api-migration post_tag \
  --schema='[{"name":"post_id","type":"unsignedBigInteger"},{"name":"tag_id","type":"unsignedBigInteger"}]' \
  --pivot=true
```

**Automatically generates:**
```php
$table->primary(['post_id', 'tag_id']);                                    // Composite PK
$table->unique(['post_id', 'tag_id'], 'post_tag_composite_unique');       // Composite Unique
```

**Use Cases:**

1. **Multi-Tenancy**: Same email/username across different tenants
   ```json
   [["email", "tenant_id"]]
   ```

2. **Warehouse Systems**: Same SKU in different warehouses
   ```json
   [["sku", "warehouse_id"]]
   ```

3. **Localization**: Same slug in different languages
   ```json
   [["slug", "language_id"]]
   ```

4. **Versioning**: Unique versions of documents
   ```json
   [["document_id", "version"]]
   ```

**Best Practices:**

- ✅ Order columns by selectivity (most selective first)
- ✅ Use composite uniques instead of multiple single-column uniques when logical relationship exists
- ✅ Consider query patterns when ordering columns
- ⚠️ Multiple composites on one table get suffix notation (_a, _b)

---

### Examples

#### Example 1: Blog Post with Comments and Relationships

```bash
# Create User (if not exists)
php artisan make:api-bundle User \
  --schema='[
    {"name":"name","type":"string","required":true},
    {"name":"email","type":"string","required":true,"unique":true},
    {"name":"password","type":"string","required":true}
  ]'

# Create Post model
php artisan make:api-bundle Post \
  --schema='[
    {"name":"title","type":"string","required":true},
    {"name":"slug","type":"string","required":true,"unique":true},
    {"name":"content","type":"text","required":true},
    {"name":"published_at","type":"datetime"},
    {"name":"user_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"user"}
  ]' \
  --soft-deletes

# Create Comment model
php artisan make:api-bundle Comment \
  --schema='[
    {"name":"content","type":"text","required":true},
    {"name":"post_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"post"},
    {"name":"user_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"user"}
  ]' \
  --soft-deletes
```

**Then add inverse relationships manually:**

```php
// app/Models/User.php
public function posts()
{
    return $this->hasMany(Post::class);
}

public function comments()
{
    return $this->hasMany(Comment::class);
}

// app/Models/Post.php
public function comments()
{
    return $this->hasMany(Comment::class);
}
```

---

#### Example 2: E-commerce Products with Tags (Many-to-Many)

```bash
# Step 1: Create Product
php artisan make:api-bundle Product \
  --schema='[
    {"name":"name","type":"string","required":true},
    {"name":"sku","type":"string","required":true,"unique":true},
    {"name":"price","type":"decimal","required":true},
    {"name":"stock","type":"integer","default":0},
    {"name":"is_active","type":"boolean","default":true}
  ]' \
  --soft-deletes

# Step 2: Create Tag
php artisan make:api-bundle Tag \
  --schema='[
    {"name":"name","type":"string","required":true,"unique":true},
    {"name":"slug","type":"string","required":true,"unique":true}
  ]'

# Step 3: Create Pivot (note: alphabetical order!)
php artisan make:api-migration product_tag \
  --schema='[
    {"name":"product_id","type":"unsignedBigInteger"},
    {"name":"tag_id","type":"unsignedBigInteger"}
  ]' \
  --pivot=true

# Step 4: Run migrations
php artisan migrate
```

**Add Many-to-Many relationships:**

```php
// app/Models/Product.php
public function tags()
{
    return $this->belongsToMany(Tag::class, 'product_tag');
}

// app/Models/Tag.php
public function products()
{
    return $this->belongsToMany(Product::class, 'product_tag');
}
```

---

#### Example 3: Multi-Tenant SaaS Application

```bash
# Create Tenant
php artisan make:api-bundle Tenant \
  --schema='[
    {"name":"name","type":"string","required":true},
    {"name":"domain","type":"string","required":true,"unique":true},
    {"name":"is_active","type":"boolean","default":true}
  ]'

# Create multi-tenant Project with composite unique
php artisan make:api-bundle Project \
  --schema='[
    {"name":"name","type":"string","required":true},
    {"name":"slug","type":"string","required":true},
    {"name":"description","type":"text"},
    {"name":"status","type":"string","required":true,"default":"active"}
  ]' \
  --composite-unique='[["slug","tenant_id"]]' \
  --multi-tenant \
  --soft-deletes
```

**This creates:**
- `tenant_id` column automatically
- Composite unique on `(slug, tenant_id)` - same slug across different tenants
- Multi-tenant trait for automatic scoping

---

#### Example 4: Warehouse Inventory System

```bash
# Create Warehouse
php artisan make:api-bundle Warehouse \
  --schema='[
    {"name":"name","type":"string","required":true},
    {"name":"code","type":"string","required":true,"unique":true},
    {"name":"location","type":"string","required":true}
  ]'

# Create Product with multiple composite uniques
php artisan make:api-bundle Product \
  --schema='[
    {"name":"name","type":"string","required":true},
    {"name":"sku","type":"string","required":true},
    {"name":"barcode","type":"string","required":true},
    {"name":"description","type":"text"}
  ]'

# Create Inventory with multi-composite unique
php artisan make:api-bundle Inventory \
  --schema='[
    {"name":"product_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"product"},
    {"name":"warehouse_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"warehouse"},
    {"name":"quantity","type":"integer","default":0},
    {"name":"batch_number","type":"string","required":true},
    {"name":"expiry_date","type":"date"}
  ]' \
  --composite-unique='[["product_id","warehouse_id","batch_number"]]'
```

**This ensures:**
- Each product can exist in multiple warehouses
- Each product in a warehouse can have multiple batches
- No duplicate product+warehouse+batch combinations

---

## 📁 Generated Files

After running `make:api-bundle Video`, you'll have:

```
app/
├── Models/
│   └── Video.php
├── Repositories/
│   ├── Interfaces/
│   │   └── VideoRepositoryInterface.php
│   └── EloquentVideoRepository.php
├── Services/
│   └── VideoService.php
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── VideoController.php
│   ├── Requests/
│   │   └── VideoRequest.php
│   └── Resources/
│       ├── VideoResource.php
│       └── VideoCollection.php
database/
├── factories/
│   └── VideoFactory.php
└── migrations/
    └── YYYY_MM_DD_HHMMSS_create_videos_table.php
tests/
└── Feature/
    └── VideoTest.php
routes/
└── api.php (updated)
```

---

## 🧪 Testing

### Run Package Tests

```bash
./vendor/bin/pest
```

### Run Generated Model Tests

After generating a bundle:

```bash
php artisan test
# or
./vendor/bin/pest
```

### Test Structure

Generated tests cover:

```php
describe('Video Listing', function () {
    test('can list videos');
    test('returns paginated videos');
    test('can search videos');
});

describe('Video Creation', function () {
    test('can create video');
    test('validates required fields');
    test('returns proper error for invalid data types');
});

describe('Video Retrieval', function () {
    test('can show single video');
    test('returns 404 when video not found');
});

describe('Video Update', function () {
    test('can update video');
    test('can partially update video');
    test('returns 404 when updating non-existent video');
});

describe('Video Deletion', function () {
    test('can delete video');
    test('returns 404 when deleting non-existent video');
});

describe('Video Batch Operations', function () {
    test('can batch show videos');
    test('can batch create videos');
    test('validates each record in batch create');
    test('can batch update videos');
    test('can batch delete videos');
});
```

---

## 🚀 Advanced Features

### Batch Operations

All generated APIs support batch operations out of the box:

#### Batch Show (Get Multiple by IDs)

```bash
POST /api/videos/batch/show
Content-Type: application/json

{
  "ids": [1, 2, 3, 4, 5]
}
```

**Response:**
```json
{
  "data": [
    {"id": 1, "title": "Video 1"},
    {"id": 2, "title": "Video 2"},
    {"id": 3, "title": "Video 3"}
  ],
  "count": 3
}
```

#### Batch Create

```bash
POST /api/videos/batch
Content-Type: application/json

{
  "records": [
    {"title": "Video 1", "duration": 120},
    {"title": "Video 2", "duration": 180},
    {"title": "Video 3", "duration": 90}
  ]
}
```

**Response:**
```json
{
  "data": [
    {"id": 1, "title": "Video 1", "duration": 120},
    {"id": 2, "title": "Video 2", "duration": 180},
    {"id": 3, "title": "Video 3", "duration": 90}
  ],
  "count": 3,
  "message": "3 resources created successfully."
}
```

#### Batch Update

```bash
PATCH /api/videos/batch
Content-Type: application/json

{
  "records": [
    {"id": 1, "title": "Updated Video 1"},
    {"id": 2, "duration": 200},
    {"id": 3, "title": "Updated Video 3", "duration": 150}
  ]
}
```

#### Batch Delete

```bash
DELETE /api/videos/batch
Content-Type: application/json

{
  "ids": [1, 2, 3]
}
```

---

### Soft Deletes

When using `--soft-deletes`, additional endpoints are generated:

#### Restore Single Record

```bash
POST /api/videos/123/restore
```

#### Force Delete Single Record

```bash
DELETE /api/videos/123/force
```

#### Batch Restore

```bash
POST /api/videos/batch/restore
Content-Type: application/json

{
  "ids": [1, 2, 3]
}
```

#### Batch Force Delete

```bash
DELETE /api/videos/batch/force
Content-Type: application/json

{
  "ids": [1, 2, 3]
}
```

---

### Multi-Tenancy

When using `--multi-tenant`, the generated model automatically:

1. ✅ Adds `tenant_id` to migration
2. ✅ Includes `MultiTenantTrait` in model
3. ✅ Automatically scopes all queries to current user's tenant
4. ✅ Auto-populates `tenant_id` on create

```php
// Automatically scoped to current tenant
$videos = Video::all();

// Get records for all tenants (admin only)
$videos = Video::allTenants()->get();

// Get records for specific tenant
$videos = Video::forTenant(5)->get();
```

---

### Search & Filtering

All `index()` endpoints support search:

```bash
GET /api/videos?search=Laravel
GET /api/videos?filter[title]=Tutorial
```

---

### Pagination

```bash
GET /api/videos?per_page=25&page=2
```

**Response:**
```json
{
  "data": [ /* ... */ ],
  "meta": {
    "total": 100,
    "count": 25,
    "per_page": 25,
    "current_page": 2,
    "total_pages": 4
  },
  "links": {
    "first": "http://api.example.com/videos?page=1",
    "last": "http://api.example.com/videos?page=4",
    "prev": "http://api.example.com/videos?page=1",
    "next": "http://api.example.com/videos?page=3"
  }
}
```

---

## 🔧 Troubleshooting

### Issue: "Class not found" errors after generation

**Solution:** Make sure to bind repository interfaces in your service provider:

```php
// app/Providers/RepositoryServiceProvider.php
$this->app->bind(
    \App\Repositories\Interfaces\VideoRepositoryInterface::class,
    \App\Repositories\EloquentVideoRepository::class
);
```

### Issue: Foreign key validation fails

**Problem:** `Foreign key 'user_id' references table/model 'user' which does not exist.`

**Solution:** Create the referenced model first:

```bash
# Create User model first
php artisan make:api-bundle User --schema='[...]'

# Then create Video with user_id foreign key
php artisan make:api-bundle Video --schema='[...,{"name":"user_id","type":"unsignedBigInteger","isForeignKey":true,"foreignTable":"user"}]'
```

### Issue: Pivot table creation fails

**Problem:** Wrong order or trying to use `make:api-bundle`

**Solution:**

```bash
# WRONG
php artisan make:api-bundle tag_post --pivot=true

# CORRECT - alphabetical order + use make:api-migration
php artisan make:api-migration post_tag \
  --schema='[{"name":"post_id","type":"unsignedBigInteger"},{"name":"tag_id","type":"unsignedBigInteger"}]' \
  --pivot=true
```

### Issue: Routes not working

**Solution:** Clear route cache:

```bash
php artisan route:clear
php artisan route:cache
```

### Issue: Validation not working

**Solution:** Check that your FormRequest's `authorize()` method returns `true`:

```php
public function authorize(): bool
{
    return true; // or your authorization logic
}
```

### Issue: Tests failing

**Solution:** Run migrations in test environment:

```bash
php artisan migrate --env=testing
```

### Issue: Composite unique constraint not created

**Solution:** Ensure you're passing valid JSON array:

```bash
# WRONG - single array
--composite-unique='["email","tenant_id"]'

# CORRECT - array of arrays
--composite-unique='[["email","tenant_id"]]'
```

---

## 🤝 Contributing

Contributions are welcome! Please follow our coding standards and include tests.

---

## 📄 License

MIT License - see LICENSE file for details.

---

## 🆘 Support

For issues, questions, or feature requests, please open an issue on GitHub.

---

**Happy Coding! 🚀**
```