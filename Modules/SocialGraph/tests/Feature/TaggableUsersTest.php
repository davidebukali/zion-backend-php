<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Models\UserBlock;
use Tests\TestCase;

class TaggableUsersTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;
    private User $followedUser;
    private User $followerUser;
    private User $pendingFollower;
    private User $blockedUser;
    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->viewer->id,
            'username' => 'viewer_user',
            'display_name' => 'The Viewer',
        ]);

        $this->followedUser = User::create([
            'name' => 'Jane Followed',
            'email' => 'followed@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->followedUser->id,
            'username' => 'jane_followed',
            'display_name' => 'Jane F',
        ]);
        Follow::create([
            'follower_id' => $this->viewer->id,
            'following_id' => $this->followedUser->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        $this->followerUser = User::create([
            'name' => 'Bob Follower',
            'email' => 'follower@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->followerUser->id,
            'username' => 'bob_follower',
            'display_name' => 'Bob F',
        ]);
        Follow::create([
            'follower_id' => $this->followerUser->id,
            'following_id' => $this->viewer->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        $this->pendingFollower = User::create([
            'name' => 'Pending Pete',
            'email' => 'pending@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->pendingFollower->id,
            'username' => 'pending_pete',
            'display_name' => 'Pete P',
        ]);
        Follow::create([
            'follower_id' => $this->pendingFollower->id,
            'following_id' => $this->viewer->id,
            'status' => FollowStatus::PENDING,
        ]);

        $this->blockedUser = User::create([
            'name' => 'Blocked Barry',
            'email' => 'blocked@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->blockedUser->id,
            'username' => 'blocked_barry',
            'display_name' => 'Barry B',
        ]);
        Follow::create([
            'follower_id' => $this->viewer->id,
            'following_id' => $this->blockedUser->id,
            'status' => FollowStatus::ACCEPTED,
        ]);
        UserBlock::create([
            'blocker_id' => $this->viewer->id,
            'blocked_id' => $this->blockedUser->id,
        ]);

        $this->stranger = User::create([
            'name' => 'Stranger Steve',
            'email' => 'stranger@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->stranger->id,
            'username' => 'stranger_steve',
            'display_name' => 'Steve S',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_taggable_users(): void
    {
        $response = $this->getJson(route('api.users.taggable'));
        $response->assertStatus(401);
    }

    public function test_taggable_users_returns_following_and_followers_including_pending_and_excludes_blocked(): void
    {
        Sanctum::actingAs($this->viewer);

        $response = $this->getJson(route('api.users.taggable'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $returnedIds = collect($response->json('data'))->pluck('id')->all();

        // Must include followed user, follower user, and pending follower
        $this->assertContains($this->followedUser->id, $returnedIds);
        $this->assertContains($this->followerUser->id, $returnedIds);
        $this->assertContains($this->pendingFollower->id, $returnedIds);

        // Must exclude blocked user, stranger, and viewer themselves
        $this->assertNotContains($this->blockedUser->id, $returnedIds);
        $this->assertNotContains($this->stranger->id, $returnedIds);
        $this->assertNotContains($this->viewer->id, $returnedIds);
    }

    public function test_taggable_users_excludes_user_who_blocked_viewer(): void
    {
        // Follower blocks viewer
        UserBlock::create([
            'blocker_id' => $this->followerUser->id,
            'blocked_id' => $this->viewer->id,
        ]);

        Sanctum::actingAs($this->viewer);

        $response = $this->getJson(route('api.users.taggable'));
        $response->assertStatus(200);

        $returnedIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($this->followerUser->id, $returnedIds);
    }

    public function test_taggable_users_autocomplete_filters_by_query(): void
    {
        Sanctum::actingAs($this->viewer);

        // Query matches 'Jane'
        $response = $this->getJson(route('api.users.taggable', ['q' => 'jane']));
        $response->assertStatus(200);

        $returnedIds = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->followedUser->id, $returnedIds);
        $this->assertNotContains($this->followerUser->id, $returnedIds);
        $this->assertNotContains($this->pendingFollower->id, $returnedIds);

        // Query matches username 'pending_pete'
        $responsePete = $this->getJson(route('api.users.taggable', ['q' => 'pete']));
        $responsePete->assertStatus(200);

        $returnedPeteIds = collect($responsePete->json('data'))->pluck('id')->all();
        $this->assertContains($this->pendingFollower->id, $returnedPeteIds);
        $this->assertNotContains($this->followedUser->id, $returnedPeteIds);
    }
}

