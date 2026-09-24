<?php

namespace Modules\Posts\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Media\Actions\ValidateAttachableMedia;
use Modules\Posts\Models\Post;

class AttachMediaToPost
{
    public function __construct(
        private readonly ValidateAttachableMedia $validateAttachableMedia
    ) {
    }

    /**
     * @param array<int, string> $mediaIds
     */
    public function handle(
        Post $post,
        array $mediaIds,
        int|string $userId
    ): void {
        if (empty($mediaIds)) {
            return;
        }

        $mediaIds = array_values(array_unique($mediaIds));

        if (count($mediaIds) > 10) {
            throw ValidationException::withMessages([
                'media_ids' => [
                    'A post can have a maximum of 10 media files.',
                ],
            ]);
        }

        $media = $this->validateAttachableMedia->handle(
            $mediaIds,
            $userId
        );

        $this->ensureMediaNotAttached($mediaIds);

        $attachments = [];

        foreach ($mediaIds as $sortOrder => $mediaId) {
            $attachments[] = [
                'media_id' => $mediaId,
                'sort_order' => $sortOrder,
            ];
        }

        $post->media()->attach($attachments);
    }

    /**
     * @param array<int, string> $mediaIds
     */
    private function ensureMediaNotAttached(array $mediaIds): void
    {
        $alreadyAttached = DB::table('post_media')
            ->whereIn('media_id', $mediaIds)
            ->exists();

        if ($alreadyAttached) {
            throw ValidationException::withMessages([
                'media_ids' => [
                    'One or more media files are already attached to a post.',
                ],
            ]);
        }
    }
}