<?php

namespace Modules\Interactions\Actions;

use Modules\Auth\Models\User;
use Modules\Interactions\Models\Bookmark;
use Modules\Posts\Models\Post;

class BookmarkPost
{
    public function __invoke(
        User $user,
        Post $post
    ): Bookmark {
        return Bookmark::firstOrCreate([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }
}
