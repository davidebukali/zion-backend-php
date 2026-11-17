<?php

namespace App\Console\Commands;

use App\Mail\BetaInviteMail;
use App\Models\WaitlistEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBetaInvites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waitlist:invite
        {limit=15 : Maximum number of uninvited users to email}
        {--emails=* : Optional specific email addresses to invite (creates a waitlist record if not already present)}
        {--dry-run : Preview matched recipients without sending or updating DB}
        {--force : Force sending even if recipient was already invited}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch early access beta invites to uninvited waitlist users in controlled batches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $optInUrl = (string) config('services.google_play.opt_in_url');

        if (blank($optInUrl)) {
            $this->error('GOOGLE_PLAY_OPT_IN_URL is not configured in config/services.php or .env.');

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->argument('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $rawEmails = (array) $this->option('emails');
        $allPassedEmails = collect($rawEmails)
            ->flatMap(fn ($val) => explode(',', (string) $val))
            ->map(fn ($email) => strtolower(trim($email)))
            ->filter()
            ->unique();

        if ($allPassedEmails->isNotEmpty()) {
            $validEmails = $allPassedEmails->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
            $invalidEmails = $allPassedEmails->diff($validEmails);

            foreach ($invalidEmails as $invalid) {
                $this->warn("Skipping invalid email format: {$invalid}");
            }

            if ($validEmails->isEmpty()) {
                $this->error('No valid email addresses provided.');

                return self::FAILURE;
            }

            $entries = collect();

            foreach ($validEmails as $email) {
                $entry = WaitlistEntry::where('email', $email)->first();

                if (! $entry) {
                    $entry = new WaitlistEntry([
                        'email' => $email,
                        'source' => 'manual_invite',
                        'status' => 'pending',
                    ]);

                    if (! $dryRun) {
                        $entry->save();
                    }
                } elseif ($entry->invited_at !== null && ! $force) {
                    $this->warn("Skipping {$email}: already invited on {$entry->invited_at->toDateTimeString()}. (Use --force to re-invite)");

                    continue;
                }

                $entries->push($entry);
            }
        } else {
            $query = WaitlistEntry::query()->whereNull('invited_at');
            $query->orderByRaw("CASE WHEN email LIKE '%@gmail.com' THEN 0 ELSE 1 END");
            $query->orderBy('created_at', 'asc')->limit($limit);
            $entries = $query->get();
        }

        if ($entries->isEmpty()) {
            $this->info('No uninvited waitlist entries found matching the criteria.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN: No emails will be queued, and database records will not be modified.');

            $this->table(
                ['#', 'ID', 'Email', 'Source', 'Registered At'],
                $entries->map(fn (WaitlistEntry $entry, int $index) => [
                    $index + 1,
                    $entry->id ?? 'New (not on waitlist)',
                    $entry->email,
                    $entry->source ?? 'manual_invite',
                    $entry->created_at?->toDateTimeString() ?? 'Now',
                ])
            );

            $this->info("Found {$entries->count()} recipient(s) ready for invite.");

            return self::SUCCESS;
        }

        $this->info("Queueing {$entries->count()} beta invite email(s)...");

        $bar = $this->output->createProgressBar($entries->count());
        $bar->start();

        foreach ($entries as $index => $entry) {
            // Stagger queue dispatch by 5 seconds per recipient to respect Resend rate limit
            Mail::to($entry->email)->later(
                now()->addSeconds($index * 5),
                new BetaInviteMail($optInUrl)
            );

            $entry->update([
                'invited_at' => now(),
                'status' => 'invited',
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Successfully queued {$entries->count()} beta invite(s).");

        return self::SUCCESS;
    }
}
