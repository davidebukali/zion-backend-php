<?php

namespace Modules\Posts\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Models\ModerationRequest;
use Modules\Posts\Models\Post;

class PostModerationEvaluated implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Post $post,
        public readonly PostModerationStatus $verdict,
        public readonly array $scores = [],
        public readonly array $metadata = [],
        public readonly ?ModerationRequest $request = null,
        public readonly ?string $reportId = null
    ) {}
}
