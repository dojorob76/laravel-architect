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
}
