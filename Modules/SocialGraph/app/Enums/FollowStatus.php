<?php

namespace Modules\SocialGraph\Enums;

enum FollowStatus: string
{
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case PENDING = 'pending';
}
