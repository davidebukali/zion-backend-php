<?php

namespace Modules\Search\Actions;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Models\User;

class SearchUsers
{
    /**
     * Search users by name, username, or display name.
     *
     * @param string $query
     * @param int $perPage
     * @param bool $paginate
     * @return Paginator|Collection
     */
    public function __invoke(string $query, int $perPage = 15, bool $paginate = true): Paginator|Collection
    {
        $term = trim($query);

        $builder = User::where(function ($q) use ($term) {
            $q->where('name', 'ILIKE', "%{$term}%")
                ->orWhereHas('profile', function ($pq) use ($term) {
                    $pq->where('username', 'ILIKE', "%{$term}%")
                        ->orWhere('display_name', 'ILIKE', "%{$term}%")
                        ->orWhere('bio', 'ILIKE', "%{$term}%");
                });
        })
        ->with(['profile.avatar', 'profile.cover'])
        ->orderByDesc('created_at');

        if (! $paginate) {
            return $builder->limit($perPage)->get();
        }

        return $builder->paginate($perPage)->withQueryString();
    }
}
