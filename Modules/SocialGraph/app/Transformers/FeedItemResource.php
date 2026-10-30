<?php

namespace Modules\SocialGraph\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Transformers\UserResource;
use Modules\Posts\Transformers\PostResource;

class FeedItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $type = $this->type ?? 'post';
        $actor = ($type !== 'post' && $this->relationLoaded('actor') && $this->actor !== null)
            ? $this->actor
            : null;

        return [
            'id' => $this->id,
            'type' => $type,
            'published_at' => $this->published_at?->toISOString(),
            'actor' => $actor ? new UserResource($actor) : null,
            'post' => new PostResource($this->whenLoaded('post')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
