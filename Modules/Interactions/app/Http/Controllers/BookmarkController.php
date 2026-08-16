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
     * Display a listing of bookmarked posts for the authenticated user.
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
     * Store a newly created bookmark in storage.
     */
    public function store(Request $request, Post $post, BookmarkPost $bookmarkPost)
    {
        $bookmarkPost($request->user(), $post);

        return $this->success(message: 'Post bookmarked successfully');
    }

    /**
     * Remove the specified bookmark from storage.
     */
    public function destroy(Request $request, Post $post, UnbookmarkPost $unbookmarkPost)
    {
        $unbookmarkPost($request->user(), $post);

        return $this->success(message: 'Post unbookmarked successfully');
    }
}

