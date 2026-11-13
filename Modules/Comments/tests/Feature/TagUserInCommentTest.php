<?php

namespace Modules\Comments\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Models\UserBlock;
use Tests\TestCase;

class TagUserInCommentTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $taggedUser;

    private User $blockedUser;

    private Post $post;

    private Comment $parentComment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::create([
            'name' => 'Commenter Claire',
            'email' => 'commenter@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->author->id,
            'username' => 'commenter_claire',
            'display_name' => 'Claire C',
        ]);

        $this->taggedUser = User::create([
            'name' => 'Tagged Tim',
            'email' => 'tagged_tim@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->taggedUser->id,
            'username' => 'tagged_tim',
            'display_name' => 'Tim T',
        ]);

        $this->blockedUser = User::create([
            'name' => 'Blocked Ben',
            'email' => 'blocked_ben@example.com',
            'password' => bcrypt('password'),
        ]);
        Profile::create([
            'user_id' => $this->blockedUser->id,
            'username' => 'blocked_ben',
            'display_name' => 'Ben B',
        ]);
        UserBlock::create([
            'blocker_id' => $this->blockedUser->id,
            'blocked_id' => $this->author->id,
        ]);

        $this->post = $this->author->posts()->create([
            'content' => 'Post for comment tagging',
            'visibility' => 'public',
        ]);

        $this->parentComment = Comment::create([
            'user_id' => $this->author->id,
            'post_id' => $this->post->id,
            'content' => 'Top level comment',
        ]);
    }

    public function test_tagging_user_in_comment_creates_notification(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson(route('api.comments.store', ['post' => $this->post->id]), [
            'content' => 'Hey @tagged_tim check this comment',
            'tagged_user_ids' => [$this->taggedUser->id],
        ]);

        $response->assertStatus(201);
        $commentId = $response->json('data.id');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->taggedUser->id,
            'actor_id' => $this->author->id,
            'type' => 'user_tagged',
            'notifiable_type' => 'comment',
            'notifiable_id' => $commentId,
        ]);

        Sanctum::actingAs($this->taggedUser);
        $notificationResponse = $this->getJson(route('api.notifications.index'));
        $notificationResponse->assertStatus(200);

        $notification = $notificationResponse->json('data.0');
        $this->assertEquals('user_tagged', $notification['type']);
        $this->assertEquals('Commenter Claire', $notification['data']['actor_name']);
        $this->assertEquals($this->post->id, $notification['data']['post_id']);
        $this->assertEquals($commentId, $notification['data']['comment_id']);
        $this->assertEquals('tagged you in a comment', $notification['data']['details']);
    }

    public function test_tagging_user_in_reply_creates_notification(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson(route('api.comments.reply.store', ['comment' => $this->parentComment->id]), [
            'content' => 'Hey @tagged_tim this is a reply',
            'tagged_user_ids' => [$this->taggedUser->id],
        ]);

        $response->assertStatus(201);
        $replyId = $response->json('data.id');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->taggedUser->id,
            'actor_id' => $this->author->id,
            'type' => 'user_tagged',
            'notifiable_type' => 'comment',
            'notifiable_id' => $replyId,
        ]);

        Sanctum::actingAs($this->taggedUser);
        $notificationResponse = $this->getJson(route('api.notifications.index'));
        $notificationResponse->assertStatus(200);

        $notification = $notificationResponse->json('data.0');
        $this->assertEquals('user_tagged', $notification['type']);
        $this->assertEquals('tagged you in a reply', $notification['data']['details']);
        $this->assertEquals($this->post->id, $notification['data']['post_id']);
        $this->assertEquals($replyId, $notification['data']['comment_id']);
    }

    public function test_tagging_blocked_user_in_comment_is_silently_suppressed(): void
    {
        Sanctum::actingAs($this->author);

        $response = $this->postJson(route('api.comments.store', ['post' => $this->post->id]), [
            'content' => 'Hey @blocked_ben',
            'tagged_user_ids' => [$this->blockedUser->id],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->blockedUser->id,
            'actor_id' => $this->author->id,
            'type' => 'user_tagged',
        ]);
    }
}
