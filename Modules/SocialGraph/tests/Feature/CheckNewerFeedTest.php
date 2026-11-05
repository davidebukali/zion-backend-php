<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Models\FeedItem;
use Tests\TestCase;

class CheckNewerFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Feed Viewer',
            'email' => 'viewer@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->author = User::create([
            'name' => 'Post Author',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_unauthenticated_user_cannot_check_newer_feed(): void
    {
        $response = $this->getJson(route('api.feed.check-newer', ['after_id' => 1]));

        $response->assertStatus(401);
    }

    public function test_validates_after_id_parameter(): void
    {
        Sanctum::actingAs($this->user);

        // Missing after_id
        $response = $this->getJson(route('api.feed.check-newer'));
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['after_id']);

        // Invalid after_id
        $response = $this->getJson(route('api.feed.check-newer', ['after_id' => 'abc']));
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['after_id']);
    }

    public function test_returns_zero_when_no_newer_items(): void
    {
        Sanctum::actingAs($this->user);

        $post = $this->author->posts()->create([
            'content' => 'First post',
            'visibility' => 'public',
        ]);

        $feedItem = FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'type' => 'post',
            'actor_id' => $this->author->id,
            'published_at' => now(),
        ]);

        $response = $this->getJson(route('api.feed.check-newer', ['after_id' => $feedItem->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Feed check completed.',
                'data' => [
                    'new_posts_count' => 0,
                ],
            ]);
    }

    public function test_returns_correct_count_for_newer_items(): void
    {
        Sanctum::actingAs($this->user);

        $post1 = $this->author->posts()->create(['content' => 'Post 1', 'visibility' => 'public']);
        $post2 = $this->author->posts()->create(['content' => 'Post 2', 'visibility' => 'public']);
        $post3 = $this->author->posts()->create(['content' => 'Post 3', 'visibility' => 'public']);

        $item1 = FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post1->id,
            'type' => 'post',
            'actor_id' => $this->author->id,
            'published_at' => now()->subMinutes(10),
        ]);

        FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post2->id,
            'type' => 'post',
            'actor_id' => $this->author->id,
            'published_at' => now()->subMinutes(5),
        ]);

        FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post3->id,
            'type' => 'post',
            'actor_id' => $this->author->id,
            'published_at' => now(),
        ]);

        $response = $this->getJson(route('api.feed.check-newer', ['after_id' => $item1->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'new_posts_count' => 2,
                ],
            ]);
    }

    public function test_only_counts_authenticated_users_feed_items(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $post1 = $this->author->posts()->create(['content' => 'Post 1', 'visibility' => 'public']);
        $post2 = $this->author->posts()->create(['content' => 'Post 2', 'visibility' => 'public']);

        $item1 = FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post1->id,
            'type' => 'post',
            'actor_id' => $this->author->id,
            'published_at' => now(),
        ]);

        // Item for another user with higher ID
        FeedItem::create([
            'user_id' => $otherUser->id,
            'post_id' => $post2->id,
            'type' => 'post',
            'actor_id' => $this->author->id,
            'published_at' => now(),
        ]);

        $response = $this->getJson(route('api.feed.check-newer', ['after_id' => $item1->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'new_posts_count' => 0,
                ],
            ]);
    }
}
