<?php

return [
    /**
     * ステップ1: 性別
     */
    'sex' => [
        'male' => [
            'label' => '男の子',
            'emoji' => '👦',
            'color' => 'blue',
            'image' => '/images/avatars/sex/male.png', // 実際の画像パスに置き換え
        ],
        'female' => [
            'label' => '女の子',
            'emoji' => '👧',
            'color' => 'pink',
            'image' => '/images/avatars/sex/female.png',
        ],
        'other' => [
            'label' => 'その他',
            'emoji' => '🧑',
            'color' => 'gray',
            'image' => '/images/avatars/sex/other.png',
        ],
    ],

    /**
     * ステップ2: 髪の色
     */
    'hair_color' => [
        'black' => [
            'label' => '黒',
            'color' => '#2d2d2d',
            'image' => '/images/avatars/hair_color/black.svg',
        ],
        'brown' => [
            'label' => '茶色',
            'color' => '#8b4513',
            'image' => '/images/avatars/hair_color/brown.svg',
        ],
        'blonde' => [
            'label' => '金髪',
            'color' => '#ffd700',
            'image' => '/images/avatars/hair_color/blonde.svg',
        ],
        'silver' => [
            'label' => '銀',
            'color' => '#c0c0c0',
            'image' => '/images/avatars/hair_color/silver.svg',
        ],
        'red' => [
            'label' => '赤',
            'color' => '#dc143c',
            'image' => '/images/avatars/hair_color/red.svg',
        ],
    ],

    /**
     * 髪型
     */
    'hair_style' => [
        'short' => [
            'label' => 'ショート',
            'emoji' => '✂️',
            'image' => '/images/avatars/hair_style/short.png',
        ],
        'middle' => [
            'label' => 'ミドル',
            'emoji' => '✂️',
            'image' => '/images/avatars/hair_style/middle.png',
        ],
        'long' => [
            'label' => 'ロング',
            'emoji' => '✂️',
            'image' => '/images/avatars/hair_style/long.png',
        ],
    ],

    /**
     * 目の色
     */
    'eye_color' => [
        'black' => [
            'label' => '黒',
            'color' => '#2d2d2d',
            'image' => '/images/avatars/eye_color/black.svg',
        ],
        'brown' => [
            'label' => '茶色',
            'color' => '#8b4513',
            'image' => '/images/avatars/eye_color/brown.svg',
        ],
        'blue' => [
            'label' => '青',
            'color' => '#1e90ff',
            'image' => '/images/avatars/eye_color/blue.svg',
        ],
        'green' => [
            'label' => '緑',
            'color' => '#32cd32',
            'image' => '/images/avatars/eye_color/green.svg',
        ],
        'gray' => [
            'label' => 'グレー',
            'color' => '#808080',
            'image' => '/images/avatars/eye_color/gray.svg',
        ],
        'purple' => [
            'label' => '紫',
            'color' => '#9370db',
            'image' => '/images/avatars/eye_color/purple.svg',
        ],
    ],

    /**
     * 服装
     */
    'clothing' => [
        'suit' => [
            'label' => 'スーツ',
            'emoji' => '🤵',
            'image' => '/images/avatars/clothing/suit.svg',
        ],
        'casual' => [
            'label' => 'カジュアル',
            'emoji' => '👕',
            'image' => '/images/avatars/clothing/casual.svg',
        ],
        'kimono' => [
            'label' => '着物',
            'emoji' => '👘',
            'image' => '/images/avatars/clothing/kimono.svg',
        ],
        'robe' => [
            'label' => 'ローブ',
            'emoji' => '🎓',
            'image' => '/images/avatars/clothing/robe.svg',
        ],
        'dress' => [
            'label' => 'ドレス',
            'emoji' => '👗',
            'image' => '/images/avatars/clothing/dress.svg',
        ],
    ],

    /**
     * アクセサリー
     */
    'accessory' => [
        'nothing' => [
            'label' => 'なし',
            'emoji' => '🚫',
            'image' => null,
        ],
        'glasses' => [
            'label' => 'メガネ',
            'emoji' => '👓',
            'image' => '/images/avatars/accessory/glasses.svg',
        ],
        'hat' => [
            'label' => '帽子',
            'emoji' => '🎩',
            'image' => '/images/avatars/accessory/hat.svg',
        ],
        'tie' => [
            'label' => 'ネクタイ',
            'emoji' => '👔',
            'image' => '/images/avatars/accessory/tie.svg',
        ],
        'cheer' => [
            'label' => '応援メガホン',
            'emoji' => '📣',
            'image' => '/images/avatars/accessory/cheer.svg',
        ],
    ],

    /**
     * 体型
     */
    'body_type' => [
        'average' => [
            'label' => 'ふつう',
            'emoji' => '🧍',
            'image' => '/images/avatars/body_type/average.svg',
        ],
        'slim' => [
            'label' => 'ほっそり',
            'emoji' => '🧘',
            'image' => '/images/avatars/body_type/slim.svg',
        ],
        'sturdy' => [
            'label' => 'がっしり',
            'emoji' => '💪',
            'image' => '/images/avatars/body_type/sturdy.svg',
        ],
    ],

    /**
     * ステップ3: 口調
     */
    'tone' => [
        'gentle' => [
            'label' => 'やさしい',
            'emoji' => '😊',
            'image' => '/images/avatars/tone/gentle.svg',
        ],
        'strict' => [
            'label' => 'きびしい',
            'emoji' => '😤',
            'image' => '/images/avatars/tone/strict.svg',
        ],
        'friendly' => [
            'label' => 'フレンドリー',
            'emoji' => '😄',
            'image' => '/images/avatars/tone/friendly.svg',
        ],
        'intellectual' => [
            'label' => 'かしこい',
            'emoji' => '🤓',
            'image' => '/images/avatars/tone/intellectual.svg',
        ],
    ],

    /**
     * 熱意
     */
    'enthusiasm' => [
        'high' => [
            'label' => 'げんき',
            'emoji' => '🔥',
            'image' => '/images/avatars/enthusiasm/high.svg',
        ],
        'normal' => [
            'label' => 'ふつう',
            'emoji' => '😌',
            'image' => '/images/avatars/enthusiasm/normal.svg',
        ],
        'modest' => [
            'label' => 'おだやか',
            'emoji' => '😇',
            'image' => '/images/avatars/enthusiasm/modest.svg',
        ],
    ],

    /**
     * 丁寧さ
     */
    'formality' => [
        'polite' => [
            'label' => 'ていねい',
            'emoji' => '🙇',
            'image' => '/images/avatars/formality/polite.svg',
        ],
        'casual' => [
            'label' => 'カジュアル',
            'emoji' => '👋',
            'image' => '/images/avatars/formality/casual.svg',
        ],
        'formal' => [
            'label' => 'かっちり',
            'emoji' => '🎩',
            'image' => '/images/avatars/formality/formal.svg',
        ],
    ],

    /**
     * ユーモア
     */
    'humor' => [
        'high' => [
            'label' => 'おもしろい',
            'emoji' => '😂',
            'image' => '/images/avatars/humor/high.svg',
        ],
        'normal' => [
            'label' => 'まじめ',
            'emoji' => '😐',
            'image' => '/images/avatars/humor/normal.svg',
        ],
        'low' => [
            'label' => 'きかい',
            'emoji' => '🤔',
            'image' => '/images/avatars/humor/low.svg',
        ],
    ],

    /**
     * ステップ4: 描画モデル（画風）
     */
    'draw_models' => [
        'anything-v4.0' => [
            'label' => 'ふんわり',
            'description' => 'せんがほそいタッチで描くよ',
            'sample_image' => '/images/avatars/models/anything-v4.png',
            'token_cost' => config('services.estimated_token_usages.anything-v4.0', 5000),
            'features' => ['やわらかい', 'かわいい'],
        ],
        'animagine-xl-3.1' => [
            'label' => 'カラフル',
            'description' => 'いろあざやかに描くよ',
            'sample_image' => '/images/avatars/models/animagine-xl-3.png',
            'token_cost' => config('services.estimated_token_usages.animagine-xl-3.1', 2000),
            'features' => ['カラフル', 'きれい'],
        ],
        'stable-diffusion-3.5-medium' => [
            'label' => 'ちみつ',
            'description' => 'ちみつに描くよ',
            'sample_image' => '/images/avatars/models/stable-diffusion.png',
            'token_cost' => config('services.estimated_token_usages.stable-diffusion-3.5-medium', 23000),
            'features' => ['ちみつ', 'すごい'],
        ],
    ],

    /**
     * デフォルト値（案Aに基づく）
     */
    'defaults' => [
        'sex' => 'male',
        'hair_color' => 'black',
        'eye_color' => 'brown',
        'clothing' => 'casual',
        'accessory' => '',
        'body_type' => 'average',
        'tone' => 'gentle',
        'enthusiasm' => 'normal',
        'formality' => 'polite',
        'humor' => 'normal',
        'draw_model_version' => 'anything-v4.0',
        'is_transparent' => true,
        'is_chibi' => false,
    ],
];