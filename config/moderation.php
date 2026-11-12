<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Moderation Pipeline Enabled
    |--------------------------------------------------------------------------
    | When disabled, posts bypass AI moderation checks and are treated as
    | approved, preventing timeout errors until the webhook service is ready.
    */
    'enabled' => (bool) env('MODERATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Account Trust Settings
    |--------------------------------------------------------------------------
    | New or low-trust accounts start with 'pending' moderation status.
    | Trusted accounts start with 'approved' and are scanned asynchronously.
    */
    'trust' => [
        'min_account_age_days' => (int) env('MODERATION_TRUST_MIN_AGE_DAYS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Thresholds
    |--------------------------------------------------------------------------
    | Numerical thresholds (0.00 to 1.00) for determining moderation verdicts
    | using OpenAI omni-moderation-latest native category scores.
    */
    'thresholds' => [
        'sexual' => ['flag' => 0.35, 'reject' => 0.65],
        'sexual/minors' => ['flag' => 0.10, 'reject' => 0.30],
        'hate' => ['flag' => 0.40, 'reject' => 0.70],
        'hate/threatening' => ['flag' => 0.30, 'reject' => 0.60],
        'harassment' => ['flag' => 0.55, 'reject' => 0.80],
        'harassment/threatening' => ['flag' => 0.40, 'reject' => 0.70],
        'self-harm' => ['flag' => 0.40, 'reject' => 0.70],
        'self-harm/intent' => ['flag' => 0.30, 'reject' => 0.60],
        'self-harm/instructions' => ['flag' => 0.25, 'reject' => 0.50],
        'violence' => ['flag' => 0.60, 'reject' => 0.85],
        'violence/graphic' => ['flag' => 0.45, 'reject' => 0.75],
        'illicit' => ['flag' => 0.60, 'reject' => 0.85],
        'illicit/violent' => ['flag' => 0.40, 'reject' => 0.70],
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Auto-Dismissal Max Risk Score
    |--------------------------------------------------------------------------
    | A user report is only auto-dismissed if the highest violation score across
    | all categories is strictly below this threshold. Otherwise it escalates.
    */
    'report_dismissal_max_score' => 0.10,

    /*
    |--------------------------------------------------------------------------
    | Request Expiration & Timeouts
    |--------------------------------------------------------------------------
    */
    'request_timeout_minutes' => 15,
    'max_retry_attempts' => 3,
];
