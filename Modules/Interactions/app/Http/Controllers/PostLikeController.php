<?php

namespace Modules\Interactions\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Interactions\Actions\LikePosts;
use Modules\Interactions\Actions\ListLikes;
use Modules\Interactions\Actions\UnlikePost;
use Modules\Posts\Models\Post;
use Modules\Posts\Transformers\PostResource;

class PostLikeController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Interactions
     *
     * @subgroup Likes
     *
     * @authenticated
     *
     * List Liked Posts
     *
     * Retrieve a paginated list of posts liked by the user. If user_id is provided,
     * retrieves liked posts for that user (subject to privacy & block settings).
     *
     * @queryParam user_id string Optional user ULID to retrieve liked posts for.
     * @queryParam per_page integer Items per page. Example: 15
     * @queryParam cursor string Cursor for pagination.
     */
    public function index(Request $request, ListLikes $listLikes)
    {
        $viewer = $request->user();
        $targetUser = $request->filled('user_id')
            ? User::findOrFail($request->string('user_id')->toString())
            : $viewer;

        $posts = $listLikes($targetUser, $viewer, $request->integer('per_page', 15));
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
     * @group Interactions
     *
     * @subgroup Likes
     *
     * @authenticated
     *
     * Like Post
     *
     * Like a post as the authenticated user.
     *
     * @urlParam post integer required The ID of the post to like. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Post liked successfully"
     * }
     */
    public function likePost(Request $request, Post $post, LikePosts $likePosts)
    {
        Gate::authorize('view', $post);

        $likePosts($request->user(), $post);

        return $this->success(message: 'Post liked successfully');
    }

    /**
     * @group Interactions
     *
     * @subgroup Likes
     *
     * @authenticated
     *
     * Unlike Post
     *
     * Remove like from a post.
     *
     * @urlParam post integer required The ID of the post to unlike. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Post unliked successfully"
     * }
     */
    public function unlikePost(Request $request, Post $post, UnlikePost $unlikePost)
    {
        $unlikePost($request->user(), $post);

        return $this->success(message: 'Post unliked successfully');
    }
}
