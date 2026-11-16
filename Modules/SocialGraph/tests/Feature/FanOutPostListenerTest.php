<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Models\User;
use Modules\Comments\Events\CommentCreated;
use Modules\Comments\Models\Comment;
use Modules\Interactions\Events\PostLiked;
use Modules\Interactions\Events\PostReposted;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Events\PostCreated;
use Modules\SocialGraph\Jobs\FanOutPostJob;
use Modules\SocialGraph\Listeners\FanOutPostListener;
use Tests\TestCase;

class FanOutPostListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_fan_out_post_listener_dispatches_fan_out_post_job(): void
    {
        Queue::fake();

        $author = User::create([
            'name' => 'Author User',
            'email' => 'author_listener@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $author->posts()->create([
            'content' => 'Test Post for Listener',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);

        $event = new PostCreated($post);
        $listener = new FanOutPostListener;
        $listener->handle($event);

        Queue::assertPushed(FanOutPostJob::class, function ($job) use ($post, $author) {
            return $job->postId === $post->id
                && $job->type === 'post'
                && $job->actorId === $author->id;
        });
    }

    public function test_post_created_event_triggers_fan_out_post_listener(): void
    {
        Queue::fake();

        $author = User::create([
            'name' => 'Author User',
            'email' => 'author_listener_event@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $author->posts()->create([
            'content' => 'Test Post for Event Trigger',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);

        event(new PostCreated($post));

        Queue::assertPushed(FanOutPostJob::class, function ($job) use ($post) {
            return $job->postId === $post->id && $job->type === 'post';
        });
    }

    public function test_post_reposted_event_dispatches_fan_out_post_job(): void
    {
        Queue::fake();

        $author = User::create([
            'name' => 'Author User',
            'email' => 'author_repost@example.com',
            'password' => bcrypt('password'),
        ]);

        $reposter = User::create([
            'name' => 'Reposter User',
            'email' => 'reposter@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $author->posts()->create([
            'content' => 'Post to be Reposted',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);

        event(new PostReposted($post, $reposter));

        Queue::assertPushed(FanOutPostJob::class, function ($job) use ($post, $reposter) {
            return $job->postId === $post->id
                && $job->type === 'repost'
                && $job->actorId === $reposter->id;
        });
    }

    public function test_post_liked_event_does_not_dispatch_fan_out_post_job(): void
    {
        Queue::fake();

        $author = User::create([
            'name' => 'Author User',
            'email' => 'author_liked@example.com',
            'password' => bcrypt('password'),
        ]);

        $liker = User::create([
            'name' => 'Liker User',
            'email' => 'liker@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $author->posts()->create([
            'content' => 'Post to be Liked',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);

        event(new PostLiked($post, $liker));

        Queue::assertNotPushed(FanOutPostJob::class);
    }

    public function test_comment_created_event_does_not_dispatch_fan_out_post_job(): void
    {
        Queue::fake();

        $author = User::create([
            'name' => 'Author User',
            'email' => 'author_commented@example.com',
            'password' => bcrypt('password'),
        ]);

        $commenter = User::create([
            'name' => 'Commenter User',
            'email' => 'commenter@example.com',
            'password' => bcrypt('password'),
        ]);

        $post = $author->posts()->create([
            'content' => 'Post to be Commented',
            'visibility' => PostVisibility::PUBLIC->value,
        ]);

        $comment = new Comment([
            'user_id' => $commenter->id,
            'post_id' => $post->id,
            'body' => 'Nice post!',
        ]);

        event(new CommentCreated($comment));

        Queue::assertNotPushed(FanOutPostJob::class);
    }
}
