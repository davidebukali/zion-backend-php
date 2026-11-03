<?php

namespace Modules\Messages\Providers;

use Illuminate\Support\Facades\Gate;
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
    }
}

