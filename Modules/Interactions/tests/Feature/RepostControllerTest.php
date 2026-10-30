<?php

namespace Modules\Interactions\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Interactions\Events\PostReposted;
use Modules\Interactions\Events\PostUnreposted;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class RepostControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Repost Tester',
            'email' => 'repost_tester@example.com',
            'password' => bcrypt('password'),
        ]);

        $author = User::create([
            'name' => 'Original Author',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $author->posts()->create([
            'content' => 'Original post for repost testing',
            'visibility' => 'public',
        ]);
    }

    public function test_user_can_repost_a_post(): void
    {
        Event::fake([PostReposted::class]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.post.repost', ['post' => $this->post->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post reposted successfully.',
                'data' => [
                    'reposted' => true,
                    'reposts_count' => 1,
                ],
            ]);

        $this->assertDatabaseHas('reposts', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(1, $this->post->refresh()->reposts_count);

        Event::assertDispatched(PostReposted::class, function ($event) {
            return $event->post->id === $this->post->id
                && $event->user->id === $this->user->id;
        });
    }

    public function test_user_cannot_duplicate_repost(): void
    {
        Event::fake([PostReposted::class]);

        Sanctum::actingAs($this->user);

        $this->postJson(route('api.post.repost', ['post' => $this->post->id]))->assertStatus(200);
        $this->postJson(route('api.post.repost', ['post' => $this->post->id]))->assertStatus(200);

        $this->assertDatabaseCount('reposts', 1);
        $this->assertEquals(1, $this->post->refresh()->reposts_count);
        Event::assertDispatchedTimes(PostReposted::class, 1);
    }

    public function test_user_can_undo_repost(): void
    {
        Event::fake([PostUnreposted::class]);

        Sanctum::actingAs($this->user);

        // Repost first
        $this->postJson(route('api.post.repost', ['post' => $this->post->id]))->assertStatus(200);
        $this->assertEquals(1, $this->post->refresh()->reposts_count);

        // Undo repost
        $response = $this->deleteJson(route('api.post.unrepost', ['post' => $this->post->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Repost removed successfully.',
                'data' => [
                    'reposted' => false,
                    'reposts_count' => 0,
                ],
            ]);

        $this->assertDatabaseMissing('reposts', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(0, $this->post->refresh()->reposts_count);

        Event::assertDispatched(PostUnreposted::class, function ($event) {
            return $event->post->id === $this->post->id
                && $event->user->id === $this->user->id;
        });
    }

    public function test_unauthenticated_user_cannot_repost_or_unrepost(): void
    {
        $this->postJson(route('api.post.repost', ['post' => $this->post->id]))->assertStatus(401);
        $this->deleteJson(route('api.post.unrepost', ['post' => $this->post->id]))->assertStatus(401);
    }
}

