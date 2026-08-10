<?php

namespace Modules\Comments\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;

class CreateReply
{
    /**
     * Create a new reply comment.
     */
    public function __invoke(User $user, Comment $parentComment, array $data): Comment
    {
        return DB::transaction(function () use ($user, $parentComment, $data) {
            $reply = Comment::create([
                'user_id' => $user->id,
                'post_id' => $parentComment->post_id,
                'parent_comment_id' => $parentComment->id,
                'content' => $data['content'],
            ]);

            $parentComment->increment('replies_count');

            return $reply;
        });
    }
}
