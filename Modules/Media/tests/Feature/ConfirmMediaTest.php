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

class ConfirmMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Confirm Media User',
            'email' => 'confirm_media_user@example.com',
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

        $media->refresh();
        $this->assertEquals(MediaStatus::Confirmed, $media->status);

        Queue::assertPushed(ProcessMediaJob::class, function ($job) use ($media) {
            return $job->media->id === $media->id;
        });
    }

    public function test_cannot_confirm_another_users_media(): void
    {
        $otherUser = User::create([
            'name' => 'Other Media User',
            'email' => 'other_media_confirm@example.com',
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
}
