<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key'  => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'replicate' => [
        'api_token' => env('REPLICATE_API_TOKEN'),
        // 描画モデル (anything-v4.0)
        'draw_model_version' => env('REPLICATE_MODEL_VERSION'),
        // 描画モデル（flux-aesthetic-anime）
        'draw_model_version_v2' => env('REPLICATE_MODEL_VERSION_V2'),
        // 背景除去モデル (rembg)
        'transparent_model_version' => env('REPLICATE_TRANSPARENT_MODEL_VERSION'),
        // ポーリング設定
        'max_polling_attempts' => env('REPLICATE_MAX_POLLING_ATTEMPTS', 60),
        'polling_interval' => env('REPLICATE_POLLING_INTERVAL', 2),
    ],

    /**
     * Amazon Cognito設定（Phase 1: JWT認証）
     * 
     * @see https://docs.aws.amazon.com/cognito/latest/developerguide/
     */
    'cognito' => [
        'region'        => env('COGNITO_REGION', 'ap-northeast-1'),
        'user_pool_id'  => env('COGNITO_USER_POOL_ID'),
        'client_id'     => env('COGNITO_CLIENT_ID'),
        'client_secret' => env('COGNITO_CLIENT_SECRET'),  // SPAの場合はnull
        
        // 管理者用Client ID（オプション）
        'admin_client_id' => env('COGNITO_ADMIN_CLIENT_ID'),
    ],

    /**
     * Firebase Cloud Messaging (FCM) 設定
     * 
     * Push通知送信に使用するFirebase Admin SDKの認証情報。
     * 
     * @see https://firebase.google.com/docs/admin/setup
     */
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase/credentials.json')),
    ],

    // ------------------------------------------
    // 教師アバター描画設定項目
    // ------------------------------------------
    // ■ 外見
    //// 性別
    'sex' => [
        'male'      => '男性',
        'female'    => '女性',
        'other'     => 'その他',
    ],
    //// 髪型
    'hair_style' => [
        'short'  => '短い',
        'middle' => '中くらい',
        'long'   => '長い',
    ],
    //// 髪色
    'hair_color' => [
        'black'     => '黒',
        'brown'     => '茶',
        'blonde'    => '金',
        'silver'    => '銀',
        'red'       => '赤',
    ],
    //// 目の色
    'eye_color' => [
        'black'     => '黒',
        'brown'     => '茶',
        'blue'      => '青',
        'green'     => '緑',
        'gray'      => '灰',
        'purple'    => '紫',
    ],
    //// 服装
    'clothing' => [
        'suit'   => 'スーツ',
        'casual' => 'カジュアル',
        'kimono' => '着物',
        'robe'   => 'ローブ',
        'dress'  => 'ドレス',
    ],
    //// アクセサリー
    'accessory' => [
        'nothing'    => 'なし',
        'glasses'    => '眼鏡',
        'hat'        => '帽子',
        'necklace'   => 'ネックレス',
        'cheer'   => '応援メガホン',
    ],
    //// 体形
    'body_type' => [
        'slim'    => '細身',
        'average' => '標準',
        'sturdy'  => 'がっしり',
        'chubby'  => 'ぽっちゃり',
    ],

    // ■ 性格
    //// 口調
    'tone' => [
        'gentle'       => '優しい',
        'friendly'     => 'フレンドリー',
        'strict'       => '厳しい',
        'intellectual' => '知的',
    ],
    //// 熱意
    'enthusiasm' => [
        'modest' => '控え目',
        'normal' => '普通',
        'high'   => '高い',
    ],
    //// 丁寧さ
    'formality' => [
        'polite' => '丁寧',
        'casual' => 'カジュアル',
        'formal' => 'フォーマル',
    ],
    //// ユーモア
    'humor' => [
        'high'   => '高い',
        'normal' => '普通',
        'low'    => '控え目',
    ],

    // ■ 描画モデル
    //// 描画モデル
    'draw_model_versions' => [
        'anything-v4.0'    => env('REPLICATE_MODEL_VERSION'),
        'animagine-xl-3.1' => env('REPLICATE_MODEL_VERSION_V2'),
        'stable-diffusion-3.5-medium' => env('REPLICATE_MODEL_VERSION_V3'),
    ],
    //// 推定使用トークン量（ユーザーへの事前表示用 - 1回のアバター生成で5枚分）
    // ※ ai_cost_ratesテーブルの値は1枚あたりの単価
    // ※ 実際のコスト計算: AICostService::calculateReplicateCost() × 枚数
    // ※ 以下は5枚分の合計値（全身5種 or バストアップ5種）
    'estimated_token_usages' => [
        'anything-v4.0'    => 5000,  // 1枚1000 × 5枚
        'animagine-xl-3.1' => 2000,  // 1枚400 × 5枚
        'stable-diffusion-3.5-medium' => 23000, // 1枚4600 × 5枚
    ],
];
