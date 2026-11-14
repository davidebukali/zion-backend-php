<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Posts\Actions\DeletePost;
use Modules\Posts\Models\Post;

class BetaPurgePostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'beta:purge-posts
        {--post= : Purge a single post by ULID}
        {--posts= : Purge comma-separated post ULIDs}
        {--user= : Purge posts by author email or username}
        {--days= : Purge posts older than X days}
        {--before= : Purge posts created before date (YYYY-MM-DD)}
        {--contains= : Purge posts matching text keyword}
        {--only-testers : Only delete posts authored by non-persistent tester accounts}
        {--dry-run : Report matching posts without deleting}
        {--force : Force deletion without confirmation prompt in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Selectively purge particular posts, associated media files, and interactions';

    /**
     * Execute the console command.
     */
    public function handle(DeletePost $deletePost): int
    {
        $hasFilter = $this->option('post')
            || $this->option('posts')
            || $this->option('user')
            || $this->option('days') !== null
            || $this->option('before')
            || $this->option('contains');

        if (! $hasFilter) {
            $this->error('Please specify at least one filter option (e.g. --post, --posts, --user, --days, --before, --contains).');

            return self::FAILURE;
        }

        $query = Post::query()->with(['user.profile', 'media']);

        // Filter by single post ID
        if ($postId = $this->option('post')) {
            $query->where('id', trim((string) $postId));
        }

        // Filter by multiple comma-separated post IDs
        if ($postIds = $this->option('posts')) {
            $ids = array_filter(array_map('trim', explode(',', (string) $postIds)));
            $query->whereIn('id', $ids);
        }

        // Filter by author email or username
        if ($userIdentifier = $this->option('user')) {
            $identifier = strtolower(trim((string) $userIdentifier));
            $query->whereHas('user', function ($q) use ($identifier) {
                $q->whereRaw('LOWER(email) = ?', [$identifier])
                    ->orWhereHas('profile', function ($pq) use ($identifier) {
                        $pq->whereRaw('LOWER(username) = ?', [$identifier]);
                    });
            });
        }

        // Filter by age in days
        if ($days = $this->option('days')) {
            $query->where('created_at', '<=', now()->subDays((int) $days));
        }

        // Filter by before date
        if ($before = $this->option('before')) {
            try {
                $parsedDate = Carbon::parse((string) $before)->endOfDay();
                $query->where('created_at', '<=', $parsedDate);
            } catch (\Exception $e) {
                $this->error("Invalid date format for --before: {$before}. Please use YYYY-MM-DD.");

                return self::FAILURE;
            }
        }

        // Filter by keyword in post content (with escaped SQL wildcards)
        if ($contains = $this->option('contains')) {
            $term = addcslashes((string) $contains, '%_\\');
            $query->where('content', 'ilike', "%{$term}%");
        }

        // Filter to only posts authored by non-persistent accounts
        if ($this->option('only-testers')) {
            $protectedEmails = array_map('strtolower', config('beta.protected_emails', []));
            $query->whereHas('user', function ($q) use ($protectedEmails) {
                $q->where('is_persistent', false);
                if (! empty($protectedEmails)) {
                    $q->whereNotIn(DB::raw('LOWER(email)'), $protectedEmails);
                }
            });
        }

        $posts = $query->get();
        $count = $posts->count();

        if ($count === 0) {
            $this->info('No matching posts found for the specified filters.');

            return self::SUCCESS;
        }

        $this->comment("Found {$count} matching post(s).");

        // Dry Run Mode
        if ($this->option('dry-run')) {
            $this->warn('--- DRY RUN MODE (No posts or media files will be deleted) ---');

            $tableRows = $posts->take(25)->map(function (Post $post) {
                return [
                    'id' => $post->id,
                    'author' => $post->user?->email ?? 'N/A',
                    'content' => Str::limit($post->content ?? '(media only)', 40),
                    'media' => $post->media->count(),
                    'created_at' => $post->created_at?->toDateTimeString(),
                ];
            });

            $this->table(['Post ID', 'Author Email', 'Content Snippet', 'Media Count', 'Created At'], $tableRows);

            if ($count > 25) {
                $this->comment('...and '.($count - 25).' more post(s).');
            }

            return self::SUCCESS;
        }

        // Confirmation guard in production
        if (! $this->option('force') && app()->environment('production')) {
            if (! $this->confirm("Are you sure you want to permanently delete {$count} post(s) and associated media?")) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $deletedPosts = 0;
        $deletedMediaFiles = 0;

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($posts as $post) {
            $deletedMediaFiles += $post->media->count();
            $deletePost($post);
            $deletedPosts++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully deleted {$deletedPosts} post(s) and purged {$deletedMediaFiles} media file(s).");

        return self::SUCCESS;
    }
}
