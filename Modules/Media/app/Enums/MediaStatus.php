<?php

namespace Modules\Media\Enums;

enum MediaStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
}
