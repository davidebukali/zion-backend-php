<?php

namespace Modules\Auth\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Media\Transformers\MediaResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'avatar_media_id' => $this->avatar_media_id,
            'avatar' => new MediaResource($this->whenLoaded('avatar')),
            'cover_media_id' => $this->cover_media_id,
            'cover' => new MediaResource($this->whenLoaded('cover')),
            'website' => $this->website,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
