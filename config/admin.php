<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 管理者エリアIP制限
    |--------------------------------------------------------------------------
    |
    | Stripe要件対応: 管理者のアクセス可能なIPアドレスを制限
    |
    | ip_restriction_enabled: IP制限を有効にする
    | allowed_ips: 許可するIPアドレスリスト（CIDR表記可）
    |
    */

    'ip_restriction_enabled' => env('ADMIN_IP_RESTRICTION_ENABLED', false),

    'allowed_ips' => explode(',', env('ADMIN_ALLOWED_IPS', '127.0.0.1')),

    /*
    |--------------------------------------------------------------------------
    | Basic認証（フォールバック）
    |--------------------------------------------------------------------------
    |
    | IP制限が使用できない環境向けのBasic認証
    |
    */

    'basic_auth_enabled' => env('ADMIN_BASIC_AUTH_ENABLED', false),

    'basic_auth_username' => env('ADMIN_BASIC_AUTH_USERNAME', 'admin'),

    'basic_auth_password' => env('ADMIN_BASIC_AUTH_PASSWORD', 'ChangeMe!2025'),

    /*
    |--------------------------------------------------------------------------
    | アカウントロック設定
    |--------------------------------------------------------------------------
    */

    'max_login_attempts' => env('ADMIN_MAX_LOGIN_ATTEMPTS', 5),

    'lockout_duration_minutes' => env('ADMIN_LOCKOUT_DURATION', 30),

    /*
    |--------------------------------------------------------------------------
    | セキュリティログ保持期間
    |--------------------------------------------------------------------------
    */

    'security_log_retention_days' => env('ADMIN_SECURITY_LOG_RETENTION_DAYS', 90),
];
