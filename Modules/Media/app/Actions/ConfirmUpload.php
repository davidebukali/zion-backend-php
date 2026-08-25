<?php

namespace Modules\Media\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Services\R2StorageService;

class ConfirmUpload
{
    public function __construct(
        private readonly R2StorageService $storage,
    ) {
    }

    public function __invoke(
        User $user,
        Media $media,
        ?string $checksum = null,
    ): Media {
        /*
         * 1. Verify ownership.
         */
        abort_unless(
            $media->user_id === $user->id,
            403,
            'You do not own this media.'
        );

        /*
         * 2. Make confirmation idempotent for Ready status.
         */
        if ($media->status === MediaStatus::Ready) {
            return $media;
        }

        if ($media->status !== MediaStatus::Pending) {
            abort(
                422,
                'Media cannot be confirmed in its current state.'
            );
        }

        /*
         * 3. Verify object exists in R2.
         */
        $object = $this->storage->head(
            $media->path
        );

        abort_unless(
            $object !== null,
            422,
            'Uploaded media was not found.'
        );

        /*
         * 4. Verify uploaded size.
         */
        if ((int) $object['ContentLength'] !== $media->size) {
            abort(
                422,
                'Uploaded file size does not match.'
            );
        }

        /*
         * 5. Checksum verification.
         */
        $eTag = isset($object['ETag']) ? trim($object['ETag'], '"') : null;

        if ($checksum !== null) {
            $objectChecksum = $object['ContentMD5'] ?? $eTag;

            if ($objectChecksum !== null && strtolower($checksum) !== strtolower($objectChecksum)) {
                abort(
                    422,
                    'Uploaded file checksum does not match.'
                );
            }
        }

        /*
         * 6. Confirm the media using pessimistic locking (lockForUpdate) for race-safe execution.
         */
        $shouldDispatch = false;

        DB::transaction(function () use ($media, $checksum, $eTag, &$shouldDispatch) {
            $lockedMedia = Media::query()
                ->where('id', $media->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedMedia) {
                abort(404, 'Media not found.');
            }

            if ($lockedMedia->status === MediaStatus::Ready) {
                return;
            }

            if ($lockedMedia->status !== MediaStatus::Pending) {
                abort(
                    422,
                    'Media cannot be confirmed in its current state.'
                );
            }

            $lockedMedia->update([
                'status' => MediaStatus::Confirmed,
                'checksum' => $checksum ?? $eTag ?? $lockedMedia->checksum,
                'confirmed_at' => now(),
            ]);

            $shouldDispatch = true;
        });

        /*
         * 7. Process asynchronously if state transition succeeded.
         */
        if ($shouldDispatch) {
            ProcessMediaJob::dispatch($media->id);
        }

        return $media->fresh();
    }
}
