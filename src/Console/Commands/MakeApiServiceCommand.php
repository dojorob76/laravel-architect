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

        $content = $this->processStub('service', [
            'namespace' => $this->getNamespace('services'),
            'interface_namespace' => $this->getNamespace('contracts'),
            'class' => $className,
        ]);

        $path = $this->getServicePath($className);
        $this->writeFile($files, $path, $content);

        $this->info("Service created: {$className}Service");
    }
}
