<?php

namespace Modules\Interactions\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Interactions\Actions\RepostPost;
use Modules\Interactions\Actions\UnrepostPost;
use Modules\Posts\Models\Post;

class RepostController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Interactions
     * @subgroup Reposts
     * @authenticated
     *
     * Repost Post
     *
     * Repost a post as the authenticated user.
     *
     * @urlParam post string required The ID of the post.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Post reposted successfully.",
     *   "data": {
     *     "reposted": true,
     *     "reposts_count": 1
     *   }
     * }
     */
    public function store(Request $request, Post $post, RepostPost $repostPost): JsonResponse
    {
        $repostPost($request->user(), $post);

        return $this->success(
            data: [
                'reposted' => true,
                'reposts_count' => (int) $post->fresh()->reposts_count,
            ],
            message: 'Post reposted successfully.'
        );
    }

    /**
     * @group Interactions
     * @subgroup Reposts
     * @authenticated
     *
     * Undo Repost
     *
     * Remove repost from a post.
     *
     * @urlParam post string required The ID of the post.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Repost removed successfully.",
     *   "data": {
     *     "reposted": false,
     *     "reposts_count": 0
     *   }
     * }
     */
    public function destroy(Request $request, Post $post, UnrepostPost $unrepostPost): JsonResponse
    {
        $unrepostPost($request->user(), $post);

        return $this->success(
            data: [
                'reposted' => false,
                'reposts_count' => (int) $post->fresh()->reposts_count,
            ],
            message: 'Repost removed successfully.'
        );
    }
}

