<?php

namespace Modules\Messages\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Media\Enums\MediaStatus;
use Modules\Media\Transformers\MediaResource;
use Modules\Messages\Models\Message;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('sanctum') ?? $request->user();
        $viewerParticipant = $this->participants->firstWhere('user_id', $viewer?->id);
        $partnerParticipant = $this->participants->firstWhere('user_id', '!=', $viewer?->id);
        $partnerUser = $partnerParticipant?->user;

        $hasBlocked = $viewer && $partnerUser ? $viewer->hasBlocked($partnerUser->id) : false;
        $isBlockedBy = $viewer && $partnerUser ? $viewer->isBlockedBy($partnerUser->id) : false;
        $canSend = ! ($hasBlocked || $isBlockedBy);

        $unreadCount = 0;
        if ($viewer && $viewerParticipant) {
            $unreadCount = Message::query()
                ->where('conversation_id', $this->id)
                ->where('sender_id', '!=', $viewer->id)
                ->whereNull('deleted_for_everyone_at')
                ->visibleTo($viewer, $viewerParticipant->cleared_at)
                ->when($viewerParticipant->last_read_message_id, function ($q, $lastReadId) {
                    $q->where('id', '>', $lastReadId);
                })
                ->count();
        }

        $lastMessage = $this->resolveLastMessage($viewer, $viewerParticipant?->cleared_at);

        return [
            'id' => $this->id,
            'created_by' => $this->created_by,
            'recipient' => $partnerUser ? $this->formatRecipient($partnerUser) : null,
            'last_message' => $lastMessage ? new MessageResource($lastMessage) : null,
            'unread_count' => $unreadCount,
            'last_read_message_id' => $viewerParticipant?->last_read_message_id,
            'partner_last_read_message_id' => $partnerParticipant?->last_read_message_id,
            'is_muted' => $viewerParticipant?->isMuted() ?? false,
            'is_archived' => $viewerParticipant?->isArchived() ?? false,
            'can_send_messages' => $canSend,
            'has_blocked_participant' => $hasBlocked,
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function formatRecipient($user): array
    {
        $profile = $user->profile;
        $avatar = $profile?->avatar;
        $statusValue = $avatar?->status instanceof MediaStatus ? $avatar->status->value : ($avatar?->status?->value ?? $avatar?->status);
        $avatarReady = $avatar && $statusValue === MediaStatus::Ready->value;

        return [
            'id' => $user->id,
            'name' => $profile?->display_name ?: $user->name,
            'username' => $profile?->username,
            'avatar' => $avatarReady ? new MediaResource($avatar) : null,
        ];
    }

    private function resolveLastMessage($viewer, $clearedAt): ?Message
    {
        if (! $viewer) {
            return $this->latestMessage;
        }

        return Message::query()
            ->where('conversation_id', $this->id)
            ->visibleTo($viewer, $clearedAt)
            ->with(['sender.profile.avatar', 'media', 'reactions'])
            ->orderByDesc('id')
            ->first();
    }
}

