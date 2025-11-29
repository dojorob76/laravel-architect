<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;
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
        $namespace = config('api_scaffolding.namespaces.factories');
        $modelNamespace = config('api_scaffolding.namespaces.models');

        $definition = $this->buildDefinition($schema, $compositeUnique);

        $stub = $this->getStub('factory');
        $content = str_replace(
            ['{{ namespace }}', '{{ model_namespace }}', '{{ class }}', '{{ definition }}'],
            [$namespace, $modelNamespace, $className, $definition],
            $stub
        );

        $path = base_path(config('api_scaffolding.paths.factories') . "/{$className}Factory.php");
        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $content);

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
            $tableName = str_replace('_id', '', $col['name']);
            $foreignModel = Str::studly($tableName);
            $modelNamespace = config('api_scaffolding.namespaces.models');
            return "\\{$modelNamespace}\\{$foreignModel}::factory()";
        }

        $name = strtolower($col['name']);
        if (str_contains($name, 'email')) return 'fake()->safeEmail()';
        if (str_contains($name, 'phone')) return 'fake()->phoneNumber()';
        if (str_contains($name, 'address')) return 'fake()->address()';
        if (str_contains($name, 'city')) return 'fake()->city()';
        if (str_contains($name, 'country')) return 'fake()->country()';
        if (str_contains($name, 'url')) return 'fake()->url()';
        if (str_contains($name, 'description')) return 'fake()->paragraph()';
        if (str_contains($name, 'title') || str_contains($name, 'name')) return 'fake()->words(3, true)';

        return match($type) {
            'string' => 'fake()->word()',
            'text' => 'fake()->paragraph()',
            'integer', 'unsignedBigInteger', 'bigInteger' => 'fake()->numberBetween(1, 1000)',
            'boolean' => 'fake()->boolean()',
            'decimal', 'float', 'double' => 'fake()->randomFloat(2, 0, 1000)',
            'date' => 'fake()->date()',
            'datetime', 'timestamp' => 'fake()->dateTime()',
            'email' => 'fake()->safeEmail()',
            'json' => "['key' => fake()->word()]",
            default => 'fake()->word()'
        };
    }
}
