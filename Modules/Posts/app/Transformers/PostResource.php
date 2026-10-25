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

        if ($this->getAttribute('is_liked') !== null) {
            $isLiked = (bool) $this->getAttribute('is_liked');
        } elseif ($this->relationLoaded('likes')) {
            $isLiked = $viewer ? $this->likes->contains('user_id', $viewer->id) : false;
        } elseif ($viewer) {
            $isLiked = $this->likes()->where('user_id', $viewer->id)->exists();
        } else {
            $isLiked = false;
        }

        return [
            'id' => $this->id,
            'content' => $this->content,
            'visibility' => $this->visibility,
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comments_count' => (int) ($this->comments_count ?? 0),
            'is_liked' => $isLiked,
            'user' => new UserResource($this->whenLoaded('user')),
            'media' => MediaResource::collection(
                $this->whenLoaded('media')
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
