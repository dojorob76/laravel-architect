<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use LaravelArchitect\Console\Commands\Support\TypeMappingHelper;
use Illuminate\Support\Str;

class MakeApiModelCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-model {name} {--schema=} {--pivot=false} {--soft-deletes}';
    protected $description = 'Create a new API Model';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $schema = $this->parseSchema($this->option('schema'));
        $isPivot = $this->option('pivot') === 'true';

        $className = Str::studly($name);
        $tableName = $isPivot ? Str::snake($name) : $this->getTableName($name);
        $namespace = $this->getNamespace('models');

        $fillable = $this->buildFillableArray($schema);
        $relationships = $this->buildRelationships($schema, $namespace);
        $casts = $this->buildCasts($schema);
        $usesSoftDeletes = $this->option('soft-deletes') && !$isPivot;

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

        $fillable = array_map(fn($col) => "        '" . $this->getFieldName($col) . "'", $schema);
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
        $foreignKeyName = $this->getFieldName($col);
        $foreignModel = $col['foreignModel'];
        $modelNamespace = $this->getNamespace('models');
        $foreignModelFQN = $this->buildFQNClass($modelNamespace, $foreignModel);
        $relationName = \Illuminate\Support\Str::camel(str_replace('_id', '', $foreignKeyName));
        $tableName = str_replace('_id', '', $foreignKeyName);

        return <<<PHP

    /**
     * Get the {$tableName} that owns this record.
     */
    public function {$relationName}()
    {
        return \$this->belongsTo({$foreignModelFQN}, '{$foreignKeyName}');
    }
PHP;
    }

    protected function buildCasts(array $schema): string
    {
        $casts = [];

        foreach ($schema as $col) {
            $cast = TypeMappingHelper::getCast($col['type'] ?? 'string');
            if ($cast) {
                $casts[] = "        '" . $this->getFieldName($col) . "' => '{$cast}'";
            }
        }

        return empty($casts) ? '' : implode(",\n", $casts);
    }
}
