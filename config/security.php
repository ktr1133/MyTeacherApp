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
