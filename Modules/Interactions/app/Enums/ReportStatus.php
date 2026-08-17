<?php

namespace Modules\Interactions\Enums;

enum ReportStatus: string
{
    case PENDING = 'pending';
    case REVIEWING = 'reviewing';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';
}
