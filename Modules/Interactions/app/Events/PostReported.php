<?php

namespace Modules\Interactions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;

class PostReported
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Post $post,
        public User $user,
        public string|object $reason,
        public ?string $description = null
    ) {}
}
