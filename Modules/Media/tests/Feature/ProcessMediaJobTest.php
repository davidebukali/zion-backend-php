<?php

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Services\R2StorageService;
use Tests\TestCase;

class ProcessMediaJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_media_job_generates_variants_and_sets_ready(): void
    {
        $user = User::create([
            'name' => 'Job Test User',
            'email' => 'job_test@example.com',
            'password' => bcrypt('password'),
        ]);

        $media = Media::create([
            'user_id' => $user->id,
            'disk' => 'r2',
            'path' => 'uploads/test-photo.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'status' => MediaStatus::Confirmed,
        ]);

        // Generate a real test image binary using Intervention Image
        $manager = new ImageManager(new Driver);
        $testImage = $manager->createImage(500, 500);
        $testImageBinary = (string) $testImage->encodeUsingMediaType('image/jpeg', 80);

        $mockStorage = $this->mock(R2StorageService::class);
        $mockStorage->shouldReceive('get')
            ->once()
            ->with('uploads/test-photo.jpg')
            ->andReturn($testImageBinary);

        $mockStorage->shouldReceive('put')
            ->twice()
            ->andReturn(['@metadata' => ['statusCode' => 200]]);

        $job = new ProcessMediaJob($media->id);
        $job->handle($mockStorage);

        $media->refresh();

        $this->assertEquals(MediaStatus::Ready, $media->status);
        $this->assertEquals(500, $media->width);
        $this->assertEquals(500, $media->height);
        $this->assertArrayHasKey('variants', $media->metadata);
        $this->assertEquals('uploads/variants/test-photo_thumb.webp', $media->metadata['variants']['thumbnail']);
        $this->assertEquals('uploads/variants/test-photo_medium.webp', $media->metadata['variants']['medium']);
        $this->assertEquals('uploads/test-photo.jpg', $media->metadata['variants']['original']);
    }

    public function test_process_media_job_sets_failed_status_on_exception(): void
    {
        $user = User::create([
            'name' => 'Failed Job User',
            'email' => 'failed_job@example.com',
            'password' => bcrypt('password'),
        ]);

        $media = Media::create([
            'user_id' => $user->id,
            'disk' => 'r2',
            'path' => 'uploads/broken-photo.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'status' => MediaStatus::Confirmed,
        ]);

        $mockStorage = $this->mock(R2StorageService::class);
        $mockStorage->shouldReceive('get')
            ->once()
            ->with('uploads/broken-photo.jpg')
            ->andReturn(null); // Triggers exception

        $this->expectException(\RuntimeException::class);

        try {
            $job = new ProcessMediaJob($media->id);
            $job->handle($mockStorage);
        } finally {
            $media->refresh();
            $this->assertEquals(MediaStatus::Failed, $media->status);
        }
    }
}
