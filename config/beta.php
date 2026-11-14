<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Beta Mode Status
    |--------------------------------------------------------------------------
    |
    | When true, new user registrations are flagged as temporary beta testers
    | (is_persistent = false) by default. When false (production mode), all
    | new user signups are permanently persistent (is_persistent = true).
    |
    */

    'mode' => env('BETA_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Protected Beta Accounts & Domains
    |--------------------------------------------------------------------------
    |
    | Accounts matching these emails or email domains will NEVER be purged
    | by the beta testing purge commands, regardless of their database
    | flag. This provides an additional safeguard for team accounts.
    |
    */

    'protected_emails' => array_values(array_filter(array_map('trim', explode(',', env('BETA_PROTECTED_EMAILS', ''))))),

    'protected_domains' => array_values(array_filter(array_map('trim', explode(',', env('BETA_PROTECTED_DOMAINS', 'tsiyonapp.com'))))),

];
