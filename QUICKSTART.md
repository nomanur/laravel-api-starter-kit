# 🚀 Quick Start Guide - Laravel API Starter Kit

Get your API up and running in less than 5 minutes!

## Step 1: Install the Package

```bash
composer require nomanur/api-starter-kit
```

## Step 2: Run the Installation Command

```bash
php artisan api-starter-kit:install --sanctum
```

This will:
- ✅ Publish configuration file
- ✅ Install Laravel Sanctum for authentication
- ✅ Configure exception handling
- ✅ Register API middleware
- ✅ Create helper functions

## Step 3: Run Migrations

```bash
php artisan migrate
```

## Step 4: Create Your First API Resource

```bash
php artisan make:api-resource Post
```

This creates:
- `app/Models/Post.php` - Your model
- `app/Http/Controllers/Api/PostsController.php` - Your controller
- `app/Transformers/PostTransformer.php` - Your transformer
- Adds routes to `routes/api.php`

## Step 5: Test Your API

Start your Laravel development server:

```bash
php artisan serve
```

Test the health check endpoint:

```bash
curl http://localhost:8000/api/v1/health
```

Expected response:
```json
{
    "status": "ok",
    "timestamp": "2024-01-01T00:00:00+00:00",
    "version": "v1"
}
```

## Step 6: Create a Test Post

Update the validation in `PostsController.php`:

```php
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string',
    ]);

    $post = Post::create($validated);
    return $this->success($post, 'Post created successfully', 201);
}
```

Create a post:

```bash
curl -X POST http://localhost:8000/api/v1/posts \
  -H "Content-Type: application/json" \
  -d '{"title":"My First Post","content":"Hello World!"}'
```

## Step 7: Get All Posts

```bash
curl http://localhost:8000/api/v1/posts
```

## 🎉 That's It!

You now have a fully functional API with:
- ✅ Standardized responses
- ✅ Error handling
- ✅ Validation
- ✅ Transformers
- ✅ Pagination support
- ✅ Rate limiting
- ✅ CORS support

## 📚 Next Steps

- Read the full [README.md](README.md) for detailed documentation
- Explore the [config/api-starter-kit.php](config/api-starter-kit.php) for customization
- Create more resources with `php artisan make:api-resource`
- Add authentication with Sanctum

## 🔐 Adding Authentication

Protect your routes:

```php
// routes/api.php
Route::middleware('api.auth')->group(function () {
    Route::apiResource('posts', PostsController::class);
});
```

Get a token from Sanctum and use it:

```bash
curl http://localhost:8000/api/v1/posts \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🛠️ Useful Commands

```bash
# Create new API resource
php artisan make:api-resource User

# Create with custom names
php artisan make:api-resource Article --model=BlogPost --controller=BlogPostsController

# View configuration
php artisan config:show api-starter-kit

# Clear cache
php artisan cache:clear

# List all routes
php artisan route:list
```

## 📖 Example Controller

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

## 🆘 Need Help?

- Check the [README.md](README.md) for full documentation
- Open an issue on GitHub
- Email: nomanurrahman@gmail.com

**Happy API Building! 🚀**
