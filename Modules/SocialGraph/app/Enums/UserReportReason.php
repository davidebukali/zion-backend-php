<?php

namespace Modules\SocialGraph\Enums;

enum UserReportReason: string
{
    case Impersonation = 'impersonation';
    case Harassment = 'harassment';
    case HateSpeech = 'hate_speech';
    case ExplicitContent = 'explicit_content';
    case ViolenceOrHarm = 'violence_or_harm';
    case ScamOrFraud = 'scam_or_fraud';
    case Spam = 'spam';
    case InappropriateProfile = 'inappropriate_profile';
    case DisruptiveContent = 'disruptive_content';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Impersonation => 'Impersonation',
            self::Harassment => 'Harassment or Bullying',
            self::HateSpeech => 'Hate Speech',
            self::ExplicitContent => 'Explicit or Inappropriate Content',
            self::ViolenceOrHarm => 'Violence or Self-Harm',
            self::ScamOrFraud => 'Scam, Fraud, or Deception',
            self::Spam => 'Spam or Bot Activity',
            self::InappropriateProfile => 'Inappropriate Profile',
            self::DisruptiveContent => 'Disruptive or Bad-Faith Conduct',
            self::Other => 'Other',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Impersonation => 'Pretending to be someone else, a leader, or an organization',
            self::Harassment => 'Targeted hostility, stalking, or abusive personal attacks',
            self::HateSpeech => 'Attacks, demeaning slurs, or promoting harm against groups or individuals',
            self::ExplicitContent => 'Nudity, sexual material, vulgarity, or graphic imagery',
            self::ViolenceOrHarm => 'Threats of physical harm, self-harm, or glorification of violence',
            self::ScamOrFraud => 'Financial exploitation, fake fundraisers, or phishing',
            self::Spam => 'Unsolicited ads, repeated link drops, or automated accounts',
            self::InappropriateProfile => 'Offensive username, avatar, banner, or bio',
            self::DisruptiveContent => 'Content that violates our Christ-centered and family-friendly sanctuary',
            self::Other => 'Issue not listed above (please specify)',
        };
    }

    /**
     * @return list<array{id: string, label: string, desc: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'id' => $case->value,
            'label' => $case->label(),
            'desc' => $case->description(),
        ], self::cases());
    }
}

