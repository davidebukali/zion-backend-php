<?php

namespace Modules\Interactions\Enums;

enum ReportReason: string
{
    case SPAM = 'spam';
    case HARASSMENT = 'harassment';
    case HATE_SPEECH = 'hate_speech';
    case VIOLENCE = 'violence';
    case SEXUAL_CONTENT = 'sexual_content';
    case FALSE_INFORMATION = 'false_information';
    case OTHER = 'other';
}
