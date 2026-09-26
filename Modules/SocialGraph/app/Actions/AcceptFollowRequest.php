<?php

namespace Modules\SocialGraph\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Enums\FollowStatus;

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

        return $follow->refresh();
    }
}