<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;

class MakeApiModelCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-model {name} {--schema=} {--pivot=false} {--soft-deletes} {--multi-tenant}';
    protected $description = 'Create a new API Model';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $schema = $this->parseSchema($this->option('schema'));
        $isPivot = $this->option('pivot') === 'true';

        $className = $this->studly($name);
        $tableName = $this->getTableName($name, $isPivot);
        $namespace = $this->getNamespace('models');

        $fillable = $this->buildFillableArray($schema);
        $relationships = $this->buildRelationships($schema, $namespace);
        $casts = $this->buildCasts($schema);
        $usesSoftDeletes = $this->option('soft-deletes') && !$isPivot;
        $usesMultiTenant = $this->option('multi-tenant') && !$isPivot;

        $stub = $this->getStub('model');
        $content = str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ table }}',
                '{{ fillable }}',
                '{{ relationships }}',
                '{{ timestamps }}',
                '{{ casts }}',
                '{{ uses_soft_deletes }}',
                '{{ soft_deletes_trait }}',
                '{{ uses_multi_tenant }}',
                '{{ multi_tenant_trait }}'
            ],
            [
                $namespace,
                $className,
                $tableName,
                $fillable,
                $relationships,
                $isPivot ? 'false' : 'true',
                $casts,
                $usesSoftDeletes ? "use Illuminate\Database\Eloquent\SoftDeletes;\n" : '',
                $usesSoftDeletes ? "\n    use SoftDeletes;" : '',
                $usesMultiTenant ? "use LaravelArchitect\\Traits\\MultiTenantTrait;\n" : '',
                $usesMultiTenant ? "\n    use MultiTenantTrait;" : ''
            ],
            $stub
        );

        $path = $this->getPath('models') . "/{$className}.php";
        $this->writeFile($files, $path, $content);

        $this->info("Model created: {$className}");
    }

    protected function buildFillableArray(array $schema): string
    {
        if (empty($schema)) {
            return '';
        }

        $fillable = array_map(fn($col) => "        '{$col['name']}'", $schema);
        return implode(",\n", $fillable);
    }

    protected function buildRelationships(array $schema, string $namespace): string
    {
        $relationships = [];

        foreach ($schema as $col) {
            if ($this->isForeignKey($col)) {
                $relationships[] = $this->buildBelongsToRelationship($col, $namespace);
            }
        }

        if (empty($relationships)) {
            return '';
        }

        return "\n" . implode("\n", $relationships);
    }

    protected function buildBelongsToRelationship(array $col, string $namespace): string
    {
        $foreignKeyName = $col['name'];
        $tableName = $this->extractForeignTableName($foreignKeyName);
        $methodName = $this->camel($tableName);
        $modelName = $this->getForeignModelName($foreignKeyName);

        return <<<PHP

    /**
     * Get the {$tableName} that owns this record.
     */
    public function {$methodName}()
    {
        return \$this->belongsTo(\\{$namespace}\\{$modelName}::class, '{$foreignKeyName}');
    }
PHP;
    }

    protected function buildCasts(array $schema): string
    {
        $casts = [];

        foreach ($schema as $col) {
            $cast = $this->determineCast($col['type'] ?? 'string');
            if ($cast) {
                $casts[] = "        '{$col['name']}' => '{$cast}'";
            }
        }

        return empty($casts) ? '' : implode(",\n", $casts);
    }

    protected function determineCast(string $type): ?string
    {
        return match($type) {
            'boolean' => 'boolean',
            'integer', 'unsignedBigInteger', 'bigInteger' => 'integer',
            'decimal', 'float', 'double' => 'decimal:2',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            'json' => 'array',
            default => null
        };
    }
}
