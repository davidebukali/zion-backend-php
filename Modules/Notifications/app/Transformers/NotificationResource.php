<?php

namespace Modules\Notifications\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Transformers\UserResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'actor_id' => $this->actor_id,
            'actor' => new UserResource($this->whenLoaded('actor')),
            'type' => $this->type,
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id' => $this->notifiable_id,
            'data' => $this->data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
