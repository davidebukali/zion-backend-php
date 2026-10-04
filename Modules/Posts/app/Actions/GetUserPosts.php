<?php

namespace Modules\Posts\Actions;

use Illuminate\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;

class GetUserPosts
{
    /**
     * Retrieve paginated posts created by a specific user with visibility filtering.
     *
     * @param User $targetUser
     * @param User|null $viewer
     * @param int $perPage
     * @return CursorPaginator
     */
    public function __invoke(User $targetUser, ?User $viewer = null, int $perPage = 15): CursorPaginator
    {
        $query = Post::where('user_id', $targetUser->id)
            ->with(['user.profile', 'media'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        // If viewer is the author, they can see all posts (public, followers, private)
        if ($viewer && $viewer->id === $targetUser->id) {
            return $query->cursorPaginate($perPage)->withQueryString();
        }

        // Check if viewer is an accepted follower of targetUser
        $isFollower = false;
        if ($viewer) {
            $isFollower = Follow::where('follower_id', $viewer->id)
                ->where('following_id', $targetUser->id)
                ->where('status', FollowStatus::ACCEPTED)
                ->exists();
        }

        if ($isFollower) {
            $query->whereIn('visibility', [
                PostVisibility::PUBLIC,
                PostVisibility::FOLLOWERS,
            ]);
        } else {
            $query->where('visibility', PostVisibility::PUBLIC);
        }

        return $query->cursorPaginate($perPage)->withQueryString();
    }
}
