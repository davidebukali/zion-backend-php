<?php

namespace Modules\SocialGraph\Actions;

use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;

class RejectFollowRequest
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
            'status' => FollowStatus::REJECTED,
        ]);

        return $follow->refresh();
    }
}
