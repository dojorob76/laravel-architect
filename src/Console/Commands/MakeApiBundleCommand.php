<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;

class MakeApiBundleCommand extends Command
{
    protected $signature = 'make:api-bundle {name}
                            {--schema=}
                            {--soft-deletes}
                            {--composite-unique=}
                            {--driver=eloquent : Repository driver (eloquent|mysql|postgres|all)}';

    protected $description = 'Scaffold a complete API resource bundle';

    public function handle(): void
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $softDeletes = $this->option('soft-deletes');
        $compositeUnique = $this->option('composite-unique');
        $driver = $this->option('driver');

        // Args for migration (includes composite-unique)
        $migrationArgs = [
            'name' => $name,
            '--schema' => $schema,
            '--soft-deletes' => $softDeletes,
            '--composite-unique' => $compositeUnique,
        ];

        // Args for model (soft-deletes)
        $modelArgs = [
            'name' => $name,
            '--schema' => $schema,
            '--soft-deletes' => $softDeletes,
        ];

        // Args for test (soft-deletes + composite-unique)
        $testArgs = [
            'name' => $name,
            '--schema' => $schema,
            '--soft-deletes' => $softDeletes,
            '--composite-unique' => $compositeUnique,
        ];

        // Args for factory, request, repository (schema + composite-unique)
        $schemaCompositeArgs = [
            'name' => $name,
            '--schema' => $schema,
            '--composite-unique' => $compositeUnique,
        ];

        // Args for routes (only soft-deletes)
        $routesArgs = [
            'name' => $name,
            '--soft-deletes' => $softDeletes,
        ];

        // Args for commands with just name
        $nameOnlyArgs = [
            'name' => $name,
        ];

        $this->info("🚀 Scaffolding API bundle for: {$name}");

        if ($softDeletes) {
            $this->comment("   • With soft deletes");
        }

        $this->newLine();

        $this->info('📝 Creating migration...');
        $exitCode = $this->call('make:api-migration', $migrationArgs);

        if ($exitCode !== 0) {
            $this->error("Bundle generation stopped due to migration errors.");
            return;
        }

        $this->info('📦 Creating model...');
        $this->call('make:api-model', $modelArgs);

        $this->info('🏭 Creating factory...');
        $this->call('make:api-factory', $schemaCompositeArgs);

        $this->info('🗄️  Creating repositories...');
        $this->call('make:api-repository', array_merge($schemaCompositeArgs, ['--driver' => $driver]));

        $this->info('⚙️  Creating service...');
        $this->call('make:api-service', $nameOnlyArgs);

        $this->info('✅ Creating form request...');
        $this->call('make:api-request', $schemaCompositeArgs);

        $this->info('🎨 Creating API resources...');
        $this->call('make:api-resource', ['name' => $name, '--schema' => $schema]);

        $this->info('🎮 Creating controller...');
        $this->call('make:api-controller', $nameOnlyArgs);

        $this->info('🧪 Creating tests...');
        $this->call('make:api-test', $testArgs);

        $this->info('🛣️  Adding routes...');
        $this->call('make:api-routes', $routesArgs);

        $this->newLine();
        $this->info("✨ Bundle scaffolding complete for {$name}!");

        $this->newLine();
        $this->comment('📋 Next steps:');
        $this->comment('1. Add repository binding to a service provider');
        $this->comment('2. Run: php artisan migrate');
        $this->comment('3. Test your API endpoints!');
    }
}
