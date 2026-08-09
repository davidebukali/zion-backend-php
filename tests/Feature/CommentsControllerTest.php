<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;
use Modules\Comments\Models\Comment;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class CommentsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->user->posts()->create([
            'content' => 'Test Post Content',
            'visibility' => 'public',
        ]);
    }

    public function test_can_create_comment_on_post(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.comments.store', ['post' => $this->post->id]), [
            'content' => 'This is a test comment.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'post_id',
                'user_id',
                'parent_comment_id',
                'content',
                'likes_count',
                'replies_count',
                'created_at',
                'updated_at',
            ],
            'meta'
        ]);

        $response->assertJsonPath('data.content', 'This is a test comment.');
        $response->assertJsonPath('data.post_id', $this->post->id);
        $response->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment.',
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_comment_without_content(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.comments.store', ['post' => $this->post->id]), [
            'content' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_cannot_create_comment_with_invalid_parent_comment(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.comments.store', ['post' => $this->post->id]), [
            'content' => 'Test reply',
            'parent_comment_id' => '01J4V8Q0000000000000000000', // non-existent ULID
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_comment_id']);
    }

    public function test_can_list_comments_for_post(): void
    {
        // 1. Create a top-level comment on our target post
        $comment1 = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'First top-level comment',
        ]);

        // 2. Create another top-level comment on our target post
        $comment2 = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Second top-level comment',
        ]);

        // 3. Create a reply comment (parent_comment_id is NOT null)
        Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_comment_id' => $comment1->id,
            'content' => 'A reply to first comment',
        ]);

        // 4. Create a comment on a different post
        $otherPost = $this->user->posts()->create([
            'content' => 'Other Post',
            'visibility' => 'public',
        ]);
        Comment::create([
            'post_id' => $otherPost->id,
            'user_id' => $this->user->id,
            'content' => 'Comment on other post',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.comments.index', ['post' => $this->post->id]));

        $response->assertStatus(200);

        // Assert response structure matching RespondsWithApi + Paginated resource format
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'post_id',
                    'user_id',
                    'parent_comment_id',
                    'content',
                    'likes_count',
                    'replies_count',
                    'created_at',
                    'updated_at',
                ]
            ],
            'meta' => [
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor']
            ]
        ]);

        // Assert only top-level comments for our post are returned (count = 2)
        $response->assertJsonCount(2, 'data');

        // Assert order (newest first)
        $response->assertJsonPath('data.0.id', $comment2->id);
        $response->assertJsonPath('data.1.id', $comment1->id);
    }

    public function test_can_update_own_comment(): void
    {
        $comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Old content',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->patchJson(route('api.comments.update', ['comment' => $comment->id]), [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.content', 'Updated content');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_cannot_update_other_user_comment(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $otherUser->id,
            'content' => 'Other content',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->patchJson(route('api.comments.update', ['comment' => $comment->id]), [
            'content' => 'Attempted update',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_update_comment_with_invalid_data(): void
    {
        $comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Original content',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->patchJson(route('api.comments.update', ['comment' => $comment->id]), [
            'content' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_can_delete_own_comment(): void
    {
        $comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Delete me comment',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson(route('api.comments.destroy', ['comment' => $comment->id]));

        $response->assertStatus(200);

        $this->assertSoftDeleted('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_cannot_delete_other_user_comment(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $otherUser->id,
            'content' => 'Cannot delete me comment',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson(route('api.comments.destroy', ['comment' => $comment->id]));

        $response->assertStatus(403);
    }
}
