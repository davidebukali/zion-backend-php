<?php

namespace Modules\Comments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class CreateReplyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Post $post;
    private Comment $parentComment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Reply Test User',
            'email' => 'reply_user@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->user->posts()->create([
            'content' => 'Post for reply test',
            'visibility' => 'public',
        ]);

        $this->parentComment = Comment::create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'content' => 'Parent comment text',
        ]);
    }

    public function test_user_can_reply_to_comment(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.comments.reply.store', ['comment' => $this->parentComment->id]), [
            'content' => 'This is a reply to the parent comment.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'This is a reply to the parent comment.')
            ->assertJsonPath('data.parent_comment_id', $this->parentComment->id);

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'parent_comment_id' => $this->parentComment->id,
            'content' => 'This is a reply to the parent comment.',
        ]);

        $this->parentComment->refresh();
        $this->assertEquals(1, $this->parentComment->replies_count);
    }

    public function test_cannot_create_empty_reply(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.comments.reply.store', ['comment' => $this->parentComment->id]), [
            'content' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }
}
