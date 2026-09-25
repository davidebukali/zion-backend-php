<?php

namespace Modules\Comments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Comments\Actions\DeleteComment;
use Modules\Comments\Models\Comment;
use Modules\Comments\Transformers\CommentResource;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class DeleteCommentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $otherUser;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Comment Owner',
            'email' => 'comment_owner@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->otherUser = User::create([
            'name' => 'Comment Other User',
            'email' => 'comment_other@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->owner->posts()->create([
            'content' => 'Post with comment to delete',
            'visibility' => 'public',
        ]);
    }

    public function test_owner_can_delete_comment(): void
    {
        $comment = Comment::create([
            'user_id' => $this->owner->id,
            'post_id' => $this->post->id,
            'content' => 'Comment to be deleted',
        ]);

        Sanctum::actingAs($this->owner);

        $response = $this->deleteJson(route('api.comments.destroy', ['comment' => $comment->id]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        $comment = Comment::create([
            'user_id' => $this->owner->id,
            'post_id' => $this->post->id,
            'content' => 'Protected comment',
        ]);

        Sanctum::actingAs($this->otherUser);

        $response = $this->deleteJson(route('api.comments.destroy', ['comment' => $comment->id]));

        $response->assertStatus(403);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Critical test: Soft-deleted comments are represented correctly.
     */
    public function test_soft_deleted_comments_are_represented_correctly(): void
    {
        $comment = Comment::create([
            'user_id' => $this->owner->id,
            'post_id' => $this->post->id,
            'content' => 'Original secret content',
        ]);

        // Soft delete the comment via action
        $deleteAction = app(DeleteComment::class);
        $deleteAction($comment);

        $comment->refresh();
        $this->assertTrue($comment->trashed());

        // Transform using CommentResource
        $resource = new CommentResource($comment);
        $data = $resource->toArray(Request::create('/api/v1/comments', 'GET'));

        // Assert representation rules:
        // 1. Content is masked
        $this->assertEquals('[This comment has been deleted]', $data['content']);
        // 2. Author/user_id is anonymized (null)
        $this->assertNull($data['user_id']);
        // 3. Likes count is reset to 0
        $this->assertEquals(0, $data['likes_count']);
        // 4. Comment ID remains intact for thread consistency
        $this->assertEquals($comment->id, $data['id']);
    }
}
