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

        $content = $this->processStub('controller', [
            'namespace' => $this->getNamespace('controllers'),
            'base_controller_namespace' => $this->getNamespace('base_controller'),
            'request_namespace' => $this->getNamespace('requests'),
            'service_namespace' => $this->getNamespace('services'),
            'resource_namespace' => $this->getNamespace('resources'),
            'class' => $className,
        ]);

        $path = $this->getControllerPath($className);
        $this->writeFile($files, $path, $content);

        $this->info("Controller created: {$className}Controller");
    }
}
