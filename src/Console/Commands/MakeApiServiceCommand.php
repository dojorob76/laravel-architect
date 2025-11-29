<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;

class MakeApiServiceCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-service {name}';
    protected $description = 'Create a new API Service';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $className = Str::studly($name);
        $namespace = config('api_scaffolding.namespaces.services');
        $interfaceNamespace = config('api_scaffolding.namespaces.repository_interfaces');

        $stub = $this->getStub('service');
        $content = str_replace(
            ['{{ namespace }}', '{{ interface_namespace }}', '{{ class }}'],
            [$namespace, $interfaceNamespace, $className],
            $stub
        );

        $path = base_path(config('api_scaffolding.paths.services') . "/{$className}Service.php");
        $this->writeFile($files, $path, $content);

        $this->info("Service created: {$className}Service");
    }
}
