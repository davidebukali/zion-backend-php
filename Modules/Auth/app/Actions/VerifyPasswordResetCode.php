<?php

namespace Modules\Auth\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyPasswordResetCode
{
    /**
     * Verify the 6-digit code for the given email with a 2-minute expiration.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function __invoke(array $data): array
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code.'],
            ]);
        }

        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(2)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
            throw ValidationException::withMessages([
                'code' => ['The verification code has expired. Please request a new one.'],
            ]);
        }

        if (! Hash::check((string) $data['code'], $record->token)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        // Code verified: delete used token record
        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        // Mark as verified for 5 minutes to allow password update
        Cache::put('password_reset_verified:' . $data['email'], true, now()->addMinutes(5));

        return [
            'message' => 'Verification code confirmed. You may now reset your password.',
        ];
    }
}
