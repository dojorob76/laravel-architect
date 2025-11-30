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
        $namespace = $this->getNamespace('resources');

        $this->createResource($files, $className, $namespace, $schema);
        $this->createResourceCollection($files, $className, $namespace);

        $this->info("API Resources created for {$className}");
    }

    protected function createResource(Filesystem $files, string $className, string $namespace, array $schema): void
    {
        $fields = $this->buildResourceFields($schema);
        $relationships = $this->buildResourceRelationships($schema);

        $content = $this->processStub('resource', [
            'namespace' => $namespace,
            'class' => $className,
            'fields' => $fields,
            'relationships' => $relationships,
        ]);

        $path = $this->getResourcePath($className, 'Resource');
        $this->writeFile($files, $path, $content);
    }

    protected function createResourceCollection(Filesystem $files, string $className, string $namespace): void
    {
        $content = $this->processStub('resource_collection', [
            'namespace' => $namespace,
            'class' => $className,
        ]);

        $path = $this->getResourcePath($className, 'Collection');
        $this->writeFile($files, $path, $content);
    }

    protected function buildResourceFields(array $schema): string
    {
        if (empty($schema)) {
            return '';
        }

        $nonFkFields = $this->getNonForeignKeyFields($schema);
        $fields = array_map(
            fn($col) => "            '{$col['name']}' => \$this->{$col['name']}",
            $nonFkFields
        );

        return empty($fields) ? '' : implode(",\n", $fields) . ',';
    }

    protected function buildResourceRelationships(array $schema): string
    {
        $relationships = [];
        $resourceNamespace = $this->getNamespace('resources');

        foreach ($schema as $col) {
            if ($this->isForeignKey($col)) {
                $rel = $this->extractRelationshipInfo($col['name']);
                $resourceClass = $rel->modelName . 'Resource';
                $resourceFQN = $this->buildFQN($resourceNamespace, $resourceClass);

                $relationships[] = "            '{$rel->relationName}' => new {$resourceFQN}(\$this->whenLoaded('{$rel->relationName}'))";
            }
        }

        return empty($relationships) ? '' : implode(",\n", $relationships) . ',';
    }
}
