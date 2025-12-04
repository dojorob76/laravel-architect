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

        $definition = $this->buildDefinition($schema, $compositeUnique);

        $content = $this->processStub('factory', [
            'namespace' => $this->getNamespace('factories'),
            'model_namespace' => $this->getNamespace('models'),
            'class' => $className,
            'definition' => $definition,
        ]);

        $path = $this->getFactoryPath($className);
        $this->writeFile($files, $path, $content);

        $this->info("Factory created: {$className}Factory");
    }

    protected function buildDefinition(array $schema, array $compositeUnique): string
    {
        if (empty($schema)) {
            return '';
        }

        $definitions = [];

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

        return implode(",\n", $definitions);
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
