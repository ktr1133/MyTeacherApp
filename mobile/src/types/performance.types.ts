/**
 * 実績・レポート機能の型定義
 * 
 * Web版Performance.mdの要件定義に基づく
 * API: GET /api/reports/performance
 */

/**
 * 期間種別
 */
export type PeriodType = 'week' | 'month' | 'year';

/**
 * タスク種別
 */
export type TaskType = 'normal' | 'group';

/**
 * グラフデータセット
 */
export interface ChartDataset {
  label: string;
  data: number[];
  backgroundColor?: string;
  borderColor?: string;
  type?: 'line' | 'bar';
}

/**
 * グラフデータ
 */
export interface ChartData {
  labels: string[];
  datasets: ChartDataset[];
}

/**
 * 集計データ
 */
export interface SummaryData {
  total_completed: number;
  total_incomplete: number;
  total_reward: number;
  average_per_day?: number;
}

/**
 * Laravel PerformanceServiceの生データ
 */
export interface RawPerformanceData {
  labels: string[];
  nDone: number[];
  nTodo: number[];
  nCum: number[];
  gDone: number[];
  gTodo: number[];
  gCum: number[];
  gReward: number[];
  gRewardCum: number[];
  periodInfo: {
    start: string;
    end: string;
    displayText: string;
    canGoPrevious: boolean;
    canGoNext: boolean;
  };
}

/**
 * パフォーマンスデータ（実績データ）
 */
export interface PerformanceData {
  period_label: string;
  task_type: TaskType;
  chart_data: ChartData;
  summary: SummaryData;
  can_navigate_prev: boolean;
  can_navigate_next: boolean;
  has_subscription: boolean;
  restrictions?: {
    period_restricted: boolean;
    navigation_restricted: boolean;
    member_restricted: boolean;
  };
  members?: GroupMember[];
  selected_user_id?: number;
  is_group_whole?: boolean;
}

/**
 * グループメンバー情報
 */
export interface GroupMember {
  id: number;
  username: string;
  name: string;
}

/**
 * メンバー統計情報
 */
export interface MemberStats {
  user_id: number;
  user_name: string; // 表示名（usersテーブルのnameカラム）
  username: string; // ユーザー名（ログインID）
  completed: number; // 合計完了タスク数（通常 + グループ）
  incomplete: number; // 未完了タスク数
  reward: number; // グループタスク報酬合計
  normal_tasks_completed: number; // 通常タスク完了数
  group_tasks_completed: number; // グループタスク完了数
}

/**
 * トレンドデータ（月次）
 */
export interface TrendData {
  labels: string[];
  normal_tasks: number[];
  group_tasks: number[];
}

/**
 * 月次レポートデータ
 */
export interface MonthlyReport {
  month_label: string;
  year_month: string;
  group_name: string;
  summary: {
    total_completed: number;
    total_incomplete: number;
    total_reward: number;
    normal_tasks_count: number;
    group_tasks_count: number;
  };
  member_stats: MemberStats[];
  trend_data: TrendData;
  has_subscription: boolean;
  can_access: boolean;
  accessible_until?: string;
  ai_summary?: {
    content: string;
    generated_at: string;
    tokens_used: number;
  };
}

/**
 * パフォーマンスリクエストパラメータ
 */
export interface PerformanceParams {
  tab?: TaskType;
  period?: PeriodType;
  offset?: number;
  user_id?: number;
}

/**
 * 月次レポートリクエストパラメータ
 */
export interface MonthlyReportParams {
  year?: string;
  month?: string;
}

/**
 * メンバーサマリー生成リクエスト
 */
export interface GenerateMemberSummaryRequest {
  user_id: number;
  group_id: number;
  year_month: string;  // YYYY-MM形式
}

/**
 * メンバーサマリーレスポンス（API生データ）
 */
export interface MemberSummaryResponse {
  summary: {
    user_name: string;
    username: string;
    comment: string;
    task_classification: {
      labels: string[];
      data: number[];
    };
    reward_trend: {
      labels: string[];
      data: number[];
    };
    tokens_used: number;
  };
  user_id: number;
  group_id: number;
  year_month: string;
}

/**
 * メンバーサマリーデータ（画面表示用）
 */
export interface MemberSummaryData {
  user_id: number;
  user_name: string;
  username: string; // ユーザー名（ログインID）
  year_month: string;
  comment: string;
  task_classification: {
    labels: string[];
    data: number[];
  };
  reward_trend: {
    labels: string[];
    data: number[];
  };
  tokens_used: number;
  generated_at: string;
}

/**
 * メンバーサマリーキャッシュキー
 */
export interface MemberSummaryCacheKey {
  user_id: number;
  year_month: string;
}

/**
 * 利用可能な月リスト
 */
export interface AvailableMonth {
  year: string;
  month: string;
  label: string;
  has_report?: boolean;  // レポートが実際に生成済みかどうか
  is_accessible?: boolean;  // アクセス可能かどうか
}
