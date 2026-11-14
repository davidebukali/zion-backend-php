<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\User;
use Modules\Media\Models\Media;
use Modules\Messages\Models\Conversation;
use Modules\Posts\Actions\DeletePost;

class BetaPurgeTestersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beta:purge-testers
        {--dry-run : Report accounts and data that would be purged without deleting}
        {--force : Force the operation to run without confirmation prompts in production}
        {--days= : Only purge accounts created more than X days ago}
        {--email= : Purge a specific single tester account}
        {--posts-only : Only delete posts created by testers, keeping their user accounts active}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge temporary beta tester accounts or their posts while preserving persistent accounts';

    /**
     * Execute the console command.
     */
    public function handle(DeletePost $deletePost): int
    {
        $query = User::query()->where('is_persistent', false)->with(['profile', 'posts.media']);

        // Extra safeguard: exclude protected emails from config
        $protectedEmails = array_map('strtolower', config('beta.protected_emails', []));
        if (! empty($protectedEmails)) {
            $query->whereNotIn(DB::raw('LOWER(email)'), $protectedEmails);
        }

        // Extra safeguard: exclude protected domains from config
        $protectedDomains = config('beta.protected_domains', []);
        foreach ($protectedDomains as $domain) {
            $cleanedDomain = addcslashes(trim((string) $domain), '%_\\');
            if ($cleanedDomain !== '') {
                $query->where('email', 'not ilike', "%@{$cleanedDomain}");
            }
        }

        // Filter by specific email
        if ($email = $this->option('email')) {
            $targetEmail = strtolower(trim((string) $email));
            $query->whereRaw('LOWER(email) = ?', [$targetEmail]);
        }

        // Filter by age in days
        if ($days = $this->option('days')) {
            $query->where('created_at', '<=', now()->subDays((int) $days));
        }

        $testers = $query->get();
        $totalTesters = $testers->count();

        if ($totalTesters === 0) {
            $this->info('No purgeable beta tester accounts found.');

            return self::SUCCESS;
        }

        $totalPosts = $testers->sum(fn ($u) => $u->posts->count());
        $totalMedia = Media::whereIn('user_id', $testers->pluck('id'))->count();

        $this->comment("Identified {$totalTesters} purgeable tester account(s) containing {$totalPosts} post(s) and {$totalMedia} media file(s).");

        // Dry Run Mode
        if ($this->option('dry-run')) {
            $this->warn('--- DRY RUN MODE (No accounts or data will be deleted) ---');

            $tableRows = $testers->take(25)->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->profile?->username ?? 'N/A',
                    'posts' => $user->posts->count(),
                    'created_at' => $user->created_at?->toDateTimeString(),
                ];
            });

            $this->table(['User ID', 'Email', 'Username', 'Posts', 'Joined'], $tableRows);

            if ($totalTesters > 25) {
                $this->comment('...and '.($totalTesters - 25).' more account(s).');
            }

            if ($this->option('posts-only')) {
                $this->info("Mode: --posts-only (Only {$totalPosts} posts would be deleted, accounts would remain).");
            } else {
                $this->info("Mode: Full Purge ({$totalTesters} accounts and all their data would be deleted).");
            }

            return self::SUCCESS;
        }

        // Confirmation guard
        if (! $this->option('force')) {
            $actionLabel = $this->option('posts-only')
                ? "delete {$totalPosts} post(s) authored by {$totalTesters} tester(s)"
                : "permanently delete {$totalTesters} tester account(s) and ALL their data";

            if (! $this->confirm("Are you sure you want to {$actionLabel}?")) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        // Mode 1: Posts Only
        if ($this->option('posts-only')) {
            $deletedPostsCount = 0;
            $bar = $this->output->createProgressBar($totalPosts);
            $bar->start();

            foreach ($testers as $tester) {
                foreach ($tester->posts as $post) {
                    $deletePost($post);
                    $deletedPostsCount++;
                    $bar->advance();
                }
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("Successfully deleted {$deletedPostsCount} test post(s). Tester user accounts were preserved.");

            return self::SUCCESS;
        }

        // Mode 2: Full Purge (Accounts + Posts + Media + Messages)
        $deletedUsersCount = 0;
        $deletedMediaCount = 0;

        $bar = $this->output->createProgressBar($totalTesters);
        $bar->start();

        foreach ($testers->chunk(50) as $chunk) {
            DB::transaction(function () use ($chunk, &$deletedUsersCount, &$deletedMediaCount) {
                foreach ($chunk as $tester) {
                    // 1. Delete physical storage files from R2/S3
                    $mediaFiles = Media::where('user_id', $tester->id)->get();
                    foreach ($mediaFiles as $media) {
                        if ($media->disk && $media->path) {
                            Storage::disk($media->disk)->delete($media->path);
                            $deletedMediaCount++;
                        }
                    }

                    // 2. Delete user (triggers PostgreSQL ON DELETE CASCADE for posts, profiles, comments, interactions, messages)
                    $tester->delete();
                    $deletedUsersCount++;
                }
            });

            $bar->advance($chunk->count());
        }

        $bar->finish();
        $this->newLine(2);

        // 3. Clean up empty conversations with no participants
        $emptyConversations = Conversation::whereDoesntHave('participants')->delete();

        $this->info("Purge complete: {$deletedUsersCount} tester account(s) deleted, {$deletedMediaCount} storage file(s) removed.");
        if ($emptyConversations > 0) {
            $this->comment("Cleaned up {$emptyConversations} empty conversation thread(s).");
        }

        return self::SUCCESS;
    }
}
