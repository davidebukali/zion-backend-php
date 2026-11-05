<?php

namespace Modules\Messages\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Auth\Transformers\UserResource;
use Modules\Messages\Actions\ArchiveConversation;
use Modules\Messages\Actions\HideConversation;
use Modules\Messages\Actions\ListConversations;
use Modules\Messages\Actions\ListMessageableRecipients;
use Modules\Messages\Actions\MarkConversationAsRead;
use Modules\Messages\Actions\MuteConversation;
use Modules\Messages\Actions\ReportConversation;
use Modules\Messages\Actions\StartOrGetConversation;
use Modules\Messages\Actions\UnarchiveConversation;
use Modules\Messages\Actions\UnmuteConversation;
use Modules\Messages\Events\TypingStarted;
use Modules\Messages\Http\Requests\ReportMessageRequest;
use Modules\Messages\Http\Requests\StartConversationRequest;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Transformers\ConversationResource;
use Modules\Messages\Transformers\MessageReportResource;

class ConversationController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * List Conversations
     *
     * Retrieve a cursor-paginated inbox list of 1-on-1 conversations for the authenticated user, including unread count, last message preview, and recipient details (`recipient.username` and `recipient.avatar` may be null).
     *
     * @queryParam per_page integer Number of conversations per page. Default: 20. Example: 20
     * @queryParam cursor string Cursor token for pagination. Example: eyJpZCI6MX0
     * @queryParam q string Search conversations by participant name or username. Example: john
     * @queryParam archived boolean Filter by archived status. Default: false. Example: false
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Conversations retrieved successfully.",
     *   "data": [
     *     {
     *       "id": "01JBD1EXAMPLE00000000000001",
     *       "created_by": "01JBD1USER0000000000000001",
     *       "recipient": {
     *         "id": "01JBD1USER0000000000000002",
     *         "name": "Jane Doe",
     *         "username": "janedoe",
     *         "avatar": null
     *       },
     *       "last_message": {
     *         "id": "01JBD1MSG00000000000000001",
     *         "sender_id": "01JBD1USER0000000000000002",
     *         "body": "Hey, how are you?",
     *         "is_deleted_for_everyone": false,
     *         "created_at": "2026-11-03T18:30:00.000000Z"
     *       },
     *       "unread_count": 2,
     *       "last_read_message_id": null,
     *       "partner_last_read_message_id": null,
     *       "is_muted": false,
     *       "is_archived": false,
     *       "can_send_messages": true,
     *       "has_blocked_participant": false,
     *       "last_message_at": "2026-11-03T18:30:00.000000Z",
     *       "created_at": "2026-11-03T18:00:00.000000Z",
     *       "updated_at": "2026-11-03T18:30:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "prev_cursor": null,
     *     "next_cursor": null,
     *     "per_page": 20
     *   }
     * }
     */
    public function index(Request $request, ListConversations $listConversations)
    {
        $perPage = (int) $request->query('per_page', 20);
        $search = $request->query('q');
        $archived = filter_var($request->query('archived', false), FILTER_VALIDATE_BOOLEAN);

        $conversations = $listConversations($request->user(), $search, $archived, $perPage);
        $paginated = ConversationResource::collection($conversations)->response()->getData(true);

        return $this->success(
            data: $paginated['data'] ?? [],
            message: 'Conversations retrieved successfully.',
            status: 200,
            meta: [
                'prev_cursor' => $paginated['meta']['prev_cursor'] ?? null,
                'next_cursor' => $paginated['meta']['next_cursor'] ?? null,
                'per_page' => $paginated['meta']['per_page'] ?? $perPage,
            ]
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * List Messageable Recipients
     *
     * Retrieve a cursor-paginated list of users that the authenticated user follows with an accepted follow status eligible for starting a direct conversation. Excludes users with an active block relationship.
     *
     * @queryParam per_page integer Number of recipients per page. Default: 20. Example: 20
     * @queryParam cursor string Cursor token for pagination. Example: eyJpZCI6MX0
     * @queryParam q string Search recipients by name or username. Example: jane
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Messageable recipients retrieved successfully.",
     *   "data": [
     *     {
     *       "id": "01JBD1USER0000000000000002",
     *       "name": "Jane Doe",
     *       "username": "janedoe",
     *       "avatar": null
     *     }
     *   ],
     *   "meta": {
     *     "prev_cursor": null,
     *     "next_cursor": null,
     *     "per_page": 20
     *   }
     * }
     */
    public function recipients(Request $request, ListMessageableRecipients $listMessageableRecipients)
    {
        $perPage = (int) $request->query('per_page', 20);
        $search = $request->query('q');

        $recipients = $listMessageableRecipients($request->user(), $search, $perPage);
        $paginated = UserResource::collection($recipients)->response()->getData(true);

        return $this->success(
            data: $paginated['data'] ?? [],
            message: 'Messageable recipients retrieved successfully.',
            status: 200,
            meta: [
                'prev_cursor' => $paginated['meta']['prev_cursor'] ?? null,
                'next_cursor' => $paginated['meta']['next_cursor'] ?? null,
                'per_page' => $paginated['meta']['per_page'] ?? $perPage,
            ]
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * Start or Get Conversation
     *
     * Initiates a deterministic 1-on-1 direct conversation with an accepted follow, or retrieves the existing conversation if one already exists (`recipient.username` and `recipient.avatar` may be null).
     *
     * @bodyParam recipient_id string required The ULID of the recipient user. Example: 01JBD1USER0000000000000002
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Conversation initialized successfully.",
     *   "data": {
     *     "id": "01JBD1EXAMPLE00000000000001",
     *     "created_by": "01JBD1USER0000000000000001",
     *     "recipient": {
     *       "id": "01JBD1USER0000000000000002",
     *       "name": "Jane Doe",
     *       "username": "janedoe",
     *       "avatar": null
     *     },
     *     "last_message": null,
     *     "unread_count": 0,
     *     "last_read_message_id": null,
     *     "partner_last_read_message_id": null,
     *     "is_muted": false,
     *     "is_archived": false,
     *     "can_send_messages": true,
     *     "has_blocked_participant": false,
     *     "last_message_at": "2026-11-03T18:00:00.000000Z",
     *     "created_at": "2026-11-03T18:00:00.000000Z",
     *     "updated_at": "2026-11-03T18:00:00.000000Z"
     *   },
     *   "meta": null
     * }
     * @response 403 scenario="Not Following or Blocked" {
     *   "success": false,
     *   "message": "You can only start conversations with users you follow.",
     *   "errors": null
     * }
     * @response 422 scenario="Self Conversation" {
     *   "success": false,
     *   "message": "You cannot start a conversation with yourself.",
     *   "errors": null
     * }
     */
    public function store(StartConversationRequest $request, StartOrGetConversation $startOrGetConversation)
    {
        $recipient = User::findOrFail($request->validated('recipient_id'));
        $conversation = $startOrGetConversation($request->user(), $recipient);

        return $this->success(
            data: new ConversationResource($conversation),
            message: 'Conversation initialized successfully.',
            status: 201
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * Get Conversation Details
     *
     * Retrieve the details of a specific conversation, including participant states, read receipts, and message transmission permissions (`recipient.username` and `recipient.avatar` may be null).
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Conversation retrieved successfully.",
     *   "data": {
     *     "id": "01JBD1EXAMPLE00000000000001",
     *     "created_by": "01JBD1USER0000000000000001",
     *     "recipient": {
     *       "id": "01JBD1USER0000000000000002",
     *       "name": "Jane Doe",
     *       "username": "janedoe",
     *       "avatar": null
     *     },
     *     "last_message": null,
     *     "unread_count": 0,
     *     "last_read_message_id": null,
     *     "partner_last_read_message_id": null,
     *     "is_muted": false,
     *     "is_archived": false,
     *     "can_send_messages": true,
     *     "has_blocked_participant": false,
     *     "last_message_at": "2026-11-03T18:30:00.000000Z",
     *     "created_at": "2026-11-03T18:00:00.000000Z",
     *     "updated_at": "2026-11-03T18:30:00.000000Z"
     *   },
     *   "meta": null
     * }
     * @response 403 scenario="Not Participant" {
     *   "success": false,
     *   "message": "You are not a participant in this conversation.",
     *   "errors": null
     * }
     */
    public function show(Request $request, Conversation $conversation)
    {
        if (Gate::forUser($request->user())->denies('view', $conversation)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        $conversation->load([
            'participants.user.profile.avatar',
            'latestMessage.sender.profile.avatar',
            'latestMessage.media',
            'latestMessage.reactions',
        ]);

        return $this->success(
            data: new ConversationResource($conversation),
            message: 'Conversation retrieved successfully.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * Hide and Clear Conversation
     *
     * Hides the conversation from the authenticated user's inbox and clears their message history. If the other user sends a new message later, the conversation resurfaces displaying only subsequent messages.
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Conversation hidden successfully.",
     *   "data": null,
     *   "meta": null
     * }
     */
    public function destroy(Request $request, Conversation $conversation, HideConversation $hideConversation)
    {
        $hideConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation hidden successfully.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * Mark Conversation as Read
     *
     * Marks all received messages in the conversation as read, advancing the user's read watermark and broadcasting `message.read` to the sender over Reverb.
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Conversation marked as read.",
     *   "data": null,
     *   "meta": null
     * }
     */
    public function read(Request $request, Conversation $conversation, MarkConversationAsRead $markConversationAsRead)
    {
        $markConversationAsRead($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation marked as read.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * Send Typing Indicator
     *
     * Broadcasts a real-time `typing.started` event over Reverb to the other participant on private channel `private-conversations.{conversation_id}`.
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Typing notification sent.",
     *   "data": null,
     *   "meta": null
     * }
     * @response 403 scenario="Blocked" {
     *   "success": false,
     *   "message": "You cannot send typing indicators in this conversation.",
     *   "errors": null
     * }
     */
    public function typing(Request $request, Conversation $conversation)
    {
        if (Gate::forUser($request->user())->denies('sendMessage', $conversation)) {
            abort(403, 'You cannot send typing indicators in this conversation.');
        }

        event(new TypingStarted($conversation, $request->user()));

        return $this->success(
            data: null,
            message: 'Typing notification sent.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * Archive Conversation
     *
     * Archives the conversation for the authenticated user, moving it out of the active inbox.
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Conversation archived successfully.",
     *   "data": null,
     *   "meta": null
     * }
     */
    public function archive(Request $request, Conversation $conversation, ArchiveConversation $archiveConversation)
    {
        $archiveConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation archived successfully.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * Unarchive Conversation
     *
     * Restores an archived conversation to the user's primary inbox view.
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Conversation unarchived successfully.",
     *   "data": null,
     *   "meta": null
     * }
     */
    public function unarchive(Request $request, Conversation $conversation, UnarchiveConversation $unarchiveConversation)
    {
        $unarchiveConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation unarchived successfully.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * Mute Conversation
     *
     * Mutes push notifications for incoming messages in this conversation.
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Conversation muted successfully.",
     *   "data": null,
     *   "meta": null
     * }
     */
    public function mute(Request $request, Conversation $conversation, MuteConversation $muteConversation)
    {
        $muteConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation muted successfully.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Conversations
     *
     * @authenticated
     *
     * Unmute Conversation
     *
     * Unmutes notifications for incoming messages in this conversation.
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Conversation unmuted successfully.",
     *   "data": null,
     *   "meta": null
     * }
     */
    public function unmute(Request $request, Conversation $conversation, UnmuteConversation $unmuteConversation)
    {
        $unmuteConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation unmuted successfully.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Moderation
     *
     * @authenticated
     *
     * Report Conversation
     *
     * Submits a moderation report against an entire conversation thread.
     *
     * @urlParam conversation string required The ULID of the conversation to report. Example: 01JBD1EXAMPLE00000000000001
     * @bodyParam reason string required The category of violation. Must be one of: `spam`, `harassment`, `hate_speech`, `inappropriate_content`, `violence`, `other`. Example: harassment
     * @bodyParam description string Optional additional details for the moderation team. Max: 1000 characters. Example: Abusive language in thread.
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Conversation reported successfully.",
     *   "data": {
     *     "id": "01JBD1REP00000000000000001",
     *     "conversation_id": "01JBD1EXAMPLE00000000000001",
     *     "message_id": null,
     *     "reason": "harassment",
     *     "description": "Abusive language in thread.",
     *     "status": "pending",
     *     "created_at": "2026-11-03T19:00:00.000000Z"
     *   },
     *   "meta": null
     * }
     */
    public function report(ReportMessageRequest $request, Conversation $conversation, ReportConversation $reportConversation)
    {
        $report = $reportConversation($request->user(), $conversation, $request->validated());

        return $this->success(
            data: new MessageReportResource($report),
            message: 'Conversation reported successfully.',
            status: 201
        );
    }
}

