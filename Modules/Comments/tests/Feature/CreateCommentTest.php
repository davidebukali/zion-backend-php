<?php

namespace Modules\Comments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class CreateCommentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Comment User',
            'email' => 'comment_user@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->user->posts()->create([
            'content' => 'Post for comments',
            'visibility' => 'public',
        ]);
    }

    private function createMedia(User $user, MediaStatus $status = MediaStatus::Ready, string $filename = 'test-comment-media.jpg'): Media
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

    public function test_user_can_create_comment_on_post(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.comments.store', ['post' => $this->post->id]), [
            'content' => 'This is a valid comment.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'This is a valid comment.');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'This is a valid comment.',
        ]);
    }

    public function test_user_cannot_create_empty_comment(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.comments.store', ['post' => $this->post->id]), [
            'content' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_user_can_create_comment_with_ready_media(): void
    {
        Sanctum::actingAs($this->user);

        $media = $this->createMedia($this->user, MediaStatus::Ready, 'comment-pic.jpg');

        $response = $this->postJson(route('api.comments.store', ['post' => $this->post->id]), [
            'content' => 'Comment with media attachment',
            'media_ids' => [$media->id],
        ]);

        $response->assertStatus(201);
        $commentId = $response->json('data.id');

        $this->assertDatabaseHas('comment_media', [
            'comment_id' => $commentId,
            'media_id' => $media->id,
            'sort_order' => 0,
        ]);
    }

    /**
     * Critical test: Comment creation and media attachment roll back together.
     */
    public function test_comment_creation_and_media_attachment_rollback_together_on_failure(): void
    {
        Sanctum::actingAs($this->user);

        $pendingMedia = $this->createMedia($this->user, MediaStatus::Pending, 'pending-comment.jpg');
        $initialCommentCount = DB::table('comments')->count();
        $initialPivotCount = DB::table('comment_media')->count();

        $response = $this->postJson(route('api.comments.store', ['post' => $this->post->id]), [
            'content' => 'Comment that should roll back',
            'media_ids' => [$pendingMedia->id],
        ]);

        $response->assertStatus(422);

        $this->assertEquals($initialCommentCount, DB::table('comments')->count());
        $this->assertEquals($initialPivotCount, DB::table('comment_media')->count());
        $this->assertDatabaseMissing('comments', [
            'content' => 'Comment that should roll back',
        ]);
    }
}
