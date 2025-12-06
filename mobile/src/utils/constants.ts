/**
 * アプリ全体で使用する定数定義
 */

// API設定
export const API_CONFIG = {
  // Phase 2.B-3以降: ローカルLaravelに接続する場合は 'http://localhost:8080/api' に変更
  // 本番環境: 'https://api.myteacher.example.com'
  BASE_URL: process.env.EXPO_PUBLIC_API_URL || 'https://api.myteacher.example.com',
  TIMEOUT: 10000,
} as const;

// ストレージキー
export const STORAGE_KEYS = {
  JWT_TOKEN: 'jwt_token',
  FCM_TOKEN: 'fcm_token',
  USER_DATA: 'user_data', // プロフィール編集用の詳細ユーザー情報
  CURRENT_USER: 'current_user', // 全画面共通で使用する基本ユーザー情報（テーマ等）
} as const;

// アプリ設定
export const APP_CONFIG = {
  APP_NAME: 'MyTeacher',
  VERSION: '1.0.0',
} as const;
