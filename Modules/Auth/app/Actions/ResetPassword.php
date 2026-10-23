<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;

class ResetPassword
{
    /**
     * Reset the user password after successful verification.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function __invoke(array $data): array
    {
        $email = $data['email'];

        $isVerified = Cache::pull('password_reset_verified:' . $email);
        if (! $isVerified) {
            throw ValidationException::withMessages([
                'email' => ['Verification code was not verified or verification session has expired. Please request a new code.'],
            ]);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['We could not find an account with this email address.'],
            ]);
        }

        $user->update([
            'password' => $data['password'],
        ]);

        // Revoke active sessions / personal access tokens for security
        $user->tokens()->delete();

        return [
            'message' => 'Password has been reset successfully.',
        ];
    }
}
