<?php

namespace Modules\Messages\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Message;
use Modules\Messages\Models\MessageReaction;

class MessageReactionUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(
        public readonly Message $message,
        public readonly User $user,
        public readonly ?MessageReaction $reaction
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.'.$this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.reaction_updated';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'user_id' => $this->user->id,
            'emoji' => $this->reaction?->emoji,
        ];
    }
}
