<?php

namespace Modules\Notifications\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Comments\Events\CommentCreated;
use Modules\Interactions\Events\CommentLiked;
use Modules\Interactions\Events\PostLiked;
use Modules\Interactions\Events\PostReported;
use Modules\Interactions\Events\PostShared;
use Modules\Notifications\Listeners\SendCommentCreatedNotification;
use Modules\Notifications\Listeners\SendCommentLikedNotification;
use Modules\Notifications\Listeners\SendFollowNotification;
use Modules\Notifications\Listeners\SendFollowRequestAcceptedNotification;
use Modules\Notifications\Listeners\SendFollowRequestedNotification;
use Modules\Notifications\Listeners\SendPostLikedNotification;
use Modules\Notifications\Listeners\SendPostReportedNotification;
use Modules\Notifications\Listeners\SendPostSharedNotification;
use Modules\SocialGraph\Events\FollowRequestAccepted;
use Modules\SocialGraph\Events\FollowRequested;
use Modules\SocialGraph\Events\UserFollowed;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        CommentLiked::class => [
            SendCommentLikedNotification::class,
        ],
        PostLiked::class => [
            SendPostLikedNotification::class,
        ],
        PostReported::class => [
            SendPostReportedNotification::class,
        ],
        PostShared::class => [
            SendPostSharedNotification::class,
        ],
        CommentCreated::class => [
            SendCommentCreatedNotification::class,
        ],
        UserFollowed::class => [
            SendFollowNotification::class,
        ],
        FollowRequested::class => [
            SendFollowRequestedNotification::class,
        ],
        FollowRequestAccepted::class => [
            SendFollowRequestAcceptedNotification::class,
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
