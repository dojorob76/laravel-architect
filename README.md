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
        'contracts' => 'app/Contracts',
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
        'contracts' => 'App\Contracts',
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

### Repository Drivers

Laravel Architect supports three repository drivers: **Eloquent**, **MySQL**, and **PostgreSQL**. You can configure different drivers for different operation types.

#### Driver Configuration

Add a `drivers` section to your config to set defaults for each operation type:

```php
// config/api_scaffolding.php
'drivers' => [
    'crud' => 'eloquent',           // eloquent | mysql | postgres
    'bulk' => 'eloquent',           // eloquent | mysql | postgres
    'soft_delete' => 'eloquent',    // eloquent | mysql | postgres
    'bulk_soft_delete' => 'eloquent', // eloquent | mysql | postgres
],
```

#### CLI Driver Options

Override config defaults per-command using CLI options:

```bash
# Use config defaults
php artisan make:api-repository Video

# Override specific drivers
php artisan make:api-repository Video \
    --crud-driver=eloquent \
    --bulk-driver=postgres \
    --soft-delete-driver=mysql

# Generate all three driver implementations for an operation type
php artisan make:api-repository Video --crud-driver=all
```

#### Generated Repositories

Depending on flags, the following repositories are generated:

| Flag Combination | Generated Repositories |
|------------------|------------------------|
| (default) | CRUD + Bulk |
| `--soft-deletes` | CRUD + Bulk + SoftDelete + BulkSoftDelete |
| `--no-bulk` | CRUD only |
| `--no-bulk --soft-deletes` | CRUD + SoftDelete |

#### Service Provider Bindings

After generating repositories, bind interfaces to implementations in your `RepositoryServiceProvider`:

```php
// app/Providers/RepositoryServiceProvider.php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Interfaces
use App\Contracts\VideoRepositoryInterface;
use App\Contracts\VideoBulkRepositoryInterface;
use App\Contracts\VideoSoftDeleteRepositoryInterface;
use App\Contracts\VideoBulkSoftDeleteRepositoryInterface;

// Implementations (use whichever driver you generated)
use App\Repositories\EloquentVideoRepository;
use App\Repositories\PostgresVideoBulkRepository;
use App\Repositories\EloquentVideoSoftDeleteRepository;
use App\Repositories\PostgresVideoBulkSoftDeleteRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // CRUD - using Eloquent
        $this->app->bind(
            VideoRepositoryInterface::class,
            EloquentVideoRepository::class
        );

        // Bulk - using PostgreSQL for performance
        $this->app->bind(
            VideoBulkRepositoryInterface::class,
            PostgresVideoBulkRepository::class
        );

        // Soft Delete - using Eloquent
        $this->app->bind(
            VideoSoftDeleteRepositoryInterface::class,
            EloquentVideoSoftDeleteRepository::class
        );

        // Bulk Soft Delete - using PostgreSQL
        $this->app->bind(
            VideoBulkSoftDeleteRepositoryInterface::class,
            PostgresVideoBulkSoftDeleteRepository::class
        );
    }
}
```

> **Tip:** The `make:api-repository` command outputs binding instructions after generation. Copy these directly into your service provider.

#### When to Use Each Driver

| Driver | Best For |
|--------|----------|
| **Eloquent** | Standard CRUD operations, when you need model events/observers, accessors/mutators |
| **MySQL** | Raw SQL performance, bulk operations, when you don't need Eloquent features |
| **PostgreSQL** | Same as MySQL, plus PostgreSQL-specific features (RETURNING, ILIKE, etc.) |

---

## 📏 Coding Standards

Laravel Architect enforces strict coding conventions by default, basing these largely on Laravel standards.
Overrides can be set, and will be honored, from within the config file, but - if you leave the defaults - this will be what you get out of the box:

### DEFAULT Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| **Model Names** | Singular, StudlyCase | `Video`, `BlogPost`, `User` |
| **Table Names** | Plural, snake_case | `videos`, `blog_posts`, `users` |
| **Pivot Tables** | Singular, alphabetical, snake_case | `post_tag`, `role_user`, `tag_video` |
| **Foreign Keys** | `{singular_table}_id` | `user_id`, `blog_post_id`, `tag_id` |
| **Primary Keys** | Always `id` (auto-increment integer) | `id` |
| **Relationships** | Singular (belongsTo) or Plural (hasMany/belongsToMany) | `user()`, `videos()`, `tags()` |

### DEFAULT Relationship Naming Rules

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

### DEFAULT Database Rules

1. ✅ **Primary Keys**: Always `id` (integer, auto-increment)
2. ✅ **Pivot Tables**:
    - No `id` column
    - Composite primary key from foreign keys
    - Composite unique key (automatically added)
    - No timestamps
    - Named alphabetically: `post_tag` not `tag_post`
3. ✅ **Foreign Keys**:
    - `{snake_case_singular_model_name}_id`
    - Referenced table/model MUST exist before creation
    - Automatic validation on generation
    - Includes constraints with cascade behavior which can be sent through as arguments
    - If no arguments are provided and the column is nullable, `onDelete('set null')` will be set, otherwise we'll set `onDelete('cascade')`
4. ✅ **Timestamps**:
    - Use `useCurrent()` and `useCurrentOnUpdate()`
    - All tables except pivots have `created_at` and `updated_at`
5. ✅ **Migrations**:
    - All check whether `Schema::hasTable()` before creating
    - All use `Schema::dropIfExists()` in `down()` method
6. ✅ **Required vs Nullable**:
    - If `"required": true` in schema → field is required (NOT NULL)
    - If `"required"` is absent or false → field is nullable (NULL allowed with `->nullable()` flag set on migration column)

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
4. ✅ **Validation**:
    - Validation rules live in the Model where they can be accessed from anywhere within your application (think seeders or module services) instead of locked behind a HTTP call via Form Request
    - Form Requests simply invoke the static methods of the model validation rules with functions that allow for validation preparation and context retrieval

### Code Quality

- ✅ Modern PHP 8.4+ features (constructor property promotion, typed properties, etc.)
- ✅ Laravel 12+ best practices
- ✅ Full type hints on all methods
- ✅ DocBlocks for public methods
- ✅ Comprehensive PEST or phpUnit tests (or both, your call) for all functionality

---

## 🚀 Usage

### Quick Start

[[ DELETED ]]

**Best Practices:**

- ✅ Order columns by selectivity (most selective first)
- ✅ Use composite uniques instead of multiple single-column uniques when logical relationship exists
- ✅ Consider query patterns when ordering columns
- ⚠️ Multiple composites on one table get suffix notation (_a, _b)

---

### Examples

[[ DELETED ]]

## 📁 Generated Files

After running `make:api-bundle Video`, (if you keep the defaults in the config file) you'll have:

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

NOTE: You are able to set the directory paths for each of these from within the config file, and that is where each class will be placed.

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

[[DELETED]]

---

### Soft Deletes

When using `--soft-deletes`, additional endpoints are generated:

[[NEEDS EXPANSION - This should also set up the SoftDelete REPO(S)/INTERFACE(S)]]

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
php artisan make:api-bundle Video --schema=[[DELETED]]
```

### Issue: Pivot table creation fails

[[DELETED]]

### Issue: Routes not working

**Solution:** Clear route cache:

```bash
php artisan route:clear
php artisan route:cache
```

### Issue: Validation not working

[[DELETED]]

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
--composite-unique='["sku","warehouse_id"]'

# CORRECT - array of arrays
--composite-unique='[["sku","warehouse_id"]]'
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