<?php

namespace Modules\SocialGraph\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Auth\Transformers\UserResource;
use Modules\SocialGraph\Enums\UserReportReason;
use Modules\SocialGraph\Enums\UserReportStatus;

class UserReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reasonEnum = $this->reason instanceof UserReportReason
            ? $this->reason
            : UserReportReason::tryFrom((string) $this->reason);

        $statusValue = $this->status instanceof UserReportStatus
            ? $this->status->value
            : (string) $this->status;

        return [
            'id' => $this->id,
            'reporter_id' => $this->reporter_id,
            'reported_user_id' => $this->reported_user_id,
            'reason' => $reasonEnum?->value ?? (string) $this->reason,
            'reason_label' => $reasonEnum?->label(),
            'description' => $this->description,
            'status' => $statusValue,
            'reported_user' => new UserResource($this->whenLoaded('reportedUser')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
