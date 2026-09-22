<?php

namespace Modules\Media\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request \): array
    {
        return [
            'id' => \->id,
            'user_id' => \->user_id,
            'type' => \->type,
            'mime_type' => \->mime_type,
            'size' => \->size,
            'width' => \->width,
            'height' => \->height,
            'status' => \->status,
            'checksum' => \->checksum,
            'metadata' => \->metadata,
            'created_at' => \->created_at,
            'updated_at' => \->updated_at,
        ];
    }
}
