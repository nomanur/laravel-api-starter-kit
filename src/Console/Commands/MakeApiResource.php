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
        $force = $this->option('force');

        $this->info("Creating API resource: {$name}");
        $this->newLine();

        // Create Model
        $this->createModel($model, $transformer, $force);

        // Create Controller
        $this->createController($controller, $model);

        // Create Transformer
        $this->createTransformer($transformer, $model);

        // Add routes
        $this->addRoutes($name, $controller);

        $this->newLine();
        $this->info("✓ API resource '{$name}' created successfully!");
        $this->info("  - Model: app/Models/{$model}.php");
        $this->info("  - Controller: app/Http/Controllers/Api/{$controller}.php");
        $this->info("  - Transformer: app/Transformers/{$transformer}.php");
        $this->info("  - Routes added to routes/api.php");

        return Command::SUCCESS;
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

        $content = <<<PHP
<?php

namespace {$namespace};

use LaravelApi\\StarterKit\\Models\\ApiModel;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;

class {$model} extends ApiModel
{
    use HasFactory;

    /**
     * The transformer class for this model.
     *
     * @var string
     */
    public static \$transformer = \\App\\Transformers\\{$transformer}::class;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected \$table = '{$table}';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected \$fillable = [
        // Add your fillable fields here
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected \$hidden = [
        // Add hidden fields here
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected \$casts = [
        // Add casts here
    ];
}

PHP;

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

        $content = <<<PHP
<?php

namespace {$namespace};

use {$modelNamespace}\\{$model};
use LaravelApi\\StarterKit\\Http\\Controllers\\ApiBaseController;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\JsonResponse;

class {$controller} extends ApiBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \\Illuminate\\Http\\JsonResponse
     */
    public function index(): JsonResponse
    {
        \${$variable}s = {$model}::paginate(request('per_page', 15));
        
        return \$this->paginatedResponse(\${$variable}s, '{$model}s retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \\Illuminate\\Http\\Request \$request
     * @return \\Illuminate\\Http\\JsonResponse
     */
    public function store(Request \$request): JsonResponse
    {
        \$validated = \$request->validate([
            // Add validation rules here
        ]);

        \${$variable} = {$model}::create(\$validated);

        return \$this->success(\${$variable}, '{$model} created successfully', 201);
    }

    /**
     * Display the specified resource.
     *
     * @param {$model} \${$variable}
     * @return \\Illuminate\\Http\\JsonResponse
     */
    public function show({$model} \${$variable}): JsonResponse
    {
        return \$this->success(\${$variable}, '{$model} retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \\Illuminate\\Http\\Request \$request
     * @param {$model} \${$variable}
     * @return \\Illuminate\\Http\\JsonResponse
     */
    public function update(Request \$request, {$model} \${$variable}): JsonResponse
    {
        \$validated = \$request->validate([
            // Add validation rules here
        ]);

        \${$variable}->update(\$validated);

        return \$this->success(\${$variable}, '{$model} updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param {$model} \${$variable}
     * @return \\Illuminate\\Http\\JsonResponse
     */
    public function destroy({$model} \${$variable}): JsonResponse
    {
        \${$variable}->delete();

        return \$this->success(null, '{$model} deleted successfully');
    }
}

PHP;

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

        $content = <<<PHP
<?php

namespace App\\Transformers;

use {$model};
use LaravelApi\\StarterKit\\Transformers\\BaseTransformer;

class {$transformer} extends BaseTransformer
{
    /**
     * Transform the model into an array.
     *
     * @param {$model} \${$model}
     * @return array
     */
    public function transform({$model} \${$model}): array
    {
        return [
            'id' => \${$model}->id,
            // Add your transformed fields here
            'created_at' => \${$model}->created_at?->toIso8601String(),
            'updated_at' => \${$model}->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Map original attributes to transformed attributes.
     *
     * @param string \$index
     * @return string|null
     */
    public static function originalAttribute(string \$index): ?string
    {
        \$attributes = [
            'id' => 'id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            // Add your attribute mappings here
        ];

        return \$attributes[\$index] ?? null;
    }

    /**
     * Map transformed attributes back to original attributes.
     *
     * @param string \$index
     * @return string|null
     */
    public static function transformedAttribute(string \$index): ?string
    {
        \$attributes = [
            'id' => 'id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            // Add your attribute mappings here
        ];

        return \$attributes[\$index] ?? null;
    }
}

PHP;

        File::ensureDirectoryExists(app_path('Transformers'));
        File::put($path, $content);

        $this->info("✓ Transformer created: {$transformer}");
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
        $routeName = Str::plural($name);
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
