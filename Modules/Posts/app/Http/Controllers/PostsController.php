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
     * @group Posts
     * @authenticated
     * 
     * List Posts
     * 
     * Retrieve a paginated list of posts.
     * 
     * @queryParam per_page integer Number of items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "content": "Exploring Laravel and Scribe!",
     *       "author": { "id": 1, "name": "Jane Doe" },
     *       "media": [],
     *       "created_at": "2026-09-24T12:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "links": { "first": "...", "last": "...", "prev": null, "next": null },
     *     "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 1 }
     *   }
     * }
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
     * @group Posts
     * @authenticated
     * 
     * Create Post
     * 
     * Create a new post with optional text content and attached media assets.
     * 
     * @bodyParam content string The text body of the post. Example: Exploring Laravel and Scribe!
     * @bodyParam media_ids string[] Optional array of media UUIDs to attach. Example: ["9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d"]
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Post created successfully",
     *   "data": {
     *     "id": 1,
     *     "content": "Exploring Laravel and Scribe!",
     *     "media": [],
     *     "created_at": "2026-09-24T12:00:00.000000Z"
     *   }
     * }
     * @response 422 status=422 scenario="Validation error" {
     *   "message": "The media_ids.0 field must be a valid existing media ID."
     * }
     */
    public function store(StorePostRequest $request, CreatePost $createPost) {
        $post = $createPost($request->user(), $request->validated());
        return $this->success($post, 'Post created successfully', 201);
    }

    /**
     * @group Posts
     * @authenticated
     * 
     * Get Post Details
     * 
     * Retrieve single post details by post ID.
     * 
     * @urlParam post integer required The ID of the post. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "content": "Exploring Laravel and Scribe!",
     *     "author": { "id": 1, "name": "Jane Doe" },
     *     "created_at": "2026-09-24T12:00:00.000000Z"
     *   }
     * }
     * @response 404 status=404 scenario="Post not found" {
     *   "message": "No query results for model [Modules\\Posts\\Models\\Post] 1"
     * }
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
     * @group Posts
     * @authenticated
     * 
     * Delete Post
     * 
     * Delete a post owned by the authenticated user.
     * 
     * @urlParam post integer required The ID of the post to delete. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Post deleted successfully"
     * }
     * @response 403 status=403 scenario="Unauthorized deletion" {
     *   "message": "This action is unauthorized."
     * }
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
