<?php

namespace App\Listeners;

use App\Models\WaitlistEntry;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;

class UpdateWaitlistOnUserRegistered
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $email = strtolower(trim((string) $event->user->email));

        $updatedCount = WaitlistEntry::where('email', $email)
            ->where('status', '!=', 'onboarded')
            ->update([
                'status' => 'onboarded',
            ]);

        if ($updatedCount > 0) {
            Log::info("Waitlist entry transitioned to onboarded for email: {$email}");
        }
    }
}
