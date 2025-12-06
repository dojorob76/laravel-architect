<?php

namespace LaravelArchitect\Console\Commands\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use LaravelArchitect\Console\Commands\Support\NamingConventionHelper;

trait SchemaParserTrait
{
    protected function parseSchema(?string $schema): array
    {
        if (!$schema) return [];

        $parsed = json_decode($schema, true) ?? [];

        // Normalize model-centric format to column-centric format
        return $this->normalizeSchema($parsed);
    }

    protected function parseCompositeUnique(?string $compositeUniqueJson): array
    {
        if (!$compositeUniqueJson) {
            return [];
        }

        $parsed = json_decode($compositeUniqueJson, true);

        if (!is_array($parsed)) {
            return [];
        }

        // If single array of strings, wrap it in another array
        if (!empty($parsed) && !is_array($parsed[0])) {
            return [$parsed];
        }

        return $parsed;
    }

    protected function getStub(string $name): string
    {
        $path = __DIR__ . '/../../../../stubs/' . $name . '.stub';
        if (!file_exists($path)) {
            $this->error("Stub not found: {$path}");
            exit(1);
        }
        return file_get_contents($path);
    }

    /**
     * Write content to a file, ensuring the directory exists.
     *
     * @param \Illuminate\Filesystem\Filesystem $files The filesystem instance
     * @param string $path The absolute path to the file
     * @param string $content The content to write
     * @return void
     */
    protected function writeFile(\Illuminate\Filesystem\Filesystem $files, string $path, string $content): void
    {
        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $content);
    }

    /**
     * Get the configured namespace for a component type.
     *
     * @param string $component The component type (e.g., 'models', 'controllers')
     * @return string The namespace
     */
    protected function getNamespace(string $component): string
    {
        return ConfigHelper::namespace($component);
    }

    /**
     * Get the configured base path for a component type.
     *
     * @param string $component The component type (e.g., 'models', 'controllers')
     * @return string The absolute base path
     */
    protected function getPath(string $component): string
    {
        return ConfigHelper::basePath($component);
    }

    /**
     * Get the full path to a model file.
     *
     * @param string $modelName The StudlyCase model name
     * @return string The absolute path to the model file
     */
    protected function getModelPath(string $modelName): string
    {
        return $this->getPath('models') . "/{$modelName}.php";
    }

    /**
     * Check if a schema column represents a foreign key.
     *
     * Detects foreign keys by checking the 'isForeignKey' flag
     * or if the column name ends with '_id'.
     *
     * @param array $col The column definition from schema
     * @return bool True if the column is a foreign key
     */
    protected function isForeignKey(array $col): bool
    {
        return ($col['isForeignKey'] ?? false) || str_ends_with($col['name'], '_id');
    }

    /**
     * Get the plural table name from a model name.
     *
     * Converts StudlyCase model names to plural snake_case table names.
     * Example: 'User' => 'users', 'BlogPost' => 'blog_posts'
     *
     * @param string $name The model name
     * @return string The plural table name
     */
    protected function getTableName(string $name): string
    {
        return NamingConventionHelper::modelToTableName($name);
    }

    /**
     * Extract the singular entity name from a foreign key column name.
     *
     * Removes the '_id' suffix from column names like 'user_id' to get 'user'.
     *
     * @param string $columnName The foreign key column name
     * @return string The singular entity name (e.g., 'user', 'blog_post')
     */
    protected function extractForeignEntityName(string $columnName): string
    {
        return NamingConventionHelper::foreignKeyToEntityName($columnName);
    }

    /**
     * Get the foreign model name from a foreign key column name.
     *
     * Converts 'user_id' => 'User', 'blog_post_id' => 'BlogPost'
     *
     * @param string $columnName The foreign key column name
     * @return string The StudlyCase model name
     */
    protected function getForeignModelName(string $columnName): string
    {
        return NamingConventionHelper::foreignKeyToModel($columnName);
    }

    /**
     * Check if a schema column is required.
     *
     * @param array $col The column definition from schema
     * @return bool True if the column is required
     */
    protected function isRequired(array $col): bool
    {
        return $col['required'] ?? false;
    }

    /**
     * Build full file path for a controller.
     *
     * @param string $className The class name
     * @return string The full path
     */
    protected function getControllerPath(string $className): string
    {
        return $this->getPath('controllers') . "/{$className}Controller.php";
    }

    /**
     * Build full file path for a request.
     *
     * @param string $className The class name
     * @return string The full path
     */
    protected function getRequestPath(string $className): string
    {
        return $this->getPath('requests') . "/{$className}Request.php";
    }

    /**
     * Build full file path for a service.
     *
     * @param string $className The class name
     * @return string The full path
     */
    protected function getServicePath(string $className): string
    {
        return $this->getPath('services') . "/{$className}Service.php";
    }

    /**
     * Build full file path for a factory.
     *
     * @param string $className The class name
     * @return string The full path
     */
    protected function getFactoryPath(string $className): string
    {
        return $this->getPath('factories') . "/{$className}Factory.php";
    }

    /**
     * Build full file path for a test.
     *
     * @param string $className The class name
     * @return string The full path
     */
    protected function getTestPath(string $className): string
    {
        return $this->getPath('tests') . "/{$className}Test.php";
    }

    /**
     * Build full file path for a resource.
     *
     * @param string $className The class name
     * @param string $suffix The resource suffix (default: 'Resource')
     * @return string The full path
     */
    protected function getResourcePath(string $className, string $suffix = 'Resource'): string
    {
        return $this->getPath('resources') . "/{$className}{$suffix}.php";
    }

    /**
     * Build full file path for a repository.
     *
     * @param string $className The class name
     * @param string $prefix The repository prefix (e.g., 'Eloquent', 'MySQL', 'Postgres')
     * @return string The full path
     */
    protected function getRepositoryPath(string $className, string $prefix = 'Eloquent'): string
    {
        return $this->getPath('repositories') . "/{$prefix}{$className}Repository.php";
    }

    /**
     * Build full file path for a migration.
     *
     * @param string $filename The migration filename
     * @return string The full path
     */
    protected function getMigrationPath(string $filename): string
    {
        return $this->getPath('migrations') . "/{$filename}";
    }

    /**
     * Build fully qualified class name.
     *
     * @param string $namespace The namespace
     * @param string $className The class name
     * @return string The FQN like "\\App\\Models\\User"
     */
    protected function buildFQN(string $namespace, string $className): string
    {
        return "\\{$namespace}\\{$className}";
    }

    /**
     * Build fully qualified class name with ::class.
     *
     * @param string $namespace The namespace
     * @param string $className The class name
     * @return string The FQN like "\\App\\Models\\User::class"
     */
    protected function buildFQNClass(string $namespace, string $className): string
    {
        return $this->buildFQN($namespace, $className) . '::class';
    }

    /**
     * Build fully qualified factory call.
     *
     * @param string $namespace The model namespace
     * @param string $modelName The model name
     * @return string Like "\\App\\Models\\User::factory()"
     */
    protected function buildFactoryCall(string $namespace, string $modelName): string
    {
        return $this->buildFQN($namespace, $modelName) . '::factory()';
    }

    /**
     * Process a stub by replacing placeholders.
     *
     * @param string $stubName The stub file name (without .stub extension)
     * @param array $replacements Associative array of placeholder => value
     * @return string The processed stub content
     */
    protected function processStub(string $stubName, array $replacements): string
    {
        $stub = $this->getStub($stubName);

        $placeholders = array_map(fn($key) => "{{ {$key} }}", array_keys($replacements));
        $values = array_values($replacements);

        return str_replace($placeholders, $values, $stub);
    }

    /**
     * Check if a column is a regular data field (not a foreign key).
     *
     * Useful for filtering schema columns to only those that should be included
     * in test data, fillable arrays, or resource fields.
     *
     * @param array $col The column definition from schema
     * @return bool True if the column is a regular data field
     */
    protected function isRegularDataField(array $col): bool
    {
        return !$this->isForeignKey($col);
    }

    /**
     * Extract comprehensive relationship information from a foreign key column name.
     *
     * Returns an object with all the information needed to build relationships,
     * resources, and other FK-related code.
     *
     * @param string $foreignKeyName The foreign key column name (e.g., 'user_id')
     * @return object Object with tableName, relationName, modelName, and modelFQN
     */
    protected function extractRelationshipInfo(string $foreignKeyName): object
    {
        $entityName = $this->extractForeignEntityName($foreignKeyName);
        $modelName = $this->getForeignModelName($foreignKeyName);

        return (object) [
            'tableName' => $entityName,
            'relationName' => \Illuminate\Support\Str::camel($entityName),
            'modelName' => $modelName,
            'modelFQN' => $this->buildFQNClass($this->getNamespace('models'), $modelName),
        ];
    }

    /**
     * Filter schema to return only foreign key columns.
     *
     * @param array $schema The schema array
     * @return array Array of foreign key columns
     */
    protected function getForeignKeyFields(array $schema): array
    {
        return array_filter($schema, fn($col) => $this->isForeignKey($col));
    }

    /**
     * Filter schema to return only non-foreign key columns.
     *
     * @param array $schema The schema array
     * @return array Array of non-foreign key columns
     */
    protected function getNonForeignKeyFields(array $schema): array
    {
        return array_filter($schema, fn($col) => !$this->isForeignKey($col));
    }

    /**
     * Filter schema to return only regular data fields (not FKs).
     *
     * @param array $schema The schema array
     * @return array Array of regular data field columns
     */
    protected function getRegularDataFields(array $schema): array
    {
        return array_filter($schema, fn($col) => $this->isRegularDataField($col));
    }

    /**
     * Convert model name to foreign key column name.
     *
     * @param string $modelName The StudlyCase model name (e.g., 'User', 'BlogPost')
     * @return string The foreign key column name (e.g., 'user_id', 'blog_post_id')
     */
    protected function modelToForeignKeyColumn(string $modelName): string
    {
        return NamingConventionHelper::modelToForeignKey($modelName);
    }

    /**
     * Validate that a model name follows StudlyCase singular convention.
     *
     * @param string $modelName The model name to validate
     * @return bool True if valid
     */
    protected function isValidModelName(string $modelName): bool
    {
        return NamingConventionHelper::isValidModelName($modelName);
    }

    /**
     * Check if a model file exists.
     *
     * @param string $modelName The StudlyCase model name
     * @return bool True if model file exists
     */
    protected function modelExists(string $modelName): bool
    {
        return File::exists($this->getModelPath($modelName));
    }

    /**
     * Normalize schema from model-centric to column-centric format.
     *
     * Converts: {"parentModel": "User", "required": true}
     * To: {"name": "user_id", "type": "unsignedBigInteger", "isForeignKey": true, ...}
     *
     * @param array $schema The raw schema array
     * @return array The normalized schema array
     * @throws \InvalidArgumentException If model name is invalid
     */
    protected function normalizeSchema(array $schema): array
    {
        $normalized = [];

        foreach ($schema as $field) {
            if (isset($field['parentModel'])) {
                // Model-centric format - normalize to column-centric
                $modelName = $field['parentModel'];

                if (!$this->isValidModelName($modelName)) {
                    throw new \InvalidArgumentException(
                        "Invalid model name: '{$modelName}'. " .
                        "Models must be singular StudlyCase (e.g., User, BlogPost, Tag)."
                    );
                }

                $required = $field['required'] ?? true;

                $normalized[] = [
                    'name' => $this->modelToForeignKeyColumn($modelName),
                    'type' => 'unsignedBigInteger',
                    'isForeignKey' => true,
                    'foreignTable' => $this->getTableName($modelName),
                    'required' => $required,
                    'onDelete' => $field['onDelete'] ?? ($required ? 'cascade' : 'set null'),
                    'onUpdate' => 'cascade', // ALWAYS cascade
                ];
            } else {
                // Regular data field or old column-centric format (pass through)
                $normalized[] = $field;
            }
        }

        return $normalized;
    }

    /**
     * Generate pivot table name from two model names (alphabetically sorted).
     *
     * @param string $model1 First model name
     * @param string $model2 Second model name
     * @return string The pivot table name (e.g., 'post_tag')
     */
    protected function generatePivotTableName(string $model1, string $model2): string
    {
        return NamingConventionHelper::generatePivotTableName($model1, $model2);
    }

    /**
     * Parse comma-separated model names from --models option.
     *
     * @param string|null $modelsString Comma-separated model names
     * @return array Array of trimmed model names
     */
    protected function parseModels(?string $modelsString): array
    {
        if (!$modelsString) {
            return [];
        }

        $models = array_map('trim', explode(',', $modelsString));
        return array_filter($models);
    }

    /**
     * Generate schema array for pivot table from two model names.
     * Preserves user's model order for composite key order.
     *
     * @param string $model1 First model name
     * @param string $model2 Second model name
     * @return array Schema array with parentModel definitions
     */
    protected function generatePivotSchema(string $model1, string $model2): array
    {
        return [
            ['parentModel' => $model1, 'required' => true],
            ['parentModel' => $model2, 'required' => true],
        ];
    }

    /**
     * Add belongsToMany relationship to an existing model file.
     *
     * @param string $modelName The model to update
     * @param string $relatedModel The related model name
     * @param string $pivotTable The pivot table name
     * @return bool True if successful, false otherwise
     */
    protected function addBelongsToManyRelationship(
        string $modelName,
        string $relatedModel,
        string $pivotTable
    ): bool {
        $modelPath = $this->getModelPath($modelName);

        if (!File::exists($modelPath)) {
            return false;
        }

        $content = File::get($modelPath);
        $relationshipName = Str::plural(Str::camel($relatedModel));

        // Check if relationship already exists (idempotent)
        if (str_contains($content, "public function {$relationshipName}()")) {
            return true;
        }

        // Build relationship method
        $modelNamespace = $this->getNamespace('models');
        $relatedModelFQN = $this->buildFQNClass($modelNamespace, $relatedModel);

        $relationship = <<<PHP

    /**
     * Get the {$relationshipName} associated with this {$modelName}.
     */
    public function {$relationshipName}()
    {
        return \$this->belongsToMany({$relatedModelFQN}, '{$pivotTable}');
    }
PHP;

        // Find closing brace and inject before it
        $lastBracePos = strrpos($content, '}');
        if ($lastBracePos === false) {
            return false;
        }

        $updatedContent = substr_replace($content, $relationship . "\n", $lastBracePos, 0);
        File::put($modelPath, $updatedContent);

        return true;
    }

    /**
     * Add belongsToMany relationships to both models in a pivot table.
     *
     * @param string $model1 First model name
     * @param string $model2 Second model name
     * @param string $pivotTable The pivot table name
     * @return array Results with success status and messages
     */
    protected function updatePivotModels(
        string $model1,
        string $model2,
        string $pivotTable
    ): array {
        $results = [
            'success' => true,
            'messages' => [],
        ];

        if ($this->addBelongsToManyRelationship($model1, $model2, $pivotTable)) {
            $results['messages'][] = "Added " . Str::plural(Str::camel($model2)) . "() to {$model1}";
        } else {
            $results['success'] = false;
            $results['messages'][] = "Failed to update {$model1} model";
        }

        if ($this->addBelongsToManyRelationship($model2, $model1, $pivotTable)) {
            $results['messages'][] = "Added " . Str::plural(Str::camel($model1)) . "() to {$model2}";
        } else {
            $results['success'] = false;
            $results['messages'][] = "Failed to update {$model2} model";
        }

        return $results;
    }
}
