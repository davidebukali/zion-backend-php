<?php

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Tests\TestCase;

class CleanupMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Cleanup Test User',
            'email' => 'cleanup_media_user@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_cleans_up_expired_pending_media(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('uploads/expired-pending.jpg', 'content');
        Storage::disk('r2')->put('uploads/recent-pending.jpg', 'content');

        $expired = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/expired-pending.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Pending,
        ]);
        $expired->created_at = now()->subHours(13);
        $expired->save();

        $recent = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/recent-pending.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Pending,
        ]);
        $recent->created_at = now()->subHours(2);
        $recent->save();

        $this->artisan('media:cleanup-orphaned', ['--pending-hours' => 12])
            ->assertSuccessful();

        $this->assertDatabaseMissing('media', ['id' => $expired->id]);
        $this->assertDatabaseHas('media', ['id' => $recent->id]);

        Storage::disk('r2')->assertMissing('uploads/expired-pending.jpg');
        Storage::disk('r2')->assertExists('uploads/recent-pending.jpg');
    }

    public function test_cleans_up_expired_unattached_ready_media(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('uploads/expired-ready.jpg', 'content');

        $expiredReady = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/expired-ready.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Ready,
        ]);
        $expiredReady->created_at = now()->subHours(25);
        $expiredReady->save();

        $this->artisan('media:cleanup-orphaned', ['--ready-hours' => 24])
            ->assertSuccessful();

        $this->assertDatabaseMissing('media', ['id' => $expiredReady->id]);
        Storage::disk('r2')->assertMissing('uploads/expired-ready.jpg');
    }
}
