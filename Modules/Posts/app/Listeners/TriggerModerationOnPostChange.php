<?php

namespace Modules\Posts\Listeners;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Interactions\Events\PostReported;
use Modules\Posts\Enums\ModerationRequestStatus;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Events\PostCreated;
use Modules\Posts\Events\PostUpdated;
use Modules\Posts\Jobs\SendPostToAiModeration;
use Modules\Posts\Models\ModerationRequest;
use Modules\Posts\Models\Post;

class TriggerModerationOnPostChange implements ShouldDispatchAfterCommit
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $post = $event->post;
        $reportId = null;

        if ($event instanceof PostReported) {
            // Find existing open report if available
            $report = $post->reports()->where('user_id', $event->user->id)->first();
            $reportId = $report?->id;
        } elseif ($event instanceof PostUpdated) {
            // Only trigger re-moderation if content or media changed
            $changed = $event->changedAttributes;
            if (! empty($changed) && ! in_array('content', $changed) && ! in_array('media', $changed)) {
                return;
            }
        } elseif ($event instanceof PostCreated) {
            // Apply trust model: low-trust accounts start as pending
            $this->applyTrustPolicy($post);
        }

        // Generate content hash to identify unique text+media version
        $mediaIds = $post->media()->pluck('media.id')->sort()->implode(',');
        $contentHash = hash('sha256', ($post->content ?? '').'|'.$mediaIds);

        // Mark any prior pending requests for this post as stale
        ModerationRequest::where('post_id', $post->id)
            ->where('status', ModerationRequestStatus::PENDING)
            ->update(['status' => ModerationRequestStatus::STALE]);

        // Create new moderation request record
        $timeoutMinutes = config('moderation.request_timeout_minutes', 15);
        $moderationRequest = ModerationRequest::create([
            'post_id' => $post->id,
            'report_id' => $reportId,
            'content_hash' => $contentHash,
            'status' => ModerationRequestStatus::PENDING,
            'attempts' => 1,
            'expires_at' => now()->addMinutes($timeoutMinutes),
        ]);

        // Dispatch outbound queued job
        SendPostToAiModeration::dispatch($moderationRequest);
    }

    protected function applyTrustPolicy(Post $post): void
    {
        $author = $post->user;
        $minAgeDays = (int) config('moderation.trust.min_account_age_days', 3);

        $isLowTrust = ! $author || ! $author->created_at || $author->created_at->diffInDays(now()) < $minAgeDays;

        if ($isLowTrust) {
            $post->updateQuietly([
                'moderation_status' => PostModerationStatus::PENDING,
            ]);
        }
    }
}
