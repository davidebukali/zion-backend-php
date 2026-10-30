<?php

namespace Modules\Posts\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Transformers\UserResource;
use Modules\Media\Transformers\MediaResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $userResource = new UserResource($this->whenLoaded('user'));

        return [
            'id' => $this->id,
            'content' => $this->content,
            'visibility' => $this->visibility,
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'reposts_count' => (int) ($this->reposts_count ?? 0),
            'is_liked' => $this->resolveInteractionFlag($viewer, 'is_liked', 'likes'),
            'is_reposted' => $this->resolveInteractionFlag($viewer, 'is_reposted', 'reposts'),
            'user' => $userResource,
            'author' => $userResource,
            'media' => MediaResource::collection(
                $this->whenLoaded('media')
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function resolveInteractionFlag($viewer, string $attribute, string $relation): bool
    {
        if ($this->getAttribute($attribute) !== null) {
            return (bool) $this->getAttribute($attribute);
        }

        if ($this->relationLoaded($relation)) {
            return $viewer ? $this->{$relation}->contains('user_id', $viewer->id) : false;
        }

        if ($viewer) {
            return $this->{$relation}()->where('user_id', $viewer->id)->exists();
        }

        return false;
    }
}
