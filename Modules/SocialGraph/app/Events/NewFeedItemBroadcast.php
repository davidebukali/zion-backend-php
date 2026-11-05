<?php

namespace Modules\SocialGraph\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewFeedItemBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $broadcastQueue = 'broadcasts';

    /**
     * @param  array<string>  $recipientUserIds
     */
    public function __construct(
        public readonly array $recipientUserIds,
        public readonly string $postId,
        public readonly string $type = 'post',
        public readonly ?string $actorId = null,
        public readonly ?string $publishedAt = null
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn (string $userId) => new PrivateChannel('users.'.$userId),
            $this->recipientUserIds
        );
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'feed.new_item';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'post_id' => $this->postId,
            'type' => $this->type,
            'actor_id' => $this->actorId,
            'published_at' => $this->publishedAt,
        ];
    }
}
