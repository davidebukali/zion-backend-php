<?php

namespace Modules\Posts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ReportReason;
use Modules\Interactions\Enums\ReportStatus;
use Modules\Interactions\Models\PostReports;
use Modules\Posts\Console\ResolveStuckModerationRequests;
use Modules\Posts\Enums\ModerationRequestStatus;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Events\PostModerationEvaluated;
use Modules\Posts\Jobs\ProcessModerationWebhookJob;
use Modules\Posts\Models\ModerationRequest;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class PostModerationWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Post $post;
    private string $secret = 'test-secret-at-least-32-chars-long-12345';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.moderation.webhook_secret' => $this->secret]);

        $this->user = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = Post::create([
            'user_id' => $this->user->id,
            'content' => 'Initial safe post content',
            'moderation_status' => PostModerationStatus::APPROVED,
        ]);
    }

    private function postWebhook(array $payload, ?int $timestamp = null, ?string $secret = null)
    {
        $body = json_encode($payload);
        $timestamp = $timestamp ?? time();
        $secret = $secret ?? $this->secret;
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        $server = [
            'HTTP_X_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];

        return $this->call(
            'POST',
            route('api.internal.moderation.webhook'),
            [],
            [],
            [],
            $server,
            $body
        );
    }

    public function test_webhook_rejects_missing_or_bad_hmac_signature(): void
    {
        $payload = ['request_id' => '01J9TEST', 'scores' => ['toxicity' => 0.1]];

        // Missing signature
        $this->postJson(route('api.internal.moderation.webhook'), $payload)
            ->assertStatus(401);

        // Invalid signature
        $this->postWebhook($payload, secret: 'wrong-secret')
            ->assertStatus(401);
    }

    public function test_webhook_rejects_expired_timestamp(): void
    {
        $payload = ['request_id' => '01J9TEST', 'scores' => ['toxicity' => 0.1]];
        $expiredTime = time() - 305; // >5 minutes ago

        $this->postWebhook($payload, timestamp: $expiredTime)
            ->assertStatus(401);
    }

    public function test_webhook_accepts_valid_payload_and_queues_processing(): void
    {
        Queue::fake();

        $request = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'hash123',
            'status' => ModerationRequestStatus::PENDING,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        $payload = [
            'request_id' => $request->id,
            'scores' => ['toxicity' => 0.05, 'nsfw' => 0.02],
            'metadata' => ['model' => 'v1.0'],
        ];

        $response = $this->postWebhook($payload);

        $response->assertStatus(202);

        $this->assertEquals(ModerationRequestStatus::COMPLETED, $request->fresh()->status);
        Queue::assertPushed(ProcessModerationWebhookJob::class);
    }

    public function test_webhook_is_idempotent_on_duplicate_deliveries(): void
    {
        Queue::fake();

        $request = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'hash123',
            'status' => ModerationRequestStatus::COMPLETED,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        $payload = [
            'request_id' => $request->id,
            'scores' => ['toxicity' => 0.05],
        ];

        $response = $this->postWebhook($payload);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Already processed']);
        Queue::assertNotPushed(ProcessModerationWebhookJob::class);
    }

    public function test_webhook_ignores_stale_request_after_post_edit(): void
    {
        Queue::fake();

        $staleRequest = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'old_hash',
            'status' => ModerationRequestStatus::STALE,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        $payload = [
            'request_id' => $staleRequest->id,
            'scores' => ['nsfw' => 0.95],
        ];

        $response = $this->postWebhook($payload);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Ignored stale request']);
        Queue::assertNotPushed(ProcessModerationWebhookJob::class);
    }

    public function test_human_moderator_decision_outranks_ai_verdict(): void
    {
        // Human moderator explicitly approved post
        $this->post->update([
            'moderation_status' => PostModerationStatus::APPROVED,
            'moderated_by_type' => 'human',
            'is_flagged_manually' => false,
        ]);

        $request = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'hash123',
            'status' => ModerationRequestStatus::COMPLETED,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        // AI callback reports extreme violation
        $job = new ProcessModerationWebhookJob($request, ['toxicity' => 0.99, 'nsfw' => 0.99]);
        $job->handle();

        $freshPost = $this->post->fresh();
        // Human decision APPROVED must remain unchanged!
        $this->assertEquals(PostModerationStatus::APPROVED, $freshPost->moderation_status);
        $this->assertEquals('human', $freshPost->moderated_by_type);
        // Telemetry is saved
        $this->assertEquals(0.99, $freshPost->moderation_metadata['scores']['toxicity']);
    }

    public function test_global_scope_hides_unapproved_post_except_author(): void
    {
        $unapprovedPost = Post::create([
            'user_id' => $this->user->id,
            'content' => 'Pending review post',
            'moderation_status' => PostModerationStatus::PENDING,
        ]);

        $otherUser = User::create([
            'name' => 'Stranger',
            'email' => 'stranger@example.com',
            'password' => bcrypt('password'),
        ]);

        // Unauthenticated guest cannot see unapproved post
        $this->assertNull(Post::find($unapprovedPost->id));

        // Other user cannot see unapproved post
        $this->actingAs($otherUser);
        $this->assertNull(Post::find($unapprovedPost->id));

        // Author can see their own pending post
        $this->actingAs($this->user);
        $this->assertNotNull(Post::find($unapprovedPost->id));
    }

    public function test_report_dismissal_requires_high_confidence(): void
    {
        $reporter = User::create([
            'name' => 'Reporter User',
            'email' => 'reporter@example.com',
            'password' => bcrypt('password'),
        ]);

        $report = PostReports::create([
            'post_id' => $this->post->id,
            'user_id' => $reporter->id,
            'reason' => ReportReason::HARASSMENT,
            'status' => ReportStatus::PENDING,
        ]);

        $listener = new \Modules\Interactions\Listeners\UpdateReportStatusOnModeration();

        // 1. Borderline AI score (0.35) -> Not high-confidence clean -> Must escalate to REVIEWING
        $eventBorderline = new PostModerationEvaluated(
            $this->post,
            PostModerationStatus::APPROVED,
            ['toxicity' => 0.35, 'harassment' => 0.25],
            [],
            null,
            $report->id
        );
        $listener->handle($eventBorderline);

        $this->assertEquals(ReportStatus::REVIEWING, $report->fresh()->status);

        // 2. High-confidence clean (all scores <= 0.10) -> Safely DISMISSED
        $eventClean = new PostModerationEvaluated(
            $this->post,
            PostModerationStatus::APPROVED,
            ['toxicity' => 0.02, 'harassment' => 0.01],
            [],
            null,
            $report->id
        );
        $listener->handle($eventClean);

        $this->assertEquals(ReportStatus::DISMISSED, $report->fresh()->status);
    }

    public function test_stuck_request_recovery_command(): void
    {
        Queue::fake();

        // Stuck request under retry limit
        $stuckRetry = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'hash1',
            'status' => ModerationRequestStatus::PENDING,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);
        \Illuminate\Support\Facades\DB::table('moderation_requests')
            ->where('id', $stuckRetry->id)
            ->update(['created_at' => now()->subMinutes(10)]);

        // Stuck request that reached max attempts
        $this->post->update(['moderation_status' => PostModerationStatus::PENDING]);
        $stuckExhausted = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'hash2',
            'status' => ModerationRequestStatus::PENDING,
            'attempts' => 3,
            'expires_at' => now()->addMinutes(15),
        ]);
        \Illuminate\Support\Facades\DB::table('moderation_requests')
            ->where('id', $stuckExhausted->id)
            ->update(['created_at' => now()->subMinutes(10)]);

        $this->artisan('moderation:resolve-stuck-requests')
            ->assertSuccessful();

        $this->assertEquals(2, $stuckRetry->fresh()->attempts);
        $this->assertEquals(ModerationRequestStatus::TIMED_OUT, $stuckExhausted->fresh()->status);
        $this->assertEquals(PostModerationStatus::FLAGGED, $this->post->fresh()->moderation_status);
    }
}
