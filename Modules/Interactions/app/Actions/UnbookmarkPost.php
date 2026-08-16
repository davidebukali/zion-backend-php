<?php

namespace Modules\Interactions\Actions;

use Modules\Auth\Models\User;
use Modules\Interactions\Models\Bookmark;
use Modules\Posts\Models\Post;

class UnbookmarkPost
{
    public function __invoke(
        User $user,
        Post $post
    ): bool {
        return (bool) Bookmark::query()
            ->where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete();
    }
}
