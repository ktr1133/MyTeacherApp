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
    // 通知関連
    // ========================================
    /**
     * システム通知の種別定義
     */
    'notification_types' => [
        // システム通知
        'token_low'           => 'token_low',           // トークン残量低下
        'token_depleted'      => 'token_depleted',      // トークン枯渇
        'payment_success'     => 'payment_success',     // 決済成功
        'payment_failed'      => 'payment_failed',      // 決済失敗
        'group_task_created'  => 'group_task_created',  // グループタスク作成
        'group_task_assigned' => 'group_task_assigned', // グループタスク割当
        'avatar_generated'    => 'avatar_generated',    // アバター生成完了
        'approval_required'   => 'approval_required',   // 承認待ち
        'task_approved'       => 'task_approved',       // タスク承認
        'task_rejected'       => 'task_rejected',       // タスク却下
        
        // 管理者通知
        'admin_announcement'  => 'admin_announcement',  // お知らせ
        'admin_maintenance'   => 'admin_maintenance',   // メンテナンス
        'admin_update'        => 'admin_update',        // アップデート
        'admin_warning'       => 'admin_warning',       // 警告
    ],

    /**
     * 通知の発信元定義
     */
    'notification_sources' => [
        'system' => 'system', // システム自動生成
        'admin'  => 'admin',  // 管理者作成
    ],

    /**
     * 通知の優先度定義
     */
    'notification_priorities' => [
        'info'      => 'info',      // 情報
        'normal'    => 'normal',    // 通常
        'important' => 'important', // 重要
    ],

    /**
     * 通知の配信対象タイプ定義
     */
    'notification_target_types' => [
        'all'    => 'all',    // 全ユーザー
        'users'  => 'users',  // 特定ユーザー
        'groups' => 'groups', // 特定グループ
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
        
        // サブスクリプションプラン
        'subscription_plans' => [
            'family' => [
                'name' => 'ファミリープラン',
                'price_id' => env('STRIPE_FAMILY_PLAN_PRICE_ID'),
                'amount' => 500,  // 円
                'currency' => 'jpy',
                'interval' => 'month',
                'max_members' => 6,
                'max_groups' => 1,
                'trial_days' => 14,
                'features' => [
                    'unlimited_group_tasks' => true,
                    'monthly_reports' => true,
                    'group_token_sharing' => true,
                ],
            ],
            'enterprise' => [
                'name' => 'エンタープライズプラン',
                'price_id' => env('STRIPE_ENTERPRISE_PLAN_PRICE_ID'),
                'amount' => 3000,  // 円（基本20名まで）
                'currency' => 'jpy',
                'interval' => 'month',
                'max_members' => 20,
                'max_groups' => 5,
                'trial_days' => 14,
                'features' => [
                    'unlimited_group_tasks' => true,
                    'monthly_reports' => true,
                    'group_token_sharing' => true,
                    'statistics_reports' => true,  // 将来実装
                    'priority_support' => true,    // 将来実装
                ],
            ],
        ],
        
        // 追加メンバー価格（エンタープライズプラン）
        'additional_member_price_id' => env('STRIPE_ADDITIONAL_MEMBER_PRICE_ID'),
        'additional_member_amount' => 150,  // 円/月/名
        
        // 無料プラン制限
        'free_plan' => [
            'max_members' => 6,
            'max_groups' => 1,
            'group_task_limit_per_month' => 3,
            'report_free_months' => 1,  // 初月のみ無料
        ],
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
        'notification_created'        => 'notification_created',
        'notification_updated'        => 'notification_updated',
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
        'notification_created'        => 'ユーザーが通知を作成したとき',
        'notification_updated'        => 'ユーザーが通知を更新したとき'
    ],

    // ========================================
    // アバターイベント×表情タイプ
    // ========================================
    'avatar_event_expression_types' => [
        'task_created'                => 'normal',      // タスク作成 - バストアップ - 通常
        'task_updated'                => 'normal',      // タスク更新 - バストアップ - 通常
        'task_deleted'                => 'surprised',   // タスク削除 - バストアップ - 驚き
        'task_completed'              => 'happy',       // タスク完了 - バストアップ - 喜び
        'task_breakdown'              => 'normal',      // タスク分解 - バストアップ - 通常
        'task_breakdown_refine'       => 'surprised',   // タスク分解改善 - バストアップ - 驚き
        'group_task_created'          => 'happy',       // グループタスク作成 - バストアップ - 喜び
        'group_task_updated'          => 'normal',      // グループタスク更新 - バストアップ - 通常
        'group_edited'                => 'normal',      // グループ編集 - バストアップ - 通常
        'login'                       => 'happy',       // ログイン - バストアップ - 喜び
        'logout'                      => 'sad',         // ログアウト - バストアップ - 悲しみ
        'login_gap'                   => 'surprised',   // 3日ぶりログイン - バストアップ - 驚き
        'token_purchased'             => 'happy',       // トークン購入 - バストアップ - 喜び
        'performance_personal_viewed' => 'normal',      // 個人実績閲覧 - 全身 - 通常
        'performance_group_viewed'    => 'normal',      // グループ実績閲覧 - 全身 - 通常
        'tag_created'                 => 'happy',       // タグ作成 - バストアップ - 喜び
        'tag_updated'                 => 'normal',      // タグ更新 - バストアップ - 通常
        'tag_deleted'                 => 'sad',         // タグ削除 - バストアップ - 悲しみ
        'group_created'               => 'happy',       // グループ作成 - バストアップ - 喜び
        'group_deleted'               => 'sad',         // グループ削除 - バストアップ - 悲しみ
        'notification_created'        => 'normal',      // お知らせ作成 - バストアップ - 通常
        'notification_updated'        => 'normal'       // お知らせ更新 - バストアップ - 通常
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

    // OpenAIの入力プロンプトと出力プロンプトの比率
    'openai_prompt_completion_ratio' => 4,

    // 画像サイズ
    'image_size' => [
        'width' => '512',
        'height' => '512',
    ],

    // ========================================
    // ポータルサイト設定
    // ========================================
    
    /**
     * アプリ名定義
     */
    'app_names' => [
        'myteacher' => 'MyTeacher',
        'app2'      => 'App2 (準備中)',
        'app3'      => 'App3 (準備中)',
    ],

    /**
     * FAQカテゴリ定義
     */
    'faq_categories' => [
        'getting_started'   => 'はじめに',
        'tasks'             => 'タスク管理',
        'group_tasks'       => 'グループタスク',
        'ai_features'       => 'AI機能',
        'avatars'           => '教師アバター',
        'tokens'            => 'トークン',
        'account'           => 'アカウント',
        'troubleshooting'   => 'トラブルシューティング',
        'other'             => 'その他',
    ],

    // ========================================
    // タイムゾーン設定（グローバル対応）
    // ========================================
    'timezones' => [
        // アジア・太平洋
        'Asia/Tokyo' => [
            'name' => '日本（東京）',
            'offset' => '+09:00',
            'region' => 'アジア・太平洋',
        ],
        'Asia/Shanghai' => [
            'name' => '中国（上海）',
            'offset' => '+08:00',
            'region' => 'アジア・太平洋',
        ],
        'Asia/Singapore' => [
            'name' => 'シンガポール',
            'offset' => '+08:00',
            'region' => 'アジア・太平洋',
        ],
        'Asia/Hong_Kong' => [
            'name' => '香港',
            'offset' => '+08:00',
            'region' => 'アジア・太平洋',
        ],
        'Asia/Seoul' => [
            'name' => '韓国（ソウル）',
            'offset' => '+09:00',
            'region' => 'アジア・太平洋',
        ],
        'Asia/Taipei' => [
            'name' => '台湾（台北）',
            'offset' => '+08:00',
            'region' => 'アジア・太平洋',
        ],
        'Asia/Bangkok' => [
            'name' => 'タイ（バンコク）',
            'offset' => '+07:00',
            'region' => 'アジア・太平洋',
        ],
        'Asia/Manila' => [
            'name' => 'フィリピン（マニラ）',
            'offset' => '+08:00',
            'region' => 'アジア・太平洋',
        ],
        'Australia/Sydney' => [
            'name' => 'オーストラリア（シドニー）',
            'offset' => '+11:00',
            'region' => 'アジア・太平洋',
        ],

        // アメリカ
        'America/New_York' => [
            'name' => 'アメリカ東部（ニューヨーク）',
            'offset' => '-05:00',
            'region' => '南北アメリカ',
        ],
        'America/Chicago' => [
            'name' => 'アメリカ中部（シカゴ）',
            'offset' => '-06:00',
            'region' => '南北アメリカ',
        ],
        'America/Denver' => [
            'name' => 'アメリカ山岳部（デンバー）',
            'offset' => '-07:00',
            'region' => '南北アメリカ',
        ],
        'America/Los_Angeles' => [
            'name' => 'アメリカ西部（ロサンゼルス）',
            'offset' => '-08:00',
            'region' => '南北アメリカ',
        ],
        'America/Toronto' => [
            'name' => 'カナダ（トロント）',
            'offset' => '-05:00',
            'region' => '南北アメリカ',
        ],
        'America/Sao_Paulo' => [
            'name' => 'ブラジル（サンパウロ）',
            'offset' => '-03:00',
            'region' => '南北アメリカ',
        ],

        // ヨーロッパ
        'Europe/London' => [
            'name' => 'イギリス（ロンドン）',
            'offset' => '+00:00',
            'region' => 'ヨーロッパ',
        ],
        'Europe/Paris' => [
            'name' => 'フランス（パリ）',
            'offset' => '+01:00',
            'region' => 'ヨーロッパ',
        ],
        'Europe/Berlin' => [
            'name' => 'ドイツ（ベルリン）',
            'offset' => '+01:00',
            'region' => 'ヨーロッパ',
        ],
        'Europe/Madrid' => [
            'name' => 'スペイン（マドリード）',
            'offset' => '+01:00',
            'region' => 'ヨーロッパ',
        ],
        'Europe/Rome' => [
            'name' => 'イタリア（ローマ）',
            'offset' => '+01:00',
            'region' => 'ヨーロッパ',
        ],
        'Europe/Moscow' => [
            'name' => 'ロシア（モスクワ）',
            'offset' => '+03:00',
            'region' => 'ヨーロッパ',
        ],

        // 中東・アフリカ
        'Asia/Dubai' => [
            'name' => 'アラブ首長国連邦（ドバイ）',
            'offset' => '+04:00',
            'region' => '中東・アフリカ',
        ],
        'Africa/Cairo' => [
            'name' => 'エジプト（カイロ）',
            'offset' => '+02:00',
            'region' => '中東・アフリカ',
        ],
        'Africa/Johannesburg' => [
            'name' => '南アフリカ（ヨハネスブルグ）',
            'offset' => '+02:00',
            'region' => '中東・アフリカ',
        ],
    ],
];