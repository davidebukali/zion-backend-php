<?php

namespace Modules\SocialGraph\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Models\User;
use Modules\Posts\Actions\CreatePost;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Events\PostCreated;
use Modules\Posts\Models\Post;
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
        $listener = new FanOutPostListener();
        $listener->handle($event);

        Queue::assertPushed(FanOutPostJob::class, function ($job) use ($post) {
            return $job->postId === $post->id;
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
            return $job->postId === $post->id;
        });
    }
}
