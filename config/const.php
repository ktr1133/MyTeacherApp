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
    // トークン取引タイプ
    // =================================
    'token_transaction_types' => [
        'consume'          => 'consume',          // 消費
        'purchase'         => 'purchase',         // 購入
        'admin_adjust'     => 'admin_adjust',     // 管理者調整
        'free_reset'       => 'free_reset',       // 無料枠リセット
        'refund'           => 'refund',           // 返金
        'ai_usage'         => 'ai_usage',         // AI使用
    ],

    // ========================================
    // 通知種別
    // ========================================
    'notification_types' => [
        'token_low'           => 'token_low',           // トークン残高低下
        'token_depleted'      => 'token_depleted',      // トークン残高ゼロ
        'payment_success'     => 'payment_success',     // 課金成功
        'payment_failed'      => 'payment_failed',      // 課金失敗
        'group_task_created'  => 'group_task_created',  // グループタスク作成
        'group_task_assigned' => 'group_task_assigned', // グループタスク割当
    ],

    // ========================================
    // 課金履歴ステータス
    // ========================================
    'payment_history_statuses' => [
        'pending'   => 'pending',   // 処理中
        'succeeded' => 'succeeded',  // 成功
        'failed'    => 'failed',     // 失敗
        'refunded'  => 'refunded',   // 返金済み
    ],

    // Stripe設定
    // ========================================
    'stripe' => [
        // テストモード（.envから取得）
        'test_mode' => env('STRIPE_TEST_MODE', true),
    ],

    // ========================================
    // アバター画像タイプ
    // ========================================
    'avatar_image_types' => [
        'bust'      => 'bust',       // バストアップ
        'full_body' => 'full_body',  // 全身
    ],

    // ========================================
    // アバター表情タイプ
    // ========================================
    'avatar_expressions' => [
        'normal'    => 'normal',    // 通常
        'happy'     => 'happy',     // 喜び
        'sad'       => 'sad',       // 悲しみ
        'angry'     => 'angry',     // 怒り
        'surprised' => 'surprised', // 驚き
    ],

    // ========================================
    // アバター性別タイプ
    // ========================================
    'avatar_sex_types' => [
        'male'   => 'male',   // 男性
        'female' => 'female', // 女性
        'other'  => 'other',  // その他
    ],

    // ========================================
    // アバターイベント
    // ========================================
    'avatar_events' => [
        'task_created'          => 'task_created',
        'task_updated'          => 'task_updated',
        'task_deleted'          => 'task_deleted',
        'task_completed'        => 'task_completed',
        'task_breakdown'        => 'task_breakdown',
        'task_breakdown_refine' => 'task_breakdown_refine',
        'group_task_created'    => 'group_task_created',
        'group_edited'          => 'group_edited',
        'login'                 => 'login',
        'logout'                => 'logout',
        'login_gap'             => 'login_gap',
        'token_purchased'       => 'token_purchased',
        'performance_viewed'    => 'performance_viewed',
        'tag_created'           => 'tag_created',
        'tag_deleted'           => 'tag_deleted',
        'group_created'         => 'group_created',
        'group_deleted'         => 'group_deleted',
    ],

    // ========================================
    // アバター生成ステータス
    // ========================================
    'avatar_generation_statuses' => [
        'pending'    => 'pending',    // 保留中
        'generating' => 'generating', // 生成中
        'completed'  => 'completed',  // 完了
        'failed'     => 'failed',     // 失敗
    ],

    // =======================================
    // グループタスク実行ステータス
    // =======================================
    'schedule_task_execution_statuses' => [
        'success' => 'success',
        'failed'  => 'failed',
        'skipped' => 'skipped',
    ],

    // 事前見積トークン数
    'estimate_token' => 2000,
];