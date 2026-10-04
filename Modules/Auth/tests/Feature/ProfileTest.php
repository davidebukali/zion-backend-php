<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;
use Modules\Media\Models\Media;
use Tests\TestCase;

class ProfileTest extends TestCase
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
        $this->userA->profile()->create([
            'username' => 'usera',
            'display_name' => 'User A Display',
            'bio' => 'Original bio for A',
        ]);

        $this->userB = User::create([
            'name' => 'User B',
            'email' => 'user_b@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->userB->profile()->create([
            'username' => 'userb',
            'display_name' => 'User B Display',
            'bio' => 'Original bio for B',
        ]);
    }

    public function test_can_view_specific_user_profile(): void
    {
        $response = $this->getJson(route('api.users.profile', $this->userA));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user_id' => $this->userA->id,
                    'username' => 'usera',
                    'display_name' => 'User A Display',
                    'bio' => 'Original bio for A',
                ],
            ]);
    }

    public function test_authenticated_user_can_update_own_profile(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->putJson(route('api.profile.update'), [
            'username' => 'new_usera_handle',
            'display_name' => 'Updated Name',
            'bio' => 'My brand new bio description',
            'website' => 'https://example.com/usera',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user_id' => $this->userA->id,
                    'username' => 'new_usera_handle',
                    'display_name' => 'Updated Name',
                    'bio' => 'My brand new bio description',
                    'website' => 'https://example.com/usera',
                ],
            ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->userA->id,
            'username' => 'new_usera_handle',
            'display_name' => 'Updated Name',
            'bio' => 'My brand new bio description',
            'website' => 'https://example.com/usera',
        ]);
    }

    public function test_user_can_update_profile_avatar_and_cover_media(): void
    {
        $avatarMedia = Media::create([
            'user_id' => $this->userA->id,
            'disk' => 'r2',
            'path' => 'avatars/avatar1.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => 'ready',
        ]);

        $coverMedia = Media::create([
            'user_id' => $this->userA->id,
            'disk' => 'r2',
            'path' => 'covers/cover1.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'status' => 'ready',
        ]);

        Sanctum::actingAs($this->userA);

        $response = $this->patchJson(route('api.profile.update'), [
            'avatar_media_id' => $avatarMedia->id,
            'cover_media_id' => $coverMedia->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.avatar_media_id', $avatarMedia->id)
            ->assertJsonPath('data.cover_media_id', $coverMedia->id);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $this->userA->id,
            'avatar_media_id' => $avatarMedia->id,
            'cover_media_id' => $coverMedia->id,
        ]);
    }

    public function test_user_can_keep_own_username_when_updating(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->putJson(route('api.profile.update'), [
            'username' => 'usera',
            'display_name' => 'New Display Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.username', 'usera');
    }

    public function test_user_cannot_update_to_an_already_taken_username(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->putJson(route('api.profile.update'), [
            'username' => 'userb', // taken by User B
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->putJson(route('api.profile.update'), [
            'display_name' => 'Hacker Name',
        ]);

        $response->assertStatus(401);
    }
}
