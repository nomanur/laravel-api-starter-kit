<?php

namespace LaravelApi\StarterKit\Examples;

use App\Http\Controllers\ApiController;
use App\Models\Post;
use App\Transformers\PostTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExamplePostsController extends ApiController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('transform.input:'.PostTransformer::class)->only(['store', 'update']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $posts = Post::all();

        return $this->showAll($posts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'sometimes|in:draft,published,archived',
            'published_at' => 'sometimes|nullable|date',
        ];

        $this->validate($request, $rules);

        $data = $request->all();
        $data['user_id'] = $request->user()->id;

        $post = Post::create($data);

        return $this->showOne($post, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return Response
     */
    public function show(Post $post)
    {
        return $this->showOne($post);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Post  $post
     * @return Response
     */
    public function update(Request $request, Post $post)
    {
        $rules = [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'status' => 'sometimes|in:draft,published,archived',
            'published_at' => 'sometimes|nullable|date',
        ];

        $this->validate($request, $rules);

        if ($request->has('title')) {
            $post->title = $request->title;
        }

        if ($request->has('content')) {
            $post->content = $request->content;
        }

        if ($request->has('status')) {
            $post->status = $request->status;
        }

        if ($request->has('published_at')) {
            $post->published_at = $request->published_at;
        }

        if (!$post->isDirty()) {
            return $this->errorResponse('You need to specify a different value to update', 422);
        }

        $post->save();

        return $this->showOne($post);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Post  $post
     * @return Response
     */
    public function destroy(Post $post)
    {
        $post->delete();

        return $this->showOne($post);
    }

    /**
     * Restore a soft deleted resource.
     *
     * @param  \App\Models\Post  $post
     * @return Response
     */
    public function restore(Post $post)
    {
        $post->restore();

        return $this->showMessage('The post has been restored successfully');
    }

    /**
     * Get published resources.
     *
     * @return Response
     */
    public function published()
    {
        $posts = Post::where('status', 'published')->get();

        return $this->showAll($posts);
    }

    /**
     * Get draft resources.
     *
     * @return Response
     */
    public function drafts()
    {
        $posts = Post::where('status', 'draft')->get();

        return $this->showAll($posts);
    }
}
