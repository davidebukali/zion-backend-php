<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;
use Modules\Interactions\Events\CommentLiked;
use Modules\Interactions\Events\CommentUnliked;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class CommentLikeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Post $post;
    private Comment $comment;

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

        $this->comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Test Comment Content',
        ]);
    }

    public function test_can_like_comment(): void
    {
        Event::fake([CommentLiked::class]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.comment.like', ['comment' => $this->comment->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Comment liked successfully',
        ]);

        $this->assertDatabaseHas('comment_likes', [
            'comment_id' => $this->comment->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(1, $this->comment->refresh()->likes_count);

        Event::assertDispatched(CommentLiked::class, function ($event) {
            return $event->comment->id === $this->comment->id
                && $event->user->id === $this->user->id;
        });
    }

    public function test_liking_comment_is_idempotent(): void
    {
        Event::fake([CommentLiked::class]);

        Sanctum::actingAs($this->user);

        // First like
        $this->postJson(route('api.comment.like', ['comment' => $this->comment->id]))->assertStatus(200);

        // Second like
        $this->postJson(route('api.comment.like', ['comment' => $this->comment->id]))->assertStatus(200);

        $this->assertEquals(1, $this->comment->refresh()->likes_count);

        $this->assertDatabaseCount('comment_likes', 1);

        Event::assertDispatchedTimes(CommentLiked::class, 1);
    }

    public function test_can_unlike_comment(): void
    {
        Event::fake([CommentUnliked::class]);

        Sanctum::actingAs($this->user);

        // First like the comment
        $this->postJson(route('api.comment.like', ['comment' => $this->comment->id]))->assertStatus(200);
        $this->assertEquals(1, $this->comment->refresh()->likes_count);

        // Now unlike the comment
        $response = $this->deleteJson(route('api.comment.unlike', ['comment' => $this->comment->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Comment unliked successfully',
        ]);

        $this->assertDatabaseMissing('comment_likes', [
            'comment_id' => $this->comment->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(0, $this->comment->refresh()->likes_count);

        Event::assertDispatched(CommentUnliked::class, function ($event) {
            return $event->comment->id === $this->comment->id
                && $event->user->id === $this->user->id;
        });
    }

    public function test_unliking_comment_when_not_liked_is_idempotent(): void
    {
        Event::fake([CommentUnliked::class]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson(route('api.comment.unlike', ['comment' => $this->comment->id]));

        $response->assertStatus(200);

        $this->assertEquals(0, $this->comment->refresh()->likes_count);

        Event::assertNotDispatched(CommentUnliked::class);
    }

    public function test_unauthenticated_user_cannot_like_or_unlike_comment(): void
    {
        $likeResponse = $this->postJson(route('api.comment.like', ['comment' => $this->comment->id]));
        $likeResponse->assertStatus(401);

        $unlikeResponse = $this->deleteJson(route('api.comment.unlike', ['comment' => $this->comment->id]));
        $unlikeResponse->assertStatus(401);
    }
}
