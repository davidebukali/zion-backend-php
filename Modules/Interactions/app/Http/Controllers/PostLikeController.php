<?php

namespace Modules\Interactions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Illuminate\Http\Request;
use Modules\Interactions\Actions\LikePosts;
use Modules\Posts\Models\Post;

class PostLikeController extends Controller
{
    use RespondsWithApi;

    /**
     * Store a newly created resource in storage.
     */
    public function likePost(Request $request, Post $post, LikePosts $likePosts)
    {
        $likePosts($request->user(), $post);

        return $this->success(message: 'Post liked successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function unlikePost($id)
    {
        //

        return response()->json([]);
    }
}

