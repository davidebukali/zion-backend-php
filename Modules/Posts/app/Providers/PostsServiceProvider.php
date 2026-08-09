<?php

namespace Modules\Posts\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

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
     * @param $schedule
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

        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Posts\Models\Post::class,
            \Modules\Posts\Policies\PostPolicy::class
        );
    }
}

