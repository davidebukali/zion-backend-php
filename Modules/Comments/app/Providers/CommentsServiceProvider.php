<?php

namespace Modules\Comments\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Comments\Models\Comment;
use Modules\Comments\Policies\CommentPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CommentsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Comments';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'comments';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

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
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Gate::policy(
            Comment::class,
            CommentPolicy::class
        );
    }
}
