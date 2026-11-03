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

class MessageSent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(
        public readonly Message $message
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('conversations.'.$this->message->conversation_id),
        ];

        $recipient = $this->message->conversation
            ?->participants
            ?->firstWhere('user_id', '!=', $this->message->sender_id);

        if ($recipient) {
            $channels[] = new PrivateChannel('users.'.$recipient->user_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender.profile.avatar', 'media', 'replyTo.sender']);

        return (new MessageResource($this->message))->resolve();
    }
}
