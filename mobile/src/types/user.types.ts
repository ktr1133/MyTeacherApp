/**
 * ユーザー関連の型定義
 */

/**
 * テーマタイプ（Web版と同一）
 * 
 * - adult: 大人向けテーマ
 * - child: 子ども向けテーマ
 */
export type ThemeType = 'adult' | 'child';

/**
 * ユーザー情報（Laravel API: /api/v1/profile/edit のレスポンス）
 */
export interface User {
  id: number;
  username: string;
  name: string | null;
  email: string;
  avatar_path: string | null;
  avatar_url?: string | null; // S3/MinIO URL（プロフィール画面用）
  timezone: string;
  theme: ThemeType;
  group_id: number | null;
  group_edit_flg: boolean;
  group?: {
    id: number;
    name: string;
    master_user_id: number;
  } | null;
  auth_provider: string;
  cognito_sub: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * プロフィール取得APIレスポンス
 */
export interface ProfileResponse {
  success: boolean;
  data: User;
  message?: string;
}
