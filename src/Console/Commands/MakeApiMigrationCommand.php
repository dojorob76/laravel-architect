<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeApiMigrationCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-migration {name} {--schema=} {--pivot=false} {--soft-deletes} {--multi-tenant} {--composite-unique=}';
    protected $description = 'Create a new API migration';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $schema = $this->parseSchema($this->option('schema'));
        $compositeUnique = $this->parseCompositeUnique($this->option('composite-unique'));
        $isPivot = $this->option('pivot') === 'true';

        if (!$this->validateForeignKeys($schema, $isPivot)) {
            return;
        }

        $tableName = $isPivot ? $this->snake($name) : $this->snake($this->pluralize($name));

        if ($isPivot && empty($compositeUnique)) {
            $foreignKeys = $this->extractForeignKeys($schema);
            if (count($foreignKeys) === 2) {
                $compositeUnique = [array_map(fn($fk) => $fk['name'], $foreignKeys)];
            }
        }

        $schemaStr = $this->buildSchemaString($schema, $isPivot, $compositeUnique, $tableName);

        $stub = $this->getStub('migration');
        $content = str_replace(
            ['{{ table }}', '{{ schema }}'],
            [$tableName, $schemaStr],
            $stub
        );

        $filename = date('Y_m_d_His') . "_create_{$tableName}_table.php";
        $path = base_path(config('api_scaffolding.paths.migrations') . "/{$filename}");

        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $content);

        $this->info("Migration created: {$filename}");

        if (!empty($compositeUnique)) {
            $this->displayCompositeUniqueInfo($compositeUnique, $tableName);
        }
    }

    protected function parseCompositeUnique(?string $compositeUniqueJson): array
    {
        if (!$compositeUniqueJson) {
            return [];
        }

        $parsed = json_decode($compositeUniqueJson, true);

        if (!is_array($parsed)) {
            return [];
        }

        if (!empty($parsed) && !is_array($parsed[0])) {
            return [$parsed];
        }

        return $parsed;
    }

    protected function extractForeignKeys(array $schema): array
    {
        return array_filter($schema, fn($col) => $this->isForeignKey($col));
    }

    protected function validateForeignKeys(array $schema, bool $isPivot): bool
    {
        foreach ($schema as $col) {
            $columnName = $col['name'];

            if ($this->isForeignKey($col)) {
                if (!str_ends_with($columnName, '_id')) {
                    $this->error("Foreign key column '{$columnName}' must end with '_id'");
                    $this->comment("Example: user_id, blog_post_id, tag_id");
                    return false;
                }

                $tableName = str_replace('_id', '', $columnName);

                if (!$this->modelOrTableExists($tableName)) {
                    $this->error("Foreign key '{$columnName}' references table/model '{$tableName}' which does not exist.");
                    $this->newLine();
                    $this->comment("You must create the '{$tableName}' model first:");
                    $this->comment("  php artisan make:api-bundle " . Str::studly($tableName));
                    $this->newLine();
                    $this->comment("Or if the table exists with a different name, rename your foreign key to match:");
                    $this->comment("  Change '{$columnName}' to '{existing_table}_id'");
                    return false;
                }

                $col['isForeignKey'] = true;

                if (!isset($col['foreignTable'])) {
                    $col['foreignTable'] = $tableName;
                }
            }
        }

        if ($isPivot) {
            $foreignKeys = $this->extractForeignKeys($schema);

            if (count($foreignKeys) !== 2) {
                $this->error("Pivot tables must have exactly 2 foreign key columns.");
                $this->comment("Example: post_id and tag_id");
                return false;
            }

            $fkNames = array_map(fn($col) => str_replace('_id', '', $col['name']), $foreignKeys);
            sort($fkNames);
            $expectedName = implode('_', $fkNames);

            if ($this->snake($this->argument('name')) !== $expectedName) {
                $this->warn("Pivot table name should be '{$expectedName}' (alphabetical order)");
                $this->comment("Current: " . $this->snake($this->argument('name')));

                if (!$this->confirm('Continue anyway?', false)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function modelOrTableExists(string $tableName): bool
    {
        $modelName = Str::studly($tableName);
        $modelPath = base_path(config('api_scaffolding.paths.models') . "/{$modelName}.php");

        if (File::exists($modelPath)) {
            return true;
        }

        $migrationsPath = base_path(config('api_scaffolding.paths.migrations'));
        $pluralTableName = $this->pluralize($tableName);

        if (File::exists($migrationsPath)) {
            $migrations = File::files($migrationsPath);
            foreach ($migrations as $migration) {
                if (str_contains($migration->getFilename(), "create_{$pluralTableName}_table") ||
                    str_contains($migration->getFilename(), "create_{$tableName}_table")) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function buildSchemaString(array $schema, bool $isPivot, array $compositeUnique, string $tableName): string
    {
        $lines = [];

        if (!$isPivot) {
            $lines[] = "            \$table->id();";
        }

        $foreignKeys = [];
        $primaryKeys = [];

        if (!$isPivot && $this->option('multi-tenant')) {
            $lines[] = "            \$table->unsignedBigInteger('tenant_id')->index();";
            $foreignKeys[] = [
                'name' => 'tenant_id',
                'foreignTable' => 'tenant',
                'isForeignKey' => true
            ];
        }

        foreach ($schema as $col) {
            $line = $this->buildColumnDefinition($col);
            $lines[] = $line;

            if ($this->isForeignKey($col)) {
                if (!isset($col['foreignTable'])) {
                    $col['foreignTable'] = str_replace('_id', '', $col['name']);
                }

                $foreignKeys[] = $col;

                if ($isPivot) {
                    $primaryKeys[] = $col['name'];
                }
            }
        }

        if ($isPivot && count($primaryKeys) > 0) {
            $pkList = implode("', '", $primaryKeys);
            $lines[] = "            \$table->primary(['{$pkList}']);";
        }

        if (!empty($compositeUnique)) {
            $lines[] = $this->buildCompositeUniqueConstraints($compositeUnique, $tableName);
        }

        foreach ($foreignKeys as $fk) {
            $lines[] = $this->buildForeignKeyConstraint($fk);
        }

        if (!$isPivot) {
            $lines[] = "            \$table->timestamp('created_at')->useCurrent();";
            $lines[] = "            \$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();";

            if ($this->option('soft-deletes')) {
                $lines[] = "            \$table->softDeletes();";
            }
        }

        return implode("\n", $lines);
    }

    protected function buildCompositeUniqueConstraints(array $compositeUnique, string $tableName): string
    {
        $constraints = [];

        foreach ($compositeUnique as $index => $columns) {
            if (empty($columns) || !is_array($columns)) {
                continue;
            }

            $indexName = $this->generateCompositeUniqueName($tableName, $columns, $index);
            $columnsList = implode("', '", $columns);
            $constraints[] = "            \$table->unique(['{$columnsList}'], '{$indexName}');";
        }

        return implode("\n", $constraints);
    }

    protected function generateCompositeUniqueName(string $tableName, array $columns, int $index): string
    {
        $columnCount = count($columns);

        if ($columnCount === 1) {
            return "{$tableName}_{$columns[0]}_unique";
        }

        if ($columnCount === 2) {
            $baseName = "{$tableName}_composite_unique";
        } else {
            $baseName = "{$tableName}_multi_composite_unique";
        }

        if ($index > 0) {
            $suffix = chr(97 + $index);
            $baseName .= "_{$suffix}";
        }

        return $baseName;
    }

    protected function displayCompositeUniqueInfo(array $compositeUnique, string $tableName): void
    {
        $this->newLine();
        $this->comment("📋 Composite Unique Keys Created:");

        foreach ($compositeUnique as $index => $columns) {
            $indexName = $this->generateCompositeUniqueName($tableName, $columns, $index);
            $columnsList = implode(', ', $columns);

            $this->comment("   • {$indexName}: [{$columnsList}]");

            if ($index > 0 || count($compositeUnique) > 1) {
                $this->warn("   ⚠️  Multiple composite unique keys detected - using suffix notation (_a, _b, etc.)");
            }
        }
    }

    protected function buildColumnDefinition(array $col): string
    {
        $type = $col['type'] ?? 'string';
        $name = $col['name'];

        $line = "            \$table->{$type}('{$name}')";

        if (!($col['required'] ?? false)) {
            $line .= "->nullable()";
        }

        if ($col['unique'] ?? false) {
            $line .= "->unique()";
        }

        if (isset($col['default'])) {
            $default = is_string($col['default']) ? "'{$col['default']}'" : $col['default'];
            $line .= "->default({$default})";
        }

        $line .= ";";

        return $line;
    }

    protected function buildForeignKeyConstraint(array $col): string
    {
        $columnName = $col['name'];
        $foreignTable = $this->snake($this->pluralize($col['foreignTable'] ?? ''));
        $foreignColumn = 'id';

        $constraint = "            \$table->foreign('{$columnName}')";
        $constraint .= "->references('{$foreignColumn}')->on('{$foreignTable}')";

        if ($col['onDelete'] ?? false) {
            $constraint .= "->onDelete('{$col['onDelete']}')";
        } else {
            $constraint .= "->onDelete('cascade')";
        }

        if ($col['onUpdate'] ?? false) {
            $constraint .= "->onUpdate('{$col['onUpdate']}')";
        }

        $constraint .= ";";

        return $constraint;
    }
}
