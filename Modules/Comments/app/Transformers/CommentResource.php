<?php

namespace Modules\Comments\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $isDeleted = $this->deleted_at !== null;

        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'user_id' => $isDeleted ? null : $this->user_id,
            'parent_comment_id' => $this->parent_comment_id,
            'content' => $isDeleted ? '[This comment has been deleted]' : $this->content,
            'likes_count' => $isDeleted ? 0 : $this->likes_count,
            'replies_count' => $this->replies_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
