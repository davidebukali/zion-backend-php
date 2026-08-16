<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ShareType;
use Modules\Interactions\Events\PostShared;
use Modules\Interactions\Events\PostUnshared;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class PostShareControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->user->posts()->create([
            'content' => 'Test Post Content',
            'visibility' => 'public',
        ]);
    }

    public function test_can_share_post_internally(): void
    {
        Event::fake([PostShared::class]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.post.internal-share', ['post' => $this->post->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Post shared internally successfully',
        ]);

        $this->assertDatabaseHas('post_shares', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => ShareType::INTERNAL->value,
        ]);

        $this->assertEquals(1, $this->post->refresh()->shares_count);

        Event::assertDispatched(PostShared::class, function ($event) {
            return $event->post->id === $this->post->id
                && $event->user->id === $this->user->id
                && $event->type === ShareType::INTERNAL;
        });
    }

    public function test_can_share_post_externally(): void
    {
        Event::fake([PostShared::class]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.post.external-share', ['post' => $this->post->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Post shared externally successfully',
        ]);

        $this->assertDatabaseHas('post_shares', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => ShareType::EXTERNAL->value,
        ]);

        // External shares should NOT increment shares_count
        $this->assertEquals(0, $this->post->refresh()->shares_count);

        Event::assertDispatched(PostShared::class, function ($event) {
            return $event->post->id === $this->post->id
                && $event->user->id === $this->user->id
                && $event->type === ShareType::EXTERNAL;
        });
    }

    public function test_multiple_shares_by_same_user_creates_multiple_records(): void
    {
        Event::fake([PostShared::class]);

        Sanctum::actingAs($this->user);

        $this->postJson(route('api.post.internal-share', ['post' => $this->post->id]))->assertStatus(200);
        $this->postJson(route('api.post.internal-share', ['post' => $this->post->id]))->assertStatus(200);

        $this->assertDatabaseCount('post_shares', 2);
        $this->assertEquals(2, $this->post->refresh()->shares_count);
        Event::assertDispatchedTimes(PostShared::class, 2);
    }

    public function test_can_unshare_post(): void
    {
        Event::fake([PostUnshared::class]);

        Sanctum::actingAs($this->user);

        // Share post internally first
        $this->postJson(route('api.post.internal-share', ['post' => $this->post->id]))->assertStatus(200);
        $this->assertEquals(1, $this->post->refresh()->shares_count);

        // Now unshare post
        $response = $this->deleteJson(route('api.post.unshare', ['post' => $this->post->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Post unshared successfully',
        ]);

        $this->assertDatabaseMissing('post_shares', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'type' => ShareType::INTERNAL->value,
        ]);

        $this->assertEquals(0, $this->post->refresh()->shares_count);

        Event::assertDispatched(PostUnshared::class, function ($event) {
            return $event->post->id === $this->post->id
                && $event->user->id === $this->user->id
                && $event->type === ShareType::INTERNAL;
        });
    }

    public function test_unauthenticated_user_cannot_share_or_unshare_post(): void
    {
        $internalResponse = $this->postJson(route('api.post.internal-share', ['post' => $this->post->id]));
        $internalResponse->assertStatus(401);

        $externalResponse = $this->postJson(route('api.post.external-share', ['post' => $this->post->id]));
        $externalResponse->assertStatus(401);

        $unshareResponse = $this->deleteJson(route('api.post.unshare', ['post' => $this->post->id]));
        $unshareResponse->assertStatus(401);
    }
}
