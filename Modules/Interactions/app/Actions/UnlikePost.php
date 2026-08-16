<?php

namespace Modules\Interactions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Interactions\Events\PostUnliked;
use Modules\Interactions\Models\PostLike;
use Modules\Posts\Models\Post;

class UnlikePost
{
    public function __invoke(
        User $user,
        Post $post
    ): void {
        DB::transaction(function () use ($user, $post) {

            $deleted = PostLike::query()
                ->where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->delete();

            if ($deleted > 0) {
                $post->decrement('likes_count');

                event(new PostUnliked($post, $user));
            }
        });
    }
}
