<?php

namespace Modules\Interactions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Illuminate\Http\Request;
use Modules\Comments\Models\Comment;
use Modules\Interactions\Actions\LikeComments;
use Modules\Interactions\Actions\UnlikeComments;

class CommentLikeController extends Controller
{
    use RespondsWithApi;
    /**
     * Store a newly created resource in storage.
     */
    public function likeComment(Request $request, Comment $comment, LikeComments $likeComments)
    {
        $likeComments($request->user(), $comment);

        return $this->success(message: 'Comment liked successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function unlikeComment(Request $request, Comment $comment, UnlikeComments $unlikeComments)
    {
        $unlikeComments($request->user(), $comment);

        return $this->success(message: 'Comment unliked successfully');
    }
}
