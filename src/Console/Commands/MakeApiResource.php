<?php

namespace LaravelApi\StarterKit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeApiResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api-resource 
                            {name : The name of the resource} 
                            {--model= : The model name (defaults to singular of resource name)}
                            {--controller= : The controller name (defaults to plural of resource name)}
                            {--transformer= : The transformer name (defaults to resource name + Transformer)}
                            {--migration : Create a migration file for the model}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API resource (model, controller, transformer, routes)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $model = $this->option('model') ?? ucfirst(Str::singular($name));
        $controller = $this->option('controller') ?? ucfirst(Str::plural($name)) . 'Controller';
        $transformer = $this->option('transformer') ?? ucfirst(Str::singular($name)) . 'Transformer';
        $createMigration = $this->option('migration');
        $force = $this->option('force');

        $this->info("Creating API resource: {$name}");
        $this->newLine();

        // Create Model
        $this->createModel($model, $transformer, $force);

        // Create Controller
        $this->createController($controller, $model);

        // Create Transformer
        $this->createTransformer($transformer, $model);

        // Create Migration if requested
        if ($createMigration) {
            $this->createMigration($model);
        }

        // Add routes
        $this->addRoutes($name, $controller);

        $this->newLine();
        $this->info("✓ API resource '{$name}' created successfully!");
        $this->info("  - Model: app/Models/{$model}.php");
        $this->info("  - Controller: app/Http/Controllers/Api/{$controller}.php");
        $this->info("  - Transformer: app/Transformers/{$transformer}.php");
        if ($createMigration) {
            $this->info("  - Migration: database/migrations/" . date('Y_m_d_His') . "_create_" . Str::plural(strtolower($model)) . "_table.php");
        }
        $this->info("  - Routes added to routes/api.php");

        return Command::SUCCESS;
    }

    /**
     * Get the path to the stubs directory.
     */
    protected function getStubPath(): string
    {
        return __DIR__ . '/../../../stubs';
    }

    /**
     * Get the contents of a stub file and replace placeholders.
     */
    protected function getStubContents(string $stubFile, array $replacements): string
    {
        $stubPath = $this->getStubPath() . '/' . $stubFile;

        if (!File::exists($stubPath)) {
            throw new \RuntimeException("Stub file not found: {$stubPath}");
        }

        $content = File::get($stubPath);

        foreach ($replacements as $placeholder => $value) {
            $content = str_replace('{{' . $placeholder . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Create the model file.
     */
    protected function createModel(string $model, string $transformer, bool $force): void
    {
        $path = app_path("Models/{$model}.php");

        if (File::exists($path) && !$force) {
            $this->warn("Model {$model} already exists. Skipping...");
            return;
        }

        $namespace = config('api-starter-kit.models_namespace', 'App\\Models');
        $table = Str::plural(strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $model)));

        $content = $this->getStubContents('model.stub', [
            'namespace' => $namespace,
            'model' => $model,
            'transformer' => $transformer,
            'table' => $table,
        ]);

        File::ensureDirectoryExists(app_path('Models'));
        File::put($path, $content);

        $this->info("✓ Model created: {$model}");
    }

    /**
     * Create the controller file.
     */
    protected function createController(string $controller, string $model): void
    {
        $path = app_path("Http/Controllers/Api/{$controller}.php");

        if (File::exists($path)) {
            $this->warn("Controller {$controller} already exists. Skipping...");
            return;
        }

        $namespace = config('api-starter-kit.controllers_namespace', 'App\\Http\\Controllers\\Api');
        $modelNamespace = config('api-starter-kit.models_namespace', 'App\\Models');
        $variable = strtolower($model);

        $content = $this->getStubContents('controller.stub', [
            'namespace' => $namespace,
            'modelNamespace' => $modelNamespace,
            'model' => $model,
            'controller' => $controller,
            'variable' => $variable,
        ]);

        File::ensureDirectoryExists(app_path('Http/Controllers/Api'));
        File::put($path, $content);

        $this->info("✓ Controller created: {$controller}");
    }

    /**
     * Create the transformer file.
     */
    protected function createTransformer(string $transformer, string $model): void
    {
        $path = app_path("Transformers/{$transformer}.php");

        if (File::exists($path)) {
            $this->warn("Transformer {$transformer} already exists. Skipping...");
            return;
        }

        $modelNamespace = config('api-starter-kit.models_namespace', 'App\\Models');

        $content = $this->getStubContents('transformer.stub', [
            'modelNamespace' => $modelNamespace,
            'model' => $model,
            'transformer' => $transformer,
        ]);

        File::ensureDirectoryExists(app_path('Transformers'));
        File::put($path, $content);

        $this->info("✓ Transformer created: {$transformer}");
    }

    /**
     * Create a migration file.
     */
    protected function createMigration(string $model): void
    {
        $table = Str::plural(strtolower($model));
        $timestamp = date('Y_m_d_His');
        $migrationName = "create_{$table}_table";
        $migrationFile = "{$timestamp}_{$migrationName}.php";

        $path = database_path("migrations/{$migrationFile}");

        if (File::exists($path)) {
            $this->warn("Migration {$migrationName} already exists. Skipping...");
            return;
        }

        $content = $this->getStubContents('migration.stub', [
            'table' => $table,
            'model' => $model,
        ]);

        File::ensureDirectoryExists(database_path('migrations'));
        File::put($path, $content);

        $this->info("✓ Migration created: {$migrationFile}");
    }

    /**
     * Add routes to api.php.
     */
    protected function addRoutes(string $name, string $controller): void
    {
        $routeFile = base_path('routes/api.php');

        if (!File::exists($routeFile)) {
            $this->warn("API routes file not found. Please create routes/api.php first.");
            return;
        }

        $content = File::get($routeFile);
        $routeName = strtolower(Str::plural($name));
        $controllerName = str_replace('Controller', '', $controller);

        $routeCode = <<<PHP

// Routes for {$controllerName}
Route::apiResource('{$routeName}', \\App\\Http\\Controllers\\Api\\{$controller}::class);
PHP;

        // Check if route already exists
        if (strpos($content, "Route::apiResource('{$routeName}'") !== false) {
            $this->warn("Route for '{$routeName}' already exists. Skipping...");
            return;
        }

        // Append routes before the closing PHP tag if exists
        if (strpos($content, '?>') !== false) {
            $content = str_replace('?>', $routeCode . PHP_EOL . '?>', $content);
        } else {
            $content .= $routeCode;
        }

        File::put($routeFile, $content);

        $this->info("✓ Routes added for: {$routeName}");
    }
}
