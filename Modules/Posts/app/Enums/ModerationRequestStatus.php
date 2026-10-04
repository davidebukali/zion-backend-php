<?php

namespace Modules\Posts\Enums;

enum ModerationRequestStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case STALE = 'stale';
    case TIMED_OUT = 'timed_out';
}
