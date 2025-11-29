<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;

class MakeApiBundleCommand extends Command
{
    protected $signature = 'make:api-bundle {name} 
                            {--schema=} 
                            {--soft-deletes} 
                            {--multi-tenant}
                            {--composite-unique=}
                            {--driver=eloquent : Repository driver (eloquent|mysql|postgres|all)}';

    protected $description = 'Scaffold a complete API resource bundle';

    public function handle(): void
    {
        $name = $this->argument('name');
        $schema = $this->option('schema');
        $softDeletes = $this->option('soft-deletes');
        $multiTenant = $this->option('multi-tenant');
        $compositeUnique = $this->option('composite-unique');
        $driver = $this->option('driver');

        $args = [
            'name' => $name,
            '--schema' => $schema,
            '--soft-deletes' => $softDeletes,
            '--multi-tenant' => $multiTenant,
            '--composite-unique' => $compositeUnique,
        ];

        $this->info("🚀 Scaffolding API bundle for: {$name}");

        if ($softDeletes) {
            $this->comment("   • With soft deletes");
        }

        if ($multiTenant) {
            $this->comment("   • With multi-tenancy");
        }

        $this->newLine();

        $this->info('📝 Creating migration...');
        $exitCode = $this->call('make:api-migration', $args);

        if ($exitCode !== 0) {
            $this->error("Bundle generation stopped due to migration errors.");
            return;
        }

        $this->info('📦 Creating model...');
        $this->call('make:api-model', $args);

        $this->info('🏭 Creating factory...');
        $this->call('make:api-factory', ['name' => $name, '--schema' => $schema]);

        $this->info('🗄️  Creating repositories...');
        $this->call('make:api-repository', array_merge($args, ['--driver' => $driver]));

        $this->info('⚙️  Creating service...');
        $this->call('make:api-service', ['name' => $name]);

        $this->info('✅ Creating form request...');
        $this->call('make:api-request', $args);

        $this->info('🎨 Creating API resources...');
        $this->call('make:api-resource', ['name' => $name, '--schema' => $schema]);

        $this->info('🎮 Creating controller...');
        $this->call('make:api-controller', ['name' => $name]);

        $this->info('🧪 Creating tests...');
        $this->call('make:api-test', $args);

        $this->info('🛣️  Adding routes...');
        $this->call('make:api-routes', [
            'name' => $name,
            '--soft-deletes' => $softDeletes
        ]);

        $this->newLine();
        $this->info("✨ Bundle scaffolding complete for {$name}!");

        $this->newLine();
        $this->comment('📋 Next steps:');
        $this->comment('1. Add repository binding to a service provider');
        $this->comment('2. Run: php artisan migrate');
        $this->comment('3. Test your API endpoints!');
    }
}
