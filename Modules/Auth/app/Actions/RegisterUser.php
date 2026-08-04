<?php

namespace Modules\Auth\Actions;

use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Modules\Auth\Transformers\UserResource;

class RegisterUser
{
    public function __invoke(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return new UserResource($user);
    }
}