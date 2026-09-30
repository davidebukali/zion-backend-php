<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Notifications\Listeners\SendFollowRequestAcceptedNotification;
use Modules\SocialGraph\Events\FollowRequestAccepted;
use Tests\TestCase;

class FollowRequestAcceptedNotificationTest extends TestCase
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

    public function test_accepting_follow_request_creates_notification_for_follower(): void
    {
        $listener = app(SendFollowRequestAcceptedNotification::class);
        // User B accepted follow request from User A
        $event = new FollowRequestAccepted($this->userA->id, $this->userB->id);

        $this->assertEquals('notifications', $listener->queue);

        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->userA->id, // recipient is follower (User A)
            'actor_id' => $this->userB->id, // actor is approver (User B)
            'type' => 'follow_request_accepted',
            'notifiable_type' => $this->userB->getMorphClass(),
            'notifiable_id' => $this->userB->id,
        ]);
    }

    public function test_accepting_own_follow_request_does_not_create_notification(): void
    {
        $listener = app(SendFollowRequestAcceptedNotification::class);
        $event = new FollowRequestAccepted($this->userA->id, $this->userA->id);

        $listener->handle($event);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->userA->id,
            'actor_id' => $this->userA->id,
            'type' => 'follow_request_accepted',
        ]);
    }
}
