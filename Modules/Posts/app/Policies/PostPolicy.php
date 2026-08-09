<?php

namespace Modules\Posts\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
