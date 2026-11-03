<?php

namespace Modules\SocialGraph\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Models\UserBlock;

class ListBlockedUsers
{
    public function __invoke(User $user, int $perPage = 20): CursorPaginator
    {
        return UserBlock::query()
            ->where('blocker_id', $user->id)
            ->with(['blocked.profile.avatar'])
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}

