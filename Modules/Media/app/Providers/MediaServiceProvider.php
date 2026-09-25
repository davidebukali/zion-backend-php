<?php

namespace Modules\Media\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Media\Console\CleanupOrphanedMedia;
use Modules\Media\Models\Media;
use Modules\Media\Policies\MediaPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MediaServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Media';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'media';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        CleanupOrphanedMedia::class,
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
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Gate::policy(
            Media::class,
            MediaPolicy::class
        );
    }
}
