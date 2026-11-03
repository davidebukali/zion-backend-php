<?php

namespace Modules\Messages\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;

class TypingStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly User $user
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.'.$this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'typing.started';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
        ];
    }
}
