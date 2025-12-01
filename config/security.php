<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ClamAV設定
    |--------------------------------------------------------------------------
    */
    'clamav' => [
        // ClamAVコマンドのパス
        'path' => env('CLAMAV_PATH', '/usr/bin/clamscan'),

        // ClamAVデーモンスキャンコマンドのパス（高速）
        'daemon_path' => env('CLAMAV_DAEMON_PATH', '/usr/bin/clamdscan'),

        // デーモンモードを使用（テスト環境で自動有効化）
        'use_daemon' => env('CLAMAV_USE_DAEMON', false),

        // スキャンタイムアウト（秒）
        'timeout' => env('CLAMAV_TIMEOUT', 60),

        // スキャン対象ファイルサイズ上限（バイト、0=無制限）
        'max_file_size' => env('CLAMAV_MAX_FILE_SIZE', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | ファイルアップロードセキュリティ
    |--------------------------------------------------------------------------
    */
    'upload' => [
        // ウイルススキャンを有効化
        'virus_scan_enabled' => env('SECURITY_VIRUS_SCAN_ENABLED', true),

        // スキャン失敗時の動作（strict: 拒否, lenient: 警告のみ）
        'scan_failure_mode' => env('SECURITY_SCAN_FAILURE_MODE', 'strict'),
    ],

];
