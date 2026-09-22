<?php

namespace Modules\Media\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Models\User;
use Modules\Media\Models\Media;

class MediaPolicy
{
    use HandlesAuthorization;

    public function __construct() {}

    public function view(User $user, Media $media): bool
    {
        return $user->id === $media->user_id;
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->id === $media->user_id;
    }
}
