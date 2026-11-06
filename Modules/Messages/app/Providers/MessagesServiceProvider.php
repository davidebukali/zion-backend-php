<?php

namespace Modules\Messages\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\Message;
use Modules\Messages\Policies\ConversationPolicy;
use Modules\Messages\Policies\MessagePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MessagesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Messages';
    protected string $nameLower = 'messages';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);

        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('messages.send', function ($request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                    return response()->json([
                        'success' => false,
                        'message' => "Too many messages sent. Please wait {$retryAfter} seconds.",
                        'retry_after' => $retryAfter,
                    ], 429, $headers);
                });
        });

        RateLimiter::for('messages.typing', function ($request) {
            $conversation = $request->route('conversation');
            $convId = $conversation instanceof Conversation ? $conversation->id : ($conversation ?: 'all');

            return Limit::perMinute(15)
                ->by(($request->user()?->id ?: $request->ip()).':'.$convId)
                ->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 4);

                    return response()->json([
                        'success' => false,
                        'message' => "Typing indicator rate limit reached. Please wait {$retryAfter} seconds.",
                        'retry_after' => $retryAfter,
                    ], 429, $headers);
                });
        });

        RateLimiter::for('conversations.create', function ($request) {
            return Limit::perMinute(15)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                    return response()->json([
                        'success' => false,
                        'message' => "Too many conversations created. Please wait {$retryAfter} seconds.",
                        'retry_after' => $retryAfter,
                    ], 429, $headers);
                });
        });
    }
}

