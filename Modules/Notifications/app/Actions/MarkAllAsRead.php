<?php

namespace Modules\Notifications\Actions;

use Modules\Auth\Models\User;

class MarkAllAsRead
{
    /**
     * Mark all unread notifications for a user as read.
     *
     * @param User $user
     * @return int Number of notifications updated
     */
    public function execute(User $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }
}
