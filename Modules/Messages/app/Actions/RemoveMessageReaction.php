<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Messages\Events\MessageReactionUpdated;
use Modules\Messages\Models\Message;
use Modules\Messages\Models\MessageReaction;

class RemoveMessageReaction
{
    public function __invoke(User $user, Message $message): void
    {
        if (Gate::forUser($user)->denies('view', $message)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        $reaction = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->first();

        if ($reaction) {
            $reaction->delete();
            event(new MessageReactionUpdated($message, $user, null));
        }
    }
}
