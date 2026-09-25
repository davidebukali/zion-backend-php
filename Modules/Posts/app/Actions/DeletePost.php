<?php

namespace Modules\Posts\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Posts\Models\Post;

class DeletePost
{
    /**
     * Delete a post.
     */
    public function __invoke(Post $post): void
    {
        // Delete media files first
        foreach ($post->media as $media) {
            if ($media->disk && $media->path) {
                Storage::disk($media->disk)->delete($media->path);
            }
        }

        // Delete all relationships
        $post->media()->delete();
        $post->likes()->delete();
        $post->comments()->delete();

        $post->delete();
    }
}
