<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;
use Modules\Media\Models\Media;
use Modules\Posts\Models\Post;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'post' => Post::class,
            'user' => User::class,
            'comment' => Comment::class,
            'media' => Media::class,
        ]);

        RateLimiter::for('media-upload', function ($request) {
            return Limit::perMinute(30)
                ->by($request->user()->id);
        });
    }
}

