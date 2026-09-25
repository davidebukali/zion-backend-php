<?php

namespace Modules\Media\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'status' => $this->status->value,
            'checksum' => $this->checksum,
            'url' => $this->getMediaUrl(),
            'sort_order' => $this->when(
                $this->pivot !== null &&
                isset($this->pivot->sort_order),
                fn () => $this->pivot->sort_order
            ),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function getMediaUrl(): ?string
    {
        if ($this->status?->value !== 'ready') {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
