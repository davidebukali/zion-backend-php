<?php

namespace Modules\Posts\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Posts\Events\PostCreated;
use Modules\Posts\Events\PostUpdated;
use Modules\Posts\Events\PostModerationEvaluated;
use Modules\Posts\Listeners\TriggerModerationOnPostChange;
use Modules\Posts\Listeners\UpdateReportStatusOnModeration;
use Modules\Interactions\Events\PostReported;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        PostCreated::class => [
            TriggerModerationOnPostChange::class,
        ],
        PostUpdated::class => [
            TriggerModerationOnPostChange::class,
        ],
        PostReported::class => [
            TriggerModerationOnPostChange::class,
        ],
        PostModerationEvaluated::class => [
            UpdateReportStatusOnModeration::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
