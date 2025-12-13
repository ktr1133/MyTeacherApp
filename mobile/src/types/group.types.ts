/**
 * Group型定義
 * 
 * グループ管理機能で使用する型定義
 */

/**
 * グループ情報
 */
export interface Group {
  id: number;
  name: string;
  master_user_id: number;
  subscription_active: boolean;
  subscription_plan: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * グループタスク作成状況
 */
export interface GroupTaskUsage {
  current: number;
  limit: number;
  remaining: number;
  reset_at: string;
}

/**
 * グループメンバー
 */
export interface GroupMember {
  id: number;
  username: string;
  name: string | null;
  email: string;
  theme: 'adult' | 'child';
  group_edit_flg: boolean;
  is_master: boolean;
}

/**
 * グループ情報API レスポンス
 */
export interface GroupEditResponse {
  success: boolean;
  data: {
    group: Group;
    task_usage: GroupTaskUsage;
    members: GroupMember[];
  };
}

/**
 * グループ更新API リクエスト
 */
export interface UpdateGroupRequest {
  name: string;
}

/**
 * メンバー追加API リクエスト
 */
export interface AddMemberRequest {
  username: string;
  group_edit_flg: boolean;
}

/**
 * 権限更新API リクエスト
 */
export interface UpdatePermissionRequest {
  group_edit_flg: boolean;
}

/**
 * テーマ切り替えAPI リクエスト
 */
export interface ToggleThemeRequest {
  theme: 'adult' | 'child';
}
