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
}
