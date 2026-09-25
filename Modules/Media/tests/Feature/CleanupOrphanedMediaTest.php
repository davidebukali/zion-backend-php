<?php

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Tests\TestCase;

class CleanupOrphanedMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Cleanup Test User',
            'email' => 'cleanup_test@example.com',
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
        Storage::disk('r2')->assertMissing('uploads/expired-pending.jpg');

        $this->assertDatabaseHas('media', ['id' => $recent->id]);
        Storage::disk('r2')->assertExists('uploads/recent-pending.jpg');
    }

    public function test_cleans_up_expired_failed_media(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('uploads/expired-failed.jpg', 'content');
        Storage::disk('r2')->put('uploads/recent-failed.jpg', 'content');

        $expired = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/expired-failed.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Failed,
        ]);
        $expired->updated_at = now()->subHours(73);
        $expired->save();

        $recent = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/recent-failed.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Failed,
        ]);
        $recent->updated_at = now()->subHours(10);
        $recent->save();

        $this->artisan('media:cleanup-orphaned', ['--failed-hours' => 72])
            ->assertSuccessful();

        $this->assertDatabaseMissing('media', ['id' => $expired->id]);
        Storage::disk('r2')->assertMissing('uploads/expired-failed.jpg');
        $this->assertDatabaseHas('media', ['id' => $recent->id]);
        Storage::disk('r2')->assertExists('uploads/recent-failed.jpg');
    }

    public function test_cleans_up_unattached_ready_media_and_preserves_attached_media(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('uploads/unattached-ready.jpg', 'content');
        Storage::disk('r2')->put('uploads/post-attached.jpg', 'content');

        $unattached = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/unattached-ready.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Ready,
        ]);
        $unattached->created_at = now()->subHours(13);
        $unattached->save();

        $attachedToPost = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/post-attached.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Ready,
        ]);
        $attachedToPost->created_at = now()->subHours(13);
        $attachedToPost->save();

        $postId = (string) Str::ulid();
        DB::table('posts')->insert([
            'id' => $postId,
            'user_id' => $this->user->id,
            'caption' => 'Test post',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('post_media')->insert([
            'post_id' => $postId,
            'media_id' => $attachedToPost->id,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('media:cleanup-orphaned', ['--unattached-hours' => 12])
            ->assertSuccessful();

        $this->assertDatabaseMissing('media', ['id' => $unattached->id]);
        Storage::disk('r2')->assertMissing('uploads/unattached-ready.jpg');
        $this->assertDatabaseHas('media', ['id' => $attachedToPost->id]);
        Storage::disk('r2')->assertExists('uploads/post-attached.jpg');
    }

    public function test_dry_run_mode_does_not_delete_media_or_files(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('uploads/dry-run.jpg', 'content');

        $media = Media::create([
            'user_id' => $this->user->id,
            'disk' => 'r2',
            'path' => 'uploads/dry-run.jpg',
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => MediaStatus::Pending,
        ]);
        $media->created_at = now()->subHours(24);
        $media->save();

        $this->artisan('media:cleanup-orphaned', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        Storage::disk('r2')->assertExists('uploads/dry-run.jpg');
    }
}
