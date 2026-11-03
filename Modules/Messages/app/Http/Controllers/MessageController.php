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

    public function store(SendMessageRequest $request, Conversation $conversation, SendMessage $sendMessage)
    {
        $message = $sendMessage($request->user(), $conversation, $request->validated());

        return $this->success(
            data: new MessageResource($message),
            message: 'Message sent successfully.',
            status: 201
        );
    }

    public function update(UpdateMessageRequest $request, Message $message, UpdateMessage $updateMessage)
    {
        $updated = $updateMessage($request->user(), $message, $request->validated('body'));

        return $this->success(
            data: new MessageResource($updated),
            message: 'Message updated successfully.'
        );
    }

    public function destroy(Request $request, Message $message, DeleteMessageForEveryone $deleteMessageForEveryone)
    {
        $deleted = $deleteMessageForEveryone($request->user(), $message);

        return $this->success(
            data: new MessageResource($deleted),
            message: 'Message deleted for everyone.'
        );
    }

    public function deleteForMe(Request $request, Message $message, DeleteMessageForMe $deleteMessageForMe)
    {
        $deleteMessageForMe($request->user(), $message);

        return $this->success(
            data: null,
            message: 'Message deleted for you.'
        );
    }

    public function react(ReactMessageRequest $request, Message $message, ReactToMessage $reactToMessage)
    {
        $reaction = $reactToMessage($request->user(), $message, $request->validated('emoji'));

        return $this->success(
            data: new MessageReactionResource($reaction),
            message: 'Reaction updated successfully.',
            status: 200
        );
    }

    public function removeReaction(Request $request, Message $message, RemoveMessageReaction $removeMessageReaction)
    {
        $removeMessageReaction($request->user(), $message);

        return $this->success(
            data: null,
            message: 'Reaction removed successfully.'
        );
    }

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

