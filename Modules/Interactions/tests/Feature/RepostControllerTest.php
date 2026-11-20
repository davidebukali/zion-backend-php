<?php

namespace Modules\Interactions\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Interactions\Events\PostReposted;
use Modules\Interactions\Events\PostUnreposted;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Models\UserBlock;
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

    public function test_can_list_reposted_posts(): void
    {
        $author = $this->post->user;
        $post2 = $author->posts()->create([
            'content' => 'Second post for repost listing',
            'visibility' => 'public',
        ]);

        $unrepostedPost = $author->posts()->create([
            'content' => 'Not reposted post',
            'visibility' => 'public',
        ]);

        $otherUser = User::create([
            'name' => 'Other Reposter',
            'email' => 'other_reposter@example.com',
            'password' => bcrypt('password'),
        ]);

        // Other user reposts the unrepostedPost
        $unrepostedPost->reposts()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($this->user);

        // Repost $this->post and $post2
        $this->postJson(route('api.post.repost', ['post' => $this->post->id]))->assertStatus(200);
        $this->postJson(route('api.post.repost', ['post' => $post2->id]))->assertStatus(200);

        $response = $this->getJson(route('api.repost.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'content',
                    'visibility',
                    'is_reposted',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
            ],
        ]);

        $response->assertJsonCount(2, 'data');

        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->all();
        $this->assertContains($this->post->id, $ids);
        $this->assertContains($post2->id, $ids);
        $this->assertNotContains($unrepostedPost->id, $ids);

        foreach ($data as $item) {
            $this->assertTrue($item['is_reposted']);
        }
    }

    public function test_authenticated_user_can_view_reposts_of_another_user(): void
    {
        $viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer_repost@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post->reposts()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson(route('api.repost.index', ['user_id' => $this->user->id]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->post->id, $data[0]['id']);
        $this->assertFalse($data[0]['is_reposted']);
    }

    public function test_viewer_cannot_view_reposts_of_private_account_if_not_following(): void
    {
        $this->user->profile()->create([
            'username' => 'private_repost_user',
            'is_private' => true,
        ]);

        $viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer_repost2@example.com',
            'password' => bcrypt('password'),
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson(route('api.repost.index', ['user_id' => $this->user->id]));

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This account is private.',
            ]);
    }

    public function test_viewer_can_view_reposts_of_private_account_if_accepted_follower(): void
    {
        $this->user->profile()->create([
            'username' => 'private_repost_user_ok',
            'is_private' => true,
        ]);

        $viewer = User::create([
            'name' => 'Follower Viewer',
            'email' => 'follower_repost_viewer@example.com',
            'password' => bcrypt('password'),
        ]);

        Follow::create([
            'follower_id' => $viewer->id,
            'following_id' => $this->user->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        $this->post->reposts()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson(route('api.repost.index', ['user_id' => $this->user->id]));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_blocked_user_cannot_view_reposts(): void
    {
        $blockedUser = User::create([
            'name' => 'Blocked Viewer',
            'email' => 'blocked_repost_viewer@example.com',
            'password' => bcrypt('password'),
        ]);

        UserBlock::create([
            'blocker_id' => $this->user->id,
            'blocked_id' => $blockedUser->id,
        ]);

        Sanctum::actingAs($blockedUser);

        $response = $this->getJson(route('api.repost.index', ['user_id' => $this->user->id]));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_repost_or_unrepost(): void
    {
        $this->postJson(route('api.post.repost', ['post' => $this->post->id]))->assertStatus(401);
        $this->deleteJson(route('api.post.unrepost', ['post' => $this->post->id]))->assertStatus(401);
        $this->getJson(route('api.repost.index'))->assertStatus(401);
    }
}
