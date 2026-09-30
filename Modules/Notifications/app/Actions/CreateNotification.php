<?php

namespace Modules\Notifications\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Notifications\Models\Notification;

class CreateNotification
{
    /**
     * Create and persist a new notification instance.
     *
     * @param User|string $user The recipient user or user ID
     * @param string $type The notification type (e.g. 'comment_liked', 'post_liked')
     * @param Model $notifiable The target polymorphic model (e.g. Comment, Post)
     * @param User|string|null $actor The actor who triggered the notification
     * @param array $data Additional metadata payload
     */
    public function __invoke(
        User|string $user,
        string $type,
        Model $notifiable,
        User|string|null $actor = null,
        array $data = []
    ): ?Notification {
        $userId = $user instanceof User ? $user->id : $user;
        $actorId = $actor instanceof User ? $actor->id : $actor;

        // Do not notify a user about their own actions
        if ($actorId !== null && $actorId === $userId) {
            return null;
        }

        return Notification::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'data' => $data,
        ]);
    }
}
