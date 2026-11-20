<?php

namespace Modules\Auth\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\User;
use Modules\Churches\Transformers\ChurchResource;
use Modules\Media\Transformers\MediaResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('sanctum') ?? $request->user() ?? auth('sanctum')->user() ?? auth()->user();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'account_type' => $this->account_type instanceof \BackedEnum ? $this->account_type->value : $this->account_type,
            'category' => $this->category,
            'location' => $this->location,
            'church_id' => $this->church_id,
            'church' => new ChurchResource($this->whenLoaded('church')),
            'is_following' => $this->resolveIsFollowing($viewer),
            'avatar_media_id' => $this->avatar_media_id,
            'avatar' => new MediaResource($this->whenLoaded('avatar')),
            'cover_media_id' => $this->cover_media_id,
            'cover' => new MediaResource($this->whenLoaded('cover')),
            'website' => $this->website,
            'is_private' => (bool) ($this->is_private ?? false),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveIsFollowing(?User $viewer): bool
    {
        if (! $viewer || $viewer->id === $this->user_id) {
            return false;
        }

        if ($this->getAttribute('is_following') !== null) {
            return (bool) $this->getAttribute('is_following');
        }

        if ($this->relationLoaded('user') && $this->user?->getAttribute('is_following') !== null) {
            return (bool) $this->user->getAttribute('is_following');
        }

        return $viewer->isFollowing($this->user_id);
    }
}
