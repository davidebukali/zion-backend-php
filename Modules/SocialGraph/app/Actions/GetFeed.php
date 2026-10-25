<?php

namespace Modules\SocialGraph\Actions;

use Modules\Auth\Models\User;
use Modules\SocialGraph\Models\FeedItem;

class GetFeed
{
    public function __invoke(User $user, int $perPage = 20)
    {
        return FeedItem::query()
            ->where('user_id', $user->id)
            ->with([
                'post' => function ($query) use ($user) {
                    $query->with(['user', 'media'])
                        ->withExists([
                            'likes as is_liked' => fn ($q) => $q->where('user_id', $user->id),
                        ]);
                },
            ])
            ->orderByDesc('published_at')
            ->cursorPaginate($perPage);
    }
}