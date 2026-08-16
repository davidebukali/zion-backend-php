<?php

namespace Modules\Interactions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Interactions\Events\PostLiked;
use Modules\Interactions\Models\PostLike;
use Modules\Posts\Models\Post;

class LikePosts
{
    public function __invoke(
        User $user,
        Post $post
    ): void {
        DB::transaction(function () use ($user, $post) {

            $like = PostLike::firstOrCreate([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);

            if ($like->wasRecentlyCreated) {
                $post->increment('likes_count');

                event(new PostLiked($post, $user));
            }
        });
    }
}
