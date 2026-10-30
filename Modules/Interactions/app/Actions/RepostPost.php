<?php

namespace Modules\Interactions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Interactions\Events\PostReposted;
use Modules\Interactions\Models\Repost;
use Modules\Posts\Models\Post;

class RepostPost
{
    public function __invoke(
        User $user,
        Post $post
    ): Repost {
        return DB::transaction(function () use ($user, $post) {
            $repost = Repost::firstOrCreate([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);

            if ($repost->wasRecentlyCreated) {
                $post->increment('reposts_count');

                event(new PostReposted($post, $user));
            }

            return $repost;
        });
    }
}

