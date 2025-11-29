<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;

class MakeApiResourceCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-resource {name} {--schema=}';
    protected $description = 'Create API Resource and Resource Collection';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $schema = $this->parseSchema($this->option('schema'));
        $className = Str::studly($name);
        $namespace = config('api_scaffolding.namespaces.resources');

        $this->createResource($files, $className, $namespace, $schema);
        $this->createResourceCollection($files, $className, $namespace);

        $this->info("API Resources created for {$className}");
    }

    protected function createResource(Filesystem $files, string $className, string $namespace, array $schema): void
    {
        $fields = $this->buildResourceFields($schema);
        $relationships = $this->buildResourceRelationships($schema);

        $stub = $this->getStub('resource');
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ fields }}', '{{ relationships }}'],
            [$namespace, $className, $fields, $relationships],
            $stub
        );

        $path = base_path(config('api_scaffolding.paths.resources') . '/' . $className . 'Resource.php');
        $this->writeFile($files, $path, $content);
    }

    protected function createResourceCollection(Filesystem $files, string $className, string $namespace): void
    {
        $stub = $this->getStub('resource_collection');
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );

        $path = base_path(config('api_scaffolding.paths.resources') . '/' . $className . 'Collection.php');
        $this->writeFile($files, $path, $content);
    }

    protected function buildResourceFields(array $schema): string
    {
        if (empty($schema)) {
            return '';
        }

        $fields = [];
        foreach ($schema as $col) {
            if (!$this->isForeignKey($col)) {
                $fields[] = "            '{$col['name']}' => \$this->{$col['name']}";
            }
        }

        return empty($fields) ? '' : implode(",\n", $fields) . ',';
    }

    protected function buildResourceRelationships(array $schema): string
    {
        $relationships = [];
        $resourceNamespace = config('api_scaffolding.namespaces.resources');

        foreach ($schema as $col) {
            if ($this->isForeignKey($col)) {
                $tableName = $this->extractForeignTableName($col['name']);
                $relationName = Str::camel($tableName);
                $resourceClass = $this->getForeignModelName($col['name']) . 'Resource';

                $relationships[] = "            '{$relationName}' => new \\{$resourceNamespace}\\{$resourceClass}(\$this->whenLoaded('{$relationName}'))";
            }
        }

        return empty($relationships) ? '' : implode(",\n", $relationships) . ',';
    }
}
