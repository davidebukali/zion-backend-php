<?php

namespace Modules\SocialGraph\Actions;

use Modules\Auth\Models\User;
use Modules\SocialGraph\Models\UserBlock;

class BlockUser
{
    public function __invoke(
        User $blocker,
        User $blocked
    ): UserBlock {
        if ($blocker->id === $blocked->id) {
            abort(422, 'You cannot block yourself.');
        }

        return UserBlock::firstOrCreate([
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
        ]);
    }
}
