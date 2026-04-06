<?php

namespace LaravelApi\StarterKit\Examples;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use LaravelApi\StarterKit\Http\Controllers\ApiBaseController;

/**
 * Example API Controller - Full CRUD Implementation
 * 
 * This file demonstrates a complete API controller implementation
 * using all the features of the API Starter Kit.
 * 
 * NOTE: This is an example file. Use `php artisan make:api-resource`
 * to generate a new controller for your resources.
 */
class ExamplePostsController extends ApiBaseController
{
    /**
     * Display a listing of the resource with pagination and filtering.
     *
     * GET /api/v1/posts
     *
     * Query Parameters:
     * - per_page: Number of items per page (default: 15, max: 100)
     * - page: Page number (default: 1)
     * - sort_by: Field to sort by
     * - desc: Sort in descending order (true/false)
     * - search: Search term
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Build query with optional filtering
        $query = \App\Models\Post::query();

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('desc', false) ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $posts = $query->paginate($perPage);

        return $this->paginatedResponse($posts, 'Posts retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     *
     * POST /api/v1/posts
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'sometimes|in:draft,published,archived',
            'published_at' => 'sometimes|nullable|date',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
        ]);

        // Create post
        $post = \App\Models\Post::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'status' => $validated['status'] ?? 'draft',
            'published_at' => $validated['published_at'] ?? null,
            'user_id' => $request->user()->id,
        ]);

        // Sync tags if provided
        if (isset($validated['tags'])) {
            // Example: $post->tags()->sync($validated['tags']);
        }

        // Load relationships
        $post->load(['user', 'tags']);

        return $this->success($post, 'Post created successfully', 201);
    }

    /**
     * Display the specified resource.
     *
     * GET /api/v1/posts/{post}
     *
     * @param \App\Models\Post $post
     * @return JsonResponse
     */
    public function show(\App\Models\Post $post): JsonResponse
    {
        // Load relationships
        $post->load(['user', 'tags', 'comments']);

        return $this->success($post, 'Post retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     *
     * PUT/PATCH /api/v1/posts/{post}
     *
     * @param Request $request
     * @param \App\Models\Post $post
     * @return JsonResponse
     */
    public function update(Request $request, \App\Models\Post $post): JsonResponse
    {
        // Authorize action
        // $this->authorize('update', $post);

        // Validate request
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'status' => 'sometimes|in:draft,published,archived',
            'published_at' => 'sometimes|nullable|date',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|max:50',
        ]);

        // Update post
        $post->update($validated);

        // Update tags if provided
        if (isset($validated['tags'])) {
            // Example: $post->tags()->sync($validated['tags']);
        }

        // Load relationships
        $post->load(['user', 'tags']);

        return $this->success($post, 'Post updated successfully');
    }

    /**
     * Soft delete the specified resource.
     *
     * DELETE /api/v1/posts/{post}
     *
     * @param \App\Models\Post $post
     * @return JsonResponse
     */
    public function destroy(\App\Models\Post $post): JsonResponse
    {
        // Authorize action
        // $this->authorize('delete', $post);

        // Soft delete
        $post->delete();

        return $this->success(null, 'Post deleted successfully');
    }

    /**
     * Restore a soft deleted resource.
     *
     * POST /api/v1/posts/{post}/restore
     *
     * @param \App\Models\Post $post
     * @return JsonResponse
     */
    public function restore(\App\Models\Post $post): JsonResponse
    {
        $post->restore();

        return $this->success($post, 'Post restored successfully');
    }

    /**
     * Bulk delete resources.
     *
     * DELETE /api/v1/posts/bulk
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:posts,id',
        ]);

        $deleted = \App\Models\Post::whereIn('id', $validated['ids'])->delete();

        return $this->success([
            'deleted_count' => $deleted,
        ], "{$deleted} posts deleted successfully");
    }

    /**
     * Get related resources.
     *
     * GET /api/v1/posts/{post}/related
     *
     * @param \App\Models\Post $post
     * @return JsonResponse
     */
    public function related(\App\Models\Post $post): JsonResponse
    {
        $related = \App\Models\Post::where('id', '!=', $post->id)
            ->where('status', 'published')
            ->where(function ($query) use ($post) {
                $query->where('category_id', $post->category_id)
                      ->orWhereHas('tags', function ($q) use ($post) {
                          $q->whereIn('tags.id', $post->tags->pluck('id'));
                      });
            })
            ->limit(5)
            ->get();

        return $this->success($related, 'Related posts retrieved successfully');
    }
}
