<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Messages\Events\MessageRead;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\Message;

class MarkConversationAsRead
{
    public function __invoke(User $user, Conversation $conversation): void
    {
        if (Gate::forUser($user)->denies('view', $conversation)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        $participant = $conversation->participants()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $latestMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->visibleTo($user, $participant->cleared_at)
            ->orderByDesc('id')
            ->first();

        $participant->update([
            'last_read_message_id' => $latestMessage?->id ?? $participant->last_read_message_id,
            'last_read_at' => now(),
        ]);

        event(new MessageRead($conversation, $user, $participant->last_read_message_id));
    }
}
