<?php

namespace Modules\SocialGraph\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\SocialGraph\Models\UserReport;

class UserReported
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly UserReport $userReport
    ) {}
}
