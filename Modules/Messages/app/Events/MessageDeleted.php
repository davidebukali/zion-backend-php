<?php

namespace Modules\Messages\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Messages\Models\Message;

class MessageDeleted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(
        public readonly Message $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.'.$this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'is_deleted_for_everyone' => true,
            'deleted_for_everyone_at' => $this->message->deleted_for_everyone_at?->toISOString(),
        ];
    }
}
