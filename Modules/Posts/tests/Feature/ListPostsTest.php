<?php

namespace Modules\Posts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class ListPostsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'List Test User',
            'email' => 'list_posts_test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_can_list_posts(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->user->posts()->create([
                'content' => "Test Post {$i}",
                'visibility' => PostVisibility::PUBLIC->value,
            ]);
        }

        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.post.index'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'visibility',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'links',
                    'meta',
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Critical test: Cursor pagination returns the expected records.
     */
    public function test_cursor_pagination_returns_expected_records(): void
    {
        Sanctum::actingAs($this->user);

        // Create 5 posts with slightly staggered timestamps
        $createdPosts = [];
        for ($i = 1; $i <= 5; $i++) {
            $post = $this->user->posts()->create([
                'content' => "Cursor Paginated Post {$i}",
                'visibility' => PostVisibility::PUBLIC->value,
                'created_at' => now()->subMinutes(10 - $i),
            ]);
            $createdPosts[] = $post;
        }

        // Request first page with per_page = 2
        $firstPageResponse = $this->getJson(route('api.post.index', ['per_page' => 2]));

        $firstPageResponse->assertStatus(200);
        $firstPageData = $firstPageResponse->json('data');
        $this->assertCount(2, $firstPageData);

        $nextCursor = $firstPageResponse->json('meta.links.next');
        $this->assertNotNull($nextCursor, 'Expected next cursor link to be present for multi-page results');

        // Request second page using the next cursor
        $secondPageResponse = $this->getJson($nextCursor);
        $secondPageResponse->assertStatus(200);
        $secondPageData = $secondPageResponse->json('data');
        $this->assertCount(2, $secondPageData);

        // Ensure records do not overlap between cursor pages
        $firstPageIds = collect($firstPageData)->pluck('id')->all();
        $secondPageIds = collect($secondPageData)->pluck('id')->all();
        $intersection = array_intersect($firstPageIds, $secondPageIds);

        $this->assertEmpty($intersection, 'Cursor pagination returned duplicate records across pages');
    }
}
