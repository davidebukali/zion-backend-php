<?php

namespace Modules\Interactions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;

class PostUnliked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Post $post,
        public User $user
    ) {}
}
