<?php

namespace Modules\Posts\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
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

    /**
     * Critical test: A user cannot update another user's post.
     */
    public function test_user_cannot_update_another_users_post(): void
    {
        $policy = app(PostPolicy::class);

        // Verify policy explicitly rejects non-owner update
        $this->assertFalse(
            $policy->update($this->otherUser, $this->post),
            'PostPolicy should forbid non-owners from updating posts.'
        );

        $this->assertTrue(
            $this->otherUser->cannot('update', $this->post),
            'User gate cannot check must reject non-owner update.'
        );
    }

    public function test_owner_can_update_their_own_post(): void
    {
        $policy = app(PostPolicy::class);

        // Verify policy permits owner update
        $this->assertTrue(
            $policy->update($this->owner, $this->post),
            'PostPolicy should allow owner to update post.'
        );

        $this->assertTrue(
            $this->owner->can('update', $this->post),
            'User gate can check must permit owner update.'
        );
    }
}
