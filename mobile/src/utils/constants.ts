/**
 * アプリ全体で使用する定数定義
 */

// API設定
export const API_CONFIG = {
  // Phase 2.B-3: ngrok経由でLaravelに接続（AP Isolation回避）
  // ngrok URL: https://fizzy-formless-sandi.ngrok-free.dev
  BASE_URL: process.env.EXPO_PUBLIC_API_URL || 'https://fizzy-formless-sandi.ngrok-free.dev/api',
  TIMEOUT: 10000,
} as const;

// ストレージキー
export const STORAGE_KEYS = {
  JWT_TOKEN: 'jwt_token',
  FCM_TOKEN: 'fcm_token',
  USER_DATA: 'user_data', // プロフィール編集用の詳細ユーザー情報
  CURRENT_USER: 'current_user', // 全画面共通で使用する基本ユーザー情報（テーマ等）
  NOTIFICATIONS_CACHE: 'notifications_cache', // 通知一覧キャッシュ
  TOKEN_BALANCE: 'token_balance', // トークン残高キャッシュ
} as const;

// アプリ設定
export const APP_CONFIG = {
  APP_NAME: 'MyTeacher',
  VERSION: '1.0.0',
} as const;

// ============================================================
// アバター設定値（Phase 2.B-7）
// config/services.php の定義に対応
// ============================================================

/**
 * アバター設定オプション
 */
export const AVATAR_OPTIONS = {
  sex: [
    { value: 'male' as const, label: '男性', emoji: '👨' },
    { value: 'female' as const, label: '女性', emoji: '👩' },
    { value: 'other' as const, label: 'その他', emoji: '🧑' },
  ],
  hair_style: [
    { value: 'short' as const, label: 'ショート' },
    { value: 'middle' as const, label: 'ミディアム' },
    { value: 'long' as const, label: 'ロング' },
  ],
  hair_color: [
    { value: 'black' as const, label: '黒' },
    { value: 'brown' as const, label: '茶' },
    { value: 'blonde' as const, label: '金' },
    { value: 'silver' as const, label: '銀' },
    { value: 'red' as const, label: '赤' },
  ],
  eye_color: [
    { value: 'black' as const, label: '黒' },
    { value: 'brown' as const, label: '茶' },
    { value: 'blue' as const, label: '青' },
    { value: 'green' as const, label: '緑' },
    { value: 'gray' as const, label: '灰' },
    { value: 'purple' as const, label: '紫' },
  ],
  clothing: [
    { value: 'suit' as const, label: 'スーツ' },
    { value: 'casual' as const, label: 'カジュアル' },
    { value: 'kimono' as const, label: '着物' },
    { value: 'robe' as const, label: 'ローブ' },
    { value: 'dress' as const, label: 'ドレス' },
  ],
  accessory: [
    { value: 'nothing' as const, label: 'なし' },
    { value: 'glasses' as const, label: '眼鏡' },
    { value: 'hat' as const, label: '帽子' },
    { value: 'necklace' as const, label: 'ネックレス' },
    { value: 'cheer' as const, label: '応援メガホン' },
  ],
  body_type: [
    { value: 'slim' as const, label: '細身' },
    { value: 'average' as const, label: '標準' },
    { value: 'sturdy' as const, label: 'がっしり' },
    { value: 'chubby' as const, label: 'ぽっちゃり' },
  ],
  tone: [
    { value: 'gentle' as const, label: '優しい' },
    { value: 'friendly' as const, label: 'フレンドリー' },
    { value: 'strict' as const, label: '厳しい' },
    { value: 'intellectual' as const, label: '知的' },
  ],
  enthusiasm: [
    { value: 'modest' as const, label: '控え目' },
    { value: 'normal' as const, label: '普通' },
    { value: 'high' as const, label: '高い' },
  ],
  formality: [
    { value: 'polite' as const, label: '丁寧' },
    { value: 'casual' as const, label: 'カジュアル' },
    { value: 'formal' as const, label: 'フォーマル' },
  ],
  humor: [
    { value: 'high' as const, label: '高い' },
    { value: 'normal' as const, label: '普通' },
    { value: 'low' as const, label: '控え目' },
  ],
  draw_model_version: [
    { 
      value: 'anything-v4.0' as const, 
      label: 'anything-v4.0',
      description: '線の細いタッチで描画',
      estimatedTokenUsage: 5000, // 1枚1000トークン × 5枚
    },
    { 
      value: 'animagine-xl-3.1' as const, 
      label: 'animagine-xl-3.1',
      description: '豊かな色彩のイラスト',
      estimatedTokenUsage: 2000, // 1枚400トークン × 5枚
    },
    { 
      value: 'stable-diffusion-3.5-medium' as const, 
      label: 'stable-diffusion-3.5-medium',
      description: '25億のパラメータで高品質描画',
      estimatedTokenUsage: 23000, // 1枚4600トークン × 5枚
    },
  ],
} as const;

/**
 * アバター生成ステータス
 */
export const AVATAR_GENERATION_STATUS = {
  PENDING: 'pending',
  PROCESSING: 'processing',
  COMPLETED: 'completed',
  FAILED: 'failed',
} as const;

/**
 * アバター生成に必要なトークン（作成時）
 */
export const AVATAR_TOKEN_COST = {
  CREATE: 100000, // アバター作成
  REGENERATE: 50000, // 画像再生成（旧デフォルト値）
} as const;

/**
 * モデル別の推定トークン消費量（画像再生成時）
 * ※ 5枚分の合計値（全身5種 or バストアップ5種）
 */
export const ESTIMATED_TOKEN_USAGES: Record<string, number> = {
  'anything-v4.0': 5000,  // 1枚1000 × 5枚
  'animagine-xl-3.1': 2000,  // 1枚400 × 5枚
  'stable-diffusion-3.5-medium': 23000, // 1枚4600 × 5枚
} as const;
