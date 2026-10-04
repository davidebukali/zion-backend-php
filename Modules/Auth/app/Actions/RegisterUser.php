<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Auth\Transformers\UserResource;

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

            $user->profile()->create([
                'username' => $name,
                'display_name' => $name,
            ]);

            return (array) new UserResource($user->load('profile'));
        });
    }
}