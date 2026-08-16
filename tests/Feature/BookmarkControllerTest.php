<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class BookmarkControllerTest extends TestCase
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

    public function test_can_bookmark_post(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.bookmark.store', ['post' => $this->post->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Post bookmarked successfully',
        ]);

        $this->assertDatabaseHas('bookmarks', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_bookmarking_post_is_idempotent(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson(route('api.bookmark.store', ['post' => $this->post->id]))->assertStatus(200);
        $this->postJson(route('api.bookmark.store', ['post' => $this->post->id]))->assertStatus(200);

        $this->assertDatabaseCount('bookmarks', 1);
    }

    public function test_can_unbookmark_post(): void
    {
        Sanctum::actingAs($this->user);

        // Bookmark first
        $this->postJson(route('api.bookmark.store', ['post' => $this->post->id]))->assertStatus(200);

        // Unbookmark
        $response = $this->deleteJson(route('api.bookmark.destroy', ['post' => $this->post->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Post unbookmarked successfully',
        ]);

        $this->assertDatabaseMissing('bookmarks', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_list_bookmarked_posts(): void
    {
        $post2 = $this->user->posts()->create([
            'content' => 'Second Bookmarked Post',
            'visibility' => 'public',
        ]);

        $otherPost = $this->user->posts()->create([
            'content' => 'Not Bookmarked Post',
            'visibility' => 'public',
        ]);

        Sanctum::actingAs($this->user);

        // Bookmark $this->post and $post2
        $this->postJson(route('api.bookmark.store', ['post' => $this->post->id]))->assertStatus(200);
        $this->postJson(route('api.bookmark.store', ['post' => $post2->id]))->assertStatus(200);

        // List bookmarks
        $response = $this->getJson(route('api.bookmark.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'content',
                    'visibility',
                    'created_at',
                    'updated_at',
                ]
            ],
            'meta' => [
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor']
            ]
        ]);

        $response->assertJsonCount(2, 'data');
    }

    public function test_unauthenticated_user_cannot_access_bookmark_endpoints(): void
    {
        $this->postJson(route('api.bookmark.store', ['post' => $this->post->id]))->assertStatus(401);
        $this->deleteJson(route('api.bookmark.destroy', ['post' => $this->post->id]))->assertStatus(401);
        $this->getJson(route('api.bookmark.index'))->assertStatus(401);
    }
}
