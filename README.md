# Laravel API Starter Kit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nomanurrahman/api-starter-kit.svg?style=flat-square)](https://packagist.org/packages/nomanurrahman/api-starter-kit)
[![Total Downloads](https://img.shields.io/packagist/dt/nomanurrahman/api-starter-kit.svg?style=flat-square)](https://packagist.org/packages/nomanurrahman/api-starter-kit)
![PHP Version](https://img.shields.io/badge/php-8.0%2B-blue.svg)
![Laravel Version](https://img.shields.io/badge/laravel-9.0%2B-red.svg)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE.md)

A complete, production-ready Laravel API boilerplate with authentication, transformers, exception handling, rate limiting, and scaffolding commands. Build APIs faster with a standardized structure and best practices built-in.

## 🚀 Features

- ✅ **Standardized API Responses** - Consistent JSON response format
- ✅ **Fractal Transformers** - Clean data transformation layer
- ✅ **Exception Handling** - Centralized error handling for all API errors
- ✅ **Rate Limiting** - Built-in API rate limiting middleware
- ✅ **Authentication** - Laravel Sanctum integration ready
- ✅ **Scaffolding Commands** - Generate API resources with one command
- ✅ **Pagination Support** - Built-in pagination with metadata
- ✅ **Caching** - Optional response caching
- ✅ **CORS Support** - Cross-origin request handling
- ✅ **Validation** - Standardized validation error responses
- ✅ **Base Controllers & Models** - Extendable foundation classes

## 📦 Installation

### Requirements

- PHP 8.0 or higher
- Laravel 9.0 or higher
- Composer

### Install via Composer

```bash
composer require nomanurrahman/api-starter-kit
```

### Quick Setup

Run the installation command to set up everything automatically:

```bash
php artisan api-starter-kit:install --sanctum --migrations
```

This will:
- Publish the configuration file
- Install Laravel Sanctum for authentication
- Publish database migrations
- Configure exception handling
- Register middleware
- Create helper functions

### Manual Setup

If you prefer manual setup:

1. Publish the configuration file:
```bash
php artisan vendor:publish --tag=api-starter-kit-config
```

2. Add the service provider to `config/app.php` (if not auto-discovered):
```php
'providers' => [
    // ...
    LaravelApi\StarterKit\ApiStarterKitServiceProvider::class,
],
```

3. Add the facade alias to `config/app.php`:
```php
'aliases' => [
    // ...
    'ApiBoilerplate' => LaravelApi\StarterKit\ApiBoilerplateFacade::class,
],
```

4. Register middleware in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'api.auth' => \LaravelApi\StarterKit\Http\Middleware\ApiAuthenticate::class,
        'api.rate_limit' => \LaravelApi\StarterKit\Http\Middleware\ApiRateLimit::class,
        'api.cors' => \LaravelApi\StarterKit\Http\Middleware\ApiCors::class,
    ]);
})
```

## 📖 Usage

### Creating Your First API Resource

The easiest way to create a complete API resource is using the artisan command:

```bash
php artisan make:api-resource Post
```

This will create:
- Model: `app/Models/Post.php`
- Controller: `app/Http/Controllers/Api/PostsController.php`
- Transformer: `app/Transformers/PostTransformer.php`
- Routes: Added to `routes/api.php`

You can also specify custom names:

```bash
php artisan make:api-resource Post --model=Article --controller=ArticlesController --transformer=ArticleTransformer
```

### API Response Format

All API responses follow a standardized format:

**Success Response:**
```json
{
    "data": {
        "id": 1,
        "title": "My Post",
        "content": "Post content here",
        "created_at": "2024-01-01T00:00:00+00:00"
    },
    "message": "Post retrieved successfully"
}
```

**Error Response:**
```json
{
    "error": "Validation failed",
    "errors": {
        "title": ["The title field is required."],
        "content": ["The content field must be at least 10 characters."]
    }
}
```

**Paginated Response:**
```json
{
    "data": [...],
    "message": "Posts retrieved successfully",
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75,
        "from": 1,
        "to": 15
    },
    "links": {
        "self": "http://example.com/api/v1/posts?page=1",
        "first": "http://example.com/api/v1/posts?page=1",
        "last": "http://example.com/api/v1/posts?page=5",
        "next": "http://example.com/api/v1/posts?page=2",
        "prev": null
    }
}
```

### Using the Base Controller

Extend the `ApiBaseController` in your controllers to get all the helper methods:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use LaravelApi\StarterKit\Http\Controllers\ApiBaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostsController extends ApiBaseController
{
    public function index(): JsonResponse
    {
        $posts = Post::paginate(15);
        return $this->paginatedResponse($posts, 'Posts retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = Post::create($validated);
        return $this->success($post, 'Post created successfully', 201);
    }

    public function show(Post $post): JsonResponse
    {
        return $this->success($post, 'Post retrieved successfully');
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ]);

        $post->update($validated);
        return $this->success($post, 'Post updated successfully');
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();
        return $this->success(null, 'Post deleted successfully');
    }
}
```

### Using Transformers

Transformers provide a clean way to format your API responses:

```php
<?php

namespace App\Transformers;

use App\Models\Post;
use LaravelApi\StarterKit\Transformers\BaseTransformer;

class PostTransformer extends BaseTransformer
{
    public function transform(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'author' => $post->author?->name,
            'published' => $post->published_at?->toIso8601String(),
            'created_at' => $post->created_at->toIso8601String(),
            'updated_at' => $post->updated_at->toIso8601String(),
        ];
    }

    public static function originalAttribute(string $index): ?string
    {
        $attributes = [
            'id' => 'id',
            'title' => 'title',
            'content' => 'content',
            'author' => 'author',
            'published' => 'published_at',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        return $attributes[$index] ?? null;
    }

    public static function transformedAttribute(string $index): ?string
    {
        $attributes = [
            'id' => 'id',
            'title' => 'title',
            'content' => 'content',
            'author' => 'author',
            'published' => 'published_at',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        return $attributes[$index] ?? null;
    }
}
```

### Using the Model

Extend `ApiModel` for automatic transformer support:

```php
<?php

namespace App\Models;

use LaravelApi\StarterKit\Models\ApiModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends ApiModel
{
    use HasFactory;

    public static $transformer = \App\Transformers\PostTransformer::class;

    protected $fillable = ['title', 'content', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
```

### API Routes

Define your API routes in `routes/api.php`:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostsController;

// Public routes
Route::get('/posts', [PostsController::class, 'index']);
Route::get('/posts/{post}', [PostsController::class, 'show']);

// Protected routes
Route::middleware('api.auth')->group(function () {
    Route::post('/posts', [PostsController::class, 'store']);
    Route::put('/posts/{post}', [PostsController::class, 'update']);
    Route::delete('/posts/{post}', [PostsController::class, 'destroy']);
});
```

### Helper Functions

The package provides helper functions for quick API responses:

```php
// Success response
api_response($data, 'Success message', 200);

// Error response
api_error('Error message', 400, $errors);

// Paginated response
api_paginated($paginator, 'Success message', 200);
```

## ⚙️ Configuration

Publish the configuration file to customize settings:

```bash
php artisan vendor:publish --tag=api-starter-kit-config
```

Configuration options in `config/api-starter-kit.php`:

- **prefix**: API URL prefix (default: `api`)
- **version**: API version (default: `v1`)
- **rate_limit**: Rate limiting settings
- **auth**: Authentication driver configuration
- **response**: Response format keys
- **cache**: Caching settings
- **exceptions**: Exception handling settings
- **validation**: Validation error format

## 🔒 Authentication

### Using Sanctum (Recommended)

1. Install Sanctum:
```bash
php artisan api-starter-kit:install --sanctum
```

2. Protect routes with middleware:
```php
Route::middleware('api.auth')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
```

3. Authenticate users via tokens:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" http://example.com/api/v1/user
```

## 🛡️ Middleware

The package includes three middleware classes:

- **ApiAuthenticate**: Handles API authentication
- **ApiRateLimit**: Rate limiting for API endpoints
- **ApiCors**: CORS headers for cross-origin requests

Apply them to routes:

```php
Route::middleware(['api.auth', 'api.rate_limit'])->group(function () {
    // Protected and rate-limited routes
});
```

## 🎯 Query Parameters

The API supports various query parameters for filtering and pagination:

- `per_page`: Items per page (default: 15, max: 100)
- `page`: Page number
- `sort_by`: Field to sort by
- `desc`: Sort in descending order (true/false)
- Custom filters based on transformer attributes

Example:
```bash
GET /api/v1/posts?per_page=10&page=2&sort_by=created_at&desc=true
```

## 📝 Exception Handling

All exceptions are automatically caught and returned as JSON:

- **ValidationException** (422): Validation errors
- **ModelNotFoundException** (404): Resource not found
- **AuthenticationException** (401): Unauthenticated
- **AuthorizationException** (403): Unauthorized
- **NotFoundHttpException** (404): Route not found
- **MethodNotAllowedHttpException** (405): Invalid HTTP method
- **QueryException** (409/500): Database errors

## 🧪 Testing

Run the package tests:

```bash
composer test
```

## 📚 Examples

### Complete CRUD API Example

Check the example files in the package to see a complete implementation.

### Health Check Endpoint

The package includes a health check endpoint:

```bash
GET /api/v1/health
```

Response:
```json
{
    "status": "ok",
    "timestamp": "2024-01-01T00:00:00+00:00",
    "version": "v1"
}
```

## 🔧 Advanced Usage

### Custom Response Keys

Configure response keys in `config/api-starter-kit.php`:

```php
'response' => [
    'success_key' => 'data',
    'message_key' => 'message',
    'error_key' => 'error',
    'meta_key' => 'meta',
    'links_key' => 'links',
],
```

### Enable Caching

Enable response caching in config:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 60, // minutes
],
```

### Custom Rate Limits

Configure rate limiting:

```php
'rate_limit' => [
    'enabled' => true,
    'max_attempts' => 60,
    'decay_minutes' => 1,
],
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔐 Security

If you discover any security related issues, please email nomanurrahman@gmail.com instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 👥 Credits

- [nomanur rahman](https://github.com/nomanurrahman)
- [All Contributors](../../contributors)

## 🙏 Support

If you find this package helpful, please ⭐ star it on GitHub!

For questions and support:
- Open an issue on GitHub
- Email: nomanurrahman@gmail.com

---

**Happy API Building! 🚀**
