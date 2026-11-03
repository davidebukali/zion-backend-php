<?php

namespace Modules\Messages\Policies;

use Modules\Auth\Models\User;
use Modules\Messages\Models\Message;

class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        return $message->conversation->participants()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Message $message): bool
    {
        if ($message->sender_id !== $user->id) {
            return false;
        }

        if ($message->isDeletedForEveryone()) {
            return false;
        }

        // 15-minute edit window
        return $message->created_at->greaterThanOrEqualTo(now()->subMinutes(15));
    }

    public function deleteForEveryone(User $user, Message $message): bool
    {
        if ($message->sender_id !== $user->id) {
            return false;
        }

        return ! $message->isDeletedForEveryone();
    }

    public function deleteForMe(User $user, Message $message): bool
    {
        return $this->view($user, $message);
    }

    public function react(User $user, Message $message): bool
    {
        if (! $this->view($user, $message)) {
            return false;
        }

        if ($message->isDeletedForEveryone()) {
            return false;
        }

        $otherParticipant = $message->conversation->participants()->where('user_id', '!=', $user->id)->first();

        if ($otherParticipant && $user->hasBlockRelationshipWith($otherParticipant->user_id)) {
            return false;
        }

        return true;
    }
}

