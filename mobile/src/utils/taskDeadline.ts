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
 * @param userTimezone ユーザーのタイムゾーン（例: "Asia/Tokyo"）。未指定の場合は端末のローカルタイムゾーン
 * @returns 期限状態の情報
 */
export function getDeadlineStatus(task: Task, isChildTheme: boolean = false, userTimezone?: string): DeadlineInfo {
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
    // due_dateをユーザーのタイムゾーンで解釈
    // "2025-12-20"形式の日付文字列を、ユーザーのタイムゾーンでの日付として扱う
    const dueDateStr = task.due_date;
    
    // 日付フォーマット（YYYY-MM-DD）の検証
    // 長期タスクの場合は任意の文字列（例："2年後"）が入るため、早期リターン
    if (!/^\d{4}-\d{2}-\d{2}$/.test(dueDateStr)) {
      return {
        status: 'none',
        message: '',
      };
    }
    
    const [year, month, day] = dueDateStr.split('-').map(Number);
    
    // ユーザーのタイムゾーンで「今日」の日付を取得
    const now = new Date();
    let todayInUserTz: Date;
    
    if (userTimezone) {
      // ユーザーのタイムゾーンでの現在時刻を取得
      const formatter = new Intl.DateTimeFormat('ja-JP', {
        timeZone: userTimezone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
      });
      const parts = formatter.formatToParts(now);
      const todayYear = parseInt(parts.find(p => p.type === 'year')?.value || '0');
      const todayMonth = parseInt(parts.find(p => p.type === 'month')?.value || '0');
      const todayDay = parseInt(parts.find(p => p.type === 'day')?.value || '0');
      todayInUserTz = new Date(todayYear, todayMonth - 1, todayDay);
    } else {
      // タイムゾーン未指定の場合は端末のローカルタイムゾーン
      todayInUserTz = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    }
    
    // 期限日（ユーザーのタイムゾーン基準）
    const dueDateInUserTz = new Date(year, month - 1, day);
    
    // ミリ秒単位の差分
    const diffMs = dueDateInUserTz.getTime() - todayInUserTz.getTime();
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
    
    // 当日の場合は「今日」と表示（時間単位表示は不要）
    if (diffDays === 0) {
      return {
        status: 'approaching',
        message: isChildTheme ? 'きょう！' : '今日',
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
 * @param userTimezone ユーザーのタイムゾーン（例: "Asia/Tokyo"）。未指定の場合は端末のローカルタイムゾーン
 * @returns 最も緊急度の高い期限情報
 */
export function getMostUrgentDeadline(tasks: Task[], isChildTheme: boolean = false, userTimezone?: string): DeadlineInfo | null {
  if (!tasks || tasks.length === 0) {
    return null;
  }

  let mostUrgent: { task: Task; info: DeadlineInfo } | null = null;

  for (const task of tasks) {
    const info = getDeadlineStatus(task, isChildTheme, userTimezone);
    
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
      // approachingの場合、日数が少ない方を優先
      const currentDays = mostUrgent.info.daysUntilDue || 0;
      const newDays = info.daysUntilDue || 0;
      if (newDays < currentDays) {
        mostUrgent = { task, info };
      }
    }
  }

  return mostUrgent?.info || null;
}
