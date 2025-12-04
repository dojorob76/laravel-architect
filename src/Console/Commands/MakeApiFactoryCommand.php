<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
use LaravelArchitect\Console\Commands\Support\TypeMappingHelper;
use Illuminate\Support\Str;

class MakeApiFactoryCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-factory {name} {--schema=} {--composite-unique=}';
    protected $description = 'Create a model factory';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $schema = $this->parseSchema($this->option('schema'));
        $compositeUnique = $this->parseCompositeUnique($this->option('composite-unique'));
        $className = Str::studly($name);

        [$definition, $imports] = $this->buildDefinitionWithImports($schema, $compositeUnique);

        $content = $this->processStub('factory', [
            'namespace' => $this->getNamespace('factories'),
            'model_namespace' => $this->getNamespace('models'),
            'class' => $className,
            'definition' => $definition,
            'additional_imports' => $this->formatImports($imports),
        ]);

        $path = $this->getFactoryPath($className);
        $this->writeFile($files, $path, $content);

        $this->info("Factory created: {$className}Factory");
    }

    protected function buildDefinitionWithImports(array $schema, array $compositeUnique): array
    {
        if (empty($schema)) {
            return ['', []];
        }

        $definitions = [];
        $imports = [];

        // Add comment about composite unique constraints if they exist
        if (!empty($compositeUnique)) {
            $definitions[] = "            // NOTE: Composite unique constraints exist on:";
            foreach ($compositeUnique as $columns) {
                $definitions[] = "            // - " . implode(', ', $columns);
            }
            $definitions[] = "            // Ensure these field combinations are unique in your tests";
        }

        foreach ($schema as $col) {
            $fakerMethod = $this->determineFakerMethod($col);

            // Collect required imports
            $requiredImports = TypeMappingHelper::getRequiredImports($fakerMethod);
            foreach ($requiredImports as $import) {
                if (!in_array($import, $imports)) {
                    $imports[] = $import;
                }
            }

            // Mark fields that are part of composite unique constraints
            $isPartOfComposite = false;
            foreach ($compositeUnique as $columns) {
                if (in_array($col['name'], $columns)) {
                    $isPartOfComposite = true;
                    break;
                }
            }

            if ($isPartOfComposite && !($col['isForeignKey'] ?? false) && !str_ends_with($col['name'], '_id')) {
                // Use unique() faker modifier for composite fields when possible
                $definitions[] = "            '{$col['name']}' => fake()->unique()->" . trim($fakerMethod, 'fake()->');
            } else {
                $definitions[] = "            '{$col['name']}' => {$fakerMethod}";
            }
        }

        return [implode(",\n", $definitions), $imports];
    }

    protected function formatImports(array $imports): string
    {
        if (empty($imports)) {
            return '';
        }

        $formatted = [];
        foreach ($imports as $import) {
            $formatted[] = "use {$import};";
        }

        return implode("\n", $formatted);
    }

    protected function determineFakerMethod(array $col): string
    {
        $type = $col['type'] ?? 'string';

        if ($this->isForeignKey($col)) {
            $foreignModel = $this->getForeignModelName($col['name']);
            $modelNamespace = $this->getNamespace('models');
            return $this->buildFactoryCall($modelNamespace, $foreignModel);
        }

        return TypeMappingHelper::getBestFakerMethod($type, $col['name']);
    }
}
