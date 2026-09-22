<?php

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Tests\TestCase;

class MediaShowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other_user@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_owner_can_view_their_media(): void
    {
        $media = Media::create([
            'user_id' => $this->owner->id,
            'disk' => 'r2',
            'path' => 'uploads/my-photo.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'status' => MediaStatus::Ready,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $media->id)
            ->assertJsonPath('data.user_id', $this->owner->id);
    }

    public function test_non_owner_cannot_view_media(): void
    {
        $media = Media::create([
            'user_id' => $this->owner->id,
            'disk' => 'r2',
            'path' => 'uploads/my-photo.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'status' => MediaStatus::Ready,
        ]);

        Sanctum::actingAs($this->otherUser);

        $response = $this->getJson("/api/v1/media/{$media->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $media = Media::create([
            'user_id' => $this->owner->id,
            'disk' => 'r2',
            'path' => 'uploads/my-photo.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'status' => MediaStatus::Ready,
        ]);

        $response = $this->getJson("/api/v1/media/{$media->id}");

        $response->assertStatus(401);
    }
}
