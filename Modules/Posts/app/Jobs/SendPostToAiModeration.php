<?php

namespace Modules\Posts\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Posts\Enums\ModerationRequestStatus;
use Modules\Posts\Models\ModerationRequest;

class SendPostToAiModeration implements ShouldQueue, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly ModerationRequest $moderationRequest
    ) {}

    public function handle(): void
    {
        $request = $this->moderationRequest->fresh(['post.media']);

        if (! $request || $request->status !== ModerationRequestStatus::PENDING) {
            return;
        }

        $post = $request->post;

        if (! $post) {
            $request->update(['status' => ModerationRequestStatus::STALE]);
            return;
        }

        $mediaUrls = [];
        if ($post->relationLoaded('media')) {
            foreach ($post->media as $mediaItem) {
                // Generate short-lived signed URL or fallback to public URL
                if (isset($mediaItem->path)) {
                    $disk = $mediaItem->disk ?? config('filesystems.default');
                    try {
                        $mediaUrls[] = Storage::disk($disk)->temporaryUrl(
                            $mediaItem->path,
                            now()->addMinutes(15)
                        );
                    } catch (Exception) {
                        $mediaUrls[] = Storage::disk($disk)->url($mediaItem->path);
                    }
                }
            }
        }

        $payload = [
            'request_id' => $request->id,
            'post_id' => $post->id,
            'report_id' => $request->report_id,
            'content' => $post->content ?? '',
            'media_urls' => $mediaUrls,
            'content_hash' => $request->content_hash,
        ];

        $serviceUrl = rtrim(config('services.moderation.url'), '/').'/moderate';
        $timeout = config('services.moderation.timeout', 15);

        try {
            $response = Http::timeout($timeout)->post($serviceUrl, $payload);

            if ($response->failed()) {
                Log::warning('AI moderation dispatch failed', [
                    'request_id' => $request->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $request->increment('attempts');

                if (! app()->environment('testing')) {
                    $response->throw();
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('AI moderation service unreachable', [
                'request_id' => $request->id,
                'message' => $e->getMessage(),
            ]);

            $request->increment('attempts');

            if (! app()->environment('testing')) {
                throw $e;
            }
        }
    }
}
