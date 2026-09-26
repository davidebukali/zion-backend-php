<?php

namespace Modules\SocialGraph\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Enums\FollowStatus;

class FollowUser
{
    public function __invoke(
        User $follower,
        User $following
    ): Follow {
        if ($follower->id === $following->id) {
            abort(422, 'You cannot follow yourself.');
        }

        return DB::transaction(function () use (
            $follower,
            $following
        ) {
            $follow = Follow::firstOrCreate(
                [
                    'follower_id' => $follower->id,
                    'following_id' => $following->id,
                ],
                [
                    'status' => FollowStatus::ACCEPTED,
                ]
            );

            return $follow;
        });
    }
}