<?php

return [
    // タスクのスパン定義
    'task_spans' => [
        'short' => 1, // 短期
        'mid'   => 2, // 中期
        'long'  => 3, // 長期
    ],

    // ========================================
    // トークン設定
    // ========================================
    'token' => [
        // 月次無料枠（1M トークン）
        'free_monthly' => env('TOKEN_FREE_MONTHLY', 1000000),

        // 残高低下の閾値（200K トークン）
        'low_threshold' => env('TOKEN_LOW_THRESHOLD', 200000),

        // 各機能のトークン消費量
        'consumption' => [
            // タスク分解
            'task_decomposition' => 50000,
            
            // AIアシスタント（先生アバター）
            'teacher_avatar' => 30000,
            
            // 日報生成
            'report_generation' => 20000,
            
            // バッチタスク生成
            'batch_task_generation' => 100000,
            
            // 画像生成（将来用）
            'image_generation' => 150000,
        ],

        // 通知設定
        'notification' => [
            // 低残高警告の再通知間隔（時間）
            'low_balance_interval_hours' => 24,
        ],
    ],

    // ========================================
    // Stripe設定
    // ========================================
    'stripe' => [
        // テストモード（.envから取得）
        'test_mode' => env('STRIPE_TEST_MODE', true),
    ],
];