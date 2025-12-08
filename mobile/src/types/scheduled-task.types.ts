/**
 * スケジュールタスク管理機能の型定義
 * 
 * @description
 * Laravel APIと連携するスケジュールタスク管理機能の型定義。
 * グループメンバーに対して定期的に自動でタスクを作成する機能を提供。
 * 
 * @see /home/ktr/mtdev/definitions/mobile/ScheduledTaskManagement.md
 * @see /home/ktr/mtdev/app/Models/ScheduledGroupTask.php
 * @see /home/ktr/mtdev/app/Models/ScheduledTaskExecution.php
 */

/**
 * スケジュール種別
 */
export type ScheduleType = 'daily' | 'weekly' | 'monthly';

/**
 * スケジュール設定
 * 
 * @description
 * タスク自動作成のスケジュールを定義
 * 
 * @example
 * // 日次スケジュール
 * { type: 'daily', time: '09:00' }
 * 
 * @example
 * // 週次スケジュール（月・水・金）
 * { type: 'weekly', time: '09:00', days: [1, 3, 5] }
 * 
 * @example
 * // 月次スケジュール（1, 15, 28日）
 * { type: 'monthly', time: '09:00', dates: [1, 15, 28] }
 */
export interface Schedule {
  /** スケジュール種別（日次・週次・月次） */
  type: ScheduleType;
  /** 実行時刻（HH:MM形式） */
  time: string;
  /** 週次スケジュールの曜日（0=日曜, 1=月曜, ..., 6=土曜） */
  days?: number[];
  /** 月次スケジュールの日付（1～31） */
  dates?: number[];
}

/**
 * スケジュールタスク基本情報
 * 
 * @description
 * スケジュールタスクの完全な情報を保持
 * Laravel APIから取得したデータをそのまま格納
 * 
 * @see /home/ktr/mtdev/app/Models/ScheduledGroupTask.php
 */
export interface ScheduledTask {
  /** スケジュールタスクID */
  id: number;
  /** グループID */
  group_id: number;
  /** 作成者ユーザーID */
  created_by: number;
  /** タイトル（必須、最大255文字） */
  title: string;
  /** 説明（任意、最大5000文字） */
  description: string | null;
  /** 画像添付必須フラグ */
  requires_image: boolean;
  /** 報酬トークン数（0～999999） */
  reward: number;
  /** 承認必須フラグ */
  requires_approval: boolean;
  /** 割り当て先ユーザーID（nullの場合はグループ全員） */
  assigned_user_id: number | null;
  /** ランダム割り当てフラグ（Phase 2.B-7では未使用） */
  auto_assign: boolean;
  /** スケジュール設定（複数設定可） */
  schedules: Schedule[];
  /** 期限（作成後の日数、null=未設定） */
  due_duration_days: number | null;
  /** 期限（作成後の時間、null=未設定） */
  due_duration_hours: number | null;
  /** 実行開始日（YYYY-MM-DD形式） */
  start_date: string;
  /** 実行終了日（YYYY-MM-DD形式、null=無期限） */
  end_date: string | null;
  /** 祝日スキップフラグ */
  skip_holidays: boolean;
  /** 祝日時に翌営業日実行フラグ */
  move_to_next_business_day: boolean;
  /** 前回未完了タスク削除フラグ（常にtrue、ユーザー変更不可） */
  delete_incomplete_previous: boolean;
  /** タグリスト（tag_namesフィールドから取得） */
  tags: string[];
  /** タグリスト（バックエンドから返される実際のフィールド名） */
  tag_names?: string[];
  /** 有効状態フラグ（true=有効, false=一時停止） */
  is_active: boolean;
  /** 一時停止日時（ISO 8601形式、null=有効） */
  paused_at: string | null;
  /** 作成日時（ISO 8601形式） */
  created_at: string;
  /** 更新日時（ISO 8601形式） */
  updated_at: string;
}

/**
 * 実行履歴ステータス
 * 
 * @description
 * スケジュールタスクの実行結果を表すステータス
 * 
 * @see config/const.php - schedule_task_execution_statuses
 */
export type ExecutionStatus = 'success' | 'failed' | 'skipped';

/**
 * 実行履歴
 * 
 * @description
 * スケジュールタスクの実行結果を記録
 * 成功・失敗・スキップの状態と詳細情報を保持
 * 
 * @see /home/ktr/mtdev/app/Models/ScheduledTaskExecution.php
 */
export interface ScheduledTaskExecution {
  /** 実行履歴ID */
  id: number;
  /** スケジュールタスクID */
  scheduled_task_id: number;
  /** 作成されたタスクID（成功時のみ） */
  created_task_id: number | null;
  /** 削除されたタスクID（前回未完了削除時のみ） */
  deleted_task_id: number | null;
  /** 実行日時（ISO 8601形式） */
  executed_at: string;
  /** 実行ステータス */
  status: ExecutionStatus;
  /** 備考（任意、実行結果の詳細説明） */
  note: string | null;
  /** エラーメッセージ（失敗時のみ） */
  error_message: string | null;
}

/**
 * スケジュールタスク作成・更新リクエスト
 * 
 * @description
 * API送信用のリクエストデータ
 * delete_incomplete_previousは常にtrueで固定（送信不要）
 * 
 * @see POST /api/scheduled-tasks
 * @see PUT /api/scheduled-tasks/{id}
 */
export interface ScheduledTaskRequest {
  /** タイトル（必須、最大255文字） */
  title: string;
  /** 説明（任意、最大5000文字） */
  description?: string;
  /** 画像添付必須フラグ（デフォルト: false） */
  requires_image?: boolean;
  /** 報酬トークン数（デフォルト: 0） */
  reward?: number;
  /** 承認必須フラグ（デフォルト: false） */
  requires_approval?: boolean;
  /** 割り当て先ユーザーID（未指定=グループ全員） */
  assigned_user_id?: number | null;
  /** スケジュール設定（必須、複数設定可） */
  schedules: Schedule[];
  /** 期限（作成後の日数） */
  due_duration_days?: number | null;
  /** 期限（作成後の時間） */
  due_duration_hours?: number | null;
  /** 実行開始日（必須、YYYY-MM-DD形式） */
  start_date: string;
  /** 実行終了日（任意、YYYY-MM-DD形式） */
  end_date?: string | null;
  /** 祝日スキップフラグ（デフォルト: false） */
  skip_holidays?: boolean;
  /** 祝日時に翌営業日実行フラグ（デフォルト: false） */
  move_to_next_business_day?: boolean;
  /** タグリスト（任意） */
  tags?: string[];
}

/**
 * スケジュールタスク作成フォームデータ
 * 
 * @description
 * 作成画面用の初期データとマスタデータ
 * 
 * @see GET /api/scheduled-tasks/create
 */
export interface ScheduledTaskFormData {
  /** グループメンバーリスト（割り当て先選択用） */
  group_members: Array<{
    id: number;
    name: string;
    username: string;
  }>;
  /** 既存タグリスト（入力補完用） */
  tags: string[];
  /** デフォルト値 */
  defaults: {
    reward: number;
    requires_image: boolean;
    requires_approval: boolean;
    skip_holidays: boolean;
    move_to_next_business_day: boolean;
    delete_incomplete_previous: boolean;
    start_date: string;
  };
}

/**
 * 実行履歴レスポンス
 * 
 * @description
 * 実行履歴画面用のレスポンスデータ
 * 
 * @see GET /api/scheduled-tasks/{id}/history
 */
export interface ScheduledTaskHistoryResponse {
  /** スケジュールタスク基本情報 */
  scheduled_task: {
    id: number;
    title: string;
  };
  /** 実行履歴リスト（最新50件、降順） */
  executions: ScheduledTaskExecution[];
}

/**
 * スケジュールタスク一覧レスポンス
 * 
 * @description
 * 一覧画面用のレスポンスデータ
 * 
 * @see GET /api/scheduled-tasks
 */
export interface ScheduledTaskListResponse {
  message: string;
  data: {
    scheduled_tasks: ScheduledTask[];
  };
}

/**
 * スケジュールタスク作成フォームレスポンス
 * 
 * @description
 * 作成画面用のレスポンスデータ
 * 
 * @see GET /api/scheduled-tasks/create
 */
export interface ScheduledTaskCreateResponse {
  message: string;
  data: ScheduledTaskFormData;
}

/**
 * スケジュールタスク編集フォームレスポンス
 * 
 * @description
 * 編集画面用のレスポンスデータ
 * 
 * @see GET /api/scheduled-tasks/{id}/edit
 */
export interface ScheduledTaskEditResponse {
  message: string;
  data: {
    scheduled_task: ScheduledTask;
    group_members: Array<{
      id: number;
      name: string;
    }>;
  };
}

/**
 * API共通レスポンス
 * 
 * @description
 * 作成・更新・削除・一時停止・再開のレスポンス
 */
export interface ScheduledTaskApiResponse {
  /** 成功フラグ */
  success: boolean;
  /** メッセージ */
  message: string;
  /** レスポンスデータ（任意） */
  data?: {
    id?: number;
    title?: string;
    scheduled_task?: ScheduledTask;
  };
}

/**
 * 実行履歴API レスポンス
 * 
 * @description
 * 実行履歴取得のレスポンス
 * 
 * @see GET /api/scheduled-tasks/{id}/history
 */
export interface ScheduledTaskHistoryApiResponse {
  message: string;
  data: ScheduledTaskHistoryResponse;
}
