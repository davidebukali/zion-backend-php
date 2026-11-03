<?php

namespace Modules\Messages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ReportReason;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Models\Media;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\ConversationParticipant;
use Modules\Messages\Models\Message;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Models\UserBlock;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->userB = User::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => bcrypt('password'),
        ]);

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

        $pairKey = Conversation::pairKeyFor($this->userA->id, $this->userB->id);
        $this->conversation = Conversation::create([
            'pair_key' => $pairKey,
            'created_by' => $this->userA->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->userA->id,
        ]);

        ConversationParticipant::create([
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->userB->id,
        ]);
    }

    private function createMedia(User $user, MediaStatus $status = MediaStatus::Ready, string $filename = 'test.jpg'): Media
    {
        return Media::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'disk' => 'r2',
            'path' => 'uploads/' . uniqid() . '_' . $filename,
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'status' => $status,
        ]);
    }

    public function test_user_can_send_text_message(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Hello Bob!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'body' => 'Hello Bob!',
                    'sender_id' => $this->userA->id,
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'sender_id' => $this->userA->id,
            'body' => 'Hello Bob!',
        ]);
    }

    public function test_user_can_send_message_with_up_to_4_images(): void
    {
        Sanctum::actingAs($this->userA);

        $m1 = $this->createMedia($this->userA, MediaStatus::Ready, '1.jpg');
        $m2 = $this->createMedia($this->userA, MediaStatus::Ready, '2.jpg');

        $response = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Check these photos',
            'media_ids' => [$m1->id, $m2->id],
        ]);

        $response->assertStatus(201);
        $this->assertCount(2, $response->json('data.media'));

        $this->assertDatabaseHas('message_media', [
            'media_id' => $m1->id,
            'sort_order' => 0,
        ]);
    }

    public function test_user_cannot_attach_more_than_4_images(): void
    {
        Sanctum::actingAs($this->userA);

        $m1 = $this->createMedia($this->userA);
        $m2 = $this->createMedia($this->userA);
        $m3 = $this->createMedia($this->userA);
        $m4 = $this->createMedia($this->userA);
        $m5 = $this->createMedia($this->userA);

        $response = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'media_ids' => [$m1->id, $m2->id, $m3->id, $m4->id, $m5->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_reply_to_existing_message(): void
    {
        Sanctum::actingAs($this->userA);
        $msg1 = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'First message',
        ])->json('data.id');

        Sanctum::actingAs($this->userB);
        $response = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Replying to first message',
            'reply_to_message_id' => $msg1,
        ]);

        $response->assertStatus(201);
        $this->assertEquals($msg1, $response->json('data.reply_to.id'));
    }

    public function test_block_prevents_sending_messages_but_allows_reading_history(): void
    {
        Sanctum::actingAs($this->userA);
        $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Message before block',
        ]);

        // Alice blocks Bob
        UserBlock::create([
            'blocker_id' => $this->userA->id,
            'blocked_id' => $this->userB->id,
        ]);

        // Bob tries to send message -> 403
        Sanctum::actingAs($this->userB);
        $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Can you hear me?',
        ])->assertStatus(403);

        // Alice also cannot send message across the block -> 403
        Sanctum::actingAs($this->userA);
        $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Blocked message',
        ])->assertStatus(403);

        // But both can still list past history
        $listA = $this->getJson(route('api.conversations.messages.index', $this->conversation));
        $listA->assertStatus(200);
        $this->assertCount(1, $listA->json('data'));

        Sanctum::actingAs($this->userB);
        $listB = $this->getJson(route('api.conversations.messages.index', $this->conversation));
        $listB->assertStatus(200);
        $this->assertCount(1, $listB->json('data'));

        // Check conversation resource attributes
        $showB = $this->getJson(route('api.conversations.show', $this->conversation));
        $this->assertFalse($showB->json('data.can_send_messages'));
        $this->assertFalse($showB->json('data.has_blocked_participant'));

        Sanctum::actingAs($this->userA);
        $showA = $this->getJson(route('api.conversations.show', $this->conversation));
        $this->assertFalse($showA->json('data.can_send_messages'));
        $this->assertTrue($showA->json('data.has_blocked_participant'));
    }

    public function test_mark_as_read_updates_participant_read_position(): void
    {
        Sanctum::actingAs($this->userB);
        $msg = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Unread message',
        ])->json('data.id');

        Sanctum::actingAs($this->userA);
        $showBefore = $this->getJson(route('api.conversations.show', $this->conversation));
        $this->assertEquals(1, $showBefore->json('data.unread_count'));

        $this->postJson(route('api.conversations.read', $this->conversation))->assertStatus(200);

        $showAfter = $this->getJson(route('api.conversations.show', $this->conversation));
        $this->assertEquals(0, $showAfter->json('data.unread_count'));
        $this->assertEquals($msg, $showAfter->json('data.last_read_message_id'));
    }

    public function test_edit_message_within_15_minutes_and_after_15_minutes(): void
    {
        Sanctum::actingAs($this->userA);
        $msgId = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Original content',
        ])->json('data.id');

        // Edit immediately -> OK
        $editRes = $this->patchJson(route('api.messages.update', $msgId), [
            'body' => 'Updated content',
        ]);
        $editRes->assertStatus(200);
        $this->assertEquals('Updated content', $editRes->json('data.body'));
        $this->assertTrue($editRes->json('data.is_edited'));

        // Age the message past 15 minutes
        Message::where('id', $msgId)->update([
            'created_at' => now()->subMinutes(16),
        ]);

        $lateEdit = $this->patchJson(route('api.messages.update', $msgId), [
            'body' => 'Too late',
        ]);
        $lateEdit->assertStatus(403);
    }

    public function test_delete_for_me_only_affects_current_user(): void
    {
        Sanctum::actingAs($this->userA);
        $msgId = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Secret message',
        ])->json('data.id');

        // Alice deletes for herself
        $this->postJson(route('api.messages.delete-for-me', $msgId))->assertStatus(200);

        // Alice message list: 0
        $aliceList = $this->getJson(route('api.conversations.messages.index', $this->conversation));
        $this->assertCount(0, $aliceList->json('data'));

        // Bob message list: 1
        Sanctum::actingAs($this->userB);
        $bobList = $this->getJson(route('api.conversations.messages.index', $this->conversation));
        $this->assertCount(1, $bobList->json('data'));
        $this->assertEquals('Secret message', $bobList->json('data.0.body'));
    }

    public function test_delete_for_everyone_redacts_body_and_media_for_all(): void
    {
        Sanctum::actingAs($this->userA);
        $media = $this->createMedia($this->userA);
        $msgId = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Delete me',
            'media_ids' => [$media->id],
        ])->json('data.id');

        $this->deleteJson(route('api.messages.destroy', $msgId))->assertStatus(200);

        // Alice sees redacted
        $aliceList = $this->getJson(route('api.conversations.messages.index', $this->conversation));
        $msgA = $aliceList->json('data.0');
        $this->assertTrue($msgA['is_deleted_for_everyone']);
        $this->assertNull($msgA['body']);
        $this->assertEmpty($msgA['media']);

        // Bob also sees redacted
        Sanctum::actingAs($this->userB);
        $bobList = $this->getJson(route('api.conversations.messages.index', $this->conversation));
        $msgB = $bobList->json('data.0');
        $this->assertTrue($msgB['is_deleted_for_everyone']);
        $this->assertNull($msgB['body']);
        $this->assertEmpty($msgB['media']);
    }

    public function test_reactions_lifecycle(): void
    {
        Sanctum::actingAs($this->userA);
        $msgId = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'React to me',
        ])->json('data.id');

        // Bob reacts with heart
        Sanctum::actingAs($this->userB);
        $this->postJson(route('api.messages.reactions.store', $msgId), [
            'emoji' => '❤️',
        ])->assertStatus(200);

        $list = $this->getJson(route('api.conversations.messages.index', $this->conversation));
        $this->assertEquals('❤️', $list->json('data.0.reactions.viewer_reaction'));
        $this->assertEquals(1, $list->json('data.0.reactions.summary.❤️'));

        // Bob changes reaction to fire
        $this->postJson(route('api.messages.reactions.store', $msgId), [
            'emoji' => '🔥',
        ])->assertStatus(200);

        $list2 = $this->getJson(route('api.conversations.messages.index', $this->conversation));
        $this->assertEquals('🔥', $list2->json('data.0.reactions.viewer_reaction'));
        $this->assertEquals(1, $list2->json('data.0.reactions.total_count'));

        // Bob removes reaction
        $this->deleteJson(route('api.messages.reactions.destroy', $msgId))->assertStatus(200);
        $list3 = $this->getJson(route('api.conversations.messages.index', $this->conversation));
        $this->assertNull($list3->json('data.0.reactions.viewer_reaction'));
        $this->assertEquals(0, $list3->json('data.0.reactions.total_count'));
    }

    public function test_reporting_conversation_and_message(): void
    {
        Sanctum::actingAs($this->userA);
        $msgId = $this->postJson(route('api.conversations.messages.store', $this->conversation), [
            'body' => 'Bad message',
        ])->json('data.id');

        Sanctum::actingAs($this->userB);

        // Report conversation
        $this->postJson(route('api.conversations.report', $this->conversation), [
            'reason' => ReportReason::SPAM->value,
            'description' => 'Spamming',
        ])->assertStatus(201);

        // Report message
        $this->postJson(route('api.messages.report', $msgId), [
            'reason' => ReportReason::HARASSMENT->value,
            'description' => 'Offensive',
        ])->assertStatus(201);

        $this->assertDatabaseHas('message_reports', [
            'reporter_id' => $this->userB->id,
            'conversation_id' => $this->conversation->id,
            'message_id' => null,
            'reason' => ReportReason::SPAM->value,
        ]);

        $this->assertDatabaseHas('message_reports', [
            'reporter_id' => $this->userB->id,
            'conversation_id' => $this->conversation->id,
            'message_id' => $msgId,
            'reason' => ReportReason::HARASSMENT->value,
        ]);
    }
}

