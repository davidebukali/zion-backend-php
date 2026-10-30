<?php

namespace Modules\Interactions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Interactions\Events\PostUnreposted;
use Modules\Interactions\Models\Repost;
use Modules\Posts\Models\Post;

class UnrepostPost
{
    public function __invoke(
        User $user,
        Post $post
    ): void {
        DB::transaction(function () use ($user, $post) {
            $deleted = Repost::query()
                ->where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->delete();

            if ($deleted > 0) {
                if ($post->reposts_count > 0) {
                    $post->decrement('reposts_count');
                }

                event(new PostUnreposted($post, $user));
            }
        });
    }
}

