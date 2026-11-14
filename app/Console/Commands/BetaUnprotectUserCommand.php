<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Models\User;

class BetaUnprotectUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beta:unprotect {email : The email address of the account to unprotect}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove persistent status from an account so it can be purged in beta cleanups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User not found with email: {$email}");

            return self::FAILURE;
        }

        $user->is_persistent = false;
        $user->save();

        $this->info("Account '{$user->name}' ({$email}) persistent flag removed.");

        if ($user->isPersistent()) {
            $this->warn("Note: This account is still protected by config('beta') email or domain allowlists.");
        }

        return self::SUCCESS;
    }
}
