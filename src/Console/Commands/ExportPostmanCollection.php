<?php

namespace LaravelApi\StarterKit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LaravelApi\StarterKit\Services\PostmanCollectionBuilder;

class ExportPostmanCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:export
                            {--output= : Output file path (default: storage/app/postman_collection.json)}
                            {--name= : Collection name (default: from config or app name)}
                            {--bearer= : Default Bearer token for authenticated routes}
                            {--base-url= : Base URL override (default: from config)}
                            {--group-by=prefix : Group routes by "prefix" or "middleware"}
                            {--include= : Only include routes matching this pattern (regex)}
                            {--exclude= : Exclude routes matching this pattern (regex)}
                            {--force : Overwrite existing file without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export API routes as a Postman Collection v2.1 JSON file';

    /**
     * Command aliases.
     *
     * @var array
     */
    protected $aliases = ['api:export-postman'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Router $router, PostmanCollectionBuilder $builder): int
    {
        $this->info('📦 Exporting Postman Collection...');
        $this->newLine();

        // Collect and filter routes
        $routes = $this->collectApiRoutes($router);

        if ($routes->isEmpty()) {
            $this->warn('⚠ No API routes found to export.');

            return Command::FAILURE;
        }

        // Apply include/exclude filters
        $routes = $this->applyFilters($routes);

        if ($routes->isEmpty()) {
            $this->warn('⚠ No routes remaining after applying filters.');

            return Command::FAILURE;
        }

        // Build the collection
        $options = [
            'name' => $this->option('name'),
            'base_url' => $this->option('base-url'),
            'bearer' => $this->option('bearer'),
            'group_by' => $this->option('group-by'),
        ];

        $collection = $builder->build($routes, array_filter($options));
        $json = $builder->toJson($collection);

        // Determine output path
        $outputPath = $this->resolveOutputPath();

        // Check if file exists
        if (File::exists($outputPath) && ! $this->option('force')) {
            if (! $this->confirm("File already exists at {$outputPath}. Overwrite?")) {
                $this->info('Export cancelled.');

                return Command::SUCCESS;
            }
        }

        // Write the file
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $json);

        // Display summary
        $this->displaySummary($routes, $outputPath, $collection);

        return Command::SUCCESS;
    }

    /**
     * Collect all API routes from the router.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return \Illuminate\Support\Collection
     */
    protected function collectApiRoutes(Router $router): Collection
    {
        $prefix = trim(config('api-starter-kit.prefix', 'api'), '/');

        return collect($router->getRoutes()->getRoutes())
            ->filter(function (Route $route) use ($prefix) {
                $uri = $route->uri();

                return Str::startsWith($uri, $prefix . '/') || $uri === $prefix;
            })
            ->values();
    }

    /**
     * Apply include/exclude regex filters to routes.
     *
     * @param  \Illuminate\Support\Collection  $routes
     * @return \Illuminate\Support\Collection
     */
    protected function applyFilters(Collection $routes): Collection
    {
        $include = $this->option('include');
        $exclude = $this->option('exclude');

        if ($include) {
            $routes = $routes->filter(function (Route $route) use ($include) {
                return preg_match('/' . $include . '/', $route->uri());
            });
        }

        if ($exclude) {
            $routes = $routes->reject(function (Route $route) use ($exclude) {
                return preg_match('/' . $exclude . '/', $route->uri());
            });
        }

        return $routes->values();
    }

    /**
     * Resolve the output file path.
     *
     * @return string
     */
    protected function resolveOutputPath(): string
    {
        if ($this->option('output')) {
            $path = $this->option('output');

            // If it's a relative path, make it absolute from the base path
            if (! Str::startsWith($path, '/')) {
                return base_path($path);
            }

            return $path;
        }

        $configPath = config('api-starter-kit.postman.output_path', 'postman_collection.json');

        return storage_path('app/' . $configPath);
    }

    /**
     * Display a summary of the exported collection.
     *
     * @param  \Illuminate\Support\Collection  $routes
     * @param  string  $outputPath
     * @param  array  $collection
     * @return void
     */
    protected function displaySummary(Collection $routes, string $outputPath, array $collection): void
    {
        $this->newLine();
        $this->info('✅ Postman Collection exported successfully!');
        $this->newLine();

        // Route summary table
        $rows = [];
        foreach ($routes as $route) {
            $methods = array_filter($route->methods(), fn ($m) => $m !== 'HEAD');
            $middleware = implode(', ', array_map(function ($mw) {
                return $mw instanceof \Closure ? 'Closure' : $mw;
            }, $route->gatherMiddleware()));

            $rows[] = [
                implode('|', $methods),
                $route->uri(),
                $route->getName() ?? '—',
                Str::limit($middleware, 40) ?: '—',
            ];
        }

        $this->table(
            ['Method', 'URI', 'Name', 'Middleware'],
            $rows
        );

        $this->newLine();
        $this->info("📄 Collection: {$collection['info']['name']}");
        $this->info("📁 File: {$outputPath}");
        $this->info("🔢 Routes exported: {$routes->count()}");
        $this->info("📦 File size: " . $this->formatFileSize(strlen(File::get($outputPath))));
    }

    /**
     * Format file size to human-readable string.
     *
     * @param  int  $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
