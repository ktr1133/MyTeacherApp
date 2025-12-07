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
