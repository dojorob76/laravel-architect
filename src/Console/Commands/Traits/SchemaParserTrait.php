<?php

namespace LaravelArchitect\Console\Commands\Traits;

trait SchemaParserTrait
{
    protected function parseSchema(?string $schema): array
    {
        if (!$schema) return [];
        return json_decode($schema, true) ?? [];
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
}
