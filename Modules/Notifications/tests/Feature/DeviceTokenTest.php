<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Notifications\Models\DeviceToken;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Device Test User',
            'email' => 'device_user@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_user_can_register_device_token(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/devices/token', [
                'token' => 'ExponentPushToken[test-token-12345]',
                'platform' => 'ios',
                'device_name' => 'iPhone 15 Pro',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Device token registered successfully.',
            ]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->user->id,
            'token' => 'ExponentPushToken[test-token-12345]',
            'platform' => 'ios',
            'device_name' => 'iPhone 15 Pro',
        ]);

        $deviceToken = DeviceToken::where('token', 'ExponentPushToken[test-token-12345]')->first();
        $this->assertNotNull($deviceToken->last_active_at);
    }

    public function test_user_can_register_via_notifications_push_token_alias(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/notifications/push-token', [
                'token' => 'ExponentPushToken[alias-token-67890]',
                'platform' => 'android',
                'device_name' => 'Pixel 8',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Device token registered successfully.',
            ]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->user->id,
            'token' => 'ExponentPushToken[alias-token-67890]',
            'platform' => 'android',
            'device_name' => 'Pixel 8',
        ]);
    }

    public function test_reassigns_device_token_when_different_user_logs_in_on_same_device(): void
    {
        $otherUser = User::create([
            'name' => 'Second User',
            'email' => 'second_user@example.com',
            'password' => bcrypt('password'),
        ]);

        // User 1 registers device
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/devices/token', [
                'token' => 'ExponentPushToken[shared-physical-device]',
                'platform' => 'ios',
                'device_name' => 'Shared iPhone',
            ])->assertOk();

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->user->id,
            'token' => 'ExponentPushToken[shared-physical-device]',
        ]);

        // User 2 logs into the same device later
        $this->actingAs($otherUser, 'sanctum')
            ->postJson('/api/v1/devices/token', [
                'token' => 'ExponentPushToken[shared-physical-device]',
                'platform' => 'ios',
                'device_name' => 'Shared iPhone',
            ])->assertOk();

        // Exactly one record exists for the token, now assigned to User 2
        $this->assertEquals(
            1,
            DeviceToken::where('token', 'ExponentPushToken[shared-physical-device]')->count()
        );

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $otherUser->id,
            'token' => 'ExponentPushToken[shared-physical-device]',
        ]);

        $this->assertDatabaseMissing('device_tokens', [
            'user_id' => $this->user->id,
            'token' => 'ExponentPushToken[shared-physical-device]',
        ]);
    }

    public function test_user_can_remove_device_token_on_logout(): void
    {
        DeviceToken::create([
            'user_id' => $this->user->id,
            'token' => 'ExponentPushToken[logout-token]',
            'platform' => 'ios',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/devices/token', [
                'token' => 'ExponentPushToken[logout-token]',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Device token removed successfully.',
            ]);

        $this->assertDatabaseMissing('device_tokens', [
            'token' => 'ExponentPushToken[logout-token]',
        ]);
    }

    public function test_user_can_remove_device_token_via_alias_route(): void
    {
        DeviceToken::create([
            'user_id' => $this->user->id,
            'token' => 'ExponentPushToken[alias-logout-token]',
            'platform' => 'android',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/notifications/push-token', [
                'token' => 'ExponentPushToken[alias-logout-token]',
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Device token removed successfully.',
            ]);

        $this->assertDatabaseMissing('device_tokens', [
            'token' => 'ExponentPushToken[alias-logout-token]',
        ]);
    }

    public function test_cannot_remove_another_users_device_token(): void
    {
        $otherUser = User::create([
            'name' => 'Victim User',
            'email' => 'victim@example.com',
            'password' => bcrypt('password'),
        ]);

        DeviceToken::create([
            'user_id' => $otherUser->id,
            'token' => 'ExponentPushToken[victim-token]',
            'platform' => 'ios',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/devices/token', [
                'token' => 'ExponentPushToken[victim-token]',
            ]);

        $response->assertOk();

        // Token still exists for victim user
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $otherUser->id,
            'token' => 'ExponentPushToken[victim-token]',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/v1/devices/token', [
            'token' => 'ExponentPushToken[guest-token]',
        ])->assertUnauthorized();

        $this->deleteJson('/api/v1/devices/token', [
            'token' => 'ExponentPushToken[guest-token]',
        ])->assertUnauthorized();
    }
}

