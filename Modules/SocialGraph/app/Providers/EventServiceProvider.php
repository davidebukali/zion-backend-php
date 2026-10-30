<?php

namespace Modules\SocialGraph\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use Modules\Comments\Events\CommentCreated;
use Modules\Interactions\Events\PostLiked;
use Modules\Interactions\Events\PostReposted;
use Modules\Interactions\Events\PostUnliked;
use Modules\Interactions\Events\PostUnreposted;
use Modules\Posts\Events\PostCreated;
use Modules\SocialGraph\Events\FollowRequestAccepted;
use Modules\SocialGraph\Events\UserUnfollowed;
use Modules\SocialGraph\Listeners\BackfillFollowerFeedListener;
use Modules\SocialGraph\Listeners\CleanUnfollowedUserFeedListener;
use Modules\SocialGraph\Listeners\FanOutPostListener;
use Modules\SocialGraph\Listeners\RemoveInteractionFromFeedListener;

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
        PostCreated::class => [
            FanOutPostListener::class,
        ],
        PostReposted::class => [
            FanOutPostListener::class,
        ],
        PostLiked::class => [
            FanOutPostListener::class,
        ],
        CommentCreated::class => [
            FanOutPostListener::class,
        ],
        PostUnreposted::class => [
            RemoveInteractionFromFeedListener::class,
        ],
        PostUnliked::class => [
            RemoveInteractionFromFeedListener::class,
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
