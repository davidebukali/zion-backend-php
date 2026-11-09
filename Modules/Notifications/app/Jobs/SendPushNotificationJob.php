<?php

namespace Modules\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Notifications\Services\ExpoPushService;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $recipientId,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
        public readonly ?int $badge = 1
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(ExpoPushService $expoPushService): void
    {
        $expoPushService->sendToUser(
            $this->recipientId,
            $this->title,
            $this->body,
            $this->data,
            $this->badge
        );
    }
}
