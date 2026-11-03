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

    public function destroy(Request $request, Conversation $conversation, HideConversation $hideConversation)
    {
        $hideConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation hidden successfully.'
        );
    }

    public function read(Request $request, Conversation $conversation, MarkConversationAsRead $markConversationAsRead)
    {
        $markConversationAsRead($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation marked as read.'
        );
    }

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

    public function archive(Request $request, Conversation $conversation, ArchiveConversation $archiveConversation)
    {
        $archiveConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation archived successfully.'
        );
    }

    public function unarchive(Request $request, Conversation $conversation, UnarchiveConversation $unarchiveConversation)
    {
        $unarchiveConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation unarchived successfully.'
        );
    }

    public function mute(Request $request, Conversation $conversation, MuteConversation $muteConversation)
    {
        $muteConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation muted successfully.'
        );
    }

    public function unmute(Request $request, Conversation $conversation, UnmuteConversation $unmuteConversation)
    {
        $unmuteConversation($request->user(), $conversation);

        return $this->success(
            data: null,
            message: 'Conversation unmuted successfully.'
        );
    }

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

