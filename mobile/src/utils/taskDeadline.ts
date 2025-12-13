/**
 * タスクの期限状態を判定するユーティリティ関数
 * 
 * Web版の仕様（task-card.blade.php）に準拠:
 * - 完了済み: 'completed' - 緑バッジ「完了済」
 * - 期限切れ: 'overdue' - 赤バッジ「N日超過」
 * - 期限が迫っている（24時間以内）: 'approaching' - 黄色バッジ「残りNh」（時間単位）
 * - 期限が迫っている（3日以内）: 'approaching' - 黄色バッジ「残りN日」
 * - 期限まで余裕あり: 'safe'
 * - 期限なし: 'none'
 * 
 * @see /home/ktr/mtdev/resources/views/components/task-card.blade.php
 */

import { Task } from '../types/task.types';

/**
 * 期限の状態
 */
export type DeadlineStatus = 'none' | 'safe' | 'approaching' | 'overdue' | 'completed';

/**
 * 期限状態の情報
 */
export interface DeadlineInfo {
  /** 期限の状態 */
  status: DeadlineStatus;
  /** 表示メッセージ */
  message: string;
  /** 期限までの時間数（24時間以内の場合のみ） */
  hoursUntilDue?: number;
  /** 期限までの日数（マイナスの場合は超過日数） */
  daysUntilDue?: number;
}

/**
 * タスクの期限状態を判定
 * 
 * @param task タスクオブジェクト
 * @param isChildTheme 子ども向けテーマかどうか
 * @returns 期限状態の情報
 */
export function getDeadlineStatus(task: Task, isChildTheme: boolean = false): DeadlineInfo {
  // 完了済みタスクの場合
  if (task.is_completed || task.completed_at) {
    return {
      status: 'completed',
      message: isChildTheme ? 'おわったよ！' : '完了済',
    };
  }

  // 期限がない、または短期タスク以外の場合はチェック不要
  if (!task.due_date || task.span !== 1) {
    return {
      status: 'none',
      message: '',
    };
  }

  try {
    const dueDate = new Date(task.due_date);
    const now = new Date();
    
    // 日付のみで比較（時刻は無視）
    const dueDateOnly = new Date(dueDate.getFullYear(), dueDate.getMonth(), dueDate.getDate());
    const todayOnly = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    
    // ミリ秒単位の差分
    const diffMs = dueDateOnly.getTime() - todayOnly.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
    
    // 期限切れ（過去の日付）
    if (diffDays < 0) {
      const overdueDays = Math.abs(diffDays);
      return {
        status: 'overdue',
        message: `${overdueDays}日超過`,
        daysUntilDue: diffDays,
      };
    }
    
    // 24時間以内の場合は時間単位で表示
    if (diffDays === 0) {
      // 残り時間を計算（現在時刻から期限までの時間）
      const diffHours = Math.floor((dueDate.getTime() - now.getTime()) / (1000 * 60 * 60));
      
      // 既に期限を過ぎている場合（同日だが時刻が過去）
      if (diffHours < 0) {
        return {
          status: 'overdue',
          message: '期限切れ',
          daysUntilDue: 0,
          hoursUntilDue: diffHours,
        };
      }
      
      return {
        status: 'approaching',
        message: `残り${diffHours}h`,
        hoursUntilDue: diffHours,
        daysUntilDue: 0,
      };
    }
    
    // 3日以内
    if (diffDays <= 3) {
      return {
        status: 'approaching',
        message: `残り${diffDays}日`,
        daysUntilDue: diffDays,
      };
    }
    
    // 期限まで余裕あり
    return {
      status: 'safe',
      message: '',
      daysUntilDue: diffDays,
    };
  } catch (error) {
    console.error('[getDeadlineStatus] Error parsing due_date:', error);
    return {
      status: 'none',
      message: '',
    };
  }
}

/**
 * タスクリストから最も緊急度の高いタスクの期限情報を取得
 * 
 * 優先順位:
 * 1. 期限切れ（overdue）
 * 2. 期限が迫っている（approaching）
 * 3. その他
 * 
 * @param tasks タスクの配列
 * @param isChildTheme 子ども向けテーマかどうか
 * @returns 最も緊急度の高い期限情報
 */
export function getMostUrgentDeadline(tasks: Task[], isChildTheme: boolean = false): DeadlineInfo | null {
  if (!tasks || tasks.length === 0) {
    return null;
  }

  let mostUrgent: { task: Task; info: DeadlineInfo } | null = null;

  for (const task of tasks) {
    const info = getDeadlineStatus(task, isChildTheme);
    
    // 完了済みとnoneはスキップ
    if (info.status === 'completed' || info.status === 'none' || info.status === 'safe') {
      continue;
    }

    if (!mostUrgent) {
      mostUrgent = { task, info };
      continue;
    }

    // 期限切れ（overdue）が最優先
    if (info.status === 'overdue') {
      if (mostUrgent.info.status !== 'overdue') {
        mostUrgent = { task, info };
      } else {
        // 両方overdueの場合、超過日数が多い方を優先
        const currentOverdue = Math.abs(mostUrgent.info.daysUntilDue || 0);
        const newOverdue = Math.abs(info.daysUntilDue || 0);
        if (newOverdue > currentOverdue) {
          mostUrgent = { task, info };
        }
      }
    } else if (info.status === 'approaching' && mostUrgent.info.status !== 'overdue') {
      // approachingの場合、時間/日数が少ない方を優先
      if (info.hoursUntilDue !== undefined) {
        // 時間単位の比較
        const currentHours = mostUrgent.info.hoursUntilDue !== undefined 
          ? mostUrgent.info.hoursUntilDue 
          : (mostUrgent.info.daysUntilDue || 0) * 24;
        if (info.hoursUntilDue < currentHours) {
          mostUrgent = { task, info };
        }
      } else if (mostUrgent.info.hoursUntilDue === undefined) {
        // 両方日数の場合、日数が少ない方を優先
        const currentDays = mostUrgent.info.daysUntilDue || 0;
        const newDays = info.daysUntilDue || 0;
        if (newDays < currentDays) {
          mostUrgent = { task, info };
        }
      }
    }
  }

  return mostUrgent?.info || null;
}
