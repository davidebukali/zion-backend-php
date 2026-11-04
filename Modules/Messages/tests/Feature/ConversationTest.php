<?php

namespace Modules\Messages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\Message;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Models\UserBlock;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private User $userC;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    private function makeMutualFollow(User $u1, User $u2): void
    {
        Follow::create([
            'follower_id' => $u1->id,
            'following_id' => $u2->id,
            'status' => FollowStatus::ACCEPTED,
        ]);
        Follow::create([
            'follower_id' => $u2->id,
            'following_id' => $u1->id,
            'status' => FollowStatus::ACCEPTED,
        ]);
    }

    public function test_starting_conversation_requires_accepted_follow(): void
    {
        Sanctum::actingAs($this->userA);

        // 1. No follow at all -> 403
        $response = $this->postJson(route('api.conversations.store'), [
            'recipient_id' => $this->userB->id,
        ]);
        $response->assertStatus(403);

        // 2. User B follows User A, but User A does NOT follow User B -> 403 for User A
        $reverseFollow = Follow::create([
            'follower_id' => $this->userB->id,
            'following_id' => $this->userA->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        $response = $this->postJson(route('api.conversations.store'), [
            'recipient_id' => $this->userB->id,
        ]);
        $response->assertStatus(403);
        $reverseFollow->delete();

        // 3. Pending follow from User A to User B -> 403
        $pendingFollow = Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::PENDING,
        ]);

        $response = $this->postJson(route('api.conversations.store'), [
            'recipient_id' => $this->userB->id,
        ]);
        $response->assertStatus(403);
        $pendingFollow->delete();

        // 4. One-way accepted follow from User A to User B (B does not follow A) -> 201
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        $response = $this->postJson(route('api.conversations.store'), [
            'recipient_id' => $this->userB->id,
        ]);
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_send_messages' => true,
                    'has_blocked_participant' => false,
                ],
            ]);

        $this->assertDatabaseHas('conversations', [
            'pair_key' => Conversation::pairKeyFor($this->userA->id, $this->userB->id),
        ]);

        // 5. Once the conversation exists, recipient (User B) can also message back even without following A
        $convId = $response->json('data.id');
        Sanctum::actingAs($this->userB);
        $replyResponse = $this->postJson(route('api.conversations.messages.store', $convId), [
            'body' => 'Thanks for reaching out!',
        ]);
        $replyResponse->assertStatus(201);
    }

    public function test_conversation_endpoint_is_deterministic_for_both_users(): void
    {
        $this->makeMutualFollow($this->userA, $this->userB);

        Sanctum::actingAs($this->userA);
        $resA = $this->postJson(route('api.conversations.store'), ['recipient_id' => $this->userB->id]);
        $resA->assertStatus(201);
        $convId = $resA->json('data.id');

        Sanctum::actingAs($this->userB);
        $resB = $this->postJson(route('api.conversations.store'), ['recipient_id' => $this->userA->id]);
        $resB->assertStatus(201);

        $this->assertEquals($convId, $resB->json('data.id'));
        $this->assertEquals(1, Conversation::count());
    }

    public function test_blocked_users_cannot_start_conversation(): void
    {
        $this->makeMutualFollow($this->userA, $this->userB);

        UserBlock::create([
            'blocker_id' => $this->userA->id,
            'blocked_id' => $this->userB->id,
        ]);

        Sanctum::actingAs($this->userA);
        $this->postJson(route('api.conversations.store'), ['recipient_id' => $this->userB->id])
            ->assertStatus(403);

        Sanctum::actingAs($this->userB);
        $this->postJson(route('api.conversations.store'), ['recipient_id' => $this->userA->id])
            ->assertStatus(403);
    }

    public function test_unfollowing_after_conversation_creation_still_allows_messaging(): void
    {
        $this->makeMutualFollow($this->userA, $this->userB);

        Sanctum::actingAs($this->userA);
        $conv = $this->postJson(route('api.conversations.store'), ['recipient_id' => $this->userB->id])->json('data.id');

        // User A unfollows User B
        Follow::where('follower_id', $this->userA->id)->where('following_id', $this->userB->id)->delete();

        // User A sends message
        $sendRes = $this->postJson(route('api.conversations.messages.store', $conv), [
            'body' => 'Still friends!',
        ]);
        $sendRes->assertStatus(201);
    }

    public function test_inbox_lists_conversations_with_unread_count_and_search(): void
    {
        $this->makeMutualFollow($this->userA, $this->userB);
        $this->makeMutualFollow($this->userA, $this->userC);

        Sanctum::actingAs($this->userA);
        $conv1Id = $this->postJson(route('api.conversations.store'), ['recipient_id' => $this->userB->id])->json('data.id');
        $conv2Id = $this->postJson(route('api.conversations.store'), ['recipient_id' => $this->userC->id])->json('data.id');

        // User B sends message to A
        Sanctum::actingAs($this->userB);
        $this->postJson(route('api.conversations.messages.store', $conv1Id), ['body' => 'Hello User A']);

        // User A checks inbox
        Sanctum::actingAs($this->userA);
        $inbox = $this->getJson(route('api.conversations.index'));
        $inbox->assertStatus(200);

        $data = $inbox->json('data');
        $this->assertCount(2, $data);
        $first = collect($data)->firstWhere('id', $conv1Id);
        $this->assertEquals(1, $first['unread_count']);
        $this->assertEquals('Hello User A', $first['last_message']['body']);

        // Search inbox for User C
        $search = $this->getJson(route('api.conversations.index', ['q' => 'User C']));
        $search->assertStatus(200);
        $this->assertCount(1, $search->json('data'));
        $this->assertEquals($conv2Id, $search->json('data.0.id'));
    }

    public function test_archive_mute_and_unarchive(): void
    {
        $this->makeMutualFollow($this->userA, $this->userB);

        Sanctum::actingAs($this->userA);
        $convId = $this->postJson(route('api.conversations.store'), ['recipient_id' => $this->userB->id])->json('data.id');

        $this->postJson(route('api.conversations.archive', $convId))->assertStatus(200);
        $this->postJson(route('api.conversations.mute', $convId))->assertStatus(200);

        $show = $this->getJson(route('api.conversations.show', $convId));
        $this->assertTrue($show->json('data.is_archived'));
        $this->assertTrue($show->json('data.is_muted'));

        // Normal inbox should exclude archived
        $inbox = $this->getJson(route('api.conversations.index'));
        $this->assertCount(0, $inbox->json('data'));

        // Archived inbox includes it
        $archivedInbox = $this->getJson(route('api.conversations.index', ['archived' => 'true']));
        $this->assertCount(1, $archivedInbox->json('data'));

        $this->postJson(route('api.conversations.unarchive', $convId))->assertStatus(200);
        $this->postJson(route('api.conversations.unmute', $convId))->assertStatus(200);

        $show2 = $this->getJson(route('api.conversations.show', $convId));
        $this->assertFalse($show2->json('data.is_archived'));
        $this->assertFalse($show2->json('data.is_muted'));
    }

    public function test_hide_conversation_clears_history_and_resurfaces_on_new_message(): void
    {
        $this->makeMutualFollow($this->userA, $this->userB);

        Sanctum::actingAs($this->userA);
        $convId = $this->postJson(route('api.conversations.store'), ['recipient_id' => $this->userB->id])->json('data.id');

        // Message 1
        $this->postJson(route('api.conversations.messages.store', $convId), ['body' => 'Old message']);

        // User A deletes conversation
        $this->deleteJson(route('api.conversations.destroy', $convId))->assertStatus(200);

        // Inbox should not show it
        $inbox = $this->getJson(route('api.conversations.index'));
        $this->assertCount(0, $inbox->json('data'));

        // User B sends new message later
        $this->travel(1)->second();
        Sanctum::actingAs($this->userB);
        $msgRes = $this->postJson(route('api.conversations.messages.store', $convId), ['body' => 'New message']);
        $msgRes->assertStatus(201);

        // User A checks inbox: thread resurfaced!
        Sanctum::actingAs($this->userA);
        $inboxResurfaced = $this->getJson(route('api.conversations.index'));
        $this->assertCount(1, $inboxResurfaced->json('data'));

        // Message list for A should only show "New message", not "Old message"
        $messages = $this->getJson(route('api.conversations.messages.index', $convId));
        $this->assertCount(1, $messages->json('data'));
        $this->assertEquals('New message', $messages->json('data.0.body'));
    }

    public function test_recipients_endpoint_returns_accepted_follows_without_requiring_mutual_follow(): void
    {
        // User A follows User B (accepted one-way follow: B does NOT follow A back)
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $this->userB->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        // User C follows User A (accepted, but A does NOT follow C back)
        Follow::create([
            'follower_id' => $this->userC->id,
            'following_id' => $this->userA->id,
            'status' => FollowStatus::ACCEPTED,
        ]);

        // User D: User A has a pending follow toward D
        $userD = User::create([
            'name' => 'User D',
            'email' => 'user_d@example.com',
            'password' => bcrypt('password'),
        ]);
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $userD->id,
            'status' => FollowStatus::PENDING,
        ]);

        // User E: User A follows E (accepted), but User A has blocked E
        $userE = User::create([
            'name' => 'User E',
            'email' => 'user_e@example.com',
            'password' => bcrypt('password'),
        ]);
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $userE->id,
            'status' => FollowStatus::ACCEPTED,
        ]);
        UserBlock::create([
            'blocker_id' => $this->userA->id,
            'blocked_id' => $userE->id,
        ]);

        // User F: User A follows F (accepted), but User F has blocked A
        $userF = User::create([
            'name' => 'User F',
            'email' => 'user_f@example.com',
            'password' => bcrypt('password'),
        ]);
        Follow::create([
            'follower_id' => $this->userA->id,
            'following_id' => $userF->id,
            'status' => FollowStatus::ACCEPTED,
        ]);
        UserBlock::create([
            'blocker_id' => $userF->id,
            'blocked_id' => $this->userA->id,
        ]);

        Sanctum::actingAs($this->userA);
        $response = $this->getJson(route('api.conversations.recipients'));
        $response->assertStatus(200);

        $data = $response->json('data');
        // Only User B should be returned
        $this->assertCount(1, $data);
        $this->assertEquals($this->userB->id, $data[0]['id']);
    }
}
