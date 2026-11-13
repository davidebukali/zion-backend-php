<?php

namespace Modules\Auth\Actions;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Auth\Transformers\UserResource;
use Modules\Churches\Models\Church;

class RegisterUser
{
    public function __invoke(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $name = $data['name'] ?? null;

            $user = User::create([
                'name' => $name ?? strstr($data['email'], '@', true),
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $churchId = $data['church_id'] ?? null;
            if (! empty($data['church_name'])) {
                $church = Church::firstOrCreate(
                    ['name' => trim($data['church_name'])],
                    [
                        'location' => $data['church_location'] ?? null,
                        'created_by_user_id' => $user->id,
                    ]
                );
                $churchId = $church->id;
            }

            $user->profile()->create([
                'username' => $name,
                'display_name' => $name,
                'account_type' => $data['account_type'] ?? null,
                'category' => $data['category'] ?? null,
                'location' => $data['location'] ?? null,
                'church_id' => $churchId,
            ]);

            event(new Registered($user));

            return (array) new UserResource($user->load('profile.church'));
        });
    }
}
