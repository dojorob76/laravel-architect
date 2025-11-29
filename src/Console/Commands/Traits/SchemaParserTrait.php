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

    protected function pluralize(string $name): string
    {
        return \Illuminate\Support\Str::plural($name);
    }

    protected function snake(string $name): string
    {
        return \Illuminate\Support\Str::snake($name);
    }

    protected function camel(string $name): string
    {
        return \Illuminate\Support\Str::camel($name);
    }

    protected function studly(string $name): string
    {
        return \Illuminate\Support\Str::studly($name);
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
            return $this->snake($name);
        }
        return $this->snake($this->pluralize($name));
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
        return $this->studly($this->extractForeignTableName($columnName));
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
        return $this->snake($this->pluralize($this->extractForeignTableName($columnName)));
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
}
