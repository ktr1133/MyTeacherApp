/**
 * タスク関連の型定義
 */

export interface Task {
  id: number;
  title: string;
  description?: string;
  due_date?: string;
  priority: number;
  status: 'incomplete' | 'completed';
  user_id: number;
  group_task_id?: string;
  assigned_by_user_id?: number;
  requires_approval: boolean;
  created_at: string;
  updated_at: string;
}

export interface CreateTaskData {
  title: string;
  description?: string;
  due_date?: string;
  priority?: number;
  tag_ids?: number[];
  assigned_user_ids?: number[];
  requires_approval?: boolean;
}

export interface TaskFilter {
  status?: 'incomplete' | 'completed';
  tag_id?: number;
  date_from?: string;
  date_to?: string;
}
