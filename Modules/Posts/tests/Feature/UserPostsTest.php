<?php

namespace Modules\Posts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Tests\TestCase;

class UserPostsTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private User $follower;
    private User $nonFollower;
    private Post $publicPost;
    private Post $followersPost;
    private Post $privatePost;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::create([
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->follower = User::create([
            'name' => 'Follower User',
            'email' => 'follower@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->nonFollower = User::create([
            'name' => 'NonFollower User',
            'email' => 'nonfollower@example.com',
            'password' => bcrypt('password'),
        ]);

        // Follower follows Author
        Follow::create([
            'follower_id' => $this->follower->id,
            'following_id' => $this->author->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        // Create posts with different visibilities
        $this->publicPost = $this->author->posts()->create([
            'content' => 'Public Post',
            'visibility' => PostVisibility::PUBLIC->value,
            'created_at' => now()->subMinutes(3),
        ]);

        $this->followersPost = $this->author->posts()->create([
            'content' => 'Followers Only Post',
            'visibility' => PostVisibility::FOLLOWERS->value,
            'created_at' => now()->subMinutes(2),
        ]);

        $this->privatePost = $this->author->posts()->create([
            'content' => 'Private Post',
            'visibility' => PostVisibility::PRIVATE->value,
            'created_at' => now()->subMinute(),
        ]);
    }

    public function test_guest_can_view_only_public_posts_of_user(): void
    {
        $response = $this->getJson(route('api.users.posts', $this->author));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->publicPost->id, $data[0]['id']);
    }

    public function test_non_follower_can_view_only_public_posts(): void
    {
        Sanctum::actingAs($this->nonFollower);

        $response = $this->getJson(route('api.users.posts', $this->author));

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->publicPost->id, $data[0]['id']);
    }

    public function test_follower_can_view_public_and_followers_posts(): void
    {
        Sanctum::actingAs($this->follower);

        $response = $this->getJson(route('api.users.posts', $this->author));

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $ids = collect($data)->pluck('id')->all();
        $this->assertContains($this->publicPost->id, $ids);
        $this->assertContains($this->followersPost->id, $ids);
        $this->assertNotContains($this->privatePost->id, $ids);
    }

    public function test_author_can_view_all_their_own_posts_including_private(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->getJson(route('api.users.posts', $this->author));

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(3, $data);

        $ids = collect($data)->pluck('id')->all();
        $this->assertContains($this->publicPost->id, $ids);
        $this->assertContains($this->followersPost->id, $ids);
        $this->assertContains($this->privatePost->id, $ids);
    }
}
