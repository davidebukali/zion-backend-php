<?php

namespace Modules\Notifications\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Interactions\Models\PostLike;
use Modules\Notifications\Models\Notification;
use Modules\Posts\Models\Post;

class UpdateGroupedPostLikesNotification
{
    /**
     * Create, update, or remove grouped post likes notification.
     */
    public function __invoke(Post $post): void
    {
        DB::transaction(function () use ($post): void {
            $postRecord = Post::withoutGlobalScopes()
                ->whereKey($post->getKey())
                ->lockForUpdate()
                ->first();

            if (! $postRecord || ! $postRecord->user_id) {
                return;
            }

            $recipientId = $postRecord->user_id;
            $groupKey = 'post_liked:'.$postRecord->id;

            $likesQuery = PostLike::query()
                ->where('post_id', $postRecord->id)
                ->where('user_id', '!=', $recipientId);

            $actorsCount = (clone $likesQuery)->count();

            if ($actorsCount === 0) {
                Notification::query()
                    ->where('user_id', $recipientId)
                    ->where('group_key', $groupKey)
                    ->delete();

                return;
            }

            $latestActorIds = (clone $likesQuery)
                ->orderByDesc('id')
                ->limit(3)
                ->pluck('user_id')
                ->all();

            $notification = Notification::query()->firstOrNew([
                'user_id' => $recipientId,
                'group_key' => $groupKey,
            ]);

            $previousCount = (int) ($notification->data['actors_count'] ?? 0);

            $notification->actor_id = $latestActorIds[0] ?? null;
            $notification->type = 'post_liked';
            $notification->notifiable_type = $postRecord->getMorphClass();
            $notification->notifiable_id = $postRecord->id;

            $notification->data = [
                'event' => 'post_liked',
                'post_id' => $postRecord->id,
                'actors_count' => $actorsCount,
                'actor_ids' => $latestActorIds,
            ];

            if (! $notification->exists || $actorsCount > $previousCount) {
                $notification->read_at = null;
                $notification->created_at = now();
            }

            $notification->save();
        });
    }
}
