<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;

class GetUserProfile
{
    /**
     * Get the profile for a given user.
     *
     * @param User $user
     * @return Profile
     */
    public function __invoke(User $user): Profile
    {
        $profile = $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'username' => null,
                'display_name' => $user->name,
            ]
        );

        return $profile->load(['avatar', 'cover']);
    }
}
