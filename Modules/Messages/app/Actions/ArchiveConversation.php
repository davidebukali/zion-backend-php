<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;

class ArchiveConversation
{
    public function __invoke(User $user, Conversation $conversation): void
    {
        if (Gate::forUser($user)->denies('view', $conversation)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        $conversation->participants()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['archived_at' => now()]);
    }
}

