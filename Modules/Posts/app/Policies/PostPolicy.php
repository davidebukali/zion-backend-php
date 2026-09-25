<?php

namespace Modules\Posts\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Models\Post;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    /**
     * Determine whether the user can view the post based on visibility.
     */
    public function view(?User $user, Post $post): bool
    {
        if ($post->visibility === PostVisibility::PUBLIC) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->id === $post->user_id) {
            return true;
        }

        if ($post->visibility === PostVisibility::FOLLOWERS) {
            return method_exists($user, 'isFollowing') && $user->isFollowing($post->user_id);
        }

        return false;
    }

    /**
     * Determine whether the user can update the post.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can delete the post.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id
            || (bool) ($user->is_admin ?? false)
            || (bool) ($user->is_moderator ?? false);
    }

    /**
     * Determine whether the user is authorized to attach media / modify the post.
     */
    public function attachMedia(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    /**
     * Determine whether an authenticated user can report the post.
     */
    public function report(User $user, Post $post): bool
    {
        return $user->id !== $post->user_id;
    }
}
