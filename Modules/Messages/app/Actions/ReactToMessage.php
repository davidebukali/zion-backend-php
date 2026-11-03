<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Messages\Events\MessageReactionUpdated;
use Modules\Messages\Models\Message;
use Modules\Messages\Models\MessageReaction;

class ReactToMessage
{
    public function __invoke(User $user, Message $message, string $emoji): MessageReaction
    {
        if (Gate::forUser($user)->denies('react', $message)) {
            abort(403, 'You cannot react to this message.');
        }

        $trimmedEmoji = trim($emoji);
        if ($trimmedEmoji === '') {
            throw ValidationException::withMessages([
                'emoji' => ['Emoji is required.'],
            ]);
        }

        $reaction = MessageReaction::updateOrCreate(
            [
                'message_id' => $message->id,
                'user_id' => $user->id,
            ],
            [
                'emoji' => $trimmedEmoji,
            ]
        );

        event(new MessageReactionUpdated($message, $user, $reaction));

        return $reaction;
    }
}

