<?php

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Services\R2StorageService;
use Tests\TestCase;

class UploadMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Upload User',
            'email' => 'upload_user@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_user_can_generate_presigned_upload_url(): void
    {
        $mockStorage = $this->mock(R2StorageService::class);
        $mockStorage->shouldReceive('createPresignedUploadUrl')
            ->once()
            ->andReturn('https://r2.example.com/uploads/photo.jpg?signature=test-sig');

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload-url', [
            'filename' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024 * 1024,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'upload_url',
                    'path',
                ],
            ]);

        $this->assertDatabaseHas('media', [
            'user_id' => $this->user->id,
            'mime_type' => 'image/jpeg',
            'status' => MediaStatus::Pending->value,
        ]);
    }

    public function test_upload_url_requires_valid_mime_type(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/media/upload-url', [
            'filename' => 'script.sh',
            'mime_type' => 'application/x-sh',
            'size' => 1024,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mime_type']);
    }
}
