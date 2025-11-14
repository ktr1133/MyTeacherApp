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
        'task_created'                => 'task_created',
        'task_updated'                => 'task_updated',
        'task_deleted'                => 'task_deleted',
        'task_completed'              => 'task_completed',
        'task_breakdown'              => 'task_breakdown',
        'task_breakdown_refine'       => 'task_breakdown_refine',
        'group_task_created'          => 'group_task_created',
        'group_task_updated'          => 'group_task_updated',
        'group_edited'                => 'group_edited',
        'login'                       => 'login',
        'logout'                      => 'logout',
        'login_gap'                   => 'login_gap',
        'token_purchased'             => 'token_purchased',
        'performance_personal_viewed' => 'performance_personal_viewed',
        'performance_group_viewed'    => 'performance_group_viewed',
        'tag_created'                 => 'tag_created',
        'tag_updated'                 => 'tag_updated',
        'tag_deleted'                 => 'tag_deleted',
        'group_created'               => 'group_created',
        'group_deleted'               => 'group_deleted',
    ],

    // ========================================
    // アバターイベント×シーン
    // ========================================
    'avatar_event_scene' => [
        'task_created'                => 'ユーザーが新しいタスクを作成したとき',
        'task_updated'                => 'ユーザーがタスクを更新したとき',
        'task_deleted'                => 'ユーザーがタスクを削除したとき',
        'task_completed'              => 'ユーザーがタスクを完了したとき',
        'task_breakdown'              => 'ユーザーがタスクを分解したとき',
        'task_breakdown_refine'       => 'ユーザーがタスク分解を改善したとき',
        'group_task_created'          => 'ユーザーがグループタスクを作成したとき',
        'group_task_updated'          => 'ユーザーがグループタスクを更新したとき',
        'group_edited'                => 'ユーザーがグループ情報を編集したとき',
        'login'                       => 'ユーザーがログインしたとき',
        'logout'                      => 'ユーザーがログアウトするとき',
        'login_gap'                   => 'ユーザーが3日ぶりにログインしたとき',
        'token_purchased'             => 'ユーザーがトークンを購入したとき',
        'performance_personal_viewed' => 'ユーザーが個人の実績を閲覧したとき',
        'performance_group_viewed'    => 'ユーザーがグループの実績を閲覧したとき',
        'tag_created'                 => 'ユーザーがタグを作成したとき',
        'tag_updated'                 => 'ユーザーがタグを更新したとき',
        'tag_deleted'                 => 'ユーザーがタグを削除したとき',
        'group_created'               => 'ユーザーがグループを作成したとき',
        'group_deleted'               => 'ユーザーがグループを削除したとき',
    ],

    // ========================================
    // アバターイベント×表情タイプ
    // ========================================
    'avatar_event_expression_types' => [
        'task_created'                => 'normal',       // タスク作成 - バストアップ - 通常
        'task_updated'                => 'normal',       // タスク更新 - バストアップ - 通常
        'task_deleted'                => 'sad',         // タスク削除 - バストアップ - 悲しみ
        'task_completed'              => 'happy',       // タスク完了 - バストアップ - 喜び
        'task_breakdown'              => 'normal',       // タスク分解 - バストアップ - 通常
        'task_breakdown_refine'       => 'surprise',    // タスク分解改善 - バストアップ - 驚き
        'group_task_created'          => 'happy',       // グループタスク作成 - バストアップ - 喜び
        'group_task_updated'          => 'normal',       // グループタスク更新 - バストアップ - 通常
        'group_edited'                => 'normal',       // グループ編集 - バストアップ - 通常
        'login'                       => 'happy',       // ログイン - バストアップ - 喜び
        'logout'                      => 'sad',         // ログアウト - バストアップ - 悲しみ
        'login_gap'                   => 'surprise',    // 3日ぶりログイン - バストアップ - 驚き
        'token_purchased'             => 'happy',       // トークン購入 - バストアップ - 喜び
        'performance_personal_viewed' => 'normal',       // 個人実績閲覧 - 全身 - 通常
        'performance_group_viewed'    => 'normal',       // グループ実績閲覧 - 全身 - 通常
        'tag_created'                 => 'happy',       // タグ作成 - バストアップ - 喜び
        'tag_updated'                 => 'normal',       // タグ更新 - バストアップ - 通常
        'tag_deleted'                 => 'sad',         // タグ削除 - バストアップ - 悲しみ
        'group_created'               => 'happy',       // グループ作成 - バストアップ - 喜び
        'group_deleted'               => 'sad',         // グループ削除 - バストアップ - 悲しみ
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