<?php

namespace Modules\Posts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Events\PostUpdated;
use Modules\Posts\Models\Post;
use Modules\Posts\Policies\PostPolicy;
use Tests\TestCase;

class UpdatePostTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherUser;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner_update@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other_update@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->owner->posts()->create([
            'content' => 'Original post content',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);
    }

    private function createMedia(User $user, MediaStatus $status = MediaStatus::Ready, string $filename = 'test.jpg'): Media
    {
        return Media::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'disk' => 'r2',
            'path' => 'uploads/'.$filename,
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
            'status' => $status,
        ]);
    }

    public function test_user_cannot_update_another_users_post_policy(): void
    {
        $policy = app(PostPolicy::class);

        $this->assertFalse(
            $policy->update($this->otherUser, $this->post),
            'PostPolicy should forbid non-owners from updating posts.'
        );

        $this->assertTrue(
            $this->otherUser->cannot('update', $this->post),
            'User gate cannot check must reject non-owner update.'
        );
    }

    public function test_owner_can_update_their_own_post_policy(): void
    {
        $policy = app(PostPolicy::class);

        $this->assertTrue(
            $policy->update($this->owner, $this->post),
            'PostPolicy should allow owner to update post.'
        );

        $this->assertTrue(
            $this->owner->can('update', $this->post),
            'User gate can check must permit owner update.'
        );
    }

    public function test_owner_can_successfully_update_post_content_via_api(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->patchJson(route('api.post.update', $this->post), [
            'content' => 'Updated content from test.',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Updated content from test.');

        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'content' => 'Updated content from test.',
        ]);
    }

    public function test_owner_can_update_post_visibility_via_api(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->patchJson(route('api.post.update', $this->post), [
            'visibility' => PostVisibility::FOLLOWERS->value,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.visibility', PostVisibility::FOLLOWERS->value);

        $this->assertDatabaseHas('posts', [
            'id' => $this->post->id,
            'visibility' => PostVisibility::FOLLOWERS->value,
        ]);
    }

    public function test_owner_can_sync_media_attachments(): void
    {
        Sanctum::actingAs($this->owner);

        $media1 = $this->createMedia($this->owner, MediaStatus::Ready, 'media1.jpg');
        $media2 = $this->createMedia($this->owner, MediaStatus::Ready, 'media2.jpg');

        // Initial update: attach media1
        $response1 = $this->patchJson(route('api.post.update', $this->post), [
            'media_ids' => [$media1->id],
        ]);
        $response1->assertStatus(200);

        $this->assertDatabaseHas('post_media', [
            'post_id' => $this->post->id,
            'media_id' => $media1->id,
        ]);

        // Second update: replace media1 with media2
        $response2 = $this->patchJson(route('api.post.update', $this->post), [
            'media_ids' => [$media2->id],
        ]);
        $response2->assertStatus(200);

        $this->assertDatabaseMissing('post_media', [
            'post_id' => $this->post->id,
            'media_id' => $media1->id,
        ]);
        $this->assertDatabaseHas('post_media', [
            'post_id' => $this->post->id,
            'media_id' => $media2->id,
        ]);
    }

    public function test_non_owner_cannot_update_post_via_api(): void
    {
        Sanctum::actingAs($this->otherUser);

        $response = $this->patchJson(route('api.post.update', $this->post), [
            'content' => 'Hacked content.',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->patchJson(route('api.post.update', $this->post), [
            'content' => 'Unauthenticated content.',
        ]);

        $response->assertStatus(401);
    }

    public function test_post_cannot_be_stripped_of_both_content_and_media(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->patchJson(route('api.post.update', $this->post), [
            'content' => '',
            'media_ids' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_updating_post_dispatches_post_updated_event(): void
    {
        Event::fake([PostUpdated::class]);
        Sanctum::actingAs($this->owner);

        $response = $this->patchJson(route('api.post.update', $this->post), [
            'content' => 'Trigger moderation on update.',
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(PostUpdated::class, function (PostUpdated $event) {
            return $event->post->id === $this->post->id
                && in_array('content', $event->changedAttributes);
        });
    }
}
