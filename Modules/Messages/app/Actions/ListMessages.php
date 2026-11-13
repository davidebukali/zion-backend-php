<?php

namespace Modules\Messages\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\Message;

class ListMessages
{
    public function __invoke(
        User $viewer,
        Conversation $conversation,
        int $perPage = 30
    ): CursorPaginator {
        if (Gate::forUser($viewer)->denies('view', $conversation)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        $participant = $conversation->participants()
            ->where('user_id', $viewer->id)
            ->first();

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->visibleTo($viewer, $participant?->cleared_at)
            ->with([
                'sender.profile.avatar',
                'media',
                'replyTo.sender.profile',
                'reactions.user',
            ])
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
