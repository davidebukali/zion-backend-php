<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Media\Actions\ValidateAttachableMedia;
use Modules\Media\Models\Media;
use Modules\Messages\Models\Message;

class AttachMediaToMessage
{
    public function __construct(
        private readonly ValidateAttachableMedia $validateAttachableMedia
    ) {}

    /**
     * @param  array<int, string>  $mediaIds
     */
    public function handle(
        Message $message,
        array $mediaIds,
        int|string $userId
    ): void {
        if (empty($mediaIds)) {
            return;
        }

        $mediaIds = array_values(array_unique($mediaIds));

        if (count($mediaIds) > 4) {
            throw ValidationException::withMessages([
                'media_ids' => [
                    'A message can have a maximum of 4 media files.',
                ],
            ]);
        }

        $media = $this->validateAttachableMedia->handle(
            $mediaIds,
            $userId
        );

        $nonImages = $media->filter(fn (Media $item) => $item->type !== 'image');
        if ($nonImages->isNotEmpty()) {
            throw ValidationException::withMessages([
                'media_ids' => [
                    'Only image attachments are supported for messages.',
                ],
            ]);
        }

        $this->ensureMediaNotAttached($mediaIds);

        $attachments = [];
        foreach ($mediaIds as $sortOrder => $mediaId) {
            $attachments[$mediaId] = [
                'sort_order' => $sortOrder,
            ];
        }

        $message->media()->attach($attachments);
    }

    /**
     * @param  array<int, string>  $mediaIds
     */
    private function ensureMediaNotAttached(array $mediaIds): void
    {
        $attachedToPost = DB::table('post_media')->whereIn('media_id', $mediaIds)->exists();
        $attachedToComment = DB::table('comment_media')->whereIn('media_id', $mediaIds)->exists();
        $attachedToMessage = DB::table('message_media')->whereIn('media_id', $mediaIds)->exists();

        if ($attachedToPost || $attachedToComment || $attachedToMessage) {
            throw ValidationException::withMessages([
                'media_ids' => [
                    'One or more media files are already attached to another item.',
                ],
            ]);
        }
    }
}
