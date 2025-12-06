/**
 * タスク管理機能の型定義
 * 
 * Laravel API（IndexTaskApiAction等）のレスポンス形式に準拠
 */

/**
 * タスクステータス
 */
export type TaskStatus = 'pending' | 'completed' | 'approved' | 'rejected';

/**
 * タスク期間（span）
 * 
 * config('const.task_spans'):
 * - 1: 短期タスク（当日～1週間）
 * - 2: 中期タスク（1週間～1ヶ月）
 * - 3: 長期タスク（1ヶ月以上、due_dateは文字列）
 */
export type TaskSpan = 1 | 2 | 3;

/**
 * タスク優先度
 * 
 * 1: 最高優先度
 * 5: 最低優先度
 */
export type TaskPriority = 1 | 2 | 3 | 4 | 5;

/**
 * タスク画像
 */
export interface TaskImage {
  id: number;
  path: string;
  url: string;
}

/**
 * タスクオブジェクト（API レスポンス形式）
 */
export interface Task {
  id: number;
  title: string;
  description: string | null;
  span: TaskSpan;
  due_date: string | null; // 'YYYY-MM-DD' or '2年後' (長期タスク)
  priority: TaskPriority;
  status: TaskStatus;
  reward: number;
  requires_approval: boolean;
  requires_image: boolean;
  is_group_task: boolean;
  group_task_id: string | null; // UUID
  assigned_by_user_id: number | null;
  tags: string[];
  images: TaskImage[];
  created_at: string; // ISO 8601
  updated_at: string; // ISO 8601
}

/**
 * タスク一覧APIレスポンス
 */
export interface TaskListResponse {
  success: boolean;
  data: {
    tasks: Task[];
    pagination: {
      current_page: number;
      per_page: number;
      total: number;
      last_page: number;
      from: number | null;
      to: number | null;
    };
  };
}

/**
 * タスク作成リクエストデータ
 */
export interface CreateTaskData {
  title: string;
  description?: string;
  span: TaskSpan;
  due_date?: string; // 'YYYY-MM-DD' or '2年後'
  priority?: TaskPriority;
  reward?: number;
  requires_approval?: boolean;
  requires_image?: boolean;
  tag_ids?: number[];
  // グループタスク用
  is_group_task?: boolean;
  assigned_user_ids?: number[];
}

/**
 * タスク更新リクエストデータ
 */
export interface UpdateTaskData {
  title?: string;
  description?: string;
  span?: TaskSpan;
  due_date?: string;
  priority?: TaskPriority;
  reward?: number;
  requires_approval?: boolean;
  requires_image?: boolean;
  tag_ids?: number[];
}

/**
 * タスク作成/更新APIレスポンス
 */
export interface TaskResponse {
  success: boolean;
  data?: {
    task: Task;
  };
  message?: string;
}

/**
 * タスク完了切り替えAPIレスポンス
 */
export interface ToggleTaskResponse {
  success: boolean;
  data?: {
    task: Task;
  };
  message?: string;
}

/**
 * タスク承認/却下APIレスポンス
 */
export interface ApprovalResponse {
  success: boolean;
  data?: {
    task: Task;
  };
  message?: string;
}

/**
 * 画像アップロードAPIレスポンス
 */
export interface ImageUploadResponse {
  success: boolean;
  data?: {
    image: TaskImage;
  };
  message?: string;
}

/**
 * エラーレスポンス
 */
export interface ErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}

/**
 * タスクフィルター（クエリパラメータ）
 */
export interface TaskFilters {
  status?: TaskStatus;
  page?: number;
  per_page?: number;
}
