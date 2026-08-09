<?php

namespace Modules\Comments\Actions;

use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;
use Modules\Comments\Models\Comment;

class CreateComment
{
    /**
     * Create a new comment for a post by a user.
     */
    public function __invoke(User $user, Post $post, array $data): Comment
    {
        return Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'parent_comment_id' => $data['parent_comment_id'] ?? null,
            'content' => $data['content'],
        ]);
    }
}
