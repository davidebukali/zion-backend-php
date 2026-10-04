<?php

namespace Modules\Posts\Console;

use Illuminate\Console\Command;
use Modules\Posts\Enums\ModerationRequestStatus;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Jobs\SendPostToAiModeration;
use Modules\Posts\Models\ModerationRequest;

class ResolveStuckModerationRequests extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'moderation:resolve-stuck-requests';

    /**
     * The console command description.
     */
    protected $description = 'Finds stuck pending moderation requests and retries or escalates them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $maxAttempts = (int) config('moderation.max_retry_attempts', 3);
        $stuckThreshold = now()->subMinutes(5);

        $stuckRequests = ModerationRequest::where('status', ModerationRequestStatus::PENDING)
            ->where('created_at', '<=', $stuckThreshold)
            ->get();

        $this->info("Found {$stuckRequests->count()} stuck moderation request(s).");

        foreach ($stuckRequests as $request) {
            if ($request->attempts < $maxAttempts) {
                $request->increment('attempts');
                SendPostToAiModeration::dispatch($request);
                $this->line("Re-queued request: {$request->id} (Attempt {$request->attempts}/{$maxAttempts})");
            } else {
                $request->update(['status' => ModerationRequestStatus::TIMED_OUT]);

                $post = $request->post;
                if ($post && $post->moderation_status === PostModerationStatus::PENDING) {
                    $post->update([
                        'moderation_status' => PostModerationStatus::FLAGGED,
                        'moderated_at' => now(),
                        'moderated_by_type' => 'system',
                        'moderation_metadata' => array_merge($post->moderation_metadata ?? [], [
                            'escalated_reason' => 'AI moderation timed out after max attempts',
                        ]),
                    ]);
                    $this->warn("Escalated post {$post->id} to FLAGGED due to repeated timeout.");
                }
            }
        }

        return self::SUCCESS;
    }
}
