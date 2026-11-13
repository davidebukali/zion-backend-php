<?php

namespace Tests\Unit;

use Illuminate\Auth\Access\Response;
use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;
use Modules\Posts\Policies\PostPolicy;
use Tests\TestCase;

class PostPolicyPrivacyTest extends TestCase
{
    private PostPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PostPolicy;
    }

    public function test_non_follower_is_denied_with_403_when_author_profile_is_private(): void
    {
        $author = new User(['name' => 'Author']);
        $author->id = '01author';

        $profile = new Profile(['is_private' => true]);
        $author->setRelation('profile', $profile);

        $post = new Post([
            'visibility' => PostVisibility::PUBLIC,
            'moderation_status' => PostModerationStatus::APPROVED,
        ]);
        $post->user_id = $author->id;
        $post->setRelation('user', $author);

        $stranger = $this->createMock(User::class);
        $stranger->id = '01stranger';
        $stranger->method('isFollowing')->with($author->id)->willReturn(false);

        $response = $this->policy->view($stranger, $post);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->denied());
        $this->assertEquals(403, $response->status());
        $this->assertEquals('This account is private.', $response->message());
    }

    public function test_follower_can_view_public_and_followers_post_of_private_author(): void
    {
        $author = new User(['name' => 'Author']);
        $author->id = '01author';

        $profile = new Profile(['is_private' => true]);
        $author->setRelation('profile', $profile);

        $publicPost = new Post([
            'visibility' => PostVisibility::PUBLIC,
            'moderation_status' => PostModerationStatus::APPROVED,
        ]);
        $publicPost->user_id = $author->id;
        $publicPost->setRelation('user', $author);

        $followersPost = new Post([
            'visibility' => PostVisibility::FOLLOWERS,
            'moderation_status' => PostModerationStatus::APPROVED,
        ]);
        $followersPost->user_id = $author->id;
        $followersPost->setRelation('user', $author);

        $follower = $this->createMock(User::class);
        $follower->id = '01follower';
        $follower->method('isFollowing')->with($author->id)->willReturn(true);

        $this->assertTrue($this->policy->view($follower, $publicPost));
        $this->assertTrue($this->policy->view($follower, $followersPost));
    }

    public function test_follower_cannot_view_private_post_of_private_author(): void
    {
        $author = new User(['name' => 'Author']);
        $author->id = '01author';

        $profile = new Profile(['is_private' => true]);
        $author->setRelation('profile', $profile);

        $privatePost = new Post([
            'visibility' => PostVisibility::PRIVATE,
            'moderation_status' => PostModerationStatus::APPROVED,
        ]);
        $privatePost->user_id = $author->id;
        $privatePost->setRelation('user', $author);

        $follower = $this->createMock(User::class);
        $follower->id = '01follower';
        $follower->method('isFollowing')->with($author->id)->willReturn(true);

        $this->assertFalse($this->policy->view($follower, $privatePost));
    }

    public function test_author_can_always_view_their_own_posts(): void
    {
        $author = new User(['name' => 'Author']);
        $author->id = '01author';

        $profile = new Profile(['is_private' => true]);
        $author->setRelation('profile', $profile);

        $privatePost = new Post([
            'visibility' => PostVisibility::PRIVATE,
            'moderation_status' => PostModerationStatus::APPROVED,
        ]);
        $privatePost->user_id = $author->id;
        $privatePost->setRelation('user', $author);

        $this->assertTrue($this->policy->view($author, $privatePost));
    }

    public function test_guest_can_view_public_post_when_author_is_public(): void
    {
        $author = new User(['name' => 'Author']);
        $author->id = '01author';

        $profile = new Profile(['is_private' => false]);
        $author->setRelation('profile', $profile);

        $publicPost = new Post([
            'visibility' => PostVisibility::PUBLIC,
            'moderation_status' => PostModerationStatus::APPROVED,
        ]);
        $publicPost->user_id = $author->id;
        $publicPost->setRelation('user', $author);

        $this->assertTrue($this->policy->view(null, $publicPost));
    }
}
