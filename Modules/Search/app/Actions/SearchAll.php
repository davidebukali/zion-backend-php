<?php

namespace Modules\Search\Actions;

use Modules\Auth\Models\User;

class SearchAll
{
    public function __construct(
        protected SearchUsers $searchUsers,
        protected SearchPosts $searchPosts
    ) {}

    /**
     * Search both users and posts for unified overview results.
     *
     * @param string $query
     * @param User|null $viewer
     * @param int $limit
     * @return array
     */
    public function __invoke(string $query, ?User $viewer = null, int $limit = 5): array
    {
        $users = ($this->searchUsers)($query, $limit, paginate: false);
        $posts = ($this->searchPosts)($query, $viewer, $limit, paginate: false);

        return [
            'users' => $users,
            'posts' => $posts,
        ];
    }
}
