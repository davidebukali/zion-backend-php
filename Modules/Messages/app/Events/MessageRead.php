<?php

namespace Modules\Messages\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;

class MessageRead implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(
        public readonly Conversation $conversation,
        public readonly User $reader,
        public readonly ?string $lastReadMessageId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversations.'.$this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'reader_id' => $this->reader->id,
            'last_read_message_id' => $this->lastReadMessageId,
            'read_at' => now()->toISOString(),
        ];
    }
}
