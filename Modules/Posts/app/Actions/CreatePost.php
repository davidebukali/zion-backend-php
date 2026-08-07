<?php

namespace Modules\Posts\Actions;

use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;
use Modules\Posts\Transformers\PostResource;
use Modules\Posts\Enums\PostVisibility;

class CreatePost
{
    /**
     * Create a new post for a user.
     */
    public function __invoke(User $user, array $data): PostResource
    {
        $post = $user->posts()->create([
            'content' => $data['content'] ?? null,
            'visibility' => $data['visibility'] ?? PostVisibility::PUBLIC->value,
        ]);

        return new PostResource($post);
    }
}
