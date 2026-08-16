<?php

namespace Modules\Interactions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;
use Modules\Interactions\Events\CommentUnliked;
use Modules\Interactions\Models\CommentLike;

class UnlikeComments
{
    public function __invoke(
        User $user,
        Comment $comment
    ): void {
        DB::transaction(function () use ($user, $comment) {

            $deleted = CommentLike::query()
                ->where('comment_id', $comment->id)
                ->where('user_id', $user->id)
                ->delete();

            if ($deleted > 0) {
                $comment->decrement('likes_count');

                event(new CommentUnliked($comment, $user));
            }
        });
    }
}
