<?php

namespace Modules\Interactions\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class PostLikeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Like Tester',
            'email' => 'like_tester@example.com',
            'password' => bcrypt('password'),
        ]);

        $author = User::create([
            'name' => 'Post Author',
            'email' => 'author@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $author->posts()->create([
            'content' => 'First Post to like',
            'visibility' => 'public',
        ]);
    }

    public function test_user_can_like_post(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.post.like', ['post' => $this->post->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post liked successfully',
            ]);

        $this->assertDatabaseHas('post_likes', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_unlike_post(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson(route('api.post.like', ['post' => $this->post->id]))->assertStatus(200);

        $response = $this->deleteJson(route('api.post.unlike', ['post' => $this->post->id]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post unliked successfully',
            ]);

        $this->assertDatabaseMissing('post_likes', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_list_liked_posts(): void
    {
        $post2 = $this->post->user->posts()->create([
            'content' => 'Second Liked Post',
            'visibility' => 'public',
        ]);

        $unlikedPost = $this->post->user->posts()->create([
            'content' => 'Not Liked Post',
            'visibility' => 'public',
        ]);

        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other_user@example.com',
            'password' => bcrypt('password'),
        ]);

        // Other user likes the unlikedPost
        $unlikedPost->likes()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($this->user);

        // Authenticated user likes $this->post and $post2
        $this->postJson(route('api.post.like', ['post' => $this->post->id]))->assertStatus(200);
        $this->postJson(route('api.post.like', ['post' => $post2->id]))->assertStatus(200);

        $response = $this->getJson(route('api.like.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'content',
                    'visibility',
                    'is_liked',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
            ],
        ]);

        $response->assertJsonCount(2, 'data');

        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->all();
        $this->assertContains($this->post->id, $ids);
        $this->assertContains($post2->id, $ids);
        $this->assertNotContains($unlikedPost->id, $ids);

        foreach ($data as $item) {
            $this->assertTrue($item['is_liked']);
        }
    }

    public function test_unauthenticated_user_cannot_access_like_endpoints(): void
    {
        $this->postJson(route('api.post.like', ['post' => $this->post->id]))->assertStatus(401);
        $this->deleteJson(route('api.post.unlike', ['post' => $this->post->id]))->assertStatus(401);
        $this->getJson(route('api.like.index'))->assertStatus(401);
    }
}
