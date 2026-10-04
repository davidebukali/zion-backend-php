<?php

return [
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
    | Numerical thresholds (0.00 to 1.00) for determining moderation verdicts.
    */
    'thresholds' => [
        'toxicity' => ['flag' => 0.60, 'reject' => 0.85],
        'nsfw' => ['flag' => 0.50, 'reject' => 0.80],
        'hate_speech' => ['flag' => 0.40, 'reject' => 0.70],
        'harassment' => ['flag' => 0.55, 'reject' => 0.80],
        'spam' => ['flag' => 0.65, 'reject' => 0.90],
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
