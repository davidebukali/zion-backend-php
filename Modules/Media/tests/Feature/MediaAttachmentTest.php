<?php

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Comments\Actions\CreateComment;
use Modules\Comments\Transformers\CommentResource;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Modules\Posts\Actions\AttachMediaToPost;
use Modules\Posts\Actions\CreatePost;
use Modules\Posts\Actions\DeletePost;
use Modules\Posts\Models\Post;
use Modules\Posts\Transformers\PostResource;
use Tests\TestCase;

class MediaAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Attachment Test User',
            'email' => 'attachment_test@example.com',
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

    public function test_user_can_create_post_with_ready_media(): void
    {
        $media = $this->createMedia($this->user, MediaStatus::Ready, 'ready-1.jpg');

        $action = app(CreatePost::class);
        $resource = $action($this->user, [
            'content' => 'Post with ready media',
            'media_ids' => [$media->id],
        ]);

        $post = $resource->resource;

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('post_media', [
            'post_id' => $post->id,
            'media_id' => $media->id,
            'sort_order' => 0,
        ]);
    }

    public function test_user_cannot_attach_another_users_media(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other_user@example.com',
            'password' => bcrypt('password'),
        ]);

        $otherMedia = $this->createMedia($otherUser, MediaStatus::Ready, 'other-user.jpg');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more media files do not exist or do not belong to you.');

        $action = app(CreatePost::class);
        $action($this->user, [
            'content' => 'Trying to use someone else media',
            'media_ids' => [$otherMedia->id],
        ]);
    }

    public function test_user_cannot_attach_pending_media(): void
    {
        $pendingMedia = $this->createMedia($this->user, MediaStatus::Pending, 'pending.jpg');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('All media files must finish processing before attachment.');

        $action = app(CreatePost::class);
        $action($this->user, [
            'content' => 'Trying to use pending media',
            'media_ids' => [$pendingMedia->id],
        ]);
    }

    public function test_user_cannot_attach_failed_media(): void
    {
        $failedMedia = $this->createMedia($this->user, MediaStatus::Failed, 'failed.jpg');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('All media files must finish processing before attachment.');

        $action = app(CreatePost::class);
        $action($this->user, [
            'content' => 'Trying to use failed media',
            'media_ids' => [$failedMedia->id],
        ]);
    }

    public function test_post_media_is_returned_in_post_resource(): void
    {
        $media = $this->createMedia($this->user, MediaStatus::Ready, 'resource-media.jpg');

        $action = app(CreatePost::class);
        $resource = $action($this->user, [
            'content' => 'Checking post resource',
            'media_ids' => [$media->id],
        ]);

        $data = $resource->toArray(Request::create('/api/v1/posts', 'POST'));

        $this->assertArrayHasKey('media', $data);
        $mediaCollection = $data['media'];
        $this->assertCount(1, $mediaCollection);
        $this->assertEquals($media->id, $mediaCollection[0]->id);
    }

    public function test_comment_media_is_returned_in_comment_resource(): void
    {
        $media = $this->createMedia($this->user, MediaStatus::Ready, 'comment-media.jpg');

        $post = $this->user->posts()->create([
            'content' => 'Sample Post',
        ]);

        $createComment = app(CreateComment::class);
        $comment = $createComment($this->user, $post, [
            'content' => 'Sample Comment with media',
            'media_ids' => [$media->id],
        ]);

        $resource = new CommentResource($comment->load('media'));
        $data = $resource->toArray(Request::create('/api/v1/posts/' . $post->id . '/comments', 'POST'));

        $this->assertArrayHasKey('media', $data);
        $mediaCollection = $data['media'];
        $this->assertCount(1, $mediaCollection);
        $this->assertEquals($media->id, $mediaCollection[0]->id);
    }

    public function test_media_order_is_preserved(): void
    {
        $media1 = $this->createMedia($this->user, MediaStatus::Ready, 'order-1.jpg');
        $media2 = $this->createMedia($this->user, MediaStatus::Ready, 'order-2.jpg');
        $media3 = $this->createMedia($this->user, MediaStatus::Ready, 'order-3.jpg');

        $orderedIds = [$media3->id, $media1->id, $media2->id];

        $action = app(CreatePost::class);
        $resource = $action($this->user, [
            'content' => 'Checking order preservation',
            'media_ids' => $orderedIds,
        ]);

        $post = $resource->resource;

        $attachments = DB::table('post_media')
            ->where('post_id', $post->id)
            ->orderBy('sort_order', 'asc')
            ->pluck('media_id')
            ->all();

        $this->assertEquals($orderedIds, $attachments);

        $this->assertDatabaseHas('post_media', ['post_id' => $post->id, 'media_id' => $media3->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('post_media', ['post_id' => $post->id, 'media_id' => $media1->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('post_media', ['post_id' => $post->id, 'media_id' => $media2->id, 'sort_order' => 2]);
    }

    public function test_duplicate_media_ids_are_rejected(): void
    {
        $media = $this->createMedia($this->user, MediaStatus::Ready, 'already-attached.jpg');

        $action = app(CreatePost::class);
        $action($this->user, [
            'content' => 'First post',
            'media_ids' => [$media->id],
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('One or more media files are already attached to a post.');

        $action($this->user, [
            'content' => 'Second post with duplicate media',
            'media_ids' => [$media->id],
        ]);
    }

    public function test_media_limit_is_enforced(): void
    {
        $mediaIds = [];
        for ($i = 1; $i <= 11; $i++) {
            $media = $this->createMedia($this->user, MediaStatus::Ready, "limit-{$i}.jpg");
            $mediaIds[] = $media->id;
        }

        $post = $this->user->posts()->create([
            'content' => 'Testing limit',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('A post can have a maximum of 10 media files.');

        $attachAction = app(AttachMediaToPost::class);
        $attachAction->handle($post, $mediaIds, $this->user->id);
    }

    public function test_failed_attachment_rolls_back_post_creation(): void
    {
        $pendingMedia = $this->createMedia($this->user, MediaStatus::Pending, 'pending-rollback.jpg');

        $initialPostCount = DB::table('posts')->count();

        try {
            $action = app(CreatePost::class);
            $action($this->user, [
                'content' => 'This post should be rolled back',
                'media_ids' => [$pendingMedia->id],
            ]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            // Expected
        }

        $this->assertEquals($initialPostCount, DB::table('posts')->count());
    }

    public function test_deleting_post_removes_post_media_attachments(): void
    {
        Storage::fake('r2');
        Storage::disk('r2')->put('uploads/delete-me.jpg', 'image-bytes');

        $media = $this->createMedia($this->user, MediaStatus::Ready, 'delete-me.jpg');

        $action = app(CreatePost::class);
        $resource = $action($this->user, [
            'content' => 'Post to be deleted',
            'media_ids' => [$media->id],
        ]);

        $post = $resource->resource;

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
        $this->assertDatabaseHas('post_media', ['post_id' => $post->id, 'media_id' => $media->id]);
        Storage::disk('r2')->assertExists('uploads/delete-me.jpg');

        $deleteAction = app(DeletePost::class);
        $deleteAction($post);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('post_media', ['post_id' => $post->id, 'media_id' => $media->id]);
        Storage::disk('r2')->assertMissing('uploads/delete-me.jpg');
    }
}
