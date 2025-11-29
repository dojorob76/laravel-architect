<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
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
        $namespace = config('api_scaffolding.namespaces.requests');

        [$rulesStr, $patchRulesStr] = $this->buildValidationRules($schema, $compositeUnique, $name);

        $stub = $this->getStub('request');
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ rules }}', '{{ patch_rules }}'],
            [$namespace, $className, $rulesStr, $patchRulesStr],
            $stub
        );

        $path = base_path(config('api_scaffolding.paths.requests') . "/{$className}Request.php");
        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $content);

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
            $fieldName = $col['name'];
            $validationRules = $this->determineValidationRules($col);

            $rules[] = "            '{$fieldName}' => [" . implode(', ', array_map(fn($r) => "'{$r}'", $validationRules)) . "]";

            if (in_array('required', $validationRules)) {
                $patchAdjustments[] = "            \$rules['{$fieldName}'] = array_diff(\$rules['{$fieldName}'], ['required']);";
                $patchAdjustments[] = "            \$rules['{$fieldName}'][] = 'sometimes';";
            }
        }

        // Add composite unique validation rules
        if (!empty($compositeUnique)) {
            $tableName = $this->snake($this->pluralize($modelName));
            foreach ($compositeUnique as $index => $columns) {
                $compositeRuleName = 'composite_unique_' . $index;
                $columnsStr = implode(',', $columns);
                $rules[] = "            '{$compositeRuleName}' => [function (\$attribute, \$value, \$fail) {
                // Composite unique validation for: {$columnsStr}
                \$query = \\DB::table('{$tableName}');
                " . implode("\n                ", array_map(fn($col) => "\$query->where('{$col}', \$this->input('{$col}'));", $columns)) . "
                if (\$this->route('" . $this->camel($modelName) . "')) {
                    \$query->where('id', '!=', \$this->route('" . $this->camel($modelName) . "')->id ?? \$this->route('" . $this->camel($modelName) . "'));
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
        $rules = [];

        if ($col['required'] ?? false) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $type = $col['type'] ?? 'string';
        $rules = array_merge($rules, $this->getTypeValidationRules($type));

        if ($col['unique'] ?? false) {
            $tableName = $this->snake($this->pluralize($col['table'] ?? 'table'));
            $rules[] = 'unique:' . $tableName . ',' . $col['name'];
        }

        if ($this->isForeignKey($col)) {
            $foreignTable = isset($col['foreignTable'])
                ? $this->snake($this->pluralize($col['foreignTable']))
                : $this->snake($this->pluralize(str_replace('_id', '', $col['name'])));

            $rules[] = "exists:{$foreignTable},id";
        }

        return array_unique($rules);
    }

    protected function getTypeValidationRules(string $type): array
    {
        return match($type) {
            'string', 'text' => ['string'],
            'integer', 'unsignedBigInteger', 'bigInteger' => ['integer'],
            'boolean' => ['boolean'],
            'date' => ['date'],
            'datetime', 'timestamp' => ['date'],
            'email' => ['email'],
            'decimal', 'float', 'double' => ['numeric'],
            'json' => ['array'],
            default => ['string']
        };
    }
}
