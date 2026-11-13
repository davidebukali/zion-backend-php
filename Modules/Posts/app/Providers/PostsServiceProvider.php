<?php

namespace Modules\Posts\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Posts\Console\ResolveStuckModerationRequests;
use Modules\Posts\Models\Post;
use Modules\Posts\Policies\PostPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PostsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Posts';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'posts';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        ResolveStuckModerationRequests::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     */
    protected function configureSchedules(Schedule $schedule): void
    {
        $schedule->command('moderation:resolve-stuck-requests')->everyFiveMinutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Gate::policy(
            Post::class,
            PostPolicy::class
        );
    }
}
