<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;

class UpdateUserProfile
{
    /**
     * Update the profile for a given user.
     *
     * @param User $user
     * @param array $data
     * @return Profile
     */
    public function __invoke(User $user, array $data): Profile
    {
        $profile = $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'username' => null,
                'display_name' => $user->name,
            ]
        );

        $profile->update($data);

        return $profile->load(['avatar', 'cover']);
    }
}
