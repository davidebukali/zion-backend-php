<?php

namespace Modules\SocialGraph\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Events\FollowRequested;
use Modules\SocialGraph\Events\UserFollowed;
use Modules\SocialGraph\Models\Follow;

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

            if ($follow->wasRecentlyCreated) {
                if ($follow->status === FollowStatus::ACCEPTED) {
                    UserFollowed::dispatch($follower->id, $following->id);
                } elseif ($follow->status === FollowStatus::PENDING) {
                    FollowRequested::dispatch($follower->id, $following->id, $follow->id);
                }
            }

            return $follow;
        });
    }
}