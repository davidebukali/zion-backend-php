<?php

namespace Modules\Media\Actions;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;

class ValidateAttachableMedia
{
    /**
     * @param array<int, string> $mediaIds
     * @param int|string $userId
     */
    public function handle(
        array $mediaIds,
        int|string $userId
    ): Collection {
        $media = Media::query()
            ->whereIn('id', $mediaIds)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->get();

        if ($media->count() !== count($mediaIds)) {
            throw ValidationException::withMessages([
                'media_ids' => [
                    'One or more media files do not exist or do not belong to you.',
                ],
            ]);
        }

        $notReady = $media->filter(
            fn (Media $item): bool =>
                $item->status !== MediaStatus::Ready
        );

        if ($notReady->isNotEmpty()) {
            throw ValidationException::withMessages([
                'media_ids' => [
                    'All media files must finish processing before attachment.',
                ],
            ]);
        }

        return $media;
    }
}