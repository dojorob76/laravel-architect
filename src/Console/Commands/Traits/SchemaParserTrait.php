<?php

namespace LaravelArchitect\Console\Commands\Traits;

trait SchemaParserTrait
{
    protected function parseSchema(?string $schema): array
    {
        if (!$schema) return [];
        return json_decode($schema, true) ?? [];
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
        return config("api_scaffolding.namespaces.{$component}");
    }

    /**
     * Get the configured base path for a component type.
     *
     * @param string $component The component type (e.g., 'models', 'controllers')
     * @return string The absolute base path
     */
    protected function getPath(string $component): string
    {
        return base_path(config("api_scaffolding.paths.{$component}"));
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
     * Get the table name from a model name.
     *
     * Regular tables are pluralized and snake_cased.
     * Pivot tables are just snake_cased (not pluralized).
     *
     * @param string $name The model name
     * @param bool $isPivot Whether this is a pivot table
     * @return string The table name
     */
    protected function getTableName(string $name, bool $isPivot = false): string
    {
        if ($isPivot) {
            return \Illuminate\Support\Str::snake($name);
        }
        return \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($name));
    }

    /**
     * Extract the foreign table name from a foreign key column name.
     *
     * Removes the '_id' suffix from column names like 'user_id' to get 'user'.
     *
     * @param string $columnName The foreign key column name
     * @return string The foreign table name (singular)
     */
    protected function extractForeignTableName(string $columnName): string
    {
        return str_replace('_id', '', $columnName);
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
        return \Illuminate\Support\Str::studly($this->extractForeignTableName($columnName));
    }

    /**
     * Get the pluralized foreign table name from a foreign key column name.
     *
     * Converts 'user_id' => 'users', 'blog_post_id' => 'blog_posts'
     *
     * @param string $columnName The foreign key column name
     * @return string The snake_case pluralized table name
     */
    protected function getForeignTableNamePluralized(string $columnName): string
    {
        return \Illuminate\Support\Str::snake(\Illuminate\Support\Str::plural($this->extractForeignTableName($columnName)));
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
     * Build full file path for a repository interface.
     *
     * @param string $className The class name
     * @return string The full path
     */
    protected function getRepositoryInterfacePath(string $className): string
    {
        return $this->getPath('repository_interfaces') . "/{$className}RepositoryInterface.php";
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
}
