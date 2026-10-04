<?php

namespace Modules\Interactions\Listeners;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Interactions\Enums\ReportStatus;
use Modules\Interactions\Models\PostReports;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Events\PostModerationEvaluated;

class UpdateReportStatusOnModeration implements ShouldDispatchAfterCommit
{
    /**
     * Handle the event.
     */
    public function handle(PostModerationEvaluated $event): void
    {
        $reportsQuery = PostReports::where('post_id', $event->post->id)
            ->whereIn('status', [ReportStatus::PENDING, ReportStatus::REVIEWING]);

        if ($event->reportId) {
            $reportsQuery->where('id', $event->reportId);
        }

        $reports = $reportsQuery->get();

        if ($reports->isEmpty()) {
            return;
        }

        $newReportStatus = $this->determineReportStatus($event);

        foreach ($reports as $report) {
            $report->update([
                'status' => $newReportStatus,
            ]);
        }
    }

    protected function determineReportStatus(PostModerationEvaluated $event): ReportStatus
    {
        if ($event->verdict === PostModerationStatus::REJECTED) {
            return ReportStatus::RESOLVED;
        }

        if ($event->verdict === PostModerationStatus::FLAGGED) {
            return ReportStatus::REVIEWING;
        }

        // For approved posts, verify max risk score across all categories
        $maxRiskScore = 0.0;
        foreach ($event->scores as $score) {
            $maxRiskScore = max($maxRiskScore, (float) $score);
        }

        $safeThreshold = (float) config('moderation.report_dismissal_max_score', 0.10);

        // Auto-dismiss ONLY if AI is with high confidence clean
        return $maxRiskScore <= $safeThreshold
            ? ReportStatus::DISMISSED
            : ReportStatus::REVIEWING; // Escalate to human queue
    }
}
