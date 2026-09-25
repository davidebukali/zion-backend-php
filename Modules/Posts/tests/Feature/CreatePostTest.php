<?php

namespace Modules\Posts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Modules\Posts\Actions\CreatePost;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class CreatePostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'create_post_test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    private function createMedia(User $user, MediaStatus $status = MediaStatus::Ready, string $filename = 'test.jpg'): Media
    {
        return Media::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'disk' => 'r2',
            'path' => 'uploads/' . $filename,
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'status' => $status,
        ]);
    }

    public function test_authenticated_user_can_create_post(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.post.store'), [
            'content' => 'Hello world! This is my test post.',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Hello world! This is my test post.')
            ->assertJsonPath('data.visibility', PostVisibility::PUBLIC->value);

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->user->id,
            'content' => 'Hello world! This is my test post.',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_post(): void
    {
        $response = $this->postJson(route('api.post.store'), [
            'content' => 'Unauthenticated post creation attempt',
        ]);

        $response->assertStatus(401);
    }

    public function test_post_creation_requires_valid_content(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.post.store'), [
            'content' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_user_can_create_post_with_attached_ready_media(): void
    {
        Sanctum::actingAs($this->user);

        $media = $this->createMedia($this->user, MediaStatus::Ready, 'photo.jpg');

        $response = $this->postJson(route('api.post.store'), [
            'content' => 'Post with media attachment',
            'media_ids' => [$media->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $postId = $response->json('data.id');

        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('post_media', [
            'post_id' => $postId,
            'media_id' => $media->id,
            'sort_order' => 0,
        ]);
    }

    /**
     * Critical test: Post creation and media attachment roll back together.
     */
    public function test_post_creation_and_media_attachment_rollback_together_on_failure(): void
    {
        Sanctum::actingAs($this->user);

        $pendingMedia = $this->createMedia($this->user, MediaStatus::Pending, 'pending.jpg');
        $initialPostCount = DB::table('posts')->count();
        $initialPivotCount = DB::table('post_media')->count();

        $response = $this->postJson(route('api.post.store'), [
            'content' => 'This post must roll back because media is pending',
            'media_ids' => [$pendingMedia->id],
        ]);

        $response->assertStatus(422);

        // Ensure both the post creation and media attachment roll back
        $this->assertEquals($initialPostCount, DB::table('posts')->count());
        $this->assertEquals($initialPivotCount, DB::table('post_media')->count());
        $this->assertDatabaseMissing('posts', [
            'content' => 'This post must roll back because media is pending',
        ]);
    }
}
