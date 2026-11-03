<?php

namespace Modules\Posts\Actions;

use Illuminate\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;

class ListPosts
{
    /**
     * Retrieve a cursor-paginated list of posts for the feed.
     */
    public function __invoke(int $perPage = 15, ?User $viewer = null): CursorPaginator
    {
        $query = Post::feed()
            ->with('media')
            ->withViewerState($viewer);

        $query->where(function ($q) use ($viewer) {
            // 1. Posts from public profiles that are marked public
            $q->whereHas('user.profile', function ($pq) {
                $pq->where('is_private', false);
            })->where('visibility', PostVisibility::PUBLIC);

            if ($viewer) {
                // 2. Viewer's own posts
                $q->orWhere('user_id', $viewer->id);

                // 3. Posts from accounts the viewer follows
                $followingIds = Follow::where('follower_id', $viewer->id)
                    ->where('status', FollowStatus::ACCEPTED)
                    ->pluck('following_id');

                if ($followingIds->isNotEmpty()) {
                    $q->orWhere(function ($sub) use ($followingIds) {
                        $sub->whereIn('user_id', $followingIds)
                            ->whereIn('visibility', [
                                PostVisibility::PUBLIC,
                                PostVisibility::FOLLOWERS,
                            ]);
                    });
                }
            }
        });

        return $query->cursorPaginate($perPage)->withQueryString();
    }
}
