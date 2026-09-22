<?php

namespace Modules\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Modules\Media\Services\R2StorageService;
use Throwable;

class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $mediaId
    ) {}

    public function handle(R2StorageService $storage): void
    {
        $media = Media::find($this->mediaId);

        if (! $media) {
            Log::error("ProcessMediaJob failed: Media ID {$this->mediaId} not found.");
            return;
        }

        // Only process media in Confirmed or Processing status
        if ($media->status !== MediaStatus::Confirmed && $media->status !== MediaStatus::Processing) {
            return;
        }

        // Mark status as Processing
        $media->update(['status' => MediaStatus::Processing]);

        try {
            // Retrieve original object content from R2
            $originalContent = $storage->get($media->path);

            if (! $originalContent) {
                throw new \RuntimeException("Original file not found at path: {$media->path}");
            }

            // Initialize Intervention ImageManager (GD driver)
            $manager = ImageManager::gd();
            $image = $manager->read($originalContent);

            $width = $image->width();
            $height = $image->height();

            // Prepare paths for derivatives
            $pathInfo = pathinfo($media->path);
            $dir = ($pathInfo['dirname'] === '.' || $pathInfo['dirname'] === '') ? '' : $pathInfo['dirname'] . '/';
            $filename = $pathInfo['filename'];

            $thumbPath = "{$dir}variants/{$filename}_thumb.webp";
            $mediumPath = "{$dir}variants/{$filename}_medium.webp";

            // Generate 300x300 thumbnail
            $thumbImage = clone $image;
            $thumbImage->cover(300, 300);
            $thumbWebp = (string) $thumbImage->toWebp(80);

            // Generate medium derivative (max 1080px dimension)
            $mediumImage = clone $image;
            $mediumImage->scaleDown(width: 1080, height: 1080);
            $mediumWebp = (string) $mediumImage->toWebp(85);

            // Upload variants to R2
            $storage->put($thumbPath, $thumbWebp, 'image/webp');
            $storage->put($mediumPath, $mediumWebp, 'image/webp');

            // Merge variants metadata
            $metadata = $media->metadata ?? [];
            $metadata['variants'] = [
                'thumbnail' => $thumbPath,
                'medium' => $mediumPath,
                'original' => $media->path,
            ];

            // Update Media model to Ready
            $media->update([
                'width' => $width,
                'height' => $height,
                'status' => MediaStatus::Ready,
                'metadata' => $metadata,
            ]);
        } catch (Throwable $e) {
            Log::error("ProcessMediaJob error for Media {$this->mediaId}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            $media->update(['status' => MediaStatus::Failed]);

            throw $e;
        }
    }
}