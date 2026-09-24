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
     * @group Interactions
     * @subgroup Likes
     * @authenticated
     * 
     * Like Comment
     * 
     * Like a comment as the authenticated user.
     * 
     * @urlParam comment string required The UUID of the comment to like. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Comment liked successfully"
     * }
     */
    public function likeComment(Request $request, Comment $comment, LikeComments $likeComments)
    {
        $likeComments($request->user(), $comment);

        return $this->success(message: 'Comment liked successfully');
    }

    /**
     * @group Interactions
     * @subgroup Likes
     * @authenticated
     * 
     * Unlike Comment
     * 
     * Remove like from a comment.
     * 
     * @urlParam comment string required The UUID of the comment to unlike. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Comment unliked successfully"
     * }
     */
    public function unlikeComment(Request $request, Comment $comment, UnlikeComments $unlikeComments)
    {
        $unlikeComments($request->user(), $comment);

        return $this->success(message: 'Comment unliked successfully');
    }
}
