<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Models\User;

class BetaProtectUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beta:protect {email : The email address of the account to protect}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark an account as persistent so it and its data are immune from beta purging';

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

        $user->is_persistent = true;
        $user->save();

        $this->info("Account '{$user->name}' ({$email}) is now marked as persistent. Its data will not be purged.");

        return self::SUCCESS;
    }
}
