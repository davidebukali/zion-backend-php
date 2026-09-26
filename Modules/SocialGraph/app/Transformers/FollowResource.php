<?php
namespace Modules\SocialGraph\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Transformers\UserResource;

class FollowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'follower_id' => $this->follower_id,
            'following_id' => $this->following_id,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'follower' => new UserResource($this->whenLoaded('follower')),
            'following' => new UserResource($this->whenLoaded('following')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
