<?php

namespace Modules\SocialGraph\Actions;

use Modules\Auth\Models\User;
use Modules\SocialGraph\Models\UserBlock;

class UnblockUser
{
    public function __invoke(
        User $blocker,
        User $blocked
    ): void {
        UserBlock::query()
            ->where('blocker_id', $blocker->id)
            ->where('blocked_id', $blocked->id)
            ->delete();
    }
}
