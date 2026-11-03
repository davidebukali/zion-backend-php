<?php

namespace Modules\Messages\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Messages\Models\Message;
use Modules\Messages\Transformers\MessageResource;

class MessageUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
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
        return 'message.updated';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender.profile.avatar', 'media', 'replyTo.sender']);

        return (new MessageResource($this->message))->resolve();
    }
}
