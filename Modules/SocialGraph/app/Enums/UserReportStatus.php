<?php

namespace Modules\SocialGraph\Enums;

enum UserReportStatus: string
{
    case Pending = 'pending';
    case Reviewing = 'reviewing';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}

