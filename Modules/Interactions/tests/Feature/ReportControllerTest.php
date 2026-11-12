<?php

namespace Modules\Interactions\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ReportReason;
use Modules\Interactions\Events\PostReported;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $user;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->author->posts()->create([
            'content' => 'Test Post Content',
            'visibility' => 'public',
        ]);
    }

    public function test_can_report_post(): void
    {
        Event::fake([PostReported::class]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.report.store', ['post' => $this->post->id]), [
            'reason' => ReportReason::SPAM->value,
            'description' => 'This is spam content.',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Post reported successfully',
        ]);

        $this->assertDatabaseHas('post_reports', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'reason' => ReportReason::SPAM->value,
            'description' => 'This is spam content.',
            'status' => 'pending',
        ]);

        Event::assertDispatched(PostReported::class, function ($event) {
            return $event->post->id === $this->post->id
                && $event->user->id === $this->user->id
                && $event->reason === ReportReason::SPAM->value;
        });
    }

    public function test_cannot_report_own_post(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson(route('api.report.store', ['post' => $this->post->id]), [
            'reason' => ReportReason::SPAM->value,
            'description' => 'Self reporting attempt.',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_report_post_with_invalid_reason(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.report.store', ['post' => $this->post->id]), [
            'reason' => 'invalid_reason_string',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    public function test_unauthenticated_user_cannot_report_post(): void
    {
        $response = $this->postJson(route('api.report.store', ['post' => $this->post->id]), [
            'reason' => ReportReason::SPAM->value,
        ]);

        $response->assertStatus(401);
    }
}
