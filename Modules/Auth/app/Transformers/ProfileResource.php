<?php

namespace Modules\Auth\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\User;
use Modules\Media\Transformers\MediaResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'is_following' => $this->resolveIsFollowing($viewer),
            'avatar_media_id' => $this->avatar_media_id,
            'avatar' => new MediaResource($this->whenLoaded('avatar')),
            'cover_media_id' => $this->cover_media_id,
            'cover' => new MediaResource($this->whenLoaded('cover')),
            'website' => $this->website,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveIsFollowing(?User $viewer): bool
    {
        if (! $viewer || $viewer->id === $this->user_id) {
            return false;
        }

        return $viewer->isFollowing($this->user_id);
    }
}
