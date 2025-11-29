<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;

class MakeApiRepositoryCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-repository {name} {--schema=} {--driver=eloquent}';
    protected $description = 'Create repository interface and implementations (eloquent|mysql|postgres|all)';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $driver = $this->option('driver');
        $className = Str::studly($name);
        $tableName = $this->snake($this->pluralize($name));

        $namespace = config('api_scaffolding.namespaces.repositories');
        $interfaceNamespace = config('api_scaffolding.namespaces.repository_interfaces');
        $modelNamespace = config('api_scaffolding.namespaces.models');

        $this->createInterface($files, $className, $interfaceNamespace);

        if ($driver === 'all' || $driver === 'eloquent') {
            $this->createEloquentRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace);
        }

        if ($driver === 'all' || $driver === 'mysql') {
            $this->createMySQLRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
        }

        if ($driver === 'all' || $driver === 'postgres') {
            $this->createPostgresRepository($files, $className, $namespace, $interfaceNamespace, $modelNamespace, $tableName);
        }

        $this->info("Repositories created for {$className}");
        $this->newLine();
        $this->comment("📋 Don't forget to bind the interface in a service provider:");
        $this->comment("   \$this->app->bind(");
        $this->comment("       \\{$interfaceNamespace}\\{$className}RepositoryInterface::class,");
        $this->comment("       \\{$namespace}\\Eloquent{$className}Repository::class");
        $this->comment("   );");
    }

    protected function createInterface(Filesystem $files, string $className, string $namespace): void
    {
        $interfacePath = base_path(config('api_scaffolding.paths.repository_interfaces'));
        $files->ensureDirectoryExists($interfacePath);

        $stub = $this->getStub('repository_interface');
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );

        $files->put("{$interfacePath}/{$className}RepositoryInterface.php", $content);
    }

    protected function createEloquentRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace): void
    {
        $repoPath = base_path(config('api_scaffolding.paths.repositories'));
        $files->ensureDirectoryExists($repoPath);

        $stub = $this->getStub('repository_eloquent');
        $content = str_replace(
            ['{{ namespace }}', '{{ interface_namespace }}', '{{ model_namespace }}', '{{ class }}'],
            [$namespace, $interfaceNamespace, $modelNamespace, $className],
            $stub
        );

        $files->put("{$repoPath}/Eloquent{$className}Repository.php", $content);
    }

    protected function createMySQLRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace, string $tableName): void
    {
        $repoPath = base_path(config('api_scaffolding.paths.repositories'));
        $files->ensureDirectoryExists($repoPath);

        $stub = $this->getStub('repository_mysql');
        $content = str_replace(
            ['{{ namespace }}', '{{ interface_namespace }}', '{{ model_namespace }}', '{{ class }}', '{{ table }}'],
            [$namespace, $interfaceNamespace, $modelNamespace, $className, $tableName],
            $stub
        );

        $files->put("{$repoPath}/MySQL{$className}Repository.php", $content);
    }

    protected function createPostgresRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace, string $tableName): void
    {
        $repoPath = base_path(config('api_scaffolding.paths.repositories'));
        $files->ensureDirectoryExists($repoPath);

        $stub = $this->getStub('repository_postgres');
        $content = str_replace(
            ['{{ namespace }}', '{{ interface_namespace }}', '{{ model_namespace }}', '{{ class }}', '{{ table }}'],
            [$namespace, $interfaceNamespace, $modelNamespace, $className, $tableName],
            $stub
        );

        $files->put("{$repoPath}/Postgres{$className}Repository.php", $content);
    }
}
