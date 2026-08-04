<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Auth\Transformers\UserResource;

class LoginUser
{
    /**
     * Authenticate a user and generate a token.
     */
    public function __invoke(array $data): array
    {
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        return [
            'user' => new UserResource($user),
            'token' => $token,
        ];
    }
}
