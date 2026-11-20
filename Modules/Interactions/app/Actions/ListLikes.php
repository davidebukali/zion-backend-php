<?php

namespace Modules\Interactions\Actions;

use Illuminate\Pagination\CursorPaginator;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Models\UserBlock;

class ListLikes
{
    /**
     * Retrieve a cursor-paginated list of posts liked by the target user.
     */
    public function __invoke(User $targetUser, ?User $viewer = null, int $perPage = 15): CursorPaginator
    {
        if ($viewer && $viewer->id !== $targetUser->id) {
            if ($viewer->hasBlockRelationshipWith($targetUser)) {
                abort(403, 'You cannot view this user\'s content.');
            }

            $isPrivate = (bool) ($targetUser->profile?->is_private ?? false);
            if ($isPrivate && ! $viewer->isFollowing($targetUser)) {
                abort(403, 'This account is private.');
            }
        }

        $query = Post::query()
            ->whereHas('likes', function ($query) use ($targetUser) {
                $query->where('user_id', $targetUser->id);
            })
            ->with(['user.profile', 'media'])
            ->withViewerState($viewer)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $query->where(function ($q) use ($viewer) {
            if ($viewer) {
                $q->where('moderation_status', PostModerationStatus::APPROVED)
                    ->orWhere('user_id', $viewer->id);
            } else {
                $q->where('moderation_status', PostModerationStatus::APPROVED);
            }
        });

        if ($viewer) {
            $blockedUserIds = UserBlock::query()
                ->where('blocker_id', $viewer->id)
                ->pluck('blocked_id')
                ->merge(
                    UserBlock::query()
                        ->where('blocked_id', $viewer->id)
                        ->pluck('blocker_id')
                );

            if ($blockedUserIds->isNotEmpty()) {
                $query->whereNotIn('user_id', $blockedUserIds);
            }
        }

        $query->where(function ($q) use ($viewer) {
            // 1. Posts from public profiles (or profiles not marked private) that are marked public
            $q->whereDoesntHave('user.profile', function ($pq) {
                $pq->where('is_private', true);
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
