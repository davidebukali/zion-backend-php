<?php

namespace Modules\Search\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private User $follower;
    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::create([
            'name' => 'Alice Laravel Expert',
            'email' => 'alice@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->author->profile()->create([
            'username' => 'alice_dev',
            'display_name' => 'Alice in Wonderland',
            'bio' => 'Fullstack Laravel architect',
        ]);

        $this->follower = User::create([
            'name' => 'Bob Builder',
            'email' => 'bob@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->follower->profile()->create([
            'username' => 'bob_builder',
            'display_name' => 'Bob B.',
        ]);

        $this->stranger = User::create([
            'name' => 'Charlie Chaplin',
            'email' => 'charlie@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->stranger->profile()->create([
            'username' => 'charlie_c',
            'display_name' => 'Charlie C.',
        ]);

        // Follower follows Author
        Follow::create([
            'follower_id' => $this->follower->id,
            'following_id' => $this->author->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        // Create posts with matching keywords
        $this->author->posts()->create([
            'content' => 'Exploring Laravel 13 new features and performance.',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);

        $this->author->posts()->create([
            'content' => 'Exclusive Laravel tips for my followers only.',
            'visibility' => PostVisibility::FOLLOWERS->value,
        ]);

        $this->author->posts()->create([
            'content' => 'My secret private Laravel draft.',
            'visibility' => PostVisibility::PRIVATE->value,
        ]);
    }

    public function test_unified_search_returns_both_matching_users_and_posts(): void
    {
        $response = $this->getJson(route('api.search', ['q' => 'Laravel']));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'users' => [
                        '*' => ['id', 'name', 'email', 'profile'],
                    ],
                    'posts' => [
                        '*' => ['id', 'content', 'visibility', 'created_at'],
                    ],
                ],
            ]);

        $users = $response->json('data.users');
        $posts = $response->json('data.posts');

        $this->assertNotEmpty($users);
        $this->assertNotEmpty($posts);
        $this->assertEquals('alice_dev', $users[0]['profile']['username']);
    }

    public function test_user_search_returns_paginated_users(): void
    {
        $response = $this->getJson(route('api.search', [
            'q' => 'alice',
            'type' => 'users',
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Users retrieved successfully')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'profile'],
                ],
                'meta' => [
                    'links',
                    'meta',
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('alice_dev', $data[0]['profile']['username']);
    }

    public function test_user_search_matches_username_display_name_and_bio(): void
    {
        // Search by bio keyword "architect"
        $responseBio = $this->getJson(route('api.search', ['q' => 'architect', 'type' => 'users']));
        $responseBio->assertStatus(200)
            ->assertJsonPath('data.0.profile.username', 'alice_dev');

        // Search by display name "Wonderland"
        $responseDisplay = $this->getJson(route('api.search', ['q' => 'Wonderland', 'type' => 'users']));
        $responseDisplay->assertStatus(200)
            ->assertJsonPath('data.0.profile.username', 'alice_dev');
    }

    public function test_post_search_returns_paginated_posts(): void
    {
        $response = $this->getJson(route('api.search', [
            'q' => 'features',
            'type' => 'posts',
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Posts retrieved successfully')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'content', 'visibility'],
                ],
                'meta' => [
                    'links',
                    'meta',
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Exploring Laravel 13', $data[0]['content']);
    }

    public function test_post_search_respects_visibility_rules_for_guest_and_stranger(): void
    {
        // Unauthenticated / Stranger search
        $response = $this->getJson(route('api.search', ['q' => 'Laravel', 'type' => 'posts']));

        $response->assertStatus(200);
        $data = $response->json('data');

        // Only public post should be returned (1 out of 3)
        $this->assertCount(1, $data);
        $this->assertEquals(PostVisibility::PUBLIC->value, $data[0]['visibility']);
    }

    public function test_post_search_respects_visibility_rules_for_follower(): void
    {
        Sanctum::actingAs($this->follower);

        $response = $this->getJson(route('api.search', ['q' => 'Laravel', 'type' => 'posts']));

        $response->assertStatus(200);
        $data = $response->json('data');

        // Follower sees public + followers posts (2 out of 3)
        $this->assertCount(2, $data);
        $visibilities = collect($data)->pluck('visibility')->all();
        $this->assertContains(PostVisibility::PUBLIC->value, $visibilities);
        $this->assertContains(PostVisibility::FOLLOWERS->value, $visibilities);
        $this->assertNotContains(PostVisibility::PRIVATE->value, $visibilities);
    }

    public function test_post_search_respects_visibility_rules_for_author(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->getJson(route('api.search', ['q' => 'Laravel', 'type' => 'posts']));

        $response->assertStatus(200);
        $data = $response->json('data');

        // Author sees all 3 posts (public, followers, private)
        $this->assertCount(3, $data);
    }

    public function test_search_requires_query_parameter(): void
    {
        $response = $this->getJson(route('api.search'));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_empty_search_returns_empty_results(): void
    {
        $response = $this->getJson(route('api.search', ['q' => 'NonExistentTerm12345']));

        $response->assertStatus(200)
            ->assertJsonPath('data.users', [])
            ->assertJsonPath('data.posts', []);
    }
}
