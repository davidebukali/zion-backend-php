<?php

namespace Modules\Posts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Illuminate\Http\Request;
use Modules\Posts\Http\Requests\StorePostRequest;
use Modules\Posts\Actions\CreatePost;
use Modules\Posts\Actions\ListPosts;
use Modules\Posts\Actions\DeletePost;
use Modules\Posts\Transformers\PostResource;
use Modules\Posts\Models\Post;
use Illuminate\Support\Facades\Auth;

class PostsController extends Controller
{
    use RespondsWithApi;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ListPosts $listPosts)
    {
        $posts = $listPosts($request->integer('per_page', 15));
        $paginated = PostResource::collection($posts)->toResponse($request)->getData(true);
        return $this->success(
            data: $paginated['data'],
            meta: [
                'links' => $paginated['links'],
                'meta' => $paginated['meta'],
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request, CreatePost $createPost) {
        $post = $createPost($request->user(), $request->validated());
        return $this->success($post, 'Post created successfully', 201);
    }

    /**
     * Show the specified resource.
     */
    public function show(Post $post)
    {
        return $this->success(new PostResource($post));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('posts::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post, DeletePost $deletePost)
    {
        if (Auth::user()->cannot('delete', $post)) {
            abort(403);
        }

        $deletePost($post);

        return $this->success(message: 'Post deleted successfully');
    }
}
