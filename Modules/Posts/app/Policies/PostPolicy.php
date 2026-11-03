<?php

namespace Modules\Posts\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Models\Post;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    /**
     * Determine whether the user can view the post based on visibility and moderation.
     */
    public function view(?User $user, Post $post): Response|bool
    {
        $isAuthor = $user && $user->id === $post->user_id;
        $isStaff = $user && ((bool) ($user->is_admin ?? false) || (bool) ($user->is_moderator ?? false));

        if ($post->moderation_status !== PostModerationStatus::APPROVED) {
            if (! $isAuthor && ! $isStaff) {
                return false;
            }
        }

        if ($isAuthor || $isStaff) {
            return true;
        }

        $authorIsPrivate = (bool) ($post->user?->profile?->is_private ?? false);
        $isFollower = $user && method_exists($user, 'isFollowing') && $user->isFollowing($post->user_id);

        if ($authorIsPrivate) {
            if (! $isFollower) {
                return Response::denyWithStatus(403, 'This account is private.');
            }

            return $post->visibility !== PostVisibility::PRIVATE;
        }

        if ($post->visibility === PostVisibility::PUBLIC) {
            return true;
        }

        if ($post->visibility === PostVisibility::FOLLOWERS) {
            return (bool) $isFollower;
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
