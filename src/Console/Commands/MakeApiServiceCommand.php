<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelArchitect\Console\Commands\Support\ClassMetaBuilderHelper;
use LaravelArchitect\Console\Commands\Support\NamingConventionHelper;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;

class MakeApiServiceCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-service {name}
        {--soft-deletes}
        {--no-bulk}';
    protected $description = 'Create a new API Service';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $softDeletes = (bool) $this->option('soft-deletes');
        $noBulk = (bool) $this->option('no-bulk');

        // Get all metadata from ClassMetaBuilderHelper
        $modelName = NamingConventionHelper::modelToClassName($name);
        $serviceMeta = ClassMetaBuilderHelper::build('service', $modelName);
        $crudInterfaceMeta = ClassMetaBuilderHelper::build('interface', $modelName, 'crud');

        // Get conditional placeholders based on flags
        $conditionalPlaceholders = $this->getServicePlaceholders($modelName, $softDeletes, $noBulk);

        // Merge with standard placeholders
        $placeholders = array_merge([
            'namespace' => $serviceMeta['namespace'],
            'contracts_namespace' => $crudInterfaceMeta['namespace'],
            'class' => $modelName,
        ], $conditionalPlaceholders);

        $content = $this->processStub('service', $placeholders);

        $this->writeFile($files, $serviceMeta['path'], $content);

        $this->info("Service created: {$serviceMeta['class_name']}");

        return self::SUCCESS;
    }

    /**
     * Get placeholder values for service stub based on flags.
     *
     * @param string $modelName The model class name
     * @param bool $softDeletes Whether soft deletes are enabled
     * @param bool $noBulk Whether bulk operations are disabled
     * @return array<string, string> Placeholder => value pairs
     */
    protected function getServicePlaceholders(string $modelName, bool $softDeletes, bool $noBulk): array
    {
        // Define operation types with their enabled state and property names.
        // The 'property' value must match the stub calls to $this->property->method()
        $operationTypes = [
            'bulk' => ['enabled' => !$noBulk, 'property' => 'bulkRepository'],
            'soft_delete' => ['enabled' => $softDeletes, 'property' => 'softDeleteRepository'],
            'bulk_soft_delete' => ['enabled' => $softDeletes && !$noBulk, 'property' => 'bulkSoftDeleteRepository'],
        ];

        $imports = '';
        $constructorParams = '';
        $methods = '';

        foreach ($operationTypes as $operationType => $config) {
            if (!$config['enabled']) {
                continue;
            }

            $meta = ClassMetaBuilderHelper::build('interface', $modelName, $operationType);

            $imports .= "\nuse {$meta['fqcn']};";
            $constructorParams .= "\n        protected {$meta['class_name']} \${$config['property']},";
            $methods .= $this->getStub($operationType . '_methods', 'service');
        }

        return [
            'additional_imports' => $imports,
            'additional_constructor_params' => $constructorParams,
            'additional_methods' => $methods,
        ];
    }
}
