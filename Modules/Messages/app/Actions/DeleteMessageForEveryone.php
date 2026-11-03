<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Messages\Events\MessageDeleted;
use Modules\Messages\Models\Message;

class DeleteMessageForEveryone
{
    public function __invoke(User $user, Message $message): Message
    {
        if (Gate::forUser($user)->denies('deleteForEveryone', $message)) {
            abort(403, 'You cannot delete this message for everyone.');
        }

        return DB::transaction(function () use ($message) {
            $message->update([
                'deleted_for_everyone_at' => now(),
                'body' => null,
            ]);

            $message->media()->detach();

            $message->load([
                'sender.profile.avatar',
                'media',
                'replyTo.sender.profile',
                'reactions',
            ]);

            event(new MessageDeleted($message));

            return $message;
        });
    }
}

