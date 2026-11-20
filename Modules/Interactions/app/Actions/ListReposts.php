<?php

namespace Modules\Interactions\Actions;

use Illuminate\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;

class ListReposts
{
    /**
     * Retrieve a cursor-paginated list of posts reposted by the user.
     */
    public function __invoke(User $user, int $perPage = 15): CursorPaginator
    {
        return Post::query()
            ->whereHas('reposts', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['user.profile', 'media'])
            ->withViewerState($user)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
