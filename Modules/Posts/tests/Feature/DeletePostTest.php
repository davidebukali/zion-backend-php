<?php

namespace Modules\Posts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Modules\Posts\Actions\CreatePost;
use Modules\Posts\Actions\DeletePost;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class DeletePostTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Delete Owner',
            'email' => 'delete_owner@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->otherUser = User::create([
            'name' => 'Delete Other User',
            'email' => 'delete_other@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    private function createMedia(User $user, MediaStatus $status = MediaStatus::Ready, string $filename = 'delete-file.jpg'): Media
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

    public function test_owner_can_delete_their_own_post(): void
    {
        $post = $this->owner->posts()->create([
            'content' => 'Post to delete',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson(route('api.post.destroy', ['post' => $post->id]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('posts', [
            'id' => $post->id,
        ]);
    }

    /**
     * Critical test: A user cannot delete another user's post.
     */
    public function test_user_cannot_delete_another_users_post(): void
    {
        $post = $this->owner->posts()->create([
            'content' => 'Protected post',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);

        Sanctum::actingAs($this->otherUser);

        $response = $this->deleteJson(route('api.post.destroy', ['post' => $post->id]));

        $response->assertStatus(403);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Critical test: Deleting a post removes its attachment records.
     */
    public function test_deleting_post_removes_its_attachment_records(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('uploads/delete-target.jpg', 'fake-image-bytes');

        $media = $this->createMedia($this->owner, MediaStatus::Ready, 'delete-target.jpg');

        $createAction = app(CreatePost::class);
        $resource = $createAction($this->owner, [
            'content' => 'Post with attachment to be deleted',
            'media_ids' => [$media->id],
        ]);

        $post = $resource->resource;

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
        $this->assertDatabaseHas('post_media', ['post_id' => $post->id, 'media_id' => $media->id]);
        Storage::disk('r2')->assertExists('uploads/delete-target.jpg');

        Sanctum::actingAs($this->owner);
        $response = $this->deleteJson(route('api.post.destroy', ['post' => $post->id]));
        $response->assertStatus(200);

        // Assert post is soft deleted
        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        // Assert attachment records are removed from pivot
        $this->assertDatabaseMissing('post_media', [
            'post_id' => $post->id,
            'media_id' => $media->id,
        ]);

        // Assert physical file is deleted
        Storage::disk('r2')->assertMissing('uploads/delete-target.jpg');
    }
}
