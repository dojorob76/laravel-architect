<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;

class MakeApiControllerCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-controller {name}';
    protected $description = 'Create a new API Controller';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $className = Str::studly($name);

        $namespace = config('api_scaffolding.namespaces.controllers');
        $baseControllerNamespace = config('api_scaffolding.namespaces.base_controller');
        $requestNamespace = config('api_scaffolding.namespaces.requests');
        $serviceNamespace = config('api_scaffolding.namespaces.services');
        $resourceNamespace = config('api_scaffolding.namespaces.resources');

        $stub = $this->getStub('controller');
        $content = str_replace(
            [
                '{{ namespace }}',
                '{{ base_controller_namespace }}',
                '{{ request_namespace }}',
                '{{ service_namespace }}',
                '{{ resource_namespace }}',
                '{{ class }}'
            ],
            [
                $namespace,
                $baseControllerNamespace,
                $requestNamespace,
                $serviceNamespace,
                $resourceNamespace,
                $className
            ],
            $stub
        );

        $path = base_path(config('api_scaffolding.paths.controllers') . "/{$className}Controller.php");
        $this->writeFile($files, $path, $content);

        $this->info("Controller created: {$className}Controller");
    }
}
