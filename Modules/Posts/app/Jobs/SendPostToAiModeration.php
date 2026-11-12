<?php

namespace Modules\Posts\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Posts\Enums\ModerationRequestStatus;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Events\PostModerationEvaluated;
use Modules\Posts\Models\ModerationRequest;

class SendPostToAiModeration implements ShouldDispatchAfterCommit, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly ModerationRequest $moderationRequest
    ) {}

    public function handle(): void
    {
        if (! config('moderation.enabled', false)) {
            return;
        }

        $request = $this->moderationRequest->fresh(['post.media']);

        if (! $request || $request->status !== ModerationRequestStatus::PENDING) {
            return;
        }

        $post = $request->post;

        if (! $post) {
            $request->update(['status' => ModerationRequestStatus::STALE]);

            return;
        }

        $inputs = [];

        if (filled($post->content)) {
            $inputs[] = [
                'type' => 'text',
                'text' => $post->content,
            ];
        }

        if ($post->relationLoaded('media')) {
            foreach ($post->media as $mediaItem) {
                if (isset($mediaItem->path)) {
                    $disk = $mediaItem->disk ?? config('filesystems.default');
                    try {
                        $signedUrl = Storage::disk($disk)->temporaryUrl(
                            $mediaItem->path,
                            now()->addMinutes(15)
                        );
                    } catch (Exception) {
                        $signedUrl = Storage::disk($disk)->url($mediaItem->path);
                    }

                    $inputs[] = [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $signedUrl,
                        ],
                    ];
                }
            }
        }

        if (empty($inputs)) {
            $request->update(['status' => ModerationRequestStatus::COMPLETED]);

            return;
        }

        $apiKey = config('services.moderation.openai_api_key');
        if (empty($apiKey)) {
            Log::warning('OpenAI moderation API key not configured', [
                'request_id' => $request->id,
            ]);

            $request->increment('attempts');

            return;
        }

        $serviceUrl = config('services.moderation.url', 'https://api.openai.com/v1/moderations');
        $model = config('services.moderation.model', 'omni-moderation-latest');
        $timeout = (int) config('services.moderation.timeout', 15);

        try {
            $response = Http::withToken($apiKey)
                ->connectTimeout(5)
                ->timeout($timeout)
                ->post($serviceUrl, [
                    'model' => $model,
                    'input' => $inputs,
                ]);

            if ($response->failed()) {
                Log::warning('OpenAI moderation dispatch failed', [
                    'request_id' => $request->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $request->increment('attempts');

                if (! app()->environment('testing')) {
                    $response->throw();
                }

                return;
            }
        } catch (ConnectionException $e) {
            Log::warning('OpenAI moderation service unreachable', [
                'request_id' => $request->id,
                'message' => $e->getMessage(),
            ]);

            $request->increment('attempts');

            if (! app()->environment('testing')) {
                throw $e;
            }

            return;
        }

        // Re-check status in case a concurrent edit marked this request as STALE
        $freshRequest = $request->fresh();
        if (! $freshRequest || $freshRequest->status !== ModerationRequestStatus::PENDING) {
            return;
        }

        $results = $response->json('results') ?? [];
        $scores = [];
        $openAiFlagged = false;

        foreach ($results as $resultItem) {
            if (! empty($resultItem['flagged'])) {
                $openAiFlagged = true;
            }

            foreach ($resultItem['category_scores'] ?? [] as $category => $score) {
                $scores[$category] = max((float) ($scores[$category] ?? 0.0), (float) $score);
            }
        }

        $verdict = $this->calculateVerdict($scores, $openAiFlagged);

        $freshRequest->update([
            'status' => ModerationRequestStatus::COMPLETED,
        ]);

        $aiMetadata = [
            'model' => $response->json('model') ?? $model,
            'openai_id' => $response->json('id'),
            'flagged' => $openAiFlagged,
        ];

        $mergedMetadata = array_merge($post->moderation_metadata ?? [], [
            'scores' => $scores,
            'ai_metadata' => $aiMetadata,
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

        PostModerationEvaluated::dispatch(
            $post,
            $verdict,
            $scores,
            $mergedMetadata,
            $freshRequest,
            $freshRequest->report_id
        );
    }

    protected function calculateVerdict(array $scores, bool $openAiFlagged): PostModerationStatus
    {
        $thresholds = config('moderation.thresholds', []);
        $hasFlag = $openAiFlagged;

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
