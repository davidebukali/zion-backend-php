<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ReportStatus;
use Modules\Messages\Models\Message;
use Modules\Messages\Models\MessageReport;

class ReportMessage
{
    public function __invoke(User $reporter, Message $message, array $data): MessageReport
    {
        if (Gate::forUser($reporter)->denies('view', $message)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        $report = MessageReport::firstOrCreate(
            [
                'reporter_id' => $reporter->id,
                'conversation_id' => $message->conversation_id,
                'message_id' => $message->id,
            ],
            [
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
                'status' => ReportStatus::PENDING,
            ]
        );

        if (! $report->wasRecentlyCreated) {
            $report->update([
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
            ]);
        }

        return $report;
    }
}
