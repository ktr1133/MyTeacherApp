/**
 * アバター型定義
 */

/**
 * アバターイベント種別
 * config/const.php の avatar_events に対応
 */
export type AvatarEventType =
  | 'task_created'
  | 'task_updated'
  | 'task_deleted'
  | 'task_completed'
  | 'task_breakdown'
  | 'task_breakdown_refine'
  | 'group_task_created'
  | 'group_task_updated'
  | 'group_edited'
  | 'login'
  | 'logout'
  | 'login_gap'
  | 'token_purchased'
  | 'performance_personal_viewed'
  | 'performance_group_viewed'
  | 'tag_created'
  | 'tag_updated'
  | 'tag_deleted'
  | 'group_created'
  | 'group_deleted'
  | 'notification_created'
  | 'notification_updated';

/**
 * アバター表情種別
 */
export type AvatarExpressionType = 'normal' | 'happy' | 'sad' | 'angry' | 'surprised';

/**
 * アバター画像タイプ
 */
export type AvatarImageType = 'bust' | 'full_body';

/**
 * アバターアニメーション種別
 */
export type AvatarAnimationType =
  | 'avatar-idle'
  | 'avatar-joy'
  | 'avatar-worry'
  | 'avatar-cheer'
  | 'avatar-question'
  | 'avatar-secretary'
  | 'avatar-wave'
  | 'avatar-goodbye'
  | 'avatar-thanks'
  | 'avatar-applause'
  | 'avatar-nod'
  | 'avatar-shake'
  | 'avatar-bless'
  | 'avatar-confirm';

/**
 * アバターコメントレスポンス
 */
export interface AvatarCommentResponse {
  comment: string;
  imageUrl: string;
  animation: AvatarAnimationType;
}

/**
 * アバター表示データ
 */
export interface AvatarDisplayData {
  comment: string;
  imageUrl: string;
  animation: AvatarAnimationType;
  eventType: AvatarEventType;
  timestamp: number;
}

/**
 * アバターウィジェット設定
 */
export interface AvatarWidgetConfig {
  autoHideDelay?: number; // 自動非表示までの遅延時間（ミリ秒）、デフォルト: 20000
  position?: 'top' | 'center' | 'bottom'; // 表示位置、デフォルト: 'center'
  enableAnimation?: boolean; // アニメーション有効化、デフォルト: true
}

/**
 * アバター表示状態
 */
export interface AvatarState {
  isVisible: boolean;
  currentData: AvatarDisplayData | null;
  isLoading: boolean;
}
