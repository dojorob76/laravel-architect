<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use LaravelArchitect\Console\Commands\Support\TypeMappingHelper;
use Illuminate\Support\Str;

class MakeApiRequestCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-request {name} {--schema=} {--composite-unique=}';
    protected $description = 'Create a new API Form Request';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $schema = $this->parseSchema($this->option('schema'));
        $compositeUnique = $this->parseCompositeUnique($this->option('composite-unique'));
        $className = Str::studly($name);

        [$rulesStr, $patchRulesStr] = $this->buildValidationRules($schema, $compositeUnique, $name);

        $content = $this->processStub('request', [
            'namespace' => $this->getNamespace('requests'),
            'class' => $className,
            'rules' => $rulesStr,
            'patch_rules' => $patchRulesStr,
        ]);

        $path = $this->getRequestPath($className);
        $this->writeFile($files, $path, $content);

        $this->info("Form Request created: {$className}Request");
    }

    protected function buildValidationRules(array $schema, array $compositeUnique, string $modelName): array
    {
        if (empty($schema)) {
            return ['', ''];
        }

        $rules = [];
        $patchAdjustments = [];

        foreach ($schema as $col) {
            $fieldName = $this->getFieldName($col);
            $validationRules = $this->determineValidationRules($col);

            $rules[] = "            '{$fieldName}' => [" . implode(', ', array_map(fn($r) => "'{$r}'", $validationRules)) . "]";

            if (in_array('required', $validationRules)) {
                $patchAdjustments[] = "            \$rules['{$fieldName}'] = array_diff(\$rules['{$fieldName}'], ['required']);";
                $patchAdjustments[] = "            \$rules['{$fieldName}'][] = 'sometimes';";
            }
        }

        // Add composite unique validation rules
        if (!empty($compositeUnique)) {
            $tableName = $this->getTableName($modelName);
            foreach ($compositeUnique as $index => $columns) {
                $compositeRuleName = 'composite_unique_' . $index;
                $columnsStr = implode(',', $columns);
                $rules[] = "            '{$compositeRuleName}' => [function (\$attribute, \$value, \$fail) {
                // Composite unique validation for: {$columnsStr}
                \$query = \\DB::table('{$tableName}');
                " . implode("\n                ", array_map(fn($col) => "\$query->where('{$col}', \$this->input('{$col}'));", $columns)) . "
                if (\$this->route('" . Str::camel($modelName) . "')) {
                    \$query->where('id', '!=', \$this->route('" . Str::camel($modelName) . "')->id ?? \$this->route('" . Str::camel($modelName) . "'));
                }
                if (\$query->exists()) {
                    \$fail('The combination of " . implode(', ', $columns) . " has already been taken.');
                }
            }]";
            }
        }

        $rulesStr = implode(",\n", $rules);
        $patchRulesStr = implode("\n", $patchAdjustments);

        return [$rulesStr, $patchRulesStr];
    }

    protected function determineValidationRules(array $col): array
    {
        // Get base rules from helper (handles required/nullable + type)
        $rules = TypeMappingHelper::buildValidationRules($col);

        // Add unique validation if specified
        if ($col['unique'] ?? false) {
            $tableName = $this->getTableName($col['table'] ?? 'table');
            $rules[] = 'unique:' . $tableName . ',' . $this->getFieldName($col);
        }

        // Add foreign key exists validation
        if ($this->isForeignKey($col)) {
            $foreignTable = $this->getTableName($col['foreignTable']);
            $rules[] = "exists:{$foreignTable},id";
        }

        return array_unique($rules);
    }
}
