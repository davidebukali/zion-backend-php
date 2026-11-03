<?php

namespace Modules\Messages\Policies;

use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('user_id', $user->id)->exists();
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        $isParticipant = $conversation->participants()->where('user_id', $user->id)->exists();

        if (! $isParticipant) {
            return false;
        }

        $otherParticipant = $conversation->participants()->where('user_id', '!=', $user->id)->first();

        if (! $otherParticipant) {
            return false;
        }

        return ! $user->hasBlockRelationshipWith($otherParticipant->user_id);
    }
}

