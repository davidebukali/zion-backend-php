<?php

namespace Modules\Posts\Actions;

use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;

class DeletePost
{
    /**
     * Delete a post.
     */
    public function __invoke(Post $post): void
    {
        $post->delete();
    }
}
