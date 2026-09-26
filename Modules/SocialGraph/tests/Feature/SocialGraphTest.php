<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Tests\TestCase;

class SocialGraphTest extends TestCase
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

    public function test_user_can_follow_another_user(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.users.follow', $this->userB));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User followed successfully.',
                'data' => [
                    'follower_id' => $this->userA->id,
                    'following_id' => $this->userB->id,
                    'status' => FollowStatus::ACCEPTED->value,
                ],
            ]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::ACCEPTED->value,
        ]);
    }

    public function test_user_cannot_follow_self(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.users.follow', $this->userA));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You cannot follow yourself.',
            ]);
    }

    public function test_user_can_unfollow_user(): void
    {
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->deleteJson(route('api.users.unfollow', $this->userB));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User unfollowed successfully.',
            ]);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
        ]);
    }

    public function test_can_get_followers_paginated(): void
    {
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->getJson(route('api.users.followers', $this->userB));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Followers retrieved successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'follower_id', 'following_id', 'status'],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);
    }

    public function test_can_get_following_paginated(): void
    {
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->getJson(route('api.users.following', $this->userA));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Following retrieved successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'follower_id', 'following_id', 'status'],
                ],
                'meta' => [
                    'current_page',
                    'total',
                ],
            ]);
    }

    public function test_user_can_accept_follow_request(): void
    {
        $follow = Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::PENDING,
        ]);

        Sanctum::actingAs($this->userB);

        $response = $this->postJson(route('api.follows.accept', $follow));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Follow request accepted successfully.',
                'data' => [
                    'id' => $follow->id,
                    'status' => FollowStatus::ACCEPTED->value,
                ],
            ]);

        $this->assertDatabaseHas('follows', [
            'id' => $follow->id,
            'status' => FollowStatus::ACCEPTED->value,
        ]);
    }

    public function test_user_cannot_accept_another_users_follow_request(): void
    {
        $follow = Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::PENDING,
        ]);

        // Acting as userA who is the follower, not the recipient (userB)
        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.follows.accept', $follow));

        $response->assertStatus(403);
    }

    public function test_user_can_reject_follow_request(): void
    {
        $follow = Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::PENDING,
        ]);

        Sanctum::actingAs($this->userB);

        $response = $this->postJson(route('api.follows.reject', $follow));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Follow request rejected successfully.',
                'data' => [
                    'id' => $follow->id,
                    'status' => FollowStatus::REJECTED->value,
                ],
            ]);

        $this->assertDatabaseHas('follows', [
            'id' => $follow->id,
            'status' => FollowStatus::REJECTED->value,
        ]);
    }
}
