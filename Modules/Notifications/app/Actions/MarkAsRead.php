<?php

namespace Modules\Notifications\Actions;

use Modules\Auth\Models\User;
use Modules\Notifications\Models\Notification;

class MarkAsRead
{
    /**
     * Mark a notification as read.
     *
     * @param Notification $notification
     * @param User|null $user
     * @return Notification
     */
    public function execute(Notification $notification, ?User $user = null): Notification
    {
        if ($user && $notification->user_id !== $user->id) {
            abort(403, 'You are not authorized to access this notification.');
        }

        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return $notification;
    }
}
