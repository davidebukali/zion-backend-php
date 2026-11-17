<?php

namespace App\Console\Commands;

use App\Models\WaitlistEntry;
use Illuminate\Console\Command;

class ExportWaitlistTesters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitlist:export-testers
        {limit=20 : Maximum number of uninvited Gmail addresses to export}
        {--lines : Output each email on a new line instead of comma-separated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export uninvited Gmail addresses from the waitlist for pasting into Google Play Console closed testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = max(1, (int) $this->argument('limit'));

        $emails = WaitlistEntry::query()
            ->whereNull('invited_at')
            ->where('email', 'LIKE', '%@gmail.com')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->pluck('email');

        if ($emails->isEmpty()) {
            $this->warn('No uninvited Gmail addresses found on the waitlist.');

            return self::SUCCESS;
        }

        if ($this->option('lines')) {
            foreach ($emails as $email) {
                $this->line($email);
            }
        } else {
            $this->line($emails->implode(', '));
        }

        return self::SUCCESS;
    }
}
