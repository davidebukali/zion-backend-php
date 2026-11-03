<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Messages\Events\MessageUpdated;
use Modules\Messages\Models\Message;

class UpdateMessage
{
    public function __invoke(User $user, Message $message, string $body): Message
    {
        if (Gate::forUser($user)->denies('update', $message)) {
            abort(403, 'You cannot edit this message.');
        }

        $trimmedBody = trim($body);
        if ($trimmedBody === '') {
            throw ValidationException::withMessages([
                'body' => ['Message body cannot be empty.'],
            ]);
        }

        $message->update([
            'body' => $trimmedBody,
            'edited_at' => now(),
        ]);

        $message->load([
            'sender.profile.avatar',
            'media',
            'replyTo.sender.profile',
            'reactions',
        ]);

        event(new MessageUpdated($message));

        return $message;
    }
}

