<?php

namespace Modules\Posts\Actions;

use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;
use Modules\Posts\Transformers\PostResource;
use Modules\Posts\Enums\PostVisibility;
use Illuminate\Support\Facades\DB;
use Modules\Posts\Actions\AttachMediaToPost;

class CreatePost
{
    public function __construct(
        private readonly AttachMediaToPost $attachMediaToPost
    ) {
    }

    /**
     * Create a new post for a user.
     */
    public function __invoke(User $user, array $data): PostResource
    {
        return DB::transaction(function () use ($data, $user) {                    
            $mediaIds = $data['media_ids'] ?? [];

            unset($data['media_ids']);

            $post = $user->posts()->create([
                'content' => $data['content'] ?? null,
                'visibility' => $data['visibility'] ?? PostVisibility::PUBLIC->value,
            ]);

            $this->attachMediaToPost->handle(
                post: $post,
                mediaIds: $mediaIds,
                userId: $user->id
            );

            return new PostResource($post->load('media'));
        });
    }
}
