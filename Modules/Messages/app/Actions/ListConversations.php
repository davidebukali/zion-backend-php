<?php

namespace Modules\Messages\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;

class ListConversations
{
    public function __invoke(
        User $user,
        ?string $search = null,
        bool $archived = false,
        int $perPage = 20
    ): CursorPaginator {
        return Conversation::query()
            ->whereHas('participants', function ($query) use ($user, $archived) {
                $query->where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->whereNull('hidden_at')
                            ->orWhereColumn('conversations.last_message_at', '>', 'conversation_participants.hidden_at');
                    })
                    ->when($archived, fn ($q) => $q->whereNotNull('archived_at'))
                    ->when(! $archived, fn ($q) => $q->whereNull('archived_at'));
            })
            ->when(! empty($search), function ($query) use ($user, $search) {
                $escaped = addcslashes(trim($search), '%_\\');
                $searchTerm = "%{$escaped}%";
                $query->whereHas('participants', function ($q) use ($user, $searchTerm) {
                    $q->where('user_id', '!=', $user->id)
                        ->whereHas('user', function ($u) use ($searchTerm) {
                            $u->where('name', 'ilike', $searchTerm)
                                ->orWhereHas('profile', function ($p) use ($searchTerm) {
                                    $p->where('username', 'ilike', $searchTerm)
                                        ->orWhere('display_name', 'ilike', $searchTerm);
                                });
                        });
                });
            })
            ->with([
                'participants.user.profile.avatar',
                'latestMessage.sender.profile.avatar',
                'latestMessage.media',
                'latestMessage.reactions',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
