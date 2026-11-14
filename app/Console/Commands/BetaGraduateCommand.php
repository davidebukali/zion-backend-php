<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Models\User;

class BetaGraduateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beta:graduate
        {--dry-run : Report accounts that would be promoted without modifying them}
        {--force : Perform promotion without confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote all beta tester accounts to permanent persistent status for production launch';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $purgeableUsers = User::query()->where('is_persistent', false)->get(['id', 'name', 'email', 'created_at']);
        $totalCount = $purgeableUsers->count();

        if ($totalCount === 0) {
            $this->info('All accounts are already marked as persistent. No action required.');

            return self::SUCCESS;
        }

        $this->info("Found {$totalCount} account(s) currently marked as beta testers (is_persistent = false).");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: The following accounts would be promoted to persistent:');
            $rows = $purgeableUsers->take(25)->map(fn ($user) => [
                $user->id,
                $user->email,
                $user->name ?? 'N/A',
                $user->created_at?->toIso8601String() ?? 'N/A',
            ])->toArray();

            $this->table(['ULID', 'Email', 'Name', 'Created At'], $rows);

            if ($totalCount > 25) {
                $this->info('... and '.($totalCount - 25).' more accounts.');
            }

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                "Are you sure you want to graduate all {$totalCount} accounts to permanent persistent status?",
                false
            );

            if (! $confirmed) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $updatedCount = User::query()->where('is_persistent', false)->update(['is_persistent' => true]);

        $this->info("Successfully graduated {$updatedCount} account(s) to permanent persistent status.");
        $this->newLine();
        $this->comment('Production Transition Checklist:');
        $this->line('1. Set BETA_MODE=false in your production .env environment.');
        $this->line('2. Run: php artisan config:cache');
        $this->line('3. All future registrations will now default to persistent (is_persistent = true).');

        return self::SUCCESS;
    }
}
