<?php

namespace Modules\Posts\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Events\PostModerationEvaluated;
use Modules\Posts\Models\ModerationRequest;

class ProcessModerationWebhookJob implements ShouldQueue, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ModerationRequest $moderationRequest,
        public readonly array $scores,
        public readonly array $metadata = []
    ) {}

    public function handle(): void
    {
        $request = $this->moderationRequest->fresh(['post']);
        $post = $request->post;

        if (! $post) {
            return;
        }

        // Determine verdict from category thresholds
        $verdict = $this->calculateVerdict($this->scores);

        $mergedMetadata = array_merge($post->moderation_metadata ?? [], [
            'scores' => $this->scores,
            'ai_metadata' => $this->metadata,
            'evaluated_at' => now()->toIso8601String(),
        ]);

        // Human decisions outrank AI verdicts
        if ($post->moderated_by_type === 'human' || $post->is_flagged_manually) {
            $post->updateQuietly([
                'moderation_metadata' => $mergedMetadata,
            ]);
        } else {
            $post->update([
                'moderation_status' => $verdict,
                'moderated_at' => now(),
                'moderated_by_type' => 'ai',
                'moderation_metadata' => $mergedMetadata,
            ]);
        }

        // Fire PostModerationEvaluated event for downstream handlers (e.g. reports, notifications)
        PostModerationEvaluated::dispatch(
            $post,
            $verdict,
            $this->scores,
            $mergedMetadata,
            $request,
            $request->report_id
        );
    }

    protected function calculateVerdict(array $scores): PostModerationStatus
    {
        $thresholds = config('moderation.thresholds', []);

        $hasFlag = false;

        foreach ($thresholds as $category => $limits) {
            $score = (float) ($scores[$category] ?? 0.0);

            if (isset($limits['reject']) && $score >= (float) $limits['reject']) {
                return PostModerationStatus::REJECTED;
            }

            if (isset($limits['flag']) && $score >= (float) $limits['flag']) {
                $hasFlag = true;
            }
        }

        return $hasFlag ? PostModerationStatus::FLAGGED : PostModerationStatus::APPROVED;
    }
}
