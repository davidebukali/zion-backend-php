<?php

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Services\R2StorageService;
use Tests\TestCase;

class ConfirmUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Media Test User',
            'email' => 'media_test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_can_confirm_media_upload_successfully(): void
    {
        Queue::fake();

        $media = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/test-image.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Pending,
        ]);

        $mockStorage = $this->mock(R2StorageService::class);
        $mockStorage->shouldReceive('head')
            ->once()
            ->with('uploads/test-image.jpg')
            ->andReturn([
                'ContentLength' => 1024,
                'ETag' => '"hash123"',
            ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/media/{$media->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'status' => MediaStatus::Confirmed->value,
        ]);

        Queue::assertPushed(ProcessMediaJob::class, function ($job) use ($media) {
            return $job->mediaId === $media->id;
        });
    }

    public function test_cannot_confirm_media_owned_by_another_user(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $media = Media::create([
            'user_id' => $otherUser->id,
            'disk' => 'r2',
            'path' => 'uploads/other-image.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Pending,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/media/{$media->id}/confirm");

        $response->assertStatus(403);
    }

    public function test_cannot_confirm_media_if_object_not_found_in_r2(): void
    {
        $media = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/missing.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Pending,
        ]);

        $mockStorage = $this->mock(R2StorageService::class);
        $mockStorage->shouldReceive('head')
            ->once()
            ->with('uploads/missing.jpg')
            ->andReturn(null);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/media/{$media->id}/confirm");

        $response->assertStatus(422);
    }

    public function test_cannot_confirm_media_if_size_mismatches(): void
    {
        $media = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/size-mismatch.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Pending,
        ]);

        $mockStorage = $this->mock(R2StorageService::class);
        $mockStorage->shouldReceive('head')
            ->once()
            ->with('uploads/size-mismatch.jpg')
            ->andReturn([
                'ContentLength' => 500, // Different size
            ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/media/{$media->id}/confirm");

        $response->assertStatus(422);
    }

    public function test_confirmation_is_idempotent_if_already_ready(): void
    {
        Queue::fake();

        $media = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/ready-image.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Ready,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/media/{$media->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'ready');

        Queue::assertNothingPushed();
    }
}