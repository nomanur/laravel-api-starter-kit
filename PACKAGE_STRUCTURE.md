# Laravel API Starter Kit - Complete Package Structure

## 📁 Package Structure

```
laravel-api-starter-kit/
├── 📂 config/
│   └── config.php                      # Main configuration file
│
├── 📂 database/
│   └── migrations/
│       └── create_api_logs_table.php.stub  # API logging migration
│
├── 📂 routes/
│   └── api.php                         # Default API routes
│
├── 📂 src/
│   ├── ApiBoilerplate.php              # Core helper class
│   ├── ApiBoilerplateFacade.php        # Facade for the core class
│   ├── ApiStarterKitServiceProvider.php # Main service provider
│   ├── ApiHelpers.php                  # Utility helper class
│   ├── helpers.php                     # Global helper functions
│   │
│   ├── 📂 Console/
│   │   └── 📂 Commands/
│   │       ├── InstallApiStarterKit.php   # Installation command
│   │       └── MakeApiResource.php        # Resource scaffolding command
│   │
│   ├── 📂 Examples/
│   │   └── ExamplePostsController.php     # Example controller
│   │
│   ├── 📂 Exceptions/
│   │   └── ApiExceptionHandler.php        # Global exception handler
│   │
│   ├── 📂 Http/
│   │   ├── 📂 Controllers/
│   │   │   ├── ApiBaseController.php      # Base controller with traits
│   │   │   └── ApiController.php          # Extended controller (your style)
│   │   │
│   │   └── 📂 Middleware/
│   │       ├── ApiAuthenticate.php        # Authentication middleware
│   │       ├── ApiRateLimit.php           # Rate limiting middleware
│   │       ├── ApiCors.php                # CORS middleware
│   │       └── TransformInputMiddleware.php # Transform input data
│   │
│   ├── 📂 Models/
│   │   └── ApiModel.php                  # Base model with transformer support
│   │
│   ├── 📂 Traits/
│   │   ├── ApiResponser.php              # Response helper methods
│   │   ├── ApiExceptionHandlerTrait.php  # Exception handling
│   │   └── ValidatesRequests.php         # Validation helpers
│   │
│   └── 📂 Transformers/
│       ├── BaseTransformer.php           # Abstract base transformer
│       └── UserTransformer.php           # Example user transformer
│
├── composer.json                         # Package dependencies
├── README.md                             # Full documentation
├── QUICKSTART.md                         # Quick start guide
├── LICENSE.md                            # MIT License
└── CHANGELOG.md                          # Version history
```

## 🚀 Installation

### 1. Install via Composer

```bash
composer require nomanur/api-starter-kit
```

### 2. Run Installation Command

```bash
php artisan api-starter-kit:install --sanctum
```

This will:
- ✅ Publish configuration file to `config/api-starter-kit.php`
- ✅ Install Laravel Sanctum for authentication
- ✅ Configure exception handling
- ✅ Register middleware
- ✅ Create helper functions

### 3. Run Migrations

```bash
php artisan migrate
```

## 🎯 Usage

### Creating Your First API Resource

```bash
php artisan make:api-resource Post
```

This creates:
- `app/Models/Post.php` - Model with transformer support
- `app/Http/Controllers/Api/PostsController.php` - Controller
- `app/Transformers/PostTransformer.php` - Data transformer
- Routes added to `routes/api.php`

### Controller Example (Your Style)

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

    public function verify($token)
    {
        $user = User::where('verification_token', $token)->firstOrFail();

        $user->verified = User::VERIFIED_USER;
        $user->verification_token = null;

        $user->save();

        return $this->showMessage('The account has been verified successfully');
    }

    public function resend(User $user)
    {
        if ($user->isVerified()) {
            return $this->errorResponse('This user is already verified', 409);
        }

        retry(5, function () use ($user) {
            Mail::to($user)->send(new UserCreated($user));
        }, 100);

        return $this->showMessage('The verification email has been sent');
    }
}
```

## 📦 Available Response Methods

All available in your controller via `ApiController`:

### `showAll(Collection $collection, int $code = 200)`
Returns a collection with transformation, filtering, sorting, and pagination.

```php
$users = User::all();
return $this->showAll($users);
```

### `showOne(Model $instance, int $code = 200)`
Returns a single model instance with transformation.

```php
$user = User::find(1);
return $this->showOne($user);
```

### `showMessage(string $message, int $code = 200)`
Returns a simple message response.

```php
return $this->showMessage('Operation successful');
```

### `errorResponse(string $message, int $code, $errors = null)`
Returns an error response.

```php
return $this->errorResponse('Resource not found', 404);
return $this->errorResponse('Validation failed', 422, $errors);
```

### `successResponse($data, string $message, int $code)`
Returns a success response with data.

```php
return $this->successResponse($user, 'User created', 201);
```

## 🛡️ Middleware

### Available Middleware

1. **`api.auth`** - API authentication
2. **`api.rate_limit`** - Rate limiting (60 req/min default)
3. **`api.cors`** - CORS headers
4. **`transform.input`** - Transform input data using transformers

### Usage in Controllers

```php
public function __construct()
{
    parent::__construct();

    // Authentication middleware
    $this->middleware('api.auth')->except(['index', 'show']);

    // Rate limiting
    $this->middleware('api.rate_limit');

    // Input transformation
    $this->middleware('transform.input:'.UserTransformer::class)->only(['store', 'update']);
}
```

## 🔄 Transformers

Transformers convert your models to API responses:

```php
<?php

namespace App\Transformers;

use App\Models\User;
use LaravelApi\StarterKit\Transformers\BaseTransformer;

class UserTransformer extends BaseTransformer
{
    public function transform(User $user)
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'isVerified' => (bool) $user->email_verified_at,
            'createdAt' => $user->created_at->toIso8601String(),
            'updatedAt' => $user->updated_at->toIso8601String(),
        ];
    }

    public static function originalAttribute(string $index): ?string
    {
        $attributes = [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'isVerified' => 'email_verified_at',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
        ];

        return $attributes[$index] ?? null;
    }

    public static function transformedAttribute(string $index): ?string
    {
        $attributes = [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'isVerified' => 'email_verified_at',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
        ];

        return $attributes[$index] ?? null;
    }
}
```

## 📝 Helper Functions

Global helper functions available everywhere:

### `api_response($data, $message, $code)`
```php
return api_response($users, 'Users retrieved', 200);
```

### `api_error($message, $code, $errors)`
```php
return api_error('Not found', 404);
return api_error('Validation failed', 422, $errors);
```

### `api_paginated($paginator, $message, $code)`
```php
$users = User::paginate(15);
return api_paginated($users, 'Users retrieved');
```

### `api_version()`
```php
$version = api_version(); // Returns 'v1'
```

### `api_base_path()`
```php
$path = api_base_path(); // Returns '/api/v1'
```

## ⚙️ Configuration

All configuration in `config/api-starter-kit.php`:

```php
return [
    'prefix' => 'api',
    'version' => 'v1',
    
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
    
    'auth' => [
        'driver' => 'sanctum',
        'guard' => 'sanctum',
    ],
    
    'response' => [
        'success_key' => 'data',
        'message_key' => 'message',
        'error_key' => 'error',
        'meta_key' => 'meta',
        'links_key' => 'links',
    ],
    
    'cache' => [
        'enabled' => false,
        'ttl' => 30,
    ],
    
    'exceptions' => [
        'debug' => false,
        'hide_exception_message' => true,
    ],
];
```

## 🎯 API Response Format

### Success Response
```json
{
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "message": "User retrieved successfully"
}
```

### Collection Response
```json
{
    "data": [...],
    "message": "Users retrieved successfully"
}
```

### Paginated Response
```json
{
    "data": [...],
    "message": "Users retrieved successfully",
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    },
    "links": {
        "self": "...",
        "first": "...",
        "last": "...",
        "next": "...",
        "prev": null
    }
}
```

### Error Response
```json
{
    "error": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

## 📚 Artisan Commands

```bash
# Install the package
php artisan api-starter-kit:install --sanctum

# Create a new API resource
php artisan make:api-resource Post

# Create with custom names
php artisan make:api-resource Article --model=BlogPost --controller=BlogPostsController

# Publish configuration
php artisan vendor:publish --tag=api-starter-kit-config

# Publish routes
php artisan vendor:publish --tag=api-starter-kit-routes
```

## 🔐 Exception Handling

All exceptions are automatically caught and converted to JSON:

| Exception | Status Code | Message |
|-----------|-------------|---------|
| ValidationException | 422 | Validation errors |
| ModelNotFoundException | 404 | Resource not found |
| AuthenticationException | 401 | Unauthenticated |
| AuthorizationException | 403 | Unauthorized |
| NotFoundHttpException | 404 | Route not found |
| MethodNotAllowedHttpException | 405 | Invalid method |
| QueryException (1451) | 409 | Cannot delete (has relations) |
| QueryException (1062) | 409 | Duplicate entry |
| General Exception | 500 | Generic error |

## ✨ Features Summary

✅ Standardized API responses  
✅ Fractal transformers  
✅ Exception handling  
✅ Rate limiting  
✅ CORS support  
✅ Input transformation  
✅ Model transformer support  
✅ Pagination with metadata  
✅ Validation error formatting  
✅ Authentication (Sanctum)  
✅ Caching support  
✅ Helper functions  
✅ Scaffolding commands  
✅ Example controllers  

## 🎉 You're All Set!

Your API is ready to build. Start creating resources with:

```bash
php artisan make:api-resource YourResource
```

Then customize the generated controller, model, and transformer to fit your needs!
