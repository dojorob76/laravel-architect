# AI Agent Instructions for Laravel Architect

> **Instructions for AI assistants helping developers use Laravel Architect**

---

## 🎯 Purpose

This document provides AI agents (like ChatGPT, Claude, GitHub Copilot, etc.) with instructions on how to help developers generate Laravel API resources using the Laravel Architect package.

---

## 📋 Core Concepts

### What is Laravel Architect?

Laravel Architect is a code generator that creates production-ready RESTful APIs following the Repository Pattern. It generates:

- Models with relationships
- Migrations with foreign keys and composite unique constraints
- Controllers with CRUD + batch operations
- Services (business logic)
- Repositories (data access)
- Form Requests (validation)
- API Resources (transformers)
- Factories (test data)
- PEST tests (comprehensive)
- API routes

---

## 🎨 Coding Standards (CRITICAL)

**You MUST enforce these rules when generating schemas:**

### 1. Naming Conventions

```
✅ CORRECT:
- Model: Video (singular, StudlyCase)
- Table: videos (plural, snake_case)
- Foreign Key: user_id (singular_table_id)
- Pivot: post_tag (alphabetical order, singular parts)
- Relationship (belongsTo): user() (singular table name)
- Relationship (hasMany): videos() (plural table name)
- Relationship (belongsToMany): tags() (plural table name)

❌ WRONG:
- Model: Videos (plural)
- Table: Video (singular)
- Foreign Key: userId (camelCase)
- Pivot: tag_post (wrong alphabetical order)
- Relationship: userVideo() (NO!)
- Relationship: userVideos() (NO!)
- Relationship: video() (wrong for hasMany)
```

### 2. Relationship Naming Rules (CRITICAL)

**BelongsTo Relationships:**
```php
// user_id foreign key
✅ CORRECT: public function user() { }
❌ WRONG: public function userVideo() { }
❌ WRONG: public function userVideos() { }
```

**HasMany Relationships:**
```php
// User model (has many videos)
✅ CORRECT: public function videos() { }
❌ WRONG: public function video() { }
```

**BelongsToMany Relationships:**
```php
// Post model (has many tags via pivot)
✅ CORRECT: public function tags() { }
❌ WRONG: public function tag() { }

// Tag model (has many posts via pivot)
✅ CORRECT: public function posts() { }
❌ WRONG: public function post() { }
```

### 3. Database Rules

```
✅ Primary Keys: Always 'id' (auto-increment integer)
✅ Foreign Keys: Always '{table_name}_id' format
✅ Pivot Tables:
   - Use make:api-migration --pivot=true (NOT make:api-bundle)
   - NO 'id' column
   - Composite primary key from foreign keys
   - Composite unique key (automatically added)
   - NO timestamps
   - Alphabetical naming: post_tag not tag_post
   - Both foreign tables MUST exist first
✅ Timestamps: created_at and updated_at on all non-pivot tables
✅ Required Fields: If "required":true then NOT NULL, else NULL
```

### 4. Required Field Logic

```json
// Field is REQUIRED (NOT NULL in DB)
{
  "name": "title",
  "type": "string",
  "required": true
}

// Field is NULLABLE (NULL allowed in DB)
{
  "name": "description",
  "type": "text"
  // No 'required' key = nullable
}
```

### 5. Foreign Key Validation

```
✅ Foreign key MUST end with '_id'
✅ Referenced table/model MUST exist before creating FK
✅ System validates this automatically
✅ Will fail with clear error if model doesn't exist
```

### 6. Field Type Selection

```php
// String data
"type": "string"        // Short text (< 255 chars)
"type": "text"          // Long text
"type": "email"         // Email addresses

// Numbers
"type": "integer"       // Whole numbers
"type": "unsignedBigInteger"  // IDs, foreign keys
"type": "decimal"       // Money, precise decimals
"type": "float"         // General decimals

// Dates
"type": "date"          // Date only
"type": "datetime"      // Date and time
"type": "timestamp"     // Unix timestamp

// Other
"type": "boolean"       // True/false
"type": "json"          // JSON data
```

---

## 🤖 How to Help Users

### Step 1: Understand the Requirement

When a user says they want to create a resource, ask clarifying questions:

```
User: "I need to create a blog post API"

You should ask:
1. What fields does a blog post have?
2. Does it belong to a user?
3. Should it support soft deletes?
4. Should posts have tags or categories?
5. Do any fields need composite unique constraints?
```

### Step 2: Design the Schema

Based on their answers, create a properly formatted schema:

```json
[
  {
    "name": "title",
    "type": "string",
    "required": true
  },
  {
    "name": "slug",
    "type": "string",
    "required": true,
    "unique": true
  },
  {
    "name": "content",
    "type": "text",
    "required": true
  },
  {
    "name": "published_at",
    "type": "datetime"
  },
  {
    "name": "user_id",
    "type": "unsignedBigInteger",
    "required": true,
    "isForeignKey": true,
    "foreignTable": "user"
  }
]
```

### Step 3: Generate the Command

Provide the complete command:

```bash
php artisan make:api-bundle Post \
  --schema='[{"name":"title","type":"string","required":true},{"name":"slug","type":"string","required":true,"unique":true},{"name":"content","type":"text","required":true},{"name":"published_at","type":"datetime"},{"name":"user_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"user"}]' \
  --soft-deletes
```

### Step 4: Provide Next Steps

Always remind them of post-generation steps:

```markdown
After running this command:

1. Add repository binding to `app/Providers/RepositoryServiceProvider.php`:
   ```php
   $this->app->bind(
       \App\Repositories\Interfaces\PostRepositoryInterface::class,
       \App\Repositories\EloquentPostRepository::class
   );
   ```

2. Run migration:
   ```bash
   php artisan migrate
   ```

3. Test the API:
   ```bash
   php artisan test --filter PostTest
   ```
```

---

## 📝 Common Scenarios

### Scenario 1: Simple CRUD Resource

**User Request:**
> "Create a Category API with name and description"

**Your Response:**

```bash
php artisan make:api-bundle Category \
  --schema='[{"name":"name","type":"string","required":true,"unique":true},{"name":"description","type":"text"}]'
```

**Note the changes:**
- `description` has no `"required":true` so it's nullable
- `name` is required and unique

---

### Scenario 2: Resource with Relationships

**User Request:**
> "Create a Comment API. Comments belong to posts and users"

**Your Response:**

First, verify the dependencies:

```bash
# Ensure Post and User models exist first
# If they don't, create them:
php artisan make:api-bundle User --schema='[...]'
php artisan make:api-bundle Post --schema='[...]'

# Then create Comment
php artisan make:api-bundle Comment \
  --schema='[{"name":"content","type":"text","required":true},{"name":"post_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"post"},{"name":"user_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"user"}]' \
  --soft-deletes
```

**Then explain the relationships:**

```php
// Generated in Comment model:
public function post()  // belongsTo - singular
{
    return $this->belongsTo(Post::class);
}

public function user()  // belongsTo - singular
{
    return $this->belongsTo(User::class);
}

// You need to manually add to Post model:
public function comments()  // hasMany - plural
{
    return $this->hasMany(Comment::class);
}

// And to User model:
public function comments()  // hasMany - plural
{
    return $this->hasMany(Comment::class);
}
```

---

### Scenario 3: Pivot Table (Many-to-Many)

**User Request:**
> "Create a many-to-many relationship between Products and Tags"

**Your Response:**

**CRITICAL: Do NOT use `make:api-bundle` for pivot tables!**

```bash
# Step 1: Ensure both main models exist
php artisan make:api-bundle Product \
  --schema='[{"name":"name","type":"string","required":true},{"name":"price","type":"decimal","required":true}]'

php artisan make:api-bundle Tag \
  --schema='[{"name":"name","type":"string","required":true,"unique":true}]'

# Step 2: Create pivot migration ONLY (note: alphabetical order!)
php artisan make:api-migration product_tag \
  --schema='[{"name":"product_id","type":"unsignedBigInteger"},{"name":"tag_id","type":"unsignedBigInteger"}]' \
  --pivot=true

# Step 3: Run migrations
php artisan migrate
```

**Then instruct them to add relationships:**

```php
// app/Models/Product.php
public function tags()  // belongsToMany - plural
{
    return $this->belongsToMany(Tag::class, 'product_tag');
}

// app/Models/Tag.php
public function products()  // belongsToMany - plural
{
    return $this->belongsToMany(Product::class, 'product_tag');
}
```

**What gets generated:**
```php
// Migration creates:
$table->primary(['product_id', 'tag_id']);  // Composite PK
$table->unique(['product_id', 'tag_id'], 'product_tag_composite_unique');  // Composite Unique
```

---

### Scenario 4: Warehouse Inventory with Multiple Composites

**User Request:**
> "Create inventory where the same product can exist in different warehouses, but SKU+warehouse and barcode+warehouse must be unique"

**Your Response:**

```bash
# Ensure dependencies exist
php artisan make:api-bundle Warehouse \
  --schema='[{"name":"name","type":"string","required":true},{"name":"code","type":"string","required":true,"unique":true}]'

php artisan make:api-bundle Product \
  --schema='[{"name":"name","type":"string","required":true},{"name":"sku","type":"string","required":true},{"name":"barcode","type":"string","required":true}]'

# Create Inventory with composite uniques
php artisan make:api-bundle Inventory \
  --schema='[{"name":"product_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"product"},{"name":"warehouse_id","type":"unsignedBigInteger","required":true,"isForeignKey":true,"foreignTable":"warehouse"},{"name":"quantity","type":"integer","default":0}]' \
  --composite-unique='[["product_id","warehouse_id"]]'
```

**Output:**
```
📋 Composite Unique Keys Created:
   • inventories_composite_unique: [product_id, warehouse_id]
```

---

## 🔑 Composite Unique Keys

### When to Use Composite Unique

**Use Cases:**

1. **Warehouse inventory**: `["sku", "warehouse_id"]` - same SKU in different warehouses
2. **Localized content**: `["slug", "language_id"]` - same slug in different languages
3. **Versioned data**: `["document_id", "version"]` - unique versions of documents

### Format

```json
// Single composite unique
[["column1", "column2"]]

// Multiple composite uniques
[["sku", "warehouse_id"], ["barcode", "warehouse_id"]]

// Multi-composite (3+ columns)
[["product_id", "warehouse_id", "batch_number"]]
```

### Naming Convention

| Columns | First Composite | Additional |
|---------|----------------|------------|
| 2 cols | `{table}_composite_unique` | `_composite_unique_a`, `_b` |
| 3+ cols | `{table}_multi_composite_unique` | `_multi_composite_unique_a`, `_b` |

### Example Commands

```bash
# Single composite
php artisan make:api-bundle Product \
  --schema='[...]' \
  --composite-unique='[["sku","warehouse_id"]]'

# Multiple composites
php artisan make:api-bundle Product \
  --schema='[...]' \
  --composite-unique='[["sku","warehouse_id"],["barcode","warehouse_id"]]'

# Multi-composite (3+ columns)
php artisan make:api-bundle Inventory \
  --schema='[...]' \
  --composite-unique='[["product_id","warehouse_id","batch_number"]]'
```

### Teaching About Composites

**Explain the difference:**

```markdown
Single Column Unique:
- Each SKU must be globally unique
- sku: ABC123 (can only exist once)

Composite Unique:
- Each combination must be unique
- (ABC123, warehouse_1) ✅
- (ABC123, warehouse_2) ✅
- (ABC123, warehouse_1) ❌ Duplicate!
```

---

## ⚠️ Common Mistakes to Avoid

### ❌ WRONG: Plural Model Names

```bash
# WRONG
php artisan make:api-bundle Videos
```

```bash
# CORRECT
php artisan make:api-bundle Video
```

---

### ❌ WRONG: CamelCase Foreign Keys

```json
{
  "name": "userId",  // WRONG
  "type": "unsignedBigInteger"
}
```

```json
{
  "name": "user_id",  // CORRECT
  "type": "unsignedBigInteger",
  "isForeignKey": true,
  "foreignTable": "user"
}
```

---

### ❌ WRONG: Wrong Relationship Method Names

```php
// WRONG
public function userVideo() { }
public function userVideos() { }
public function video() { }  // for hasMany
```

```php
// CORRECT
public function user() { }      // belongsTo
public function videos() { }    // hasMany
public function tags() { }      // belongsToMany
```

---

### ❌ WRONG: Wrong Pivot Table Naming

```bash
# WRONG - not alphabetical
php artisan make:api-migration video_tag --pivot=true

# CORRECT - alphabetical order
php artisan make:api-migration tag_video --pivot=true
```

---

### ❌ WRONG: Using make:api-bundle for Pivots

```bash
# WRONG
php artisan make:api-bundle post_tag --pivot=true
```

```bash
# CORRECT
php artisan make:api-migration post_tag \
  --schema='[...]' \
  --pivot=true
```

---

### ❌ WRONG: Creating Pivot Before Main Models

```bash
# WRONG - Post and Tag don't exist yet
php artisan make:api-migration post_tag --pivot=true
```

```bash
# CORRECT - Create models first
php artisan make:api-bundle Post --schema='[...]'
php artisan make:api-bundle Tag --schema='[...]'
php artisan make:api-migration post_tag --schema='[...]' --pivot=true
```

---

### ❌ WRONG: Invalid Foreign Key Names

```json
// WRONG - doesn't match any table
{
  "name": "author_id",
  "type": "unsignedBigInteger"
}
// System will fail: "table 'author' does not exist"
```

```json
// CORRECT - 'user' table/model exists
{
  "name": "user_id",
  "type": "unsignedBigInteger",
  "isForeignKey": true,
  "foreignTable": "user"
}
```

---

### ❌ WRONG: Assuming Fields are Required

```json
// WRONG - This field will be NULLABLE
{
  "name": "title",
  "type": "string"
}
```

```json
// CORRECT - Explicitly mark as required
{
  "name": "title",
  "type": "string",
  "required": true
}
```

---

### ❌ WRONG: Composite Unique Format

```bash
# WRONG - single array
--composite-unique='["sku","warehouse_id"]'

# CORRECT - array of arrays
--composite-unique='[["sku","warehouse_id"]]'
```

---

### ❌ WRONG: ID in Pivot Table

```json
// WRONG - pivot tables should NOT have 'id'
[
  {"name": "id", "type": "unsignedBigInteger"},
  {"name": "post_id", "type": "unsignedBigInteger"},
  {"name": "tag_id", "type": "unsignedBigInteger"}
]
```

```json
// CORRECT - no 'id' field
[
  {"name": "post_id", "type": "unsignedBigInteger"},
  {"name": "tag_id", "type": "unsignedBigInteger"}
]
```

---

### ❌ WRONG: Using Integer for IDs

```json
{
  "name": "user_id",
  "type": "integer"  // WRONG - too small
}
```

```json
{
  "name": "user_id",
  "type": "unsignedBigInteger"  // CORRECT
}
```

---

## 🎓 Teaching Users

When helping users, educate them on:

1. **Repository Pattern Benefits:**
    - Separation of concerns
    - Easy to test
    - Can swap data sources (Eloquent → raw SQL)

2. **Batch Operations:**
    - All APIs support bulk operations
    - More efficient than multiple requests
    - Wrapped in transactions

3. **Soft Deletes:**
    - Data isn't permanently deleted
    - Can be restored
    - Good for audit trails

4. **API Resources:**
    - Transform data consistently
    - Hide sensitive fields
    - Include related data with `whenLoaded()`

5. **Composite Unique Keys:**
    - Different from single unique
    - Allows same value in different contexts
    - Automatically indexed for performance

6. **Relationship Naming:**
    - Singular for belongsTo
    - Plural for hasMany and belongsToMany
    - Based on related table name, not field name

---

## 📊 Schema Field Reference

### Required Fields

```json
{
  "name": "field_name",     // REQUIRED - column name
  "type": "string"          // REQUIRED - data type
}
```

### Optional Fields

```json
{
  "required": true,         // If true: NOT NULL + 'required' validation
                            // If false/absent: NULL + 'nullable' validation
  "unique": true,           // Single-column unique constraint
  "default": "value",       // Default value
  "isForeignKey": true,     // Mark as FK
  "foreignTable": "user",   // Related table (singular)
  "onDelete": "cascade",    // Delete behavior
  "onUpdate": "cascade"     // Update behavior
}
```

**Composite Unique** (separate option):
```bash
--composite-unique='[["col1","col2"],["col3","col4","col5"]]'
```

---

## 🔄 Typical Workflow

1. **Design Schema** - Understand requirements
2. **Generate Main Models** - Run `make:api-bundle` for each resource
3. **Generate Pivots** - Use `make:api-migration --pivot=true` for many-to-many
4. **Add Manual Relationships** - Add inverse relationships (hasMany, etc.) to models
5. **Bind Repositories** - Register in service provider
6. **Run Migrations** - `php artisan migrate`
7. **Test** - Run PEST tests
8. **Use API** - Start making requests

---

## 🚨 Critical Reminders

**Always remind users to:**

1. ✅ Bind repository interfaces in service provider
2. ✅ Run migrations after generation
3. ✅ Add inverse relationships manually if needed (hasMany, belongsToMany)
4. ✅ Test the generated code
5. ✅ Review validation rules for their specific needs
6. ✅ Verify foreign key tables exist before creating FKs
7. ✅ Use alphabetical order for pivot table names
8. ✅ Use `make:api-migration` (not `make:api-bundle`) for pivots
9. ✅ Mark fields as `"required":true` explicitly if they should be NOT NULL

---

## 🚨 Critical Validation Rules

**The system automatically validates:**

1. ✅ Foreign key ends with `_id`
2. ✅ Referenced table/model exists
3. ✅ Pivot table name is alphabetical
4. ✅ Pivot created with exactly 2 foreign keys
5. ✅ Composite unique columns exist in schema

**Will FAIL with clear error messages if:**
- FK doesn't end with `_id`
- Referenced model doesn't exist
- Pivot has wrong number of FKs
- Pivot name not alphabetical

---

## 📖 Full Command Reference

```bash
# Complete bundle
php artisan make:api-bundle {Name} [--schema=JSON] [--soft-deletes] [--composite-unique=JSON] [--driver=eloquent|mysql|postgres|all]

# Individual components
php artisan make:api-model {Name} [--schema=JSON] [--pivot=true] [--soft-deletes]
php artisan make:api-migration {Name} [--schema=JSON] [--pivot=true] [--soft-deletes] [--composite-unique=JSON]
php artisan make:api-repository {Name} [--schema=JSON] [--driver=eloquent|mysql|postgres|all]
php artisan make:api-service {Name}
php artisan make:api-controller {Name}
php artisan make:api-request {Name} [--schema=JSON]
php artisan make:api-resource {Name} [--schema=JSON]
php artisan make:api-factory {Name} [--schema=JSON]
php artisan make:api-test {Name} [--schema=JSON] [--soft-deletes]
php artisan make:api-routes {Name} [--soft-deletes]
```

---

## 🐳 Docker Environment Context (For AI Assistants)

### What the Docker Setup Is

The Laravel Architect package includes a Docker environment (`docker-compose.yml`, `docker.sh`, `DOCKER.md`) for **package development and testing only**.

### Critical Understanding for AI Assistants

**When helping users, you need to distinguish between two contexts:**

#### Context 1: Package Development (Docker IS relevant)
User is **contributing to Laravel Architect** itself:
```
User: "How do I test my changes to the package?"
AI: "Use the Docker setup! Run ./docker.sh test-all to test against all databases"
```

#### Context 2: Package Usage (Docker NOT relevant)
User is **using Laravel Architect in their Laravel app**:
```
User: "How do I use Laravel Architect in my project?"
AI: "Install via composer require. You DON'T need the Docker setup—use your app's database."
```

### Docker Environment Details

**What It Provides:**
- PHP 8.4 CLI container for running package tests
- MySQL 8.0 for testing MySQL repository generators
- PostgreSQL 16 for testing PostgreSQL repository generators
- SQLite support (in-memory) for fast testing

**Credentials (Test Only):**
- MySQL: `laravel` / `secret` (port 33060, localhost only)
- PostgreSQL: `laravel` / `secret` (port 54320, localhost only)
- These are **intentionally simple test credentials**
- Databases contain zero real data (only test fixtures)

**Environment Variables:**
- `.env.example` exists but `.env` is optional
- Default values use shell parameter expansion: `${VAR:-default}`
- Everything works without `.env` file (defaults built into docker-compose.yml)

**Security Context:**
- Ports bound to `127.0.0.1` only (not accessible from network)
- Clear warnings in `DOCKER.md` and `docker-compose.yml` about dev/test only
- Not designed for production use

### How AI Assistants Should Handle This

✅ **DO:**
- Explain Docker setup when user is contributing to the package
- Reference `DOCKER.md` for package development questions
- Clarify that end users don't need Docker
- Explain test credentials are intentionally simple

❌ **DON'T:**
- Suggest Docker setup for users installing the package in their Laravel app
- Treat test credentials as security vulnerabilities
- Recommend production use of the Docker environment
- Confuse package development with package usage

### Example Scenarios

**Scenario 1: Contributing to Package**
```
User: "I want to add a new feature to Laravel Architect"
AI Response: "Great! Here's how to set up the development environment:
1. Clone the repo
2. Run ./docker.sh build && ./docker.sh up
3. Run ./docker.sh composer-install
4. Make your changes
5. Test with ./docker.sh test-all
See DOCKER.md for complete details."
```

**Scenario 2: Using the Package**
```
User: "I want to generate API resources in my Laravel app"
AI Response: "Install Laravel Architect in your application:
1. composer require vendor/laravel-architect
2. php artisan vendor:publish --tag=api-scaffolding-config
3. Use php artisan make:api-bundle to generate resources
Note: You use your app's existing database—no special setup needed."
```

---

## 🎯 Your Goal as an AI Agent

Your job is to:

1. ✅ Understand user requirements
2. ✅ Generate correct schemas following coding standards
3. ✅ Provide complete, runnable commands
4. ✅ Explain what was generated
5. ✅ Remind them of post-generation steps
6. ✅ Educate them on best practices
7. ✅ Warn them about common mistakes
8. ✅ Validate their approach before they run commands

**Always prioritize correctness over speed. Better to ask clarifying questions than generate wrong code.**

---

## 📝 Quick Reference Checklist

Before generating any command, verify:

- [ ] Model name is singular
- [ ] All foreign key tables exist
- [ ] Foreign keys end with `_id`
- [ ] Pivot tables use `make:api-migration` not `make:api-bundle`
- [ ] Pivot table name is alphabetical
- [ ] Required fields explicitly marked with `"required":true`
- [ ] Relationship names are correct (singular for belongsTo, plural for hasMany/belongsToMany)
- [ ] Composite unique format is array of arrays
- [ ] All columns in composite unique exist in schema

---

**Happy AI Assisting! 🤖**
```
