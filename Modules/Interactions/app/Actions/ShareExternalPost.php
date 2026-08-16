<?php

namespace Modules\Interactions\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ShareType;
use Modules\Interactions\Events\PostShared;
use Modules\Interactions\Models\PostShare;
use Modules\Posts\Models\Post;

class ShareExternalPost
{
    public function __invoke(
        User $user,
        Post $post
    ): PostShare {
        return DB::transaction(function () use ($user, $post) {
            $share = PostShare::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => ShareType::EXTERNAL,
            ]);

            event(new PostShared($post, $user, ShareType::EXTERNAL));

            return $share;
        });
    }
}
