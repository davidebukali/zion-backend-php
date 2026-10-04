<?php

namespace Modules\Posts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Posts\Enums\ModerationRequestStatus;
use Modules\Posts\Jobs\ProcessModerationWebhookJob;
use Modules\Posts\Models\ModerationRequest;

class PostModerationWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'string'],
            'scores' => ['required', 'array'],
            'metadata' => ['sometimes', 'array'],
        ]);

        $moderationRequest = ModerationRequest::find($validated['request_id']);

        if (! $moderationRequest) {
            return response()->json(['error' => 'Moderation request not found'], 404);
        }

        // Idempotency: duplicate deliveries return 200 without reprocessing
        if ($moderationRequest->status === ModerationRequestStatus::COMPLETED) {
            return response()->json(['message' => 'Already processed'], 200);
        }

        // Stale edit: ignored without mutation
        if ($moderationRequest->status === ModerationRequestStatus::STALE) {
            return response()->json(['message' => 'Ignored stale request'], 200);
        }

        // Expired requests
        if ($moderationRequest->isExpired()) {
            $moderationRequest->update(['status' => ModerationRequestStatus::TIMED_OUT]);
            return response()->json(['error' => 'Moderation request expired'], 422);
        }

        // Atomic transition to completed
        DB::transaction(function () use ($moderationRequest) {
            $moderationRequest->update([
                'status' => ModerationRequestStatus::COMPLETED,
            ]);
        });

        // Offload processing to queue worker and respond with fast 202
        ProcessModerationWebhookJob::dispatch(
            $moderationRequest,
            $validated['scores'],
            $validated['metadata'] ?? []
        );

        return response()->json(['message' => 'Accepted for processing'], 202);
    }
}
