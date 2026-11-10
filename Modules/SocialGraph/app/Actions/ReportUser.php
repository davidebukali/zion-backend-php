<?php

namespace Modules\SocialGraph\Actions;

use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\UserReportStatus;
use Modules\SocialGraph\Events\UserReported;
use Modules\SocialGraph\Models\UserReport;

class ReportUser
{
    /**
     * Report another user with reason and optional description.
     *
     * @param  array{reason: string, description?: ?string}  $data
     */
    public function __invoke(
        User $reporter,
        User $reportedUser,
        array $data
    ): UserReport {
        if ($reporter->id === $reportedUser->id) {
            abort(422, 'You cannot report yourself.');
        }

        $existingReport = UserReport::query()
            ->where('reporter_id', $reporter->id)
            ->where('reported_user_id', $reportedUser->id)
            ->whereIn('status', [UserReportStatus::Pending->value, UserReportStatus::Reviewing->value])
            ->first();

        if ($existingReport) {
            $existingReport->update([
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
            ]);

            UserReported::dispatch($existingReport);

            return $existingReport;
        }

        $report = UserReport::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reportedUser->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => UserReportStatus::Pending,
        ]);

        UserReported::dispatch($report);

        return $report;
    }
}

