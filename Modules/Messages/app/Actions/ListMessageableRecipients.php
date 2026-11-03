<?php

namespace Modules\Messages\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;

class ListMessageableRecipients
{
    public function __invoke(User $user, ?string $search = null, int $perPage = 20): CursorPaginator
    {
        return User::query()
            ->where('id', '!=', $user->id)
            ->whereHas('followers', function ($q) use ($user) {
                $q->where('follower_id', $user->id)
                    ->where('status', FollowStatus::ACCEPTED);
            })
            ->whereHas('following', function ($q) use ($user) {
                $q->where('following_id', $user->id)
                    ->where('status', FollowStatus::ACCEPTED);
            })
            ->whereDoesntHave('blockedUsers', function ($q) use ($user) {
                $q->where('blocked_id', $user->id);
            })
            ->whereDoesntHave('blockedByUsers', function ($q) use ($user) {
                $q->where('blocker_id', $user->id);
            })
            ->when(! empty($search), function ($query) use ($search) {
                $searchTerm = '%'.trim($search).'%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'ilike', $searchTerm)
                        ->orWhereHas('profile', function ($p) use ($searchTerm) {
                            $p->where('username', 'ilike', $searchTerm)
                                ->orWhere('display_name', 'ilike', $searchTerm);
                        });
                });
            })
            ->with(['profile.avatar'])
            ->orderBy('name')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }
}

