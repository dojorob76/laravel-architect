<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use LaravelArchitect\Console\Commands\Support\ConfigHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeApiMigrationCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-migration {name?} {--schema=} {--pivot} {--models=} {--update-models=true} {--soft-deletes} {--composite-unique=}';
    protected $description = 'Create a new API migration';

    public function handle(Filesystem $files): void
    {
        $isPivot = $this->option('pivot');

        if ($isPivot) {
            $this->handlePivotTable($files);
        } else {
            $this->handleRegularTable($files);
        }
    }

    protected function handlePivotTable(Filesystem $files): void
    {
        $modelsOption = $this->option('models');

        // Validation: --models required when --pivot
        if (!$modelsOption) {
            $this->error("The --models option is required when --pivot is used");
            $this->comment("Example: php artisan make:api-migration --pivot --models='Post,Tag'");
            return;
        }

        $models = $this->parseModels($modelsOption);

        // Validation: At least 2 models required
        if (count($models) < 2) {
            $this->error("Pivot tables require at least 2 models.");
            $this->comment("Example: --models='Post,Tag' or --models='User,Role,Team'");
            return;
        }

        // Validate all models exist and are valid
        foreach ($models as $model) {
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
        $tableName = $this->generatePivotTableName($models);

        // Generate FK schema for all models (preserving user's order for composite key)
        $fkSchema = $this->generatePivotForeignKeys($models);

        // Parse any additional fields from --schema
        $additionalFields = $this->parseSchema($this->option('schema'));

        // Merge FK schema with additional fields
        $fullSchema = array_merge($fkSchema, $additionalFields);

        // Auto-generate composite unique constraint from FK columns (in passed order)
        $fkColumns = array_map(fn($col) => $this->getFieldName($col), $fkSchema);
        $compositeUnique = [$fkColumns];

        if (!$this->validateForeignKeys($fullSchema, true)) {
            return;
        }

        // Build and write migration
        $schemaStr = $this->buildSchemaString($fullSchema, true, $compositeUnique, $tableName);
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

            $result = $this->updatePivotModels($models, $tableName);

            foreach ($result['messages'] as $message) {
                $this->comment("  ✓ {$message}");
            }

            if (!$result['success']) {
                $this->warn("Some model updates failed. You may need to manually add relationships.");
            }
        } else {
            $this->newLine();
            $this->comment("Skipped model updates (--update-models=false)");
            $this->comment("Manually add relationships to all models using belongsToMany()");
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
                $compositeUnique = [array_map(fn($fk) => $this->getFieldName($fk), $foreignKeys)];
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
            if ($this->isForeignKey($col)) {
                $columnName = $this->getFieldName($col);

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
            }
        }

        if ($isPivot) {
            $foreignKeys = $this->extractForeignKeys($schema);

            if (count($foreignKeys) < 2) {
                $this->error("Pivot tables must have at least 2 foreign key columns.");
                $this->comment("Example: post_id and tag_id");
                return false;
            }

            // Only validate table name if one was provided
            $providedName = $this->argument('name');
            if ($providedName) {
                $fkNames = array_map(fn($col) => $this->extractForeignEntityName($this->getFieldName($col)), $foreignKeys);
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

        $migrationsPath = ConfigHelper::basePath('migrations');
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
                    $col['foreignTable'] = $this->extractForeignEntityName($this->getFieldName($col));
                }

                $foreignKeys[] = $col;

                if ($isPivot) {
                    $primaryKeys[] = $this->getFieldName($col);
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
        $name = $this->getFieldName($col);

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
        $columnName = $this->getFieldName($col);
        $foreignTable = $this->getTableName($col['foreignTable'] ?? '');
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
