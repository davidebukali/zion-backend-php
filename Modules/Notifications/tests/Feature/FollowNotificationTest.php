<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Notifications\Listeners\RemoveFollowNotificationOnUnfollow;
use Modules\Notifications\Listeners\SendFollowNotification;
use Modules\Notifications\Listeners\SendFollowRequestedNotification;
use Modules\Notifications\Models\Notification;
use Modules\SocialGraph\Events\FollowRequested;
use Modules\SocialGraph\Events\UserFollowed;
use Modules\SocialGraph\Events\UserUnfollowed;
use Tests\TestCase;

class FollowNotificationTest extends TestCase
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

    public function test_following_user_creates_notification_for_followed_user(): void
    {
        $listener = app(SendFollowNotification::class);
        $event = new UserFollowed($this->userA->id, $this->userB->id);

        $this->assertEquals('notifications', $listener->queue);

        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->userB->id,
            'actor_id' => $this->userA->id,
            'type' => 'user_followed',
            'notifiable_type' => $this->userA->getMorphClass(),
            'notifiable_id' => $this->userA->id,
        ]);
    }

    public function test_following_self_does_not_create_notification(): void
    {
        $listener = app(SendFollowNotification::class);
        $event = new UserFollowed($this->userA->id, $this->userA->id);

        $listener->handle($event);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->userA->id,
            'actor_id' => $this->userA->id,
            'type' => 'user_followed',
        ]);
    }

    public function test_follow_requested_creates_notification_for_target_user(): void
    {
        $listener = app(SendFollowRequestedNotification::class);
        $event = new FollowRequested($this->userA->id, $this->userB->id, 123);

        $this->assertEquals('notifications', $listener->queue);

        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->userB->id,
            'actor_id' => $this->userA->id,
            'type' => 'follow_requested',
            'notifiable_type' => $this->userA->getMorphClass(),
            'notifiable_id' => $this->userA->id,
        ]);
    }

    public function test_following_user_multiple_times_does_not_create_duplicate_notifications(): void
    {
        $listener = app(SendFollowNotification::class);
        $event = new UserFollowed($this->userA->id, $this->userB->id);

        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame(
            1,
            Notification::where('user_id', $this->userB->id)
                ->where('type', 'user_followed')
                ->where('group_key', 'user_followed:'.$this->userA->id)
                ->count()
        );
    }

    public function test_unfollowing_user_removes_follow_notification(): void
    {
        $followListener = app(SendFollowNotification::class);
        $followEvent = new UserFollowed($this->userA->id, $this->userB->id);
        $followListener->handle($followEvent);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->userB->id,
            'group_key' => 'user_followed:'.$this->userA->id,
        ]);

        $unfollowListener = app(RemoveFollowNotificationOnUnfollow::class);
        $unfollowEvent = new UserUnfollowed($this->userA->id, $this->userB->id);
        $unfollowListener->handle($unfollowEvent);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->userB->id,
            'group_key' => 'user_followed:'.$this->userA->id,
        ]);
    }

    public function test_follow_requested_multiple_times_does_not_create_duplicates(): void
    {
        $listener = app(SendFollowRequestedNotification::class);
        $event = new FollowRequested($this->userA->id, $this->userB->id, 123);

        $listener->handle($event);
        $listener->handle($event);

        $this->assertSame(
            1,
            Notification::where('user_id', $this->userB->id)
                ->where('type', 'follow_requested')
                ->where('group_key', 'follow_requested:'.$this->userA->id)
                ->count()
        );
    }
}
