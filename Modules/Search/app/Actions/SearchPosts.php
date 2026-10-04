<?php

namespace Modules\Search\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;

class SearchPosts
{
    /**
     * Search posts by text content with viewer visibility rules.
     *
     * @param string $query
     * @param User|null $viewer
     * @param int $perPage
     * @param bool $paginate
     * @return Paginator|Collection
     */
    public function __invoke(string $query, ?User $viewer = null, int $perPage = 15, bool $paginate = true): Paginator|Collection
    {
        $term = trim($query);

        $builder = Post::where('content', 'ILIKE', "%{$term}%")
            ->where(function ($q) use ($viewer) {
                // 1. Always include public posts
                $q->where('visibility', PostVisibility::PUBLIC);

                if ($viewer) {
                    // 2. Author's own posts (including followers and private)
                    $q->orWhere('user_id', $viewer->id);

                    // 3. Posts with followers visibility where viewer is an accepted follower
                    $followingIds = Follow::where('follower_id', $viewer->id)
                        ->where('status', FollowStatus::ACCEPTED)
                        ->pluck('following_id');

                    if ($followingIds->isNotEmpty()) {
                        $q->orWhere(function ($fq) use ($followingIds) {
                            $fq->whereIn('user_id', $followingIds)
                               ->where('visibility', PostVisibility::FOLLOWERS);
                        });
                    }
                }
            })
            ->with(['user.profile.avatar', 'media'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! $paginate) {
            return $builder->limit($perPage)->get();
        }

        return $builder->paginate($perPage)->withQueryString();
    }
}
