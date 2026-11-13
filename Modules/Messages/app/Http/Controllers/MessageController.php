<?php

namespace Modules\Messages\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Messages\Actions\DeleteMessageForEveryone;
use Modules\Messages\Actions\DeleteMessageForMe;
use Modules\Messages\Actions\ListMessages;
use Modules\Messages\Actions\ReactToMessage;
use Modules\Messages\Actions\RemoveMessageReaction;
use Modules\Messages\Actions\ReportMessage;
use Modules\Messages\Actions\SendMessage;
use Modules\Messages\Actions\UpdateMessage;
use Modules\Messages\Http\Requests\ReactMessageRequest;
use Modules\Messages\Http\Requests\ReportMessageRequest;
use Modules\Messages\Http\Requests\SendMessageRequest;
use Modules\Messages\Http\Requests\UpdateMessageRequest;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\Message;
use Modules\Messages\Transformers\MessageReactionResource;
use Modules\Messages\Transformers\MessageReportResource;
use Modules\Messages\Transformers\MessageResource;

class MessageController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Messages
     *
     * @subgroup Messages
     *
     * @authenticated
     *
     * List Messages
     *
     * Retrieve a cursor-paginated list of messages in a conversation. Messages are returned in chronological order and automatically filtered for any messages the user has deleted for themselves or cleared prior to.
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @queryParam per_page integer Number of messages per page. Default: 30. Example: 30
     * @queryParam cursor string Cursor token for pagination. Example: eyJpZCI6MX0
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Messages retrieved successfully.",
     *   "data": [
     *     {
     *       "id": "01JBD1MSG00000000000000001",
     *       "conversation_id": "01JBD1EXAMPLE00000000000001",
     *       "sender_id": "01JBD1USER0000000000000001",
     *       "sender": {
     *         "id": "01JBD1USER0000000000000001",
     *         "name": "John Doe",
     *         "username": "johndoe",
     *         "avatar": null
     *       },
     *       "body": "Hey there! How is the project going?",
     *       "media": [],
     *       "reply_to": null,
     *       "reactions": [],
     *       "is_edited": false,
     *       "edited_at": null,
     *       "is_deleted_for_everyone": false,
     *       "deleted_for_everyone_at": null,
     *       "created_at": "2026-11-03T18:15:00.000000Z",
     *       "updated_at": "2026-11-03T18:15:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "prev_cursor": null,
     *     "next_cursor": null,
     *     "per_page": 30
     *   }
     * }
     */
    public function index(Request $request, Conversation $conversation, ListMessages $listMessages)
    {
        $perPage = (int) $request->query('per_page', 30);
        $messages = $listMessages($request->user(), $conversation, $perPage);
        $paginated = MessageResource::collection($messages)->response()->getData(true);

        return $this->success(
            data: $paginated['data'] ?? [],
            message: 'Messages retrieved successfully.',
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
     * @subgroup Messages
     *
     * @authenticated
     *
     * Send Message
     *
     * Sends a new message in the conversation. Supports plain text, threaded replies, and up to 4 confirmed image attachments. Broadcasts `message.sent` over Reverb in real time.
     *
     * @urlParam conversation string required The ULID of the conversation. Example: 01JBD1EXAMPLE00000000000001
     *
     * @bodyParam body string Text body of the message. Required unless `media_ids` is provided. Max: 5000 characters. Example: Check out these wireframes!
     * @bodyParam media_ids string[] Optional array of media ULIDs (maximum 4). Each media must belong to the sender, be of type `image`, have status `ready`, and not already be attached. Example: ["01JBD1MEDIA0000000000000001"]
     * @bodyParam reply_to_message_id string Optional ULID of the message being replied to. Must belong to the same conversation. Example: 01JBD1MSG00000000000000001
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Message sent successfully.",
     *   "data": {
     *     "id": "01JBD1MSG00000000000000002",
     *     "conversation_id": "01JBD1EXAMPLE00000000000001",
     *     "sender_id": "01JBD1USER0000000000000001",
     *     "sender": {
     *       "id": "01JBD1USER0000000000000001",
     *       "name": "John Doe",
     *       "username": "johndoe",
     *       "avatar": null
     *     },
     *     "body": "Check out these wireframes!",
     *     "media": [],
     *     "reply_to": null,
     *     "reactions": [],
     *     "is_edited": false,
     *     "edited_at": null,
     *     "is_deleted_for_everyone": false,
     *     "deleted_for_everyone_at": null,
     *     "created_at": "2026-11-03T18:20:00.000000Z",
     *     "updated_at": "2026-11-03T18:20:00.000000Z"
     *   },
     *   "meta": null
     * }
     * @response 403 scenario="Blocked or Non Participant" {
     *   "success": false,
     *   "message": "You cannot send messages in this conversation.",
     *   "errors": null
     * }
     * @response 422 scenario="Media Limit Exceeded" {
     *   "success": false,
     *   "message": "You can attach at most 4 images per message.",
     *   "errors": null
     * }
     */
    public function store(SendMessageRequest $request, Conversation $conversation, SendMessage $sendMessage)
    {
        $message = $sendMessage($request->user(), $conversation, $request->validated());

        return $this->success(
            data: new MessageResource($message),
            message: 'Message sent successfully.',
            status: 201
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Messages
     *
     * @authenticated
     *
     * Edit Message
     *
     * Edits the body text of a message. Permitted only for the original author and strictly within 15 minutes of message creation. Broadcasts `message.updated` over Reverb.
     *
     * @urlParam message string required The ULID of the message to edit. Example: 01JBD1MSG00000000000000002
     *
     * @bodyParam body string required The updated text body. Max: 5000 characters. Example: Check out these updated wireframes!
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Message updated successfully.",
     *   "data": {
     *     "id": "01JBD1MSG00000000000000002",
     *     "conversation_id": "01JBD1EXAMPLE00000000000001",
     *     "sender_id": "01JBD1USER0000000000000001",
     *     "body": "Check out these updated wireframes!",
     *     "is_edited": true,
     *     "edited_at": "2026-11-03T18:25:00.000000Z",
     *     "is_deleted_for_everyone": false,
     *     "created_at": "2026-11-03T18:20:00.000000Z",
     *     "updated_at": "2026-11-03T18:25:00.000000Z"
     *   },
     *   "meta": null
     * }
     * @response 403 scenario="Edit Window Expired" {
     *   "success": false,
     *   "message": "Messages can only be edited within 15 minutes of sending.",
     *   "errors": null
     * }
     */
    public function update(UpdateMessageRequest $request, Message $message, UpdateMessage $updateMessage)
    {
        $updated = $updateMessage($request->user(), $message, $request->validated('body'));

        return $this->success(
            data: new MessageResource($updated),
            message: 'Message updated successfully.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Messages
     *
     * @authenticated
     *
     * Delete Message for Everyone
     *
     * Permanently redacts the body text and clears all media attachments of a message for all participants. Broadcasts `message.deleted` over Reverb. Can only be performed by the sender.
     *
     * @urlParam message string required The ULID of the message to delete. Example: 01JBD1MSG00000000000000002
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Message deleted for everyone.",
     *   "data": {
     *     "id": "01JBD1MSG00000000000000002",
     *     "conversation_id": "01JBD1EXAMPLE00000000000001",
     *     "sender_id": "01JBD1USER0000000000000001",
     *     "body": null,
     *     "media": [],
     *     "is_deleted_for_everyone": true,
     *     "deleted_for_everyone_at": "2026-11-03T18:26:00.000000Z"
     *   },
     *   "meta": null
     * }
     * @response 403 scenario="Not Message Author" {
     *   "success": false,
     *   "message": "You can only delete your own messages for everyone.",
     *   "errors": null
     * }
     */
    public function destroy(Request $request, Message $message, DeleteMessageForEveryone $deleteMessageForEveryone)
    {
        $deleted = $deleteMessageForEveryone($request->user(), $message);

        return $this->success(
            data: new MessageResource($deleted),
            message: 'Message deleted for everyone.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Messages
     *
     * @authenticated
     *
     * Delete Message for Me
     *
     * Hides the message from the authenticated user's message history only. The recipient will continue to see the message.
     *
     * @urlParam message string required The ULID of the message to hide. Example: 01JBD1MSG00000000000000002
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Message deleted for you.",
     *   "data": null,
     *   "meta": null
     * }
     */
    public function deleteForMe(Request $request, Message $message, DeleteMessageForMe $deleteMessageForMe)
    {
        $deleteMessageForMe($request->user(), $message);

        return $this->success(
            data: null,
            message: 'Message deleted for you.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Reactions
     *
     * @authenticated
     *
     * Add or Update Reaction
     *
     * Attaches or replaces the user's emoji reaction on a message. Each user can have at most one active reaction per message. Broadcasts `message.reaction_updated` over Reverb.
     *
     * @urlParam message string required The ULID of the message. Example: 01JBD1MSG00000000000000002
     *
     * @bodyParam emoji string required Single emoji string. Max: 16 characters. Example: 🔥
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Reaction updated successfully.",
     *   "data": {
     *     "id": "01JBD1REACT000000000000001",
     *     "message_id": "01JBD1MSG00000000000000002",
     *     "user_id": "01JBD1USER0000000000000001",
     *     "emoji": "🔥",
     *     "created_at": "2026-11-03T18:27:00.000000Z"
     *   },
     *   "meta": null
     * }
     */
    public function react(ReactMessageRequest $request, Message $message, ReactToMessage $reactToMessage)
    {
        $reaction = $reactToMessage($request->user(), $message, $request->validated('emoji'));

        return $this->success(
            data: new MessageReactionResource($reaction),
            message: 'Reaction updated successfully.',
            status: 200
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Reactions
     *
     * @authenticated
     *
     * Remove Reaction
     *
     * Removes the authenticated user's emoji reaction from a message. Broadcasts `message.reaction_updated` with `emoji: null` over Reverb.
     *
     * @urlParam message string required The ULID of the message. Example: 01JBD1MSG00000000000000002
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Reaction removed successfully.",
     *   "data": null,
     *   "meta": null
     * }
     */
    public function removeReaction(Request $request, Message $message, RemoveMessageReaction $removeMessageReaction)
    {
        $removeMessageReaction($request->user(), $message);

        return $this->success(
            data: null,
            message: 'Reaction removed successfully.'
        );
    }

    /**
     * @group Messages
     *
     * @subgroup Moderation
     *
     * @authenticated
     *
     * Report Message
     *
     * Submits a moderation report against a specific message.
     *
     * @urlParam message string required The ULID of the message to report. Example: 01JBD1MSG00000000000000002
     *
     * @bodyParam reason string required Category of violation (`spam`, `harassment`, `hate_speech`, `inappropriate_content`, `violence`, `other`). Example: hate_speech
     * @bodyParam description string Optional explanation. Max: 1000 characters. Example: Offensive language.
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Message reported successfully.",
     *   "data": {
     *     "id": "01JBD1REP00000000000000002",
     *     "conversation_id": "01JBD1EXAMPLE00000000000001",
     *     "message_id": "01JBD1MSG00000000000000002",
     *     "reason": "hate_speech",
     *     "description": "Offensive language.",
     *     "status": "pending",
     *     "created_at": "2026-11-03T18:28:00.000000Z"
     *   },
     *   "meta": null
     * }
     */
    public function report(ReportMessageRequest $request, Message $message, ReportMessage $reportMessage)
    {
        $report = $reportMessage($request->user(), $message, $request->validated());

        return $this->success(
            data: new MessageReportResource($report),
            message: 'Message reported successfully.',
            status: 201
        );
    }
}
