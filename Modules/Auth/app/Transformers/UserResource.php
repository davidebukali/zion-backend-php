<?php

namespace Modules\Auth\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_following' => $this->resolveIsFollowing($viewer),
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function resolveIsFollowing(?User $viewer): bool
    {
        if (! $viewer || $viewer->id === $this->id) {
            return false;
        }

        if ($this->getAttribute('is_following') !== null) {
            return (bool) $this->getAttribute('is_following');
        }

        if ($this->relationLoaded('followers')) {
            return $this->followers->contains(function ($follow) use ($viewer) {
                $status = $follow->status instanceof \BackedEnum ? $follow->status->value : $follow->status;

                return $follow->follower_id === $viewer->id && $status === FollowStatus::ACCEPTED->value;
            });
        }

        return $viewer->isFollowing($this->resource);
    }
}
