<?php

namespace Modules\SocialGraph\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Posts\Transformers\PostResource;

class FeedItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'published_at' => $this->published_at?->toISOString(),
            'post' => new PostResource($this->whenLoaded('post')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
