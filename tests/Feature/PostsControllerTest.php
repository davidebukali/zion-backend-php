<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class PostsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_can_list_posts(): void
    {
        // Create some posts
        $this->user->posts()->create(['content' => 'Post 1', 'visibility' => 'public']);
        $this->user->posts()->create(['content' => 'Post 2', 'visibility' => 'public']);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.post.index'));

        $response->assertStatus(200);

        // We expect the new RespondsWithApi structure:
        // success: true, message: 'Success', data: [...], meta: { links: ..., meta: ... }
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
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'path',
                    'per_page',
                    'next_cursor',
                    'prev_cursor',
                ]
            ]
        ]);
    }

    public function test_can_create_post(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('api.post.store'), [
            'content' => 'New Post Content',
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'content',
                'visibility',
                'created_at',
                'updated_at',
            ],
            'meta'
        ]);

        $this->assertDatabaseHas('posts', [
            'content' => 'New Post Content',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_show_post(): void
    {
        $post = $this->user->posts()->create(['content' => 'Specific Post', 'visibility' => 'public']);

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.post.show', ['post' => $post->id]));

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'content',
                'visibility',
                'created_at',
                'updated_at',
            ],
            'meta'
        ]);

        $response->assertJsonPath('data.content', 'Specific Post');
    }

    public function test_can_delete_own_post(): void
    {
        $post = $this->user->posts()->create(['content' => 'Delete Me', 'visibility' => 'public']);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson(route('api.post.destroy', ['post' => $post->id]));

        $response->assertStatus(200);

        $this->assertSoftDeleted('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_cannot_delete_other_user_post(): void
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $otherUser->posts()->create(['content' => 'Cannot Delete Me', 'visibility' => 'public']);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson(route('api.post.destroy', ['post' => $post->id]));

        $response->assertStatus(403);
    }
}
