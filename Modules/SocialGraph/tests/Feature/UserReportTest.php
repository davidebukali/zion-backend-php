<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Events\UserReported;
use Modules\SocialGraph\Models\UserReport;
use Tests\TestCase;

class UserReportTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::create([
            'name' => 'User A',
            'email' => 'user_a@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->userB = User::create([
            'name' => 'User B',
            'email' => 'user_b@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_user_can_fetch_report_reasons_options_list(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->getJson(route('api.users.report-reasons'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Report reasons retrieved successfully.',
            ])
            ->assertJsonCount(10, 'data');

        $reasons = collect($response->json('data'))->pluck('id')->all();

        $this->assertEquals([
            'impersonation',
            'harassment',
            'hate_speech',
            'explicit_content',
            'violence_or_harm',
            'scam_or_fraud',
            'spam',
            'inappropriate_profile',
            'disruptive_content',
            'other',
        ], $reasons);

        $response->assertJsonFragment([
            'id' => 'impersonation',
            'label' => 'Impersonation',
            'desc' => 'Pretending to be someone else, a leader, or an organization',
        ]);

        $response->assertJsonFragment([
            'id' => 'disruptive_content',
            'label' => 'Disruptive or Bad-Faith Conduct',
            'desc' => 'Content that violates our Christ-centered and family-friendly sanctuary',
        ]);
    }

    public function test_user_can_report_another_user_with_valid_reason(): void
    {
        Event::fake([UserReported::class]);
        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.users.report', $this->userB), [
            'reason' => 'impersonation',
            'description' => 'This account is pretending to be a verified ministry leader.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User reported successfully.',
                'data' => [
                    'reporter_id' => $this->userA->id,
                    'reported_user_id' => $this->userB->id,
                    'reason' => 'impersonation',
                    'reason_label' => 'Impersonation',
                    'description' => 'This account is pretending to be a verified ministry leader.',
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('user_reports', [
            'reporter_id' => $this->userA->id,
            'reported_user_id' => $this->userB->id,
            'reason' => 'impersonation',
            'status' => 'pending',
        ]);

        Event::assertDispatched(UserReported::class, function (UserReported $event) {
            return $event->userReport->reporter_id === $this->userA->id
                && $event->userReport->reported_user_id === $this->userB->id;
        });
    }

    public function test_user_cannot_report_themselves(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.users.report', $this->userA), [
            'reason' => 'spam',
            'description' => 'Self reporting attempt',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_report_with_invalid_reason(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.users.report', $this->userB), [
            'reason' => 'nonexistent_reason',
            'description' => 'Invalid reason test',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_submitting_duplicate_pending_report_updates_existing_record(): void
    {
        Sanctum::actingAs($this->userA);

        $this->postJson(route('api.users.report', $this->userB), [
            'reason' => 'harassment',
            'description' => 'First report.',
        ])->assertStatus(201);

        $this->assertEquals(1, UserReport::count());

        $response = $this->postJson(route('api.users.report', $this->userB), [
            'reason' => 'hate_speech',
            'description' => 'Updated details with more severe violation.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.reason', 'hate_speech')
            ->assertJsonPath('data.reason_label', 'Hate Speech')
            ->assertJsonPath('data.description', 'Updated details with more severe violation.');

        $this->assertEquals(1, UserReport::count());

        $this->assertDatabaseHas('user_reports', [
            'reporter_id' => $this->userA->id,
            'reported_user_id' => $this->userB->id,
            'reason' => 'hate_speech',
            'description' => 'Updated details with more severe violation.',
            'status' => 'pending',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->postJson(route('api.users.report', $this->userB), [
            'reason' => 'spam',
        ]);

        $response->assertStatus(401);
    }
}
