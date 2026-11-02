<?php

namespace Modules\Auth\Actions;

use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;
use Modules\Churches\Models\Church;

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
        if (!empty($data['church_name'])) {
            $church = Church::firstOrCreate(
                ['name' => trim($data['church_name'])],
                [
                    'location' => $data['church_location'] ?? null,
                    'created_by_user_id' => $user->id,
                ]
            );
            $data['church_id'] = $church->id;
        }
        unset($data['church_name'], $data['church_location']);

        $profile = $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'username' => null,
                'display_name' => $user->name,
            ]
        );

        $profile->update($data);

        return $profile->load(['avatar', 'cover', 'church']);
    }
}
