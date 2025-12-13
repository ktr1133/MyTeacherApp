/**
 * API関連の型定義
 */

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface User {
  id: number;
  name: string;
  username?: string; // ログイン時・GET /api/v1/user/current で使用
  email?: string;    // 登録時・一部APIで使用
  avatar_url?: string;
  created_at?: string;
  group_id?: number;
  group?: {
    id: number;
    name: string;
    owner_user_id?: number; // 一部APIで欠落の可能性
    master_user_id: number;
  };
  group_edit_flg?: boolean;
  teacher_avatar_id?: number;
  theme?: 'adult' | 'child';
}

export interface AuthResponse {
  token: string;
  user: User;
}

// ============================================================
// FCM関連の型定義（Phase 2.B-7.5）
// ============================================================

/**
 * デバイス種別
 */
export type DeviceType = 'ios' | 'android';

/**
 * FCMトークン登録リクエスト
 */
export interface FcmTokenRequest {
  device_token: string;
  device_type: DeviceType;
  device_name?: string;
  app_version?: string;
}

/**
 * FCMトークン削除リクエスト
 */
export interface FcmTokenDeleteRequest {
  device_token: string;
}

/**
 * 通知設定
 */
export interface NotificationSettings {
  /** Push通知全体のON/OFF */
  push_enabled: boolean;
  /** タスク通知のON/OFF */
  push_task_enabled: boolean;
  /** グループ通知のON/OFF */
  push_group_enabled: boolean;
  /** トークン通知のON/OFF */
  push_token_enabled: boolean;
  /** システム通知のON/OFF */
  push_system_enabled: boolean;
  /** 通知音のON/OFF */
  push_sound_enabled: boolean;
  /** バイブレーションのON/OFF（Android） */
  push_vibration_enabled: boolean;
}

/**
 * 通知設定更新リクエスト（部分更新可能）
 */
export type NotificationSettingsUpdateRequest = Partial<NotificationSettings>;

