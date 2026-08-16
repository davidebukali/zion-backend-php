<?php

namespace Modules\Interactions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ShareType;
use Modules\Interactions\Events\PostUnshared;
use Modules\Interactions\Models\PostShare;
use Modules\Posts\Models\Post;

class UnsharePost
{
    public function __invoke(
        User $user,
        Post $post
    ): void {
        DB::transaction(function () use ($user, $post) {
            $deleted = PostShare::query()
                ->where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->where('type', ShareType::INTERNAL->value)
                ->delete();

            if ($deleted > 0) {
                for ($i = 0; $i < $deleted; $i++) {
                    $post->decrement('shares_count');
                }

                event(new PostUnshared($post, $user, ShareType::INTERNAL));
            }
        });
    }
}
