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

    protected $signature = 'make:api-migration {name?} {--schema=} {--pivot=false} {--models=} {--update-models=true} {--soft-deletes} {--composite-unique=}';
    protected $description = 'Create a new API migration';

    public function handle(Filesystem $files): void
    {
        $isPivot = $this->option('pivot') === 'true';

        if ($isPivot) {
            $this->handlePivotTable($files);
        } else {
            $this->handleRegularTable($files);
        }
    }

    protected function handlePivotTable(Filesystem $files): void
    {
        $modelsOption = $this->option('models');

        // Validation: --models required when --pivot=true
        if (!$modelsOption) {
            $this->error("The --models option is required when --pivot=true");
            $this->comment("Example: php artisan make:api-migration --pivot=true --models='Post,Tag'");
            return;
        }

        $models = $this->parseModels($modelsOption);

        // Validation: Exactly 2 models required
        if (count($models) !== 2) {
            $this->error("Exactly 2 models are required for pivot tables.");
            $this->comment("Example: --models='Post,Tag'");
            return;
        }

        [$model1, $model2] = $models;

        // Validate both models exist and are valid
        foreach ([$model1, $model2] as $model) {
            if (!$this->isValidModelName($model)) {
                $this->error("Invalid model name: '{$model}'. Must be singular StudlyCase.");
                return;
            }

            if (!$this->modelExists($model)) {
                $this->error("Model '{$model}' does not exist. Create it first:");
                $this->comment("  php artisan make:api-bundle {$model}");
                return;
            }
        }

        // Generate table name (alphabetical)
        $tableName = $this->generatePivotTableName($model1, $model2);

        // Generate schema (preserving user's model order for composite key)
        $schema = $this->parseSchema(json_encode($this->generatePivotSchema($model1, $model2)));

        // Auto-generate composite unique constraint
        $compositeUnique = [array_map(fn($col) => $col['name'], $schema)];

        if (!$this->validateForeignKeys($schema, true)) {
            return;
        }

        // Build and write migration
        $schemaStr = $this->buildSchemaString($schema, true, $compositeUnique, $tableName);
        $content = $this->processStub('migration', [
            'table' => $tableName,
            'schema' => $schemaStr,
        ]);

        $filename = date('Y_m_d_His') . "_create_{$tableName}_table.php";
        $path = $this->getMigrationPath($filename);
        $this->writeFile($files, $path, $content);

        $this->info("Pivot migration created: {$filename}");
        $this->info("Pivot table: {$tableName}");

        // Update models with belongsToMany relationships
        $shouldUpdateModels = $this->option('update-models') !== 'false';

        if ($shouldUpdateModels) {
            $this->newLine();
            $this->info("Updating models with belongsToMany relationships...");

            $result = $this->updatePivotModels($model1, $model2, $tableName);

            foreach ($result['messages'] as $message) {
                $this->comment("  ✓ {$message}");
            }

            if (!$result['success']) {
                $this->warn("Some model updates failed. You may need to manually add relationships.");
            }
        } else {
            $this->newLine();
            $this->comment("Skipped model updates (--update-models=false)");
            $this->comment("Manually add relationships:");
            $this->comment("  {$model1}::belongsToMany({$model2}::class, '{$tableName}')");
            $this->comment("  {$model2}::belongsToMany({$model1}::class, '{$tableName}')");
        }

        $this->displayCompositeUniqueInfo($compositeUnique, $tableName);
    }

    protected function handleRegularTable(Filesystem $files): void
    {
        $name = $this->argument('name');

        if (!$name) {
            $this->error("The name argument is required for non-pivot tables.");
            $this->comment("Example: php artisan make:api-migration Video --schema='...'");
            return;
        }

        // Continue with existing logic...
        $schema = $this->parseSchema($this->option('schema'));
        $compositeUnique = $this->parseCompositeUnique($this->option('composite-unique'));
        $isPivot = false;

        if (!$this->validateForeignKeys($schema, $isPivot)) {
            return;
        }

        $tableName = $this->getTableName($name);

        if ($isPivot && empty($compositeUnique)) {
            $foreignKeys = $this->extractForeignKeys($schema);
            if (count($foreignKeys) === 2) {
                $compositeUnique = [array_map(fn($fk) => $fk['name'], $foreignKeys)];
            }
        }

        $schemaStr = $this->buildSchemaString($schema, $isPivot, $compositeUnique, $tableName);

        $content = $this->processStub('migration', [
            'table' => $tableName,
            'schema' => $schemaStr,
        ]);

        $filename = date('Y_m_d_His') . "_create_{$tableName}_table.php";
        $path = $this->getMigrationPath($filename);

        $this->writeFile($files, $path, $content);

        $this->info("Migration created: {$filename}");

        if (!empty($compositeUnique)) {
            $this->displayCompositeUniqueInfo($compositeUnique, $tableName);
        }
    }

    protected function extractForeignKeys(array $schema): array
    {
        return $this->getForeignKeyFields($schema);
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

                $entityName = $this->extractForeignEntityName($columnName);

                if (!$this->modelOrTableExists($entityName)) {
                    $this->error("Foreign key '{$columnName}' references table/model '{$entityName}' which does not exist.");
                    $this->newLine();
                    $this->comment("You must create the '{$entityName}' model first:");
                    $this->comment("  php artisan make:api-bundle " . $this->getForeignModelName($columnName));
                    $this->newLine();
                    $this->comment("Or if the table exists with a different name, rename your foreign key to match:");
                    $this->comment("  Change '{$columnName}' to '{existing_table}_id'");
                    return false;
                }

                $col['isForeignKey'] = true;

                if (!isset($col['foreignTable'])) {
                    $col['foreignTable'] = $entityName;
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

            // Only validate table name if one was provided (old column-centric syntax)
            // New model-centric syntax doesn't provide a name argument
            $providedName = $this->argument('name');
            if ($providedName) {
                $fkNames = array_map(fn($col) => $this->extractForeignEntityName($col['name']), $foreignKeys);
                sort($fkNames);
                $expectedName = implode('_', $fkNames);

                if (Str::snake($providedName) !== $expectedName) {
                    $this->warn("Pivot table name should be '{$expectedName}' (alphabetical order)");
                    $this->comment("Current: " . Str::snake($providedName));

                    if (!$this->confirm('Continue anyway?', false)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    protected function modelOrTableExists(string $tableName): bool
    {
        $modelName = Str::studly($tableName);
        $modelPath = $this->getModelPath($modelName);

        if (File::exists($modelPath)) {
            return true;
        }

        $migrationsPath = base_path(config('api_scaffolding.paths.migrations'));
        $pluralTableName = Str::plural($tableName);

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

        foreach ($schema as $col) {
            $line = $this->buildColumnDefinition($col);
            $lines[] = $line;

            if ($this->isForeignKey($col)) {
                if (!isset($col['foreignTable'])) {
                    $col['foreignTable'] = $this->extractForeignEntityName($col['name']);
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
            $suffix = chr(96 + $index);  // 96 + 1 = 97 ('a'), 96 + 2 = 98 ('b'), etc.
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
        }

        if (count($compositeUnique) > 1) {
            $this->warn("⚠️  Multiple composite unique keys detected - using suffix notation (_a, _b, etc.)");
        }
    }

    protected function buildColumnDefinition(array $col): string
    {
        $type = $col['type'] ?? 'string';
        $name = $col['name'];

        $line = "            \$table->{$type}('{$name}')";

        if (!$this->isRequired($col)) {
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
        $foreignTable = Str::snake(Str::plural($col['foreignTable'] ?? ''));
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
