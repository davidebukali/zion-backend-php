<?php

namespace Modules\Interactions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Illuminate\Http\Request;
use Modules\Interactions\Actions\BookmarkPost;
use Modules\Interactions\Actions\ListBookmarks;
use Modules\Interactions\Actions\UnbookmarkPost;
use Modules\Posts\Models\Post;
use Modules\Posts\Transformers\PostResource;

class BookmarkController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Interactions
     * @subgroup Bookmarks
     * @authenticated
     * 
     * List Bookmarked Posts
     * 
     * Retrieve a paginated list of posts bookmarked by the authenticated user.
     * 
     * @queryParam per_page integer Items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "content": "Bookmarked post example",
     *       "author": { "id": 1, "name": "Jane Doe" }
     *     }
     *   ],
     *   "meta": {
     *     "links": { "first": "...", "last": "...", "prev": null, "next": null },
     *     "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 1 }
     *   }
     * }
     */
    public function index(Request $request, ListBookmarks $listBookmarks)
    {
        $posts = $listBookmarks($request->user(), $request->integer('per_page', 15));
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
     * @subgroup Bookmarks
     * @authenticated
     * 
     * Bookmark Post
     * 
     * Save a post to the user's bookmarks list.
     * 
     * @urlParam post integer required The ID of the post to bookmark. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Post bookmarked successfully"
     * }
     */
    public function store(Request $request, Post $post, BookmarkPost $bookmarkPost)
    {
        $bookmarkPost($request->user(), $post);

        return $this->success(message: 'Post bookmarked successfully');
    }

    /**
     * @group Interactions
     * @subgroup Bookmarks
     * @authenticated
     * 
     * Unbookmark Post
     * 
     * Remove a post from the user's bookmarks list.
     * 
     * @urlParam post integer required The ID of the post to unbookmark. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Post unbookmarked successfully"
     * }
     */
    public function destroy(Request $request, Post $post, UnbookmarkPost $unbookmarkPost)
    {
        $unbookmarkPost($request->user(), $post);

        return $this->success(message: 'Post unbookmarked successfully');
    }
}
