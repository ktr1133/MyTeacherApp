/**
 * タグ-タスク紐付け管理Hook
 * 
 * Web版との整合性（mobile-rules.md総則4項準拠）:
 * - Web版APIを使用してタグとタスクの紐付け・解除を管理
 * - 楽観的更新でUXを最適化
 * - アバターイベントは紐付け・解除操作では発火しない（Web版準拠）
 * 
 * @see /home/ktr/mtdev/app/Http/Actions/Tags/TagTaskAction.php (Web版API)
 * @see /home/ktr/mtdev/docs/mobile/mobile-rules.md (モバイル開発規則)
 */

import { useState, useCallback } from 'react';
import { tagTaskService } from '../services/tag-task.service';
import { useAuth } from '../contexts/AuthContext';

/**
 * エラーメッセージを抽出
 */
const getErrorMessage = (error: any): string => {
  if (error.response?.data?.message) {
    return error.response.data.message;
  }
  if (error.message) {
    return error.message;
  }
  return 'エラーが発生しました。';
};

/**
 * タグ-タスク紐付け管理Hookの戻り値
 */
export interface UseTagTasksReturn {
  /** タグに紐づくタスク一覧 */
  linkedTasks: Array<{ id: number; title: string }>;
  /** 追加可能なタスク一覧 */
  availableTasks: Array<{ id: number; title: string }>;
  /** データ読み込み中フラグ */
  loading: boolean;
  /** エラーメッセージ */
  error: string | null;
  /** タスク紐付け中フラグ */
  attaching: boolean;
  /** タスク解除中フラグ */
  detaching: boolean;
  /** タグのタスク一覧を取得 */
  fetchTagTasks: (tagId: number) => Promise<void>;
  /** タスクをタグに紐付ける */
  attachTask: (tagId: number, taskId: number) => Promise<boolean>;
  /** タスクからタグを解除 */
  detachTask: (tagId: number, taskId: number) => Promise<boolean>;
  /** エラーをクリア */
  clearError: () => void;
}

/**
 * タグ-タスク紐付け管理Hook
 * 
 * @returns タグ-タスク紐付け管理機能
 */
export const useTagTasks = (): UseTagTasksReturn => {
  const { isAuthenticated } = useAuth();
  const [linkedTasks, setLinkedTasks] = useState<Array<{ id: number; title: string }>>([]);
  const [availableTasks, setAvailableTasks] = useState<Array<{ id: number; title: string }>>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [attaching, setAttaching] = useState(false);
  const [detaching, setDetaching] = useState(false);

  /**
   * タグのタスク一覧を取得
   */
  const fetchTagTasks = useCallback(async (tagId: number) => {
    if (!isAuthenticated) {
      setError('認証が必要です。');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const data = await tagTaskService.getTagTasks(tagId);
      setLinkedTasks(data.linked);
      setAvailableTasks(data.available);
    } catch (err) {
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  }, [isAuthenticated]);

  /**
   * タスクをタグに紐付ける（楽観的更新）
   */
  const attachTask = useCallback(async (tagId: number, taskId: number): Promise<boolean> => {
    if (!isAuthenticated) {
      setError('認証が必要です。');
      return false;
    }

    setAttaching(true);
    setError(null);

    // 楽観的更新: 紐付け前の状態を保存
    const taskToMove = availableTasks.find(t => t.id === taskId);
    if (!taskToMove) {
      setError('タスクが見つかりません。');
      setAttaching(false);
      return false;
    }

    const previousAvailable = [...availableTasks];
    const previousLinked = [...linkedTasks];

    // UIを即座に更新
    setAvailableTasks(prev => prev.filter(t => t.id !== taskId));
    setLinkedTasks(prev => [...prev, taskToMove]);

    try {
      await tagTaskService.attachTask(tagId, taskId);
      return true;
    } catch (err) {
      // エラー時はロールバック
      setAvailableTasks(previousAvailable);
      setLinkedTasks(previousLinked);
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
      return false;
    } finally {
      setAttaching(false);
    }
  }, [isAuthenticated, availableTasks, linkedTasks]);

  /**
   * タスクからタグを解除（楽観的更新）
   */
  const detachTask = useCallback(async (tagId: number, taskId: number): Promise<boolean> => {
    if (!isAuthenticated) {
      setError('認証が必要です。');
      return false;
    }

    setDetaching(true);
    setError(null);

    // 楽観的更新: 解除前の状態を保存
    const taskToMove = linkedTasks.find(t => t.id === taskId);
    if (!taskToMove) {
      setError('タスクが見つかりません。');
      setDetaching(false);
      return false;
    }

    const previousLinked = [...linkedTasks];
    const previousAvailable = [...availableTasks];

    // UIを即座に更新
    setLinkedTasks(prev => prev.filter(t => t.id !== taskId));
    setAvailableTasks(prev => [...prev, taskToMove]);

    try {
      await tagTaskService.detachTask(tagId, taskId);
      return true;
    } catch (err) {
      // エラー時はロールバック
      setLinkedTasks(previousLinked);
      setAvailableTasks(previousAvailable);
      const errorMessage = getErrorMessage(err);
      setError(errorMessage);
      return false;
    } finally {
      setDetaching(false);
    }
  }, [isAuthenticated, linkedTasks, availableTasks]);

  /**
   * エラーをクリア
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  return {
    linkedTasks,
    availableTasks,
    loading,
    error,
    attaching,
    detaching,
    fetchTagTasks,
    attachTask,
    detachTask,
    clearError,
  };
};
