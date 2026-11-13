<?php

namespace Modules\Posts\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Events\PostUpdated;
use Modules\Posts\Models\Post;
use Modules\Posts\Transformers\PostResource;

class UpdatePost
{
    public function __construct(
        private readonly AttachMediaToPost $attachMediaToPost
    ) {}

    /**
     * Update an existing post for a user.
     */
    public function __invoke(Post $post, User $user, array $data): PostResource
    {
        return DB::transaction(function () use ($post, $user, $data) {
            $changedAttributes = [];

            // 1. Update text content if explicitly provided
            if (array_key_exists('content', $data)) {
                if ($post->content !== $data['content']) {
                    $post->content = $data['content'];
                    $changedAttributes[] = 'content';
                }
            }

            // 2. Update visibility if explicitly provided
            if (array_key_exists('visibility', $data) && $data['visibility'] !== null) {
                $newVisibility = $data['visibility'] instanceof PostVisibility
                    ? $data['visibility']
                    : PostVisibility::from($data['visibility']);

                if ($post->visibility !== $newVisibility) {
                    $post->visibility = $newVisibility;
                    $changedAttributes[] = 'visibility';
                }
            }

            $post->save();

            // 3. Sync media attachments if media_ids key is provided
            if (array_key_exists('media_ids', $data)) {
                $newMediaIds = array_values(array_unique($data['media_ids'] ?? []));
                $currentMediaIds = $post->media()->pluck('media.id')->all();

                $toDetach = array_diff($currentMediaIds, $newMediaIds);
                $toAttach = array_diff($newMediaIds, $currentMediaIds);

                if (! empty($toDetach) || ! empty($toAttach)) {
                    $changedAttributes[] = 'media';
                }

                if (! empty($toDetach)) {
                    $post->media()->detach($toDetach);
                }

                if (! empty($toAttach)) {
                    $this->attachMediaToPost->handle(
                        post: $post,
                        mediaIds: array_values($toAttach),
                        userId: $user->id
                    );
                }

                // Update sort_order for all currently attached media
                foreach ($newMediaIds as $sortOrder => $mediaId) {
                    $post->media()->updateExistingPivot($mediaId, ['sort_order' => $sortOrder]);
                }
            }

            // 4. Dispatch PostUpdated event if any tracked attributes changed
            if (! empty($changedAttributes)) {
                PostUpdated::dispatch($post, $changedAttributes);
            }

            return new PostResource($post->loadMissing(['user', 'media']));
        });
    }
}
