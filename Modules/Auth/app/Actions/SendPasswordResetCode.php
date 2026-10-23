<?php

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Emails\PasswordResetCodeMail;
use Modules\Auth\Models\User;

class SendPasswordResetCode
{
    /**
     * Generate and send a 6-digit password reset code to the user's email.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function __invoke(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('passwords.user', ['attribute' => 'email']) ?: 'We could not find an account with this email address.'],
            ]);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        Mail::to($user->email)->send(new PasswordResetCodeMail($code, $user->name));

        return [
            'message' => 'Password reset code has been sent to your email.',
        ];
    }
}
