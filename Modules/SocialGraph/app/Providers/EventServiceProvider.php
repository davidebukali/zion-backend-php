<?php

namespace Modules\SocialGraph\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use Modules\SocialGraph\Events\FollowRequestAccepted;
use Modules\SocialGraph\Events\UserUnfollowed;
use Modules\SocialGraph\Listeners\BackfillFollowerFeedListener;
use Modules\SocialGraph\Listeners\CleanUnfollowedUserFeedListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        UserUnfollowed::class => [
            CleanUnfollowedUserFeedListener::class,
        ],
        FollowRequestAccepted::class => [
            BackfillFollowerFeedListener::class,
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
