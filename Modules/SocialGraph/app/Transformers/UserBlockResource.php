<?php

namespace Modules\SocialGraph\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Transformers\UserResource;

class UserBlockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blocker_id' => $this->blocker_id,
            'blocked_id' => $this->blocked_id,
            'blocked' => new UserResource($this->whenLoaded('blocked')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
