<?php

namespace Modules\Interactions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ShareType;
use Modules\Posts\Models\Post;

class PostUnshared
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Post $post,
        public User $user,
        public ShareType $type = ShareType::INTERNAL
    ) {}
}
