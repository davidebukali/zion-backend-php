<?php

namespace Modules\Search\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;

class SearchUsers
{
    /**
     * Search users by name, username, or display name.
     *
     * @param  User|int|null  $viewer
     */
    public function __invoke(string $query, $viewer = null, int $perPage = 15, bool $paginate = true): Paginator|Collection
    {
        if (is_int($viewer)) {
            $perPage = $viewer;
            $viewer = null;
        }

        $term = trim($query);
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'like';

        $builder = User::query();

        if ($term !== '' && $term !== '*') {
            $builder->where(function ($q) use ($term, $operator) {
                $q->where('name', $operator, "%{$term}%")
                    ->orWhereHas('profile', function ($pq) use ($term, $operator) {
                        $pq->where('username', $operator, "%{$term}%")
                            ->orWhere('display_name', $operator, "%{$term}%")
                            ->orWhere('bio', $operator, "%{$term}%");
                    });
            });
        }

        $builder->with(['profile.avatar', 'profile.cover'])
            ->withViewerFollowStatus($viewer)
            ->orderByDesc('created_at');

        if (! $paginate) {
            return $builder->limit($perPage)->get();
        }

        return $builder->paginate($perPage)->withQueryString();
    }
}
