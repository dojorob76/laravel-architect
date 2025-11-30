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

        $className = Str::studly($name);
        $tableName = $this->getTableName($name, $isPivot);
        $namespace = $this->getNamespace('models');

        $fillable = $this->buildFillableArray($schema);
        $relationships = $this->buildRelationships($schema, $namespace);
        $casts = $this->buildCasts($schema);
        $usesSoftDeletes = $this->option('soft-deletes') && !$isPivot;
        $usesMultiTenant = $this->option('multi-tenant') && !$isPivot;

        $content = $this->processStub('model', [
            'namespace' => $namespace,
            'class' => $className,
            'table' => $tableName,
            'fillable' => $fillable,
            'relationships' => $relationships,
            'timestamps' => $isPivot ? 'false' : 'true',
            'casts' => $casts,
            'uses_soft_deletes' => $usesSoftDeletes ? "use Illuminate\Database\Eloquent\SoftDeletes;\n" : '',
            'soft_deletes_trait' => $usesSoftDeletes ? "\n    use SoftDeletes;" : '',
            'uses_multi_tenant' => $usesMultiTenant ? "use LaravelArchitect\\Traits\\MultiTenantTrait;\n" : '',
            'multi_tenant_trait' => $usesMultiTenant ? "\n    use MultiTenantTrait;" : '',
        ]);

        $path = $this->getModelPath($className);
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
        $rel = $this->extractRelationshipInfo($foreignKeyName);

        return <<<PHP

    /**
     * Get the {$rel->tableName} that owns this record.
     */
    public function {$rel->relationName}()
    {
        return \$this->belongsTo({$rel->modelFQN}, '{$foreignKeyName}');
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
