/**
 * スケジュールタスク管理フック
 * 
 * ScheduledTaskServiceを呼び出し、状態管理とテーマに応じたエラーメッセージ表示を担当
 */

import { useState, useCallback } from 'react';
import scheduledTaskService from '../services/scheduledTask.service';
import { useTheme } from '../contexts/ThemeContext';
import { getErrorMessage } from '../utils/errorMessages';
import {
  ScheduledTask,
  ScheduledTaskFormData,
  ScheduledTaskRequest,
  ScheduledTaskExecution,
} from '../types/scheduled-task.types';

/**
 * スケジュールタスクフックの戻り値型
 */
interface UseScheduledTasksReturn {
  // 状態
  scheduledTasks: ScheduledTask[];
  formData: ScheduledTaskFormData | null;
  executionHistory: ScheduledTaskExecution[];
  isLoading: boolean;
  error: string | null;

  // スケジュールタスク操作
  getScheduledTasks: (groupId: number) => Promise<ScheduledTask[]>;
  getCreateFormData: (groupId: number) => Promise<ScheduledTaskFormData | null>;
  createScheduledTask: (data: ScheduledTaskRequest) => Promise<ScheduledTask | null>;
  getEditFormData: (id: number) => Promise<any>;
  updateScheduledTask: (id: number, data: ScheduledTaskRequest) => Promise<ScheduledTask | null>;
  deleteScheduledTask: (id: number) => Promise<boolean>;
  pauseScheduledTask: (id: number) => Promise<ScheduledTask | null>;
  resumeScheduledTask: (id: number) => Promise<ScheduledTask | null>;
  getExecutionHistory: (id: number) => Promise<ScheduledTaskExecution[]>;
  
  // ユーティリティ
  clearError: () => void;
  refreshScheduledTasks: (groupId: number) => Promise<void>;
}

/**
 * スケジュールタスク管理カスタムフック
 * 
 * ScheduledTaskServiceを呼び出し、UIに必要な状態管理とエラーハンドリングを提供
 * テーマに応じたエラーメッセージを自動変換
 * 
 * @example
 * ```tsx
 * const { scheduledTasks, isLoading, error, getScheduledTasks } = useScheduledTasks();
 * 
 * useEffect(() => {
 *   getScheduledTasks(groupId);
 * }, [groupId]);
 * ```
 */
export const useScheduledTasks = (): UseScheduledTasksReturn => {
  const { theme } = useTheme();
  const [scheduledTasks, setScheduledTasks] = useState<ScheduledTask[]>([]);
  const [formData, setFormData] = useState<ScheduledTaskFormData | null>(null);
  const [executionHistory, setExecutionHistory] = useState<ScheduledTaskExecution[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  /**
   * エラーをテーマに応じたメッセージに変換してセット
   */
  const handleError = useCallback(
    (error: any) => {
      const errorMessage = error.message || 'UNKNOWN_ERROR';
      const localizedMessage = getErrorMessage(errorMessage, theme);
      setError(localizedMessage);
    },
    [theme]
  );

  /**
   * エラーをクリア
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  /**
   * スケジュールタスク一覧を取得
   * 
   * @param groupId - グループID
   * @returns スケジュールタスク配列
   */
  const getScheduledTasks = useCallback(
    async (groupId: number): Promise<ScheduledTask[]> => {
      try {
        console.log('[useScheduledTasks] getScheduledTasks started, groupId:', groupId);
        setIsLoading(true);
        setError(null);

        const tasks = await scheduledTaskService.getScheduledTasks(groupId);
        console.log('[useScheduledTasks] getScheduledTasks success, tasks count:', tasks.length);
        setScheduledTasks(tasks);
        return tasks;
      } catch (err: any) {
        console.error('[useScheduledTasks] getScheduledTasks error:', err);
        console.error('[useScheduledTasks] getScheduledTasks error message:', err.message);
        handleError(err);
        setScheduledTasks([]);
        return [];
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * 作成フォームデータを取得
   * 
   * @param groupId - グループID
   * @returns フォームデータ（エラー時はnull）
   */
  const getCreateFormData = useCallback(
    async (groupId: number): Promise<ScheduledTaskFormData | null> => {
      try {
        console.log('[useScheduledTasks] getCreateFormData started, groupId:', groupId);
        setIsLoading(true);
        setError(null);

        const data = await scheduledTaskService.getCreateFormData(groupId);
        console.log('[useScheduledTasks] getCreateFormData success');
        setFormData(data);
        return data;
      } catch (err: any) {
        console.error('[useScheduledTasks] getCreateFormData error:', err);
        handleError(err);
        setFormData(null);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * スケジュールタスクを作成
   * 
   * @param data - スケジュールタスク作成データ
   * @returns 作成されたスケジュールタスク（失敗時はnull）
   */
  const createScheduledTask = useCallback(
    async (data: ScheduledTaskRequest): Promise<ScheduledTask | null> => {
      try {
        console.log('[useScheduledTasks] createScheduledTask started');
        setIsLoading(true);
        setError(null);

        const newTask = await scheduledTaskService.createScheduledTask(data);
        console.log('[useScheduledTasks] createScheduledTask success, id:', newTask.id);
        
        // 楽観的更新: 作成したタスクをリストに追加
        setScheduledTasks((prev) => [...prev, newTask]);

        return newTask;
      } catch (err: any) {
        console.error('[useScheduledTasks] createScheduledTask error:', err);
        handleError(err);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * 編集フォームデータを取得
   * 
   * @param id - スケジュールタスクID
   * @returns フォームデータ（エラー時はnull）
   * @note getEditFormDataの戻り値はScheduledTaskEditResponse['data']であり、
   *       ScheduledTaskFormDataとは異なるため、any型を使用
   */
  const getEditFormData = useCallback(
    async (id: number) => {
      try {
        console.log('[useScheduledTasks] getEditFormData started, id:', id);
        setIsLoading(true);
        setError(null);

        const data = await scheduledTaskService.getEditFormData(id);
        console.log('[useScheduledTasks] getEditFormData success');
        // getEditFormDataの戻り値はscheduled_taskとgroup_membersのみ
        // formDataにはセットしない（型が不一致）
        return data;
      } catch (err: any) {
        console.error('[useScheduledTasks] getEditFormData error:', err);
        handleError(err);
        setFormData(null);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * スケジュールタスクを更新
   * 
   * @param id - スケジュールタスクID
   * @param data - 更新データ
   * @returns 更新されたスケジュールタスク（失敗時はnull）
   */
  const updateScheduledTask = useCallback(
    async (id: number, data: ScheduledTaskRequest): Promise<ScheduledTask | null> => {
      try {
        console.log('[useScheduledTasks] updateScheduledTask started, id:', id);
        setIsLoading(true);
        setError(null);

        const updatedTask = await scheduledTaskService.updateScheduledTask(id, data);
        console.log('[useScheduledTasks] updateScheduledTask success');
        
        // 楽観的更新: タスクリストを更新
        setScheduledTasks((prev) =>
          prev.map((task) => (task.id === id ? updatedTask : task))
        );

        return updatedTask;
      } catch (err: any) {
        console.error('[useScheduledTasks] updateScheduledTask error:', err);
        handleError(err);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * スケジュールタスクを削除
   * 
   * @param id - スケジュールタスクID
   * @returns 成功したかどうか
   */
  const deleteScheduledTask = useCallback(
    async (id: number): Promise<boolean> => {
      try {
        console.log('[useScheduledTasks] deleteScheduledTask started, id:', id);
        setIsLoading(true);
        setError(null);

        await scheduledTaskService.deleteScheduledTask(id);
        console.log('[useScheduledTasks] deleteScheduledTask success');
        
        // 楽観的更新: タスクリストから削除
        setScheduledTasks((prev) => prev.filter((task) => task.id !== id));

        return true;
      } catch (err: any) {
        console.error('[useScheduledTasks] deleteScheduledTask error:', err);
        handleError(err);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * スケジュールタスクを一時停止
   * 
   * @param id - スケジュールタスクID
   * @returns 更新されたスケジュールタスク（失敗時はnull）
   */
  const pauseScheduledTask = useCallback(
    async (id: number): Promise<ScheduledTask | null> => {
      try {
        console.log('[useScheduledTasks] pauseScheduledTask started, id:', id);
        setIsLoading(true);
        setError(null);

        const updatedTask = await scheduledTaskService.pauseScheduledTask(id);
        console.log('[useScheduledTasks] pauseScheduledTask success');
        
        // 楽観的更新: タスクリストを更新
        setScheduledTasks((prev) =>
          prev.map((task) => (task.id === id ? updatedTask : task))
        );

        return updatedTask;
      } catch (err: any) {
        console.error('[useScheduledTasks] pauseScheduledTask error:', err);
        handleError(err);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * スケジュールタスクを再開
   * 
   * @param id - スケジュールタスクID
   * @returns 更新されたスケジュールタスク（失敗時はnull）
   */
  const resumeScheduledTask = useCallback(
    async (id: number): Promise<ScheduledTask | null> => {
      try {
        console.log('[useScheduledTasks] resumeScheduledTask started, id:', id);
        setIsLoading(true);
        setError(null);

        const updatedTask = await scheduledTaskService.resumeScheduledTask(id);
        console.log('[useScheduledTasks] resumeScheduledTask success');
        
        // 楽観的更新: タスクリストを更新
        setScheduledTasks((prev) =>
          prev.map((task) => (task.id === id ? updatedTask : task))
        );

        return updatedTask;
      } catch (err: any) {
        console.error('[useScheduledTasks] resumeScheduledTask error:', err);
        handleError(err);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * 実行履歴を取得
   * 
   * @param id - スケジュールタスクID
   * @returns 実行履歴配列
   */
  const getExecutionHistory = useCallback(
    async (id: number): Promise<ScheduledTaskExecution[]> => {
      try {
        console.log('[useScheduledTasks] getExecutionHistory started, id:', id);
        setIsLoading(true);
        setError(null);

        const history = await scheduledTaskService.getExecutionHistory(id);
        console.log('[useScheduledTasks] getExecutionHistory success, count:', history.length);
        setExecutionHistory(history);
        return history;
      } catch (err: any) {
        console.error('[useScheduledTasks] getExecutionHistory error:', err);
        handleError(err);
        setExecutionHistory([]);
        return [];
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * スケジュールタスクを再取得
   * 
   * @param groupId - グループID
   */
  const refreshScheduledTasks = useCallback(
    async (groupId: number) => {
      await getScheduledTasks(groupId);
    },
    [getScheduledTasks]
  );

  return {
    // 状態
    scheduledTasks,
    formData,
    executionHistory,
    isLoading,
    error,

    // スケジュールタスク操作
    getScheduledTasks,
    getCreateFormData,
    createScheduledTask,
    getEditFormData,
    updateScheduledTask,
    deleteScheduledTask,
    pauseScheduledTask,
    resumeScheduledTask,
    getExecutionHistory,

    // ユーティリティ
    clearError,
    refreshScheduledTasks,
  };
};
