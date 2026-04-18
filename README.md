# Laravel API Starter Kit

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nomanur/api-starter-kit.svg?style=flat-square)](https://packagist.org/packages/nomanur/api-starter-kit)
[![Total Downloads](https://img.shields.io/packagist/dt/nomanur/api-starter-kit.svg?style=flat-square)](https://packagist.org/packages/nomanur/api-starter-kit)
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
composer require nomanur/api-starter-kit
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

To create a migration along with the API resource:

```bash
php artisan make:api-resource Post --migration
```

This will also create a migration file in `database/migrations/` with a basic table structure (id and timestamps).

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

Extend the `ApiController` in your controllers to get all the helper methods:

```php
<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Transformers\UserTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('transform.input:'.UserTransformer::class)->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return $this->showAll($users);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ];

        $this->validate($request, $rules);

        $data = $request->except('password_confirmation');
        $data['password'] = bcrypt($request->password);
        $data['verified'] = User::UNVERIFIED_USER;
        $data['verification_token'] = User::generateVerificationCode();
        $data['admin'] = User::REGULAR_USER;

        $user = User::create($data);

        return $this->showOne($user, 201);
    }

    public function show(User $user)
    {
        return $this->showOne($user);
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'email' => 'email|unique:users,email,'.$user->id,
            'password' => 'min:6|confirmed',
            'admin' => 'in:'.User::ADMIN_USER.','.User::REGULAR_USER,
        ];

        $this->validate($request, $rules);

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email') && $user->email != $request->email) {
            $user->verified = User::UNVERIFIED_USER;
            $user->verification_token = User::generateVerificationCode();
            $user->email = $request->email;
        }

        if ($request->has('password')) {
            $user->password = bcrypt($request->password);
        }

        if ($request->has('admin')) {
            if (!$user->isVerified()) {
                return $this->errorResponse('Only verified users can modify the admin field', 409);
            }

            $user->admin = $request->admin;
        }

        if (!$user->isDirty()) {
            return $this->errorResponse('You need to specify a different value to update', 422);
        }

        $user->save();

        return $this->showOne($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return $this->showOne($user);
    }

    public function showMessage(string $message, int $code = 200)
    {
        return $this->successResponse(['data' => $message], $code);
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

    /**
     * Map transformed attributes to original attributes.
     */
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

    /**
     * Map original attributes to transformed attributes.
     */
    public static function transformedAttribute(string $index): ?string
    {
        $attributes = [
            'id' => 'id',
            'title' => 'title',
            'content' => 'content',
            'author' => 'author',
            'published_at' => 'published',
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

Define your API routes in `routes/api.php`. You can use the `api_version()` helper to automatically apply the version prefix from your config:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostsController;

// Group routes by version
Route::prefix(api_version())->group(function () {
    // Public routes
    Route::get('/posts', [PostsController::class, 'index']);
    Route::get('/posts/{post}', [PostsController::class, 'show']);

    // Protected routes
    Route::middleware('api.auth')->group(function () {
        Route::apiResource('posts', PostsController::class)->except(['index', 'show']);
    });
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
GET /health
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

- [nomanur rahman](https://github.com/nomanur)
- [All Contributors](../../contributors)

## 🙏 Support

If you find this package helpful, please ⭐ star it on GitHub!

For questions and support:
- Open an issue on GitHub
- Email: nomanurrahman@gmail.com

---

**Happy API Building! 🚀**
