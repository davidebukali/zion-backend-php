<?php

namespace Modules\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;

class CleanupOrphanedMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cleanup-orphaned
        {--pending-hours=12 : Timeout in hours for pending unconfirmed uploads}
        {--failed-hours=72 : Retention period in hours for failed media before cleanup}
        {--unattached-hours=12 : Grace period in hours for unattached ready/confirmed media}
        {--dry-run : Report candidate files and records without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired pending uploads, failed media, and unattached ready media records and their storage files.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pendingHours = (int) $this->option('pending-hours');
        $failedHours = (int) $this->option('failed-hours');
        $unattachedHours = (int) $this->option('unattached-hours');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Starting orphaned media cleanup...');
        if ($dryRun) {
            $this->warn('--- DRY RUN MODE ENABLED (No files or database records will be deleted) ---');
        }

        $this->comment("Configuration: Pending threshold = {$pendingHours}h, Failed threshold = {$failedHours}h, Unattached threshold = {$unattachedHours}h");

        $totalProcessed = 0;
        $totalDeleted = 0;
        $totalErrors = 0;

        // 1. Process Pending unconfirmed uploads
        $pendingThreshold = now()->subHours($pendingHours);
        $pendingMedia = Media::query()
            ->where('status', MediaStatus::Pending)
            ->where('created_at', '<=', $pendingThreshold)
            ->get();

        $this->info(sprintf('[Pending Media] Found %d candidate(s) (>= %d hours old).', $pendingMedia->count(), $pendingHours));
        foreach ($pendingMedia as $media) {
            $totalProcessed++;
            if ($this->processMediaItem($media, $dryRun)) {
                $totalDeleted++;
            } else {
                $totalErrors++;
            }
        }

        // 2. Process Failed media
        $failedThreshold = now()->subHours($failedHours);
        $failedMedia = Media::query()
            ->where('status', MediaStatus::Failed)
            ->where('updated_at', '<=', $failedThreshold)
            ->get();

        $this->info(sprintf('[Failed Media] Found %d candidate(s) (>= %d hours old).', $failedMedia->count(), $failedHours));
        foreach ($failedMedia as $media) {
            $totalProcessed++;
            if ($this->processMediaItem($media, $dryRun)) {
                $totalDeleted++;
            } else {
                $totalErrors++;
            }
        }

        // 3. Process Unattached Ready/Confirmed media
        $unattachedThreshold = now()->subHours($unattachedHours);
        $unattachedMedia = Media::query()
            ->whereIn('status', [MediaStatus::Ready, MediaStatus::Confirmed])
            ->where('created_at', '<=', $unattachedThreshold)
            ->whereNotIn('id', function ($query) {
                $query->select('media_id')->from('post_media');
            })
            ->whereNotIn('id', function ($query) {
                $query->select('media_id')->from('comment_media');
            })
            ->get();

        $this->info(sprintf('[Unattached Media] Found %d candidate(s) (>= %d hours old).', $unattachedMedia->count(), $unattachedHours));
        foreach ($unattachedMedia as $media) {
            $totalProcessed++;
            if ($this->processMediaItem($media, $dryRun)) {
                $totalDeleted++;
            } else {
                $totalErrors++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn("Cleanup dry-run complete. Total candidates evaluated: {$totalProcessed}");
        } else {
            $this->info("Cleanup completed. Total items processed: {$totalProcessed}, successfully deleted: {$totalDeleted}, errors: {$totalErrors}");
        }

        return Command::SUCCESS;
    }

    /**
     * Process an individual media item for cleanup.
     */
    protected function processMediaItem(Media $media, bool $dryRun): bool
    {
        if ($dryRun) {
            $this->line("  [DRY RUN] Would delete file: {$media->path} (Disk: {$media->disk}) | Record ID: {$media->id} | Status: {$media->status->value}");
            return true;
        }

        try {
            if (!empty($media->disk) && !empty($media->path)) {
                $disk = Storage::disk($media->disk);
                 if ($disk->exists($media->path)) {
                     $disk->delete($media->path);
                }
            }

            $media->delete();
            $this->info("  [DELETED] File: {$media->path} | Record ID: {$media->id} | Status: {$media->status->value}");
            return true;
        } catch (\Throwable $e) {
            $this->error("  [ERROR] Failed to delete Media ID {$media->id}: {$e->getMessage()}");
            return false;
        }
    }
}
