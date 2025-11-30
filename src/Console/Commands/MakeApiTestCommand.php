<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;

class MakeApiTestCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-test {name} {--schema=} {--soft-deletes} {--multi-tenant} {--composite-unique=}';
    protected $description = 'Create PEST tests for API resource';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $schema = $this->parseSchema($this->option('schema'));
        $softDeletes = $this->option('soft-deletes');
        $multiTenant = $this->option('multi-tenant');
        $compositeUnique = $this->parseCompositeUnique($this->option('composite-unique'));

        $className = Str::studly($name);
        $route = $this->getTableName($name);
        $singularRoute = Str::snake($name);
        $table = $this->getTableName($name);

        $testData = $this->buildTestData($schema);
        $testUpdateData = $this->buildTestUpdateData($schema);
        $testInvalidData = $this->buildTestInvalidData($schema);
        $testPartialUpdate = $this->buildTestPartialUpdate($schema);
        $testBatchCreate = $this->buildTestBatchCreate($schema);
        $testBatchUpdateField = $this->buildTestBatchUpdateField($schema);
        $requiredFields = $this->buildRequiredFields($schema);
        $searchField = $this->getFirstSearchableField($schema);
        $softDeleteTests = $softDeletes ? $this->buildSoftDeleteTests($route, $singularRoute, $className) : '';
        $softDeleteAssertion = $softDeletes
            ? "    \$this->assertSoftDeleted('{{ table }}', ['id' => \$record->id]);"
            : "    \$this->assertDatabaseMissing('{{ table }}', ['id' => \$record->id]);";
        $batchDeleteAssertion = $this->buildBatchDeleteAssertion($softDeletes);
        $batchSoftDeleteTests = $softDeletes ? $this->buildBatchSoftDeleteTests($route, $className) : '';
        $multiTenantTests = $multiTenant ? $this->buildMultiTenantTests($route, $className) : '';

        $content = $this->processStub('test', [
            'model_namespace' => $this->getNamespace('models'),
            'class' => $className,
            'route' => $route,
            'singular_route' => $singularRoute,
            'table' => $table,
            'test_data' => $testData,
            'test_update_data' => $testUpdateData,
            'test_invalid_data' => $testInvalidData,
            'test_partial_update' => $testPartialUpdate,
            'test_batch_create' => $testBatchCreate,
            'test_batch_update_field' => $testBatchUpdateField,
            'required_fields' => $requiredFields,
            'search_field' => $searchField,
            'soft_delete_tests' => $softDeleteTests,
            'soft_delete_assertion' => $softDeleteAssertion,
            'batch_delete_assertion' => $batchDeleteAssertion,
            'batch_soft_delete_tests' => $batchSoftDeleteTests,
            'multi_tenant_tests' => $multiTenantTests,
        ]);

        $path = $this->getTestPath($className);
        $this->writeFile($files, $path, $content);

        $this->info("PEST test created: {$className}Test");
    }

    protected function buildTestData(array $schema): string
    {
        if (empty($schema)) {
            return '';
        }

        $data = [];
        foreach ($schema as $col) {
            if ($col['name'] !== 'tenant_id') {
                $value = $this->generateTestValue($col);
                $data[] = "        '{$col['name']}' => {$value}";
            }
        }

        return implode(",\n", $data);
    }

    protected function buildTestUpdateData(array $schema): string
    {
        if (empty($schema)) {
            return '';
        }

        $data = [];
        foreach ($schema as $col) {
            if (!$this->isForeignKey($col) && $col['name'] !== 'tenant_id') {
                $value = $this->generateTestValue($col, true);
                $data[] = "        '{$col['name']}' => {$value}";
                break;
            }
        }

        return implode(",\n", $data);
    }

    protected function buildTestInvalidData(array $schema): string
    {
        if (empty($schema)) {
            return '';
        }

        $data = [];
        foreach ($schema as $col) {
            if ($this->isRequired($col)) {
                $invalidValue = match($col['type'] ?? 'string') {
                    'integer', 'unsignedBigInteger' => "'not-a-number'",
                    'boolean' => "'not-a-boolean'",
                    'email' => "'invalid-email'",
                    default => "123"
                };
                $data[] = "        '{$col['name']}' => {$invalidValue}";
                break;
            }
        }

        return implode(",\n", $data);
    }

    protected function buildTestPartialUpdate(array $schema): string
    {
        foreach ($schema as $col) {
            if (!$this->isForeignKey($col) && $col['name'] !== 'tenant_id') {
                $value = $this->generateTestValue($col, true);
                return "        '{$col['name']}' => {$value}";
            }
        }

        return "        'id' => \$record->id";
    }

    protected function buildTestBatchCreate(array $schema): string
    {
        $records = [];
        for ($i = 1; $i <= 2; $i++) {
            $fields = [];
            foreach ($schema as $col) {
                if ($col['name'] !== 'tenant_id') {
                    $value = $this->generateTestValue($col);
                    $fields[] = "'{$col['name']}' => {$value}";
                }
            }
            $records[] = "            [" . implode(', ', $fields) . "]";
        }

        return implode(",\n", $records);
    }

    protected function buildTestBatchUpdateField(array $schema): string
    {
        foreach ($schema as $col) {
            if (!$this->isForeignKey($col) && $col['name'] !== 'tenant_id') {
                $value = $this->generateTestValue($col, true);
                return "                '{$col['name']}' => {$value}";
            }
        }

        return "                'updated' => true";
    }

    protected function buildRequiredFields(array $schema): string
    {
        $required = [];
        foreach ($schema as $col) {
            if ($this->isRequired($col) && $col['name'] !== 'tenant_id') {
                $required[] = "            '{$col['name']}'";
            }
        }

        return implode(",\n", $required);
    }

    protected function getFirstSearchableField(array $schema): string
    {
        foreach ($schema as $col) {
            if (in_array($col['type'] ?? '', ['string', 'text'])) {
                return "'{$col['name']}'";
            }
        }

        return "'id'";
    }

    protected function buildBatchDeleteAssertion(bool $softDeletes): string
    {
        if ($softDeletes) {
            return "            \$this->assertSoftDeleted('{{ table }}', ['id' => \$id]);";
        }

        return "            \$this->assertDatabaseMissing('{{ table }}', ['id' => \$id]);";
    }

    protected function buildSoftDeleteTests(string $route, string $singularRoute, string $className): string
    {
        return <<<PHP

test('can restore soft-deleted {$singularRoute}', function () {
    \$record = {$className}::factory()->create();
    \$record->delete();
    
    \$response = \$this->postJson("/api/{$route}/{\$record->id}/restore");
    
    \$response->assertStatus(config('api_scaffolding.status_codes.success', 200));
    \$this->assertDatabaseHas('{$route}', [
        'id' => \$record->id,
        'deleted_at' => null
    ]);
});

test('can force delete {$singularRoute}', function () {
    \$record = {$className}::factory()->create();
    
    \$response = \$this->deleteJson("/api/{$route}/{\$record->id}/force");
    
    \$response->assertStatus(config('api_scaffolding.status_codes.no_content', 204));
    \$this->assertDatabaseMissing('{$route}', ['id' => \$record->id]);
});
PHP;
    }

    protected function buildBatchSoftDeleteTests(string $route, string $className): string
    {
        return <<<PHP

    test('can batch restore {$route}', function () {
        \$records = {$className}::factory()->count(3)->create();
        \$ids = \$records->pluck('id')->toArray();
        
        {$className}::whereIn('id', \$ids)->delete();
        
        \$response = \$this->postJson('/api/{$route}/batch/restore', [
            'ids' => \$ids
        ]);
        
        \$response->assertStatus(config('api_scaffolding.status_codes.success', 200))
            ->assertJsonPath('restored', 3);
        
        foreach (\$ids as \$id) {
            \$this->assertDatabaseHas('{$route}', [
                'id' => \$id,
                'deleted_at' => null
            ]);
        }
    });

    test('can batch force delete {$route}', function () {
        \$records = {$className}::factory()->count(3)->create();
        \$ids = \$records->pluck('id')->toArray();
        
        \$response = \$this->deleteJson('/api/{$route}/batch/force', [
            'ids' => \$ids
        ]);
        
        \$response->assertStatus(config('api_scaffolding.status_codes.success', 200))
            ->assertJsonPath('deleted', 3);
        
        foreach (\$ids as \$id) {
            \$this->assertDatabaseMissing('{$route}', ['id' => \$id]);
        }
    });
PHP;
    }

    protected function buildMultiTenantTests(string $route, string $className): string
    {
        return <<<PHP

test('users can only see their tenant {$route}', function () {
    \$user1 = User::factory()->create(['tenant_id' => 1]);
    \$user2 = User::factory()->create(['tenant_id' => 2]);
    
    \$record1 = {$className}::factory()->create(['tenant_id' => 1]);
    \$record2 = {$className}::factory()->create(['tenant_id' => 2]);
    
    \$this->actingAs(\$user1);
    \$response = \$this->getJson('/api/{$route}');
    
    \$response->assertStatus(config('api_scaffolding.status_codes.success', 200));
    \$ids = collect(\$response->json('data'))->pluck('id')->toArray();
    
    expect(\$ids)->toContain(\$record1->id)
        ->not->toContain(\$record2->id);
});
PHP;
    }

    protected function generateTestValue(array $col, bool $isUpdate = false): string
    {
        $type = $col['type'] ?? 'string';

        if ($this->isForeignKey($col)) {
            $foreignModel = $this->getForeignModelName($col['name']);
            return "{$foreignModel}::factory()->create()->id";
        }

        return match($type) {
            'string' => $isUpdate ? "'Updated Test String'" : "'Test String'",
            'text' => $isUpdate ? "'Updated test text content'" : "'Test text content'",
            'integer', 'unsignedBigInteger', 'bigInteger' => $isUpdate ? '200' : '100',
            'boolean' => $isUpdate ? 'false' : 'true',
            'decimal', 'float', 'double' => $isUpdate ? '99.99' : '19.99',
            'date' => "now()->format('Y-m-d')",
            'datetime', 'timestamp' => "now()->toDateTimeString()",
            'email' => $isUpdate ? "'updated@example.com'" : "'test@example.com'",
            'json' => "['key' => 'value']",
            default => "'test value'"
        };
    }
}
