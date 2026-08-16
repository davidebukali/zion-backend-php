<?php

namespace Modules\Interactions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;

class CommentUnliked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public User $user
    ) {}
}
