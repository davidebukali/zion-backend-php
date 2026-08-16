<?php

namespace Modules\Interactions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;
use Modules\Interactions\Events\CommentLiked;
use Modules\Interactions\Models\CommentLike;

class LikeComments
{
    public function __invoke(
        User $user,
        Comment $comment
    ): void {
        DB::transaction(function () use ($user, $comment) {

            $like = CommentLike::firstOrCreate([
                'comment_id' => $comment->id,
                'user_id' => $user->id,
            ]);

            if ($like->wasRecentlyCreated) {
                $comment->increment('likes_count');

                event(new CommentLiked($comment, $user));
            }
        });
    }
}
