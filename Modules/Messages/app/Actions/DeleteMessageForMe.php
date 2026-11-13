<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Message;
use Modules\Messages\Models\MessageUserDeletion;

class DeleteMessageForMe
{
    public function __invoke(User $user, Message $message): void
    {
        if (Gate::forUser($user)->denies('deleteForMe', $message)) {
            abort(403, 'You cannot delete this message.');
        }

        MessageUserDeletion::firstOrCreate([
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);
    }
}
