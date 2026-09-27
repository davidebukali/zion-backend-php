<?php

namespace Modules\SocialGraph\Actions;

use Modules\Auth\Models\User;
use Modules\SocialGraph\Events\UserUnfollowed;
use Modules\SocialGraph\Models\Follow;

class UnfollowUser
{
    public function __invoke(
        User $follower,
        User $following
    ): void {
        Follow::query()
            ->where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->delete();

        UserUnfollowed::dispatch($follower->id, $following->id);
    }
}