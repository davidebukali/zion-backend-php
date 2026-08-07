<?php

namespace Modules\Posts\Enums;

enum PostVisibility: string
{
    case PUBLIC = 'public';
    case FOLLOWERS = 'followers';
    case PRIVATE = 'private';
}
