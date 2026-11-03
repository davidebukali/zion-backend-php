<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Models\UserBlock;
use Tests\TestCase;

class UserBlockTest extends TestCase
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

    public function test_user_can_block_another_user(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.users.block', $this->userB));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User blocked successfully.',
                'data' => [
                    'blocker_id' => $this->userA->id,
                    'blocked_id' => $this->userB->id,
                ],
            ]);

        $this->assertDatabaseHas('user_blocks', [
            'blocker_id' => $this->userA->id,
            'blocked_id' => $this->userB->id,
        ]);

        $this->assertTrue($this->userA->hasBlocked($this->userB));
        $this->assertTrue($this->userB->isBlockedBy($this->userA));
        $this->assertTrue($this->userA->hasBlockRelationshipWith($this->userB));
        $this->assertTrue($this->userB->hasBlockRelationshipWith($this->userA));
    }

    public function test_user_cannot_block_themselves(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.users.block', $this->userA));

        $response->assertStatus(422);
    }

    public function test_blocking_is_idempotent(): void
    {
        Sanctum::actingAs($this->userA);

        $this->postJson(route('api.users.block', $this->userB))->assertStatus(201);
        $response = $this->postJson(route('api.users.block', $this->userB));

        $response->assertStatus(201);
        $this->assertEquals(1, UserBlock::count());
    }

    public function test_user_can_unblock_user(): void
    {
        UserBlock::create([
            'blocker_id' => $this->userA->id,
            'blocked_id' => $this->userB->id,
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->deleteJson(route('api.users.unblock', $this->userB));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User unblocked successfully.',
            ]);

        $this->assertDatabaseMissing('user_blocks', [
            'blocker_id' => $this->userA->id,
            'blocked_id' => $this->userB->id,
        ]);

        $this->assertFalse($this->userA->hasBlocked($this->userB));
    }

    public function test_user_can_list_blocked_users(): void
    {
        UserBlock::create([
            'blocker_id' => $this->userA->id,
            'blocked_id' => $this->userB->id,
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->getJson(route('api.blocks.index'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    [
                        'blocker_id' => $this->userA->id,
                        'blocked_id' => $this->userB->id,
                    ],
                ],
            ]);
    }

    public function test_blocking_keeps_existing_follows_intact(): void
    {
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        Sanctum::actingAs($this->userA);
        $this->postJson(route('api.users.block', $this->userB))->assertStatus(201);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::ACCEPTED->value,
        ]);
    }
}

