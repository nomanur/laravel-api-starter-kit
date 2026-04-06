<?php

namespace LaravelApi\StarterKit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallApiStarterKit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-starter-kit:install 
                            {--force : Overwrite existing files}
                            {--sanctum : Install Laravel Sanctum for API authentication}
                            {--migrations : Publish database migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure the API Starter Kit';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Installing API Starter Kit...');
        $this->newLine();

        $force = $this->option('force');
        $installSanctum = $this->option('sanctum');
        $publishMigrations = $this->option('migrations');

        // Publish config
        $this->publishConfig($force);

        // Install Sanctum if requested
        if ($installSanctum) {
            $this->installSanctum();
        }

        // Publish migrations if requested
        if ($publishMigrations) {
            $this->publishMigrations();
        }

        // Setup exception handler
        $this->setupExceptionHandler($force);

        // Add middleware
        $this->addMiddleware();

        // Add helper functions
        $this->addHelperFunctions();

        $this->newLine();
        $this->info('✅ API Starter Kit installed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->info('1. Run php artisan vendor:publish --tag=api-starter-kit-config');
        if ($installSanctum) {
            $this->info('2. Run php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"');
            $this->info('3. Run php artisan migrate');
        }
        $this->info('4. Create your first API resource: php artisan make:api-resource Post');
        $this->info('5. Check the README.md for usage examples');

        return Command::SUCCESS;
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfig(bool $force): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'api-starter-kit-config',
            '--force' => $force,
        ]);

        $this->info('✓ Configuration published');
    }

    /**
     * Install Laravel Sanctum.
     */
    protected function installSanctum(): void
    {
        $this->info('Installing Laravel Sanctum...');

        // Check if Sanctum is already installed
        if (class_exists(\Laravel\Sanctum\Sanctum::class)) {
            $this->info('✓ Sanctum is already installed');
            return;
        }

        $this->call('vendor:publish', [
            '--provider' => 'Laravel\Sanctum\SanctumServiceProvider',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'sanctum-migrations',
        ]);

        $this->info('✓ Sanctum installed and published');
    }

    /**
     * Publish database migrations.
     */
    protected function publishMigrations(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'api-starter-kit-migrations',
        ]);

        $this->info('✓ Migrations published');
    }

    /**
     * Setup the exception handler.
     */
    protected function setupExceptionHandler(bool $force): void
    {
        $exceptionHandlerPath = app_path('Exceptions/Handler.php');

        if (!File::exists($exceptionHandlerPath)) {
            // Laravel 11+ doesn't have Handler.php by default
            $this->info('⚠ Laravel 11+ detected - Please register the exception handler in bootstrap/app.php');
            return;
        }

        $content = File::get($exceptionHandlerPath);

        // Check if already using the trait
        if (strpos($content, 'ApiExceptionHandlerTrait') !== false) {
            $this->info('✓ Exception handler already configured');
            return;
        }

        // Add the trait import
        $content = str_replace(
            'use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;',
            "use Illuminate\\Foundation\\Exceptions\\Handler as ExceptionHandler;\nuse LaravelApi\\StarterKit\\Traits\\ApiExceptionHandlerTrait;",
            $content
        );

        // Add the trait usage
        $content = str_replace(
            'class Handler extends ExceptionHandler',
            "class Handler extends ExceptionHandler\n{\n    use ApiExceptionHandlerTrait;",
            $content
        );

        // Remove duplicate class opening brace
        $content = str_replace(
            "{\n    use ApiExceptionHandlerTrait;\n{",
            "{\n    use ApiExceptionHandlerTrait;",
            $content
        );

        File::put($exceptionHandlerPath, $content);

        $this->info('✓ Exception handler configured');
    }

    /**
     * Add middleware aliases.
     */
    protected function addMiddleware(): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (!File::exists($bootstrapPath)) {
            $this->warn('⚠ Could not find bootstrap/app.php - Please register middleware manually');
            return;
        }

        $content = File::get($bootstrapPath);

        // Check if middleware is already registered
        if (strpos($content, 'ApiRateLimit') !== false) {
            $this->info('✓ Middleware already configured');
            return;
        }

        // Add middleware registration before the return statement
        $middlewareCode = <<<'PHP'

// API Middleware
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'api.auth' => \LaravelApi\StarterKit\Http\Middleware\ApiAuthenticate::class,
        'api.rate_limit' => \LaravelApi\StarterKit\Http\Middleware\ApiRateLimit::class,
        'api.cors' => \LaravelApi\StarterKit\Http\Middleware\ApiCors::class,
    ]);
    
    $middleware->api(prepend: [
        \LaravelApi\StarterKit\Http\Middleware\ApiRateLimit::class,
    ]);
})
PHP;

        // Find the position to insert middleware configuration
        if (strpos($content, '->withMiddleware') === false) {
            // Insert before the closing parenthesis
            $content = preg_replace(
                '/\)\s*;?$/',
                $middlewareCode . "\n);",
                $content
            );

            File::put($bootstrapPath, $content);

            $this->info('✓ Middleware registered');
        }
    }

    /**
     * Add helper functions.
     */
    protected function addHelperFunctions(): void
    {
        // Create helpers file if it doesn't exist
        $helpersPath = app_path('helpers.php');

        if (File::exists($helpersPath)) {
            $this->info('✓ Helpers file already exists');
            return;
        }

        $content = <<<'PHP'
<?php

if (!function_exists('api_response')) {
    /**
     * Return a standardized API response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    function api_response($data = null, string $message = '', int $code = 200)
    {
        return response()->json([
            config('api-starter-kit.response.success_key', 'data') => $data,
            config('api-starter-kit.response.message_key', 'message') => $message,
        ], $code);
    }
}

if (!function_exists('api_error')) {
    /**
     * Return an error API response.
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    function api_error(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = [
            config('api-starter-kit.response.error_key', 'error') => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}

if (!function_exists('api_paginated')) {
    /**
     * Return a paginated API response.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    function api_paginated($paginator, string $message = '', int $code = 200)
    {
        return response()->json([
            config('api-starter-kit.response.success_key', 'data') => $paginator->items(),
            config('api-starter-kit.response.message_key', 'message') => $message,
            config('api-starter-kit.response.meta_key', 'meta') => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            config('api-starter-kit.response.links_key', 'links') => [
                'self' => $paginator->url($paginator->currentPage()),
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'next' => $paginator->nextPageUrl(),
                'prev' => $paginator->previousPageUrl(),
            ],
        ], $code);
    }
}

PHP;

        File::put($helpersPath, $content);

        $this->info('✓ Helper functions created');
    }
}
