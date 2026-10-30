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
            ->whereHas('post')
            ->with([
                'actor.profile',
                'post' => function ($query) use ($user) {
                    $query->with(['user.profile', 'media'])
                        ->withViewerState($user);
                },
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }
}
