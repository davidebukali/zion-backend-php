<?php

namespace Modules\Interactions\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Interactions\Actions\LikePosts;
use Modules\Interactions\Actions\UnlikePost;
use Modules\Posts\Models\Post;

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
