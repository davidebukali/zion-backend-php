<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ReportStatus;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\MessageReport;

class ReportConversation
{
    public function __invoke(User $reporter, Conversation $conversation, array $data): MessageReport
    {
        if (Gate::forUser($reporter)->denies('view', $conversation)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        $report = MessageReport::firstOrCreate(
            [
                'reporter_id' => $reporter->id,
                'conversation_id' => $conversation->id,
                'message_id' => null,
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
