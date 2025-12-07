/**
 * 通知機能の型定義
 * 
 * Laravel API `/api/notifications` エンドポイントのレスポンス型
 */

/**
 * 通知タイプ（Laravel const.phpと同期）
 */
export type NotificationType =
  | 'group_task_created'      // グループタスク作成
  | 'task_approval_requested' // タスク承認リクエスト
  | 'task_approved'           // タスク承認済み
  | 'task_rejected'           // タスク却下
  | 'avatar_generated'        // アバター生成完了
  | 'token_low'               // トークン残量低下
  | 'token_depleted'          // トークン枯渇
  | 'payment_success'         // 決済成功
  | 'payment_failed'          // 決済失敗
  | 'group_task_assigned'     // グループタスク割当
  | 'approval_required'       // 承認待ち
  | 'admin_announcement'      // お知らせ
  | 'admin_maintenance'       // メンテナンス
  | 'admin_update'            // アップデート
  | 'admin_warning'           // 警告
  | 'system';                 // システム通知

/**
 * 通知種別を日本語表示に変換
 * Laravel config/const.php の notification_types 定義に基づく
 * 
 * @param type 通知種別（Laravel実装値）
 * @returns 日本語表示
 */
export const getNotificationTypeLabel = (type: string | null): string => {
  if (!type) return 'その他';
  
  const typeMap: Record<string, string> = {
    // システム通知
    'token_low': 'トークン残量低下',
    'token_depleted': 'トークン枯渇',
    'payment_success': '決済成功',
    'payment_failed': '決済失敗',
    'group_task_created': 'グループタスク作成',
    'group_task_assigned': 'グループタスク割当',
    'avatar_generated': 'アバター生成完了',
    'approval_required': '承認待ち',
    'task_approved': 'タスク承認',
    'task_rejected': 'タスク却下',
    
    // 管理者通知
    'admin_announcement': 'お知らせ',
    'admin_maintenance': 'メンテナンス',
    'admin_update': 'アップデート',
    'admin_warning': '警告',
  };
  
  return typeMap[type] || type;
};

/**
 * 通知テンプレート
 * Laravel API実装に完全準拠
 * - priority: 'info' | 'normal' | 'important' (Laravel実装値)
 * - content/category: null許容 (データがない場合あり)
 */
export interface NotificationTemplate {
  id: number;
  title: string;
  content: string | null;
  priority: 'info' | 'normal' | 'important';
  category: string | null;
}

/**
 * 通知データ
 */
export interface Notification {
  id: number;
  user_id: number;
  notification_template_id: number;
  is_read: boolean;
  read_at: string | null;           // 既読日時（ISO 8601形式）
  created_at: string;               // 作成日時（ISO 8601形式）
  updated_at: string;               // 更新日時（ISO 8601形式）
  template: NotificationTemplate | null; // テンプレート情報
}

/**
 * 通知一覧レスポンス
 * GET /api/notifications
 */
export interface NotificationListResponse {
  success: boolean;
  data: {
    notifications: Notification[];
    unread_count: number;
    pagination: {
      total: number;
      per_page: number;
      current_page: number;
      last_page: number;
      from: number | null;
      to: number | null;
    };
  };
}

/**
 * 通知詳細レスポンス
 * GET /api/notifications/{id}
 */
export interface NotificationDetailResponse {
  success: boolean;
  data: {
    notification: Notification;
  };
}

/**
 * 未読件数レスポンス
 * GET /api/notifications/unread-count
 */
export interface UnreadCountResponse {
  success: boolean;
  count: number;  // 直接countプロパティ
}

/**
 * 既読化レスポンス
 * PATCH /api/notifications/{id}/read
 */
export interface MarkAsReadResponse {
  success: boolean;
  message: string;
}

/**
 * 全既読化レスポンス
 * POST /api/notifications/read-all
 */
export interface MarkAllAsReadResponse {
  success: boolean;
  message: string;
}

/**
 * 通知検索レスポンス
 * GET /api/notifications/search
 */
export interface SearchNotificationsResponse {
  success: boolean;
  data: {
    notifications: Notification[];
    search_params: {
      terms: string[];
      operator: string;
    };
    pagination: {
      total: number;
      per_page: number;
      current_page: number;
      last_page: number;
      from: number | null;
      to: number | null;
    };
  };
}
