<?php

namespace Modules\SocialGraph\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;

class ListTaggableUsers
{
    /**
     * Retrieve a cursor-paginated list of users eligible to be tagged by the given user.
     * Includes users followed by the user and users who follow the user (including pending),
     * while silently excluding blocked users.
     */
    public function __invoke(User $user, ?string $search = null, int $perPage = 20): CursorPaginator
    {
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'like';

        return User::query()
            ->where('id', '!=', $user->id)
            // Either followed by auth user OR follows auth user (including pending)
            ->where(function ($relationQuery) use ($user) {
                $relationQuery->whereHas('followers', function ($q) use ($user) {
                    $q->where('follower_id', $user->id);
                })->orWhereHas('following', function ($q) use ($user) {
                    $q->where('following_id', $user->id);
                });
            })
            // Exclude users blocked by auth user
            ->whereDoesntHave('blockedByUsers', function ($q) use ($user) {
                $q->where('blocker_id', $user->id);
            })
            // Exclude users who have blocked auth user
            ->whereDoesntHave('blockedUsers', function ($q) use ($user) {
                $q->where('blocked_id', $user->id);
            })
            // Autocomplete search by name, username, or display_name
            ->when(! empty($search), function ($query) use ($search, $operator) {
                $escaped = addcslashes(trim($search), '%_\\');
                $searchTerm = "%{$escaped}%";
                $query->where(function ($q) use ($searchTerm, $operator) {
                    $q->where('name', $operator, $searchTerm)
                        ->orWhereHas('profile', function ($p) use ($searchTerm, $operator) {
                            $p->where('username', $operator, $searchTerm)
                                ->orWhere('display_name', $operator, $searchTerm);
                        });
                });
            })
            ->with(['profile.avatar'])
            ->withViewerFollowStatus($user)
            ->orderBy('name')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }
}
