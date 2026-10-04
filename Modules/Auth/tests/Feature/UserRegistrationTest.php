<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_name_and_creates_profile(): void
    {
        $response = $this->postJson(route('api.register'), [
            'name' => 'janedoe',
            'email' => 'jane@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'janedoe',
        ]);

        $user = User::where('email', 'jane@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'username' => 'janedoe',
            'display_name' => 'janedoe',
        ]);

        $this->assertNotNull($user->profile);
        $this->assertEquals('janedoe', $user->profile->username);
    }

    public function test_user_can_register_without_name_and_creates_profile(): void
    {
        $response = $this->postJson(route('api.register'), [
            'email' => 'anonymous@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'email' => 'anonymous@example.com',
            'name' => 'anonymous',
        ]);

        $user = User::where('email', 'anonymous@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'username' => null,
            'display_name' => null,
        ]);

        $this->assertNotNull($user->profile);
        $this->assertNull($user->profile->username);
    }

    public function test_multiple_users_can_register_without_name_without_unique_conflict(): void
    {
        $response1 = $this->postJson(route('api.register'), [
            'email' => 'user1@example.com',
            'password' => 'secret1234',
        ]);
        $response1->assertStatus(200);

        $response2 = $this->postJson(route('api.register'), [
            'email' => 'user2@example.com',
            'password' => 'secret1234',
        ]);
        $response2->assertStatus(200);

        $this->assertEquals(2, Profile::whereNull('username')->count());
    }

    public function test_user_cannot_register_with_duplicate_username(): void
    {
        // First user registers with 'janedoe'
        $this->postJson(route('api.register'), [
            'name' => 'janedoe',
            'email' => 'jane1@example.com',
            'password' => 'secret1234',
        ])->assertStatus(200);

        // Second user attempts to register with same username 'janedoe'
        $response = $this->postJson(route('api.register'), [
            'name' => 'janedoe',
            'email' => 'jane2@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        $this->postJson(route('api.register'), [
            'name' => 'user1',
            'email' => 'duplicate@example.com',
            'password' => 'secret1234',
        ])->assertStatus(200);

        $response = $this->postJson(route('api.register'), [
            'name' => 'user2',
            'email' => 'duplicate@example.com',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
