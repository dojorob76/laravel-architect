<?php

namespace LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use LaravelArchitect\Console\Commands\Traits\SchemaParserTrait;

class MakeApiRoutesCommand extends Command
{
    use SchemaParserTrait;

    protected $signature = 'make:api-routes {name} {--soft-deletes}';
    protected $description = 'Add API resource routes to routes/api.php';

    public function handle(Filesystem $files): void
    {
        $name = $this->argument('name');
        $hasSoftDeletes = $this->option('soft-deletes');
        $className = $this->studly($name);
        $routeName = Str::snake(Str::pluralStudly($name));

        $apiRoutesPath = base_path(config('api_scaffolding.paths.routes'));

        if (!$files->exists($apiRoutesPath)) {
            $this->error('routes/api.php not found!');
            return;
        }

        $content = $files->get($apiRoutesPath);

        $controllerNamespace = config('api_scaffolding.namespaces.controllers');

        if (str_contains($content, "'{$routeName}'") || str_contains($content, "{$className}Controller")) {
            $this->warn("Routes for {$className} may already exist in routes/api.php");

            if (!$this->confirm('Do you want to add them anyway?', false)) {
                return;
            }
        }

        $useStatement = "use {$controllerNamespace}\\{$className}Controller;";
        $routeDefinition = $this->buildRouteDefinition($routeName, $className, $hasSoftDeletes);

        if (!str_contains($content, $useStatement)) {
            $content = $this->addUseStatement($content, $useStatement);
        }

        $content = $this->addRoutesInOrder($content, $routeName, $routeDefinition);

        $files->put($apiRoutesPath, $content);

        $this->info("✅ Routes added to routes/api.php");
        $this->newLine();
        $this->comment("Added routes:");
        $this->comment("  GET     /api/{$routeName}");
        $this->comment("  POST    /api/{$routeName}");
        $this->comment("  GET     /api/{$routeName}/{id}");
        $this->comment("  PATCH   /api/{$routeName}/{id}");
        $this->comment("  DELETE  /api/{$routeName}/{id}");
        $this->comment("  POST    /api/{$routeName}/batch/show");
        $this->comment("  POST    /api/{$routeName}/batch");
        $this->comment("  PATCH   /api/{$routeName}/batch");
        $this->comment("  DELETE  /api/{$routeName}/batch");

        if ($hasSoftDeletes) {
            $this->comment("  POST    /api/{$routeName}/{id}/restore");
            $this->comment("  DELETE  /api/{$routeName}/{id}/force");
            $this->comment("  POST    /api/{$routeName}/batch/restore");
            $this->comment("  DELETE  /api/{$routeName}/batch/force");
        }
    }

    protected function buildRouteDefinition(string $routeName, string $className, bool $hasSoftDeletes): string
    {
        $routes = "// {$className} routes\n";
        $routes .= "Route::apiResource('{$routeName}', {$className}Controller::class);\n";
        $routes .= "Route::post('{$routeName}/batch/show', [{$className}Controller::class, 'batchShow']);\n";
        $routes .= "Route::post('{$routeName}/batch', [{$className}Controller::class, 'batchStore']);\n";
        $routes .= "Route::patch('{$routeName}/batch', [{$className}Controller::class, 'batchUpdate']);\n";
        $routes .= "Route::delete('{$routeName}/batch', [{$className}Controller::class, 'batchDestroy']);";

        if ($hasSoftDeletes) {
            $routes .= "\nRoute::post('{$routeName}/{id}/restore', [{$className}Controller::class, 'restore']);";
            $routes .= "\nRoute::delete('{$routeName}/{id}/force', [{$className}Controller::class, 'forceDelete']);";
            $routes .= "\nRoute::post('{$routeName}/batch/restore', [{$className}Controller::class, 'batchRestore']);";
            $routes .= "\nRoute::delete('{$routeName}/batch/force', [{$className}Controller::class, 'batchForceDelete']);";
        }

        return $routes;
    }

    protected function addUseStatement(string $content, string $useStatement): string
    {
        preg_match_all('/^use .+;$/m', $content, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            $content = preg_replace('/^<\?php\s*/m', "<?php\n\n{$useStatement}\n", $content);
        } else {
            $useStatements = array_column($matches[0], 0);
            $useStatements[] = $useStatement;
            sort($useStatements);

            $lastUse = end($matches[0]);
            $position = $lastUse[1] + strlen($lastUse[0]);

            foreach (array_reverse($matches[0]) as $match) {
                $content = substr_replace($content, '', $match[1], strlen($match[0]) + 1);
            }

            $useBlock = implode("\n", $useStatements) . "\n";
            $content = preg_replace('/^<\?php\s*/m', "<?php\n\n{$useBlock}\n", $content);
        }

        return $content;
    }

    protected function addRoutesInOrder(string $content, string $routeName, string $routeDefinition): string
    {
        preg_match_all('/\/\/ .+ routes\s*\nRoute::apiResource\([\'"]([^\'"]+)[\'"].*?\);(?:\s*Route::(?:post|patch|delete).*?;)*/s', $content, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            return rtrim($content) . "\n\n" . $routeDefinition . "\n";
        }

        $routes = [];
        foreach ($matches[1] as $index => $match) {
            $routes[$match[0]] = [
                'name' => $match[0],
                'definition' => $matches[0][$index][0],
                'position' => $matches[0][$index][1]
            ];
        }

        $routes[$routeName] = [
            'name' => $routeName,
            'definition' => $routeDefinition,
            'position' => -1
        ];

        ksort($routes);

        foreach (array_reverse($matches[0]) as $match) {
            $content = substr_replace($content, '', $match[1], strlen($match[0]));
        }

        preg_match_all('/^use .+;$/m', $content, $useMatches, PREG_OFFSET_CAPTURE);

        if (!empty($useMatches[0])) {
            $lastUse = end($useMatches[0]);
            $insertPosition = $lastUse[1] + strlen($lastUse[0]);
        } else {
            $insertPosition = strlen($content);
        }

        $routeBlock = "\n\n" . implode("\n\n", array_column($routes, 'definition')) . "\n";

        $content = substr_replace($content, $routeBlock, $insertPosition, 0);

        return $content;
    }
}
