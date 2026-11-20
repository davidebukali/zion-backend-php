<?php

namespace Modules\Interactions\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Interactions\Actions\ListReposts;
use Modules\Interactions\Actions\RepostPost;
use Modules\Interactions\Actions\UnrepostPost;
use Modules\Posts\Models\Post;
use Modules\Posts\Transformers\PostResource;

class RepostController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Interactions
     *
     * @subgroup Reposts
     *
     * @authenticated
     *
     * List Reposted Posts
     *
     * Retrieve a paginated list of posts reposted by the user. If user_id is provided,
     * retrieves reposted posts for that user (subject to privacy & block settings).
     *
     * @queryParam user_id string Optional user ULID to retrieve reposted posts for.
     * @queryParam per_page integer Items per page. Example: 15
     * @queryParam cursor string Cursor for pagination.
     */
    public function index(Request $request, ListReposts $listReposts): JsonResponse
    {
        $viewer = $request->user();
        $targetUser = $request->filled('user_id')
            ? User::findOrFail($request->string('user_id')->toString())
            : $viewer;

        $posts = $listReposts($targetUser, $viewer, $request->integer('per_page', 15));
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
     * @subgroup Reposts
     *
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
        Gate::authorize('view', $post);

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
     *
     * @subgroup Reposts
     *
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
