<?php

namespace Modules\Messages\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Transformers\UserResource;
use Modules\Media\Transformers\MediaResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('sanctum') ?? $request->user();
        $isDeletedForEveryone = $this->isDeletedForEveryone();

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'body' => $isDeletedForEveryone ? null : $this->body,
            'media' => $isDeletedForEveryone ? [] : MediaResource::collection($this->whenLoaded('media')),
            'reply_to' => $this->whenLoaded('replyTo', function () {
                if (! $this->replyTo) {
                    return null;
                }

                return [
                    'id' => $this->replyTo->id,
                    'sender' => new UserResource($this->replyTo->sender),
                    'body' => $this->replyTo->isDeletedForEveryone() ? null : $this->replyTo->body,
                    'is_deleted_for_everyone' => $this->replyTo->isDeletedForEveryone(),
                ];
            }),
            'reactions' => $this->whenLoaded('reactions', function () use ($viewer) {
                return $this->formatReactions($viewer);
            }),
            'is_edited' => $this->isEdited(),
            'edited_at' => $this->edited_at,
            'is_deleted_for_everyone' => $isDeletedForEveryone,
            'deleted_for_everyone_at' => $this->deleted_for_everyone_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function formatReactions($viewer): array
    {
        $grouped = [];
        $viewerReaction = null;

        foreach ($this->reactions as $reaction) {
            $emoji = $reaction->emoji;
            $grouped[$emoji] = ($grouped[$emoji] ?? 0) + 1;

            if ($viewer && $reaction->user_id === $viewer->id) {
                $viewerReaction = $emoji;
            }
        }

        return [
            'summary' => $grouped,
            'viewer_reaction' => $viewerReaction,
            'total_count' => count($this->reactions),
        ];
    }
}

