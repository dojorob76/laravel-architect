<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;

class MakeApiRepositoryCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-repository {name} {--schema=} {--driver=eloquent} {--composite-unique=}';
    protected $description = 'Create repository interface and implementations (eloquent|mysql|postgres|all)';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $driver = $this->option('driver');
        $compositeUnique = $this->parseCompositeUnique($this->option('composite-unique'));
        $className = Str::studly($name);
        $tableName = $this->getTableName($name);

        $namespace = $this->getNamespace('repositories');
        $interfaceNamespace = $this->getNamespace('repository_interfaces');
        $modelNamespace = $this->getNamespace('models');

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
        $content = $this->processStub('repository_interface', [
            'namespace' => $namespace,
            'class' => $className,
        ]);

        $path = $this->getRepositoryInterfacePath($className);
        $this->writeFile($files, $path, $content);
    }

    protected function createEloquentRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace): void
    {
        $content = $this->processStub('repository_eloquent', [
            'namespace' => $namespace,
            'interface_namespace' => $interfaceNamespace,
            'model_namespace' => $modelNamespace,
            'class' => $className,
        ]);

        $path = $this->getRepositoryPath($className, 'Eloquent');
        $this->writeFile($files, $path, $content);
    }

    protected function createMySQLRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace, string $tableName): void
    {
        $content = $this->processStub('repository_mysql', [
            'namespace' => $namespace,
            'interface_namespace' => $interfaceNamespace,
            'model_namespace' => $modelNamespace,
            'class' => $className,
            'table' => $tableName,
        ]);

        $path = $this->getRepositoryPath($className, 'MySQL');
        $this->writeFile($files, $path, $content);
    }

    protected function createPostgresRepository(Filesystem $files, string $className, string $namespace, string $interfaceNamespace, string $modelNamespace, string $tableName): void
    {
        $content = $this->processStub('repository_postgres', [
            'namespace' => $namespace,
            'interface_namespace' => $interfaceNamespace,
            'model_namespace' => $modelNamespace,
            'class' => $className,
            'table' => $tableName,
        ]);

        $path = $this->getRepositoryPath($className, 'Postgres');
        $this->writeFile($files, $path, $content);
    }
}
