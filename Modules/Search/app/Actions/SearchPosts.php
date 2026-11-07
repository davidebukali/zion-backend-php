<?php

namespace Modules\Search\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;

class SearchPosts
{
    /**
     * Search posts by text content with viewer visibility rules.
     */
    public function __invoke(string $query, ?User $viewer = null, int $perPage = 15, bool $paginate = true): Paginator|Collection
    {
        $term = trim($query);
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'like';

        $builder = Post::query();

        if ($term !== '' && $term !== '*') {
            $builder->where('content', $operator, "%{$term}%");
        }

        $builder->where(function ($q) use ($viewer) {
            // 1. Posts from public profiles that are marked public
            $q->where(function ($publicQ) {
                $publicQ->where('visibility', PostVisibility::PUBLIC)
                    ->whereHas('user.profile', function ($pq) {
                        $pq->where('is_private', false);
                    });
            });

            if ($viewer) {
                // 2. Author's own posts (including followers and private)
                $q->orWhere('user_id', $viewer->id);

                // 3. Posts from followed accounts (including private accounts)
                $followingIds = Follow::where('follower_id', $viewer->id)
                    ->where('status', FollowStatus::ACCEPTED)
                    ->pluck('following_id');

                if ($followingIds->isNotEmpty()) {
                    $q->orWhere(function ($fq) use ($followingIds) {
                        $fq->whereIn('user_id', $followingIds)
                            ->whereIn('visibility', [
                                PostVisibility::PUBLIC,
                                PostVisibility::FOLLOWERS,
                            ]);
                    });
                }
            }
        })
            ->with(['user.profile.avatar', 'media'])
            ->withViewerState($viewer)
            ->orderByRaw('((likes_count * 1) + (comments_count * 2) + (reposts_count * 3)) DESC')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! $paginate) {
            return $builder->limit($perPage)->get();
        }

        return $builder->paginate($perPage)->withQueryString();
    }
}
