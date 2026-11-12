<?php

namespace Modules\Posts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ReportReason;
use Modules\Interactions\Enums\ReportStatus;
use Modules\Interactions\Listeners\UpdateReportStatusOnModeration;
use Modules\Interactions\Models\PostReports;
use Modules\Posts\Enums\ModerationRequestStatus;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Events\PostModerationEvaluated;
use Modules\Posts\Jobs\SendPostToAiModeration;
use Modules\Posts\Models\ModerationRequest;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class PostModerationWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Post $post;

    private string $apiKey = 'test-openai-key-12345';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'moderation.enabled' => true,
            'services.moderation.openai_api_key' => $this->apiKey,
            'services.moderation.url' => 'https://api.openai.com/v1/moderations',
            'services.moderation.model' => 'omni-moderation-latest',
        ]);

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

    public function test_openai_moderation_approves_clean_post(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-1',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => false,
                        'categories' => [
                            'sexual' => false,
                            'hate' => false,
                            'harassment' => false,
                        ],
                        'category_scores' => [
                            'sexual' => 0.001,
                            'hate' => 0.002,
                            'harassment' => 0.003,
                        ],
                    ],
                ],
            ], 200),
        ]);

        Event::fake([PostModerationEvaluated::class]);

        $this->post->update(['moderation_status' => PostModerationStatus::PENDING]);

        $request = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'hash123',
            'status' => ModerationRequestStatus::PENDING,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        (new SendPostToAiModeration($request))->handle();

        Http::assertSent(function (Request $outbound) {
            return $outbound->hasHeader('Authorization', 'Bearer '.$this->apiKey)
                && $outbound['model'] === 'omni-moderation-latest'
                && $outbound['input'][0]['type'] === 'text'
                && $outbound['input'][0]['text'] === 'Initial safe post content';
        });

        $this->assertEquals(ModerationRequestStatus::COMPLETED, $request->fresh()->status);
        $this->assertEquals(PostModerationStatus::APPROVED, $this->post->fresh()->moderation_status);

        Event::assertDispatched(PostModerationEvaluated::class, function (PostModerationEvaluated $event) {
            return $event->post->id === $this->post->id
                && $event->verdict === PostModerationStatus::APPROVED;
        });
    }

    public function test_openai_moderation_flags_borderline_post(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-flag',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'category_scores' => [
                            'harassment' => 0.60, // Above flag threshold 0.55, below reject 0.80
                            'sexual' => 0.01,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->post->update(['moderation_status' => PostModerationStatus::PENDING]);

        $request = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'hash123',
            'status' => ModerationRequestStatus::PENDING,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        (new SendPostToAiModeration($request))->handle();

        $this->assertEquals(ModerationRequestStatus::COMPLETED, $request->fresh()->status);
        $this->assertEquals(PostModerationStatus::FLAGGED, $this->post->fresh()->moderation_status);
    }

    public function test_openai_moderation_rejects_violating_post(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-reject',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'category_scores' => [
                            'sexual' => 0.95, // Above reject threshold 0.65
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->post->update(['moderation_status' => PostModerationStatus::PENDING]);

        $request = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'hash123',
            'status' => ModerationRequestStatus::PENDING,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        (new SendPostToAiModeration($request))->handle();

        $this->assertEquals(ModerationRequestStatus::COMPLETED, $request->fresh()->status);
        $this->assertEquals(PostModerationStatus::REJECTED, $this->post->fresh()->moderation_status);
    }

    public function test_openai_moderation_ignores_stale_request_after_post_edit(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-stale',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'category_scores' => [
                            'sexual' => 0.99,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->post->update(['moderation_status' => PostModerationStatus::PENDING]);

        $staleRequest = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'old_hash',
            'status' => ModerationRequestStatus::STALE,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        (new SendPostToAiModeration($staleRequest))->handle();

        // Stale request must not change the post status
        $this->assertEquals(PostModerationStatus::PENDING, $this->post->fresh()->moderation_status);
        $this->assertEquals(ModerationRequestStatus::STALE, $staleRequest->fresh()->status);
    }

    public function test_human_moderator_decision_outranks_ai_verdict(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://api.openai.com/v1/moderations' => Http::response([
                'id' => 'modr-test-human',
                'model' => 'omni-moderation-latest',
                'results' => [
                    [
                        'flagged' => true,
                        'category_scores' => [
                            'sexual' => 0.99,
                            'hate' => 0.99,
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Human moderator explicitly approved post
        $this->post->update([
            'moderation_status' => PostModerationStatus::APPROVED,
            'moderated_by_type' => 'human',
            'is_flagged_manually' => false,
        ]);

        $request = ModerationRequest::create([
            'post_id' => $this->post->id,
            'content_hash' => 'hash123',
            'status' => ModerationRequestStatus::PENDING,
            'attempts' => 1,
            'expires_at' => now()->addMinutes(15),
        ]);

        (new SendPostToAiModeration($request))->handle();

        $freshPost = $this->post->fresh();
        // Human decision APPROVED must remain unchanged!
        $this->assertEquals(PostModerationStatus::APPROVED, $freshPost->moderation_status);
        $this->assertEquals('human', $freshPost->moderated_by_type);
        // Telemetry is saved
        $this->assertEquals(0.99, $freshPost->moderation_metadata['scores']['sexual']);
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

        $listener = new UpdateReportStatusOnModeration;

        // 1. Borderline AI score (0.35) -> Not high-confidence clean -> Must escalate to REVIEWING
        $eventBorderline = new PostModerationEvaluated(
            $this->post,
            PostModerationStatus::APPROVED,
            ['harassment' => 0.35, 'hate' => 0.25],
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
            ['harassment' => 0.02, 'hate' => 0.01],
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
        DB::table('moderation_requests')
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
        DB::table('moderation_requests')
            ->where('id', $stuckExhausted->id)
            ->update(['created_at' => now()->subMinutes(10)]);

        $this->artisan('moderation:resolve-stuck-requests')
            ->assertSuccessful();

        $this->assertEquals(2, $stuckRetry->fresh()->attempts);
        $this->assertEquals(ModerationRequestStatus::TIMED_OUT, $stuckExhausted->fresh()->status);
        $this->assertEquals(PostModerationStatus::FLAGGED, $this->post->fresh()->moderation_status);
    }
}
