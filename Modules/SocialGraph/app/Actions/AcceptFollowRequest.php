<?php

namespace Modules\SocialGraph\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Events\FollowRequestAccepted;
use Modules\SocialGraph\Models\Follow;

class AcceptFollowRequest
{
    public function __invoke(
        User $user,
        Follow $follow
    ): Follow {
        if ($follow->following_id !== $user->id) {
            abort(403);
        }

        if ($follow->status !== FollowStatus::PENDING) {
            abort(422, 'This follow request is not pending.');
        }

        $follow->update([
            'status' => FollowStatus::ACCEPTED,
        ]);

        FollowRequestAccepted::dispatch($follow->follower_id, $follow->following_id);

        return $follow->refresh();
    }
}