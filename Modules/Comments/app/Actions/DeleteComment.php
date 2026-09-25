<?php

namespace Modules\Comments\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Comments\Models\Comment;

class DeleteComment
{
    /**
     * Delete a comment.
     */
    public function __invoke(Comment $comment): void
    {
        DB::transaction(function () use ($comment) {
            $comment->post->decrement('comments_count');

            // Delete media files first
            foreach ($comment->media as $media) {
                if ($media->disk && $media->path) {
                    Storage::disk($media->disk)->delete($media->path);
                }
            }

            // Delete all relationships
            $comment->media()->delete();
            $comment->likes()->delete();

            $comment->delete();
        });
    }
}
