<?php

namespace Modules\Notifications\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Models\User;
use Modules\Interactions\Actions\LikePosts;
use Modules\Notifications\Jobs\SendPushNotificationJob;
use Modules\Notifications\Models\DeviceToken;
use Modules\Notifications\Services\ExpoPushService;
use Modules\Posts\Models\Post;
use Tests\TestCase;

class ExpoPushServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $liker;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::create([
            'name' => 'Author User',
            'email' => 'author_push@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->liker = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane_push@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post = $this->author->posts()->create([
            'content' => 'Sample post for push notifications test',
            'visibility' => 'public',
        ]);
    }

    public function test_expo_push_service_sends_payload_formatted_for_mobile_deep_linking(): void
    {
        DeviceToken::create([
            'user_id' => $this->author->id,
            'token' => 'ExponentPushToken[mobile-device-123]',
            'platform' => 'ios',
        ]);

        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response([
                'data' => [
                    [
                        'status' => 'ok',
                        'id' => 'ticket-12345',
                    ],
                ],
            ], 200),
        ]);

        $service = app(ExpoPushService::class);
        $sentCount = $service->sendToUser(
            $this->author->id,
            'Jane Doe liked your post',
            'Jane liked your post.',
            [
                'postId' => $this->post->id,
                'type' => 'post_liked',
            ]
        );

        $this->assertSame(1, $sentCount);

        Http::assertSent(function ($request) {
            $chunk = $request->data();

            return isset($chunk[0])
                && $chunk[0]['to'] === 'ExponentPushToken[mobile-device-123]'
                && $chunk[0]['title'] === 'Jane Doe liked your post'
                && $chunk[0]['body'] === 'Jane liked your post.'
                && $chunk[0]['sound'] === 'default'
                && $chunk[0]['badge'] === 1
                && ($chunk[0]['data']['postId'] ?? null) === $this->post->id
                && ($chunk[0]['data']['type'] ?? null) === 'post_liked';
        });
    }

    public function test_expo_push_service_automatically_prunes_unregistered_device_token(): void
    {
        DeviceToken::create([
            'user_id' => $this->author->id,
            'token' => 'ExponentPushToken[unregistered-device-456]',
            'platform' => 'android',
        ]);

        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response([
                'data' => [
                    [
                        'status' => 'error',
                        'message' => 'The recipient device is not registered with Expo',
                        'details' => [
                            'error' => 'DeviceNotRegistered',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(ExpoPushService::class);
        $service->sendToUser(
            $this->author->id,
            'Test Title',
            'Test Body'
        );

        $this->assertDatabaseMissing('device_tokens', [
            'token' => 'ExponentPushToken[unregistered-device-456]',
        ]);
    }

    public function test_post_like_dispatches_push_notification_job(): void
    {
        Queue::fake([SendPushNotificationJob::class]);

        $likeAction = app(LikePosts::class);
        $likeAction($this->liker, $this->post);

        Queue::assertPushed(SendPushNotificationJob::class, function ($job) {
            return $job->recipientId === $this->author->id
                && ($job->data['postId'] ?? null) === $this->post->id
                && ($job->data['type'] ?? null) === 'post_liked'
                && str_contains($job->title, 'Jane Doe liked your post');
        });
    }
}
