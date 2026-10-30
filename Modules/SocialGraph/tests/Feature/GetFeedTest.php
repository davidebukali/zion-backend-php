<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Models\FeedItem;
use Tests\TestCase;

class GetFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('r2');

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

    public function test_user_can_retrieve_feed_with_posts_and_attached_media(): void
    {
        $post = $this->author->posts()->create([
            'content' => 'Post with media attached',
            'visibility' => 'public',
        ]);

        $media = Media::create([
            'user_id' => $this->author->id,
            'disk' => 'r2',
            'path' => 'photos/test.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
            'width' => 1200,
            'height' => 800,
            'status' => MediaStatus::Ready,
        ]);

        $post->media()->attach($media->id, ['sort_order' => 0]);

        $feedItem = FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.feed.index'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Feed retrieved successfully.',
            ])
            ->assertJsonPath('data.0.id', $feedItem->id)
            ->assertJsonPath('data.0.type', 'post')
            ->assertJsonPath('data.0.actor', null)
            ->assertJsonPath('data.0.post.id', $post->id)
            ->assertJsonPath('data.0.post.content', 'Post with media attached')
            ->assertJsonPath('data.0.post.user.id', $this->author->id)
            ->assertJsonPath('data.0.post.user.name', 'Post Author')
            ->assertJsonPath('data.0.post.media.0.id', $media->id)
            ->assertJsonPath('data.0.post.media.0.mime_type', 'image/jpeg');
    }

    public function test_user_only_sees_their_own_feed_items(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $post1 = $this->author->posts()->create(['content' => 'Post for User 1', 'visibility' => 'public']);
        $post2 = $this->author->posts()->create(['content' => 'Post for Other User', 'visibility' => 'public']);

        FeedItem::create(['user_id' => $this->user->id, 'post_id' => $post1->id, 'published_at' => now()]);
        FeedItem::create(['user_id' => $otherUser->id, 'post_id' => $post2->id, 'published_at' => now()]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.feed.index'));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals($post1->id, $data[0]['post']['id']);
    }

    public function test_feed_is_ordered_by_published_at_descending(): void
    {
        $oldPost = $this->author->posts()->create(['content' => 'Old Post', 'visibility' => 'public']);
        $newPost = $this->author->posts()->create(['content' => 'New Post', 'visibility' => 'public']);

        FeedItem::create(['user_id' => $this->user->id, 'post_id' => $oldPost->id, 'published_at' => now()->subHours(2)]);
        FeedItem::create(['user_id' => $this->user->id, 'post_id' => $newPost->id, 'published_at' => now()]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.feed.index'));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
        $this->assertEquals($newPost->id, $data[0]['post']['id']);
        $this->assertEquals($oldPost->id, $data[1]['post']['id']);
    }

    public function test_feed_supports_cursor_pagination(): void
    {
        $post1 = $this->author->posts()->create(['content' => 'Post 1', 'visibility' => 'public']);
        $post2 = $this->author->posts()->create(['content' => 'Post 2', 'visibility' => 'public']);

        FeedItem::create(['user_id' => $this->user->id, 'post_id' => $post1->id, 'published_at' => now()->subMinute()]);
        FeedItem::create(['user_id' => $this->user->id, 'post_id' => $post2->id, 'published_at' => now()]);

        Sanctum::actingAs($this->user);

        // Fetch page 1 (per_page = 1)
        $response1 = $this->getJson(route('api.feed.index', ['per_page' => 1]));

        $response1->assertStatus(200);
        $this->assertCount(1, $response1->json('data'));
        $this->assertEquals($post2->id, $response1->json('data.0.post.id'));

        $nextCursor = $response1->json('meta.next_cursor');
        $this->assertNotNull($nextCursor);

        // Fetch page 2 using cursor
        $response2 = $this->getJson(route('api.feed.index', ['per_page' => 1, 'cursor' => $nextCursor]));

        $response2->assertStatus(200);
        $this->assertCount(1, $response2->json('data'));
        $this->assertEquals($post1->id, $response2->json('data.0.post.id'));
    }

    public function test_unauthenticated_user_cannot_access_feed(): void
    {
        $response = $this->getJson(route('api.feed.index'));

        $response->assertStatus(401);
    }

    public function test_feed_includes_is_liked_and_counts_for_viewer(): void
    {
        $likedPost = $this->author->posts()->create([
            'content' => 'Liked Feed Post',
            'visibility' => 'public',
        ]);
        $likedPost->forceFill([
            'likes_count' => 1,
            'comments_count' => 3,
        ])->save();
        $likedPost->likes()->create(['user_id' => $this->user->id]);

        $unlikedPost = $this->author->posts()->create([
            'content' => 'Unliked Feed Post',
            'visibility' => 'public',
        ]);

        FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $likedPost->id,
            'published_at' => now()->subMinute(),
        ]);

        FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $unlikedPost->id,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.feed.index'));

        $response->assertStatus(200);

        $items = collect($response->json('data'));

        $likedItem = $items->firstWhere('post.id', $likedPost->id);
        $this->assertNotNull($likedItem);
        $this->assertTrue($likedItem['post']['is_liked']);
        $this->assertEquals(1, $likedItem['post']['likes_count']);
        $this->assertEquals(3, $likedItem['post']['comments_count']);

        $unlikedItem = $items->firstWhere('post.id', $unlikedPost->id);
        $this->assertNotNull($unlikedItem);
        $this->assertFalse($unlikedItem['post']['is_liked']);
        $this->assertEquals(0, $unlikedItem['post']['likes_count']);
        $this->assertEquals(0, $unlikedItem['post']['comments_count']);
    }

    public function test_feed_includes_reposts_from_followed_users(): void
    {
        $friend = User::create([
            'name' => 'Friend User',
            'email' => 'friend@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $this->author->posts()->create([
            'content' => 'Post to be reposted',
            'visibility' => 'public',
        ]);
        $post->forceFill(['reposts_count' => 1])->save();
        $post->reposts()->create(['user_id' => $friend->id]);

        FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'type' => 'repost',
            'actor_id' => $friend->id,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.feed.index'));

        $response->assertStatus(200);
        $item = $response->json('data.0');

        $this->assertEquals('repost', $item['type']);
        $this->assertEquals($friend->id, $item['actor']['id']);
        $this->assertEquals('Friend User', $item['actor']['name']);
        $this->assertEquals($post->id, $item['post']['id']);
        $this->assertEquals(1, $item['post']['reposts_count']);
        $this->assertFalse($item['post']['is_reposted']);
    }

    public function test_feed_includes_likes_and_comments_from_followed_users(): void
    {
        $friend = User::create([
            'name' => 'Liking Friend',
            'email' => 'liker_friend@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $this->author->posts()->create([
            'content' => 'Post liked by friend',
            'visibility' => 'public',
        ]);

        FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'type' => 'like',
            'actor_id' => $friend->id,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.feed.index'));

        $response->assertStatus(200);
        $item = $response->json('data.0');

        $this->assertEquals('like', $item['type']);
        $this->assertEquals($friend->id, $item['actor']['id']);
        $this->assertEquals('Liking Friend', $item['actor']['name']);
        $this->assertEquals($post->id, $item['post']['id']);
    }

    public function test_unrepost_and_unlike_remove_interaction_from_feed(): void
    {
        $friend = User::create([
            'name' => 'Active Friend',
            'email' => 'active_friend@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $this->author->posts()->create([
            'content' => 'Active Post',
            'visibility' => 'public',
        ]);

        FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'type' => 'repost',
            'actor_id' => $friend->id,
            'published_at' => now(),
        ]);

        $this->assertDatabaseHas('feed_items', [
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'type' => 'repost',
            'actor_id' => $friend->id,
        ]);

        event(new \Modules\Interactions\Events\PostUnreposted($post, $friend));

        $this->assertDatabaseMissing('feed_items', [
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'type' => 'repost',
            'actor_id' => $friend->id,
        ]);
    }

    public function test_feed_distinguishes_original_posts_and_reposted_posts_per_contract(): void
    {
        $friend = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $this->author->posts()->create([
            'content' => 'Check out my new photos!',
            'visibility' => 'public',
        ]);
        $post->forceFill([
            'likes_count' => 12,
            'comments_count' => 4,
            'reposts_count' => 1,
        ])->save();
        $post->likes()->create(['user_id' => $this->user->id]);
        $post->reposts()->create(['user_id' => $this->user->id]);

        // Original post entry
        FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'type' => 'post',
            'actor_id' => $this->author->id,
            'published_at' => now()->subHour(),
        ]);

        // Repost entry from friend
        FeedItem::create([
            'user_id' => $this->user->id,
            'post_id' => $post->id,
            'type' => 'repost',
            'actor_id' => $friend->id,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.feed.index'));

        $response->assertStatus(200);

        // Reposted entry (item 0)
        $repostEntry = $response->json('data.0');
        $this->assertEquals('repost', $repostEntry['type']);
        $this->assertNotNull($repostEntry['actor']);
        $this->assertEquals($friend->id, $repostEntry['actor']['id']);
        $this->assertEquals('Jane Doe', $repostEntry['actor']['name']);
        $this->assertEquals($post->id, $repostEntry['post']['id']);
        $this->assertEquals(1, $repostEntry['post']['reposts_count']);
        $this->assertTrue($repostEntry['post']['is_reposted']);

        // Original post entry (item 1)
        $postEntry = $response->json('data.1');
        $this->assertEquals('post', $postEntry['type']);
        $this->assertNull($postEntry['actor']);
        $this->assertEquals($post->id, $postEntry['post']['id']);
        $this->assertEquals(1, $postEntry['post']['reposts_count']);
        $this->assertTrue($postEntry['post']['is_reposted']);
    }
}
