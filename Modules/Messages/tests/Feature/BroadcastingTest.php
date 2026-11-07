<?php

namespace Modules\Messages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Messages\Actions\SendMessage;
use Modules\Messages\Actions\StartOrGetConversation;
use Modules\Messages\Events\MessageDeleted;
use Modules\Messages\Events\MessageReactionUpdated;
use Modules\Messages\Events\MessageRead;
use Modules\Messages\Events\MessageSent;
use Modules\Messages\Events\MessageUpdated;
use Modules\Messages\Events\TypingStarted;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\Message;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Models\UserBlock;
use Tests\TestCase;

class BroadcastingTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private User $userC;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);

        require base_path('routes/channels.php');

        $this->userA = User::create([
            'name' => 'User A',
            'email' => 'user_a@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->userB = User::create([
            'name' => 'User B',
            'email' => 'user_b@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->userC = User::create([
            'name' => 'User C',
            'email' => 'user_c@example.com',
            'password' => bcrypt('password'),
        ]);

        // Setup mutual follow between A and B
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::ACCEPTED,
        ]);
        Follow::create([
            'follower_id' => $this->userB->id,
            'following_id' => $this->userA->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        $action = app(StartOrGetConversation::class);
        $this->conversation = $action($this->userA, $this->userB);
    }

    public function test_user_can_authorize_own_private_channel(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-users.'.$this->userA->id,
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['auth']);
    }

    public function test_user_cannot_authorize_another_users_private_channel(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-users.'.$this->userB->id,
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(403);
    }

    public function test_participant_can_authorize_conversation_channel(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-conversations.'.$this->conversation->id,
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['auth']);
    }

    public function test_participant_can_authorize_conversation_presence_channel(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'presence-conversations.'.$this->conversation->id,
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['auth', 'channel_data']);

        $channelData = json_decode($response->json('channel_data'), true);
        $this->assertEquals((string) $this->userA->id, $channelData['user_id']);
        $this->assertEquals($this->userA->name, $channelData['user_info']['name']);
    }

    public function test_non_participant_cannot_authorize_conversation_presence_channel(): void
    {
        Sanctum::actingAs($this->userC);

        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'presence-conversations.'.$this->conversation->id,
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_authorize_online_presence_channel(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'presence-online',
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['auth', 'channel_data']);

        $channelData = json_decode($response->json('channel_data'), true);
        $this->assertEquals((string) $this->userA->id, $channelData['user_id']);
        $this->assertEquals($this->userA->name, $channelData['user_info']['name']);
    }

    public function test_guest_cannot_authorize_online_presence_channel(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'presence-online',
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_participant_cannot_authorize_conversation_channel(): void
    {
        Sanctum::actingAs($this->userC);

        $response = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-conversations.'.$this->conversation->id,
            'socket_id' => '1234.5678',
        ]);

        $response->assertStatus(403);
    }

    public function test_blocked_participant_cannot_authorize_conversation_channel(): void
    {
        UserBlock::create([
            'blocker_id' => $this->userA->id,
            'blocked_id' => $this->userB->id,
        ]);

        // Blocker cannot subscribe to active message channel
        Sanctum::actingAs($this->userA);
        $responseA = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-conversations.'.$this->conversation->id,
            'socket_id' => '1234.5678',
        ]);
        $responseA->assertStatus(403);

        // Blocked user cannot subscribe
        Sanctum::actingAs($this->userB);
        $responseB = $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-conversations.'.$this->conversation->id,
            'socket_id' => '1234.5678',
        ]);
        $responseB->assertStatus(403);
    }

    public function test_participant_can_trigger_typing_indicator(): void
    {
        Event::fake([TypingStarted::class]);
        Sanctum::actingAs($this->userA);

        $response = $this->postJson("/api/v1/conversations/{$this->conversation->id}/typing");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Typing notification sent.',
            ]);

        Event::assertDispatched(TypingStarted::class, function (TypingStarted $event) {
            return $event->conversation->id === $this->conversation->id
                && $event->user->id === $this->userA->id
                && $event->broadcastAs() === 'typing.started'
                && $event->broadcastOn()[0]->name === 'private-conversations.'.$this->conversation->id;
        });
    }

    public function test_blocked_participant_cannot_trigger_typing_indicator(): void
    {
        Event::fake([TypingStarted::class]);

        UserBlock::create([
            'blocker_id' => $this->userA->id,
            'blocked_id' => $this->userB->id,
        ]);

        Sanctum::actingAs($this->userB);

        $response = $this->postJson("/api/v1/conversations/{$this->conversation->id}/typing");

        $response->assertStatus(403);
        Event::assertNotDispatched(TypingStarted::class);
    }

    public function test_message_sent_event_broadcasts_on_dual_channels(): void
    {
        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->userA->id,
            'body' => 'Hello Realtime!',
        ]);

        // Load participants relation to ensure dual-channel broadcast computes recipient
        $this->conversation->load('participants');
        $message->setRelation('conversation', $this->conversation);

        $event = new MessageSent($message);

        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertSame('private-conversations.'.$this->conversation->id, $channels[0]->name);
        $this->assertSame('private-users.'.$this->userB->id, $channels[1]->name);

        $this->assertSame('message.sent', $event->broadcastAs());
        $this->assertSame('broadcasts', $event->broadcastQueue);

        $payload = $event->broadcastWith();
        $this->assertSame($message->id, $payload['id']);
        $this->assertSame('Hello Realtime!', $payload['body']);
        $this->assertSame($this->userA->id, $payload['sender']['id']);
    }

    public function test_message_updated_event_broadcasts_on_conversation_channel(): void
    {
        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->userA->id,
            'body' => 'Original text',
        ]);

        $message->update(['body' => 'Edited text', 'edited_at' => now()]);

        $event = new MessageUpdated($message);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertSame('private-conversations.'.$this->conversation->id, $channels[0]->name);
        $this->assertSame('message.updated', $event->broadcastAs());
        $this->assertSame('broadcasts', $event->broadcastQueue);

        $payload = $event->broadcastWith();
        $this->assertSame('Edited text', $payload['body']);
        $this->assertNotNull($payload['edited_at']);
    }

    public function test_message_deleted_event_broadcasts_on_conversation_channel(): void
    {
        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->userA->id,
            'body' => 'To be deleted',
            'deleted_for_everyone_at' => now(),
        ]);

        $event = new MessageDeleted($message);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertSame('private-conversations.'.$this->conversation->id, $channels[0]->name);
        $this->assertSame('message.deleted', $event->broadcastAs());
        $this->assertSame('broadcasts', $event->broadcastQueue);

        $payload = $event->broadcastWith();
        $this->assertSame($message->id, $payload['id']);
        $this->assertTrue($payload['is_deleted_for_everyone']);
        $this->assertNotNull($payload['deleted_for_everyone_at']);
    }

    public function test_message_reaction_updated_event_broadcasts_on_conversation_channel(): void
    {
        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->userA->id,
            'body' => 'Nice picture',
        ]);

        $reaction = $message->reactions()->create([
            'user_id' => $this->userB->id,
            'emoji' => '🔥',
        ]);

        $event = new MessageReactionUpdated($message, $this->userB, $reaction);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertSame('private-conversations.'.$this->conversation->id, $channels[0]->name);
        $this->assertSame('message.reaction_updated', $event->broadcastAs());
        $this->assertSame('broadcasts', $event->broadcastQueue);

        $payload = $event->broadcastWith();
        $this->assertSame($message->id, $payload['message_id']);
        $this->assertSame($this->userB->id, $payload['user_id']);
        $this->assertSame('🔥', $payload['emoji']);
    }

    public function test_message_read_event_broadcasts_on_conversation_channel(): void
    {
        $message = Message::create([
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->userA->id,
            'body' => 'Did you read this?',
        ]);

        $event = new MessageRead($this->conversation, $this->userB, $message->id);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertSame('private-conversations.'.$this->conversation->id, $channels[0]->name);
        $this->assertSame('message.read', $event->broadcastAs());
        $this->assertSame('broadcasts', $event->broadcastQueue);

        $payload = $event->broadcastWith();
        $this->assertSame($this->conversation->id, $payload['conversation_id']);
        $this->assertSame($this->userB->id, $payload['reader_id']);
        $this->assertSame($message->id, $payload['last_read_message_id']);
        $this->assertNotNull($payload['read_at']);
    }
}
