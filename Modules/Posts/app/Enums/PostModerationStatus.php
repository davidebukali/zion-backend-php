<?php

namespace Modules\Posts\Enums;

enum PostModerationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case FLAGGED = 'flagged';
    case REJECTED = 'rejected';
}
