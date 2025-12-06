/**
 * タスク管理フック
 * 
 * TaskServiceを呼び出し、状態管理とテーマに応じたエラーメッセージ表示を担当
 * 楽観的更新（Optimistic Updates）でUXを向上
 */

import { useState, useCallback } from 'react';
import { taskService } from '../services/task.service';
import { useTheme } from '../contexts/ThemeContext';
import { getErrorMessage } from '../utils/errorMessages';
import {
  Task,
  TaskFilters,
  CreateTaskData,
  UpdateTaskData,
} from '../types/task.types';

/**
 * タスクフックの戻り値型
 */
interface UseTasksReturn {
  // 状態
  tasks: Task[];
  isLoading: boolean;
  error: string | null;
  pagination: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  } | null;

  // タスク操作
  fetchTasks: (filters?: TaskFilters) => Promise<void>;
  createTask: (data: CreateTaskData) => Promise<Task | null>;
  updateTask: (taskId: number, data: UpdateTaskData) => Promise<Task | null>;
  deleteTask: (taskId: number) => Promise<boolean>;
  toggleComplete: (taskId: number) => Promise<boolean>;
  
  // 承認操作
  approveTask: (taskId: number, comment?: string) => Promise<boolean>;
  rejectTask: (taskId: number, comment: string) => Promise<boolean>;
  
  // 画像操作
  uploadImage: (taskId: number, imageUri: string) => Promise<boolean>;
  deleteImage: (taskId: number, imageId: number) => Promise<boolean>;
  
  // ユーティリティ
  clearError: () => void;
  refreshTasks: () => Promise<void>;
}

/**
 * タスク管理カスタムフック
 * 
 * TaskServiceを呼び出し、UIに必要な状態管理とエラーハンドリングを提供
 * テーマに応じたエラーメッセージを自動変換
 * 
 * @example
 * ```tsx
 * const { tasks, isLoading, error, fetchTasks } = useTasks();
 * 
 * useEffect(() => {
 *   fetchTasks({ status: 'pending' });
 * }, []);
 * ```
 */
export const useTasks = (): UseTasksReturn => {
  const { theme } = useTheme();
  const [tasks, setTasks] = useState<Task[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [pagination, setPagination] = useState<UseTasksReturn['pagination']>(null);
  const [currentFilters, setCurrentFilters] = useState<TaskFilters | undefined>(undefined);

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
   * タスク一覧を取得
   */
  const fetchTasks = useCallback(
    async (filters?: TaskFilters) => {
      try {
        setIsLoading(true);
        setError(null);
        setCurrentFilters(filters);

        const response = await taskService.getTasks(filters);
        setTasks(response.tasks);
        setPagination(response.pagination);
      } catch (err: any) {
        handleError(err);
        setTasks([]);
        setPagination(null);
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * タスクを再取得（現在のフィルターで）
   */
  const refreshTasks = useCallback(async () => {
    await fetchTasks(currentFilters);
  }, [fetchTasks, currentFilters]);

  /**
   * タスクを作成
   * 
   * @param data - タスク作成データ
   * @returns 作成されたタスク（失敗時はnull）
   */
  const createTask = useCallback(
    async (data: CreateTaskData): Promise<Task | null> => {
      try {
        setIsLoading(true);
        setError(null);

        const newTask = await taskService.createTask(data);
        
        // 楽観的更新: 作成したタスクをリストの先頭に追加
        setTasks((prev) => [newTask, ...prev]);
        
        // ページネーション更新
        if (pagination) {
          setPagination({
            ...pagination,
            total: pagination.total + 1,
          });
        }

        return newTask;
      } catch (err: any) {
        handleError(err);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError, pagination]
  );

  /**
   * タスクを更新
   * 
   * @param taskId - タスクID
   * @param data - 更新データ
   * @returns 更新されたタスク（失敗時はnull）
   */
  const updateTask = useCallback(
    async (taskId: number, data: UpdateTaskData): Promise<Task | null> => {
      try {
        setIsLoading(true);
        setError(null);

        const updatedTask = await taskService.updateTask(taskId, data);
        
        // 楽観的更新: タスクリストを更新
        setTasks((prev) =>
          prev.map((task) => (task.id === taskId ? updatedTask : task))
        );

        return updatedTask;
      } catch (err: any) {
        handleError(err);
        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * タスクを削除
   * 
   * @param taskId - タスクID
   * @returns 成功したかどうか
   */
  const deleteTask = useCallback(
    async (taskId: number): Promise<boolean> => {
      try {
        setIsLoading(true);
        setError(null);

        await taskService.deleteTask(taskId);
        
        // 楽観的更新: タスクリストから削除
        setTasks((prev) => prev.filter((task) => task.id !== taskId));
        
        // ページネーション更新
        if (pagination) {
          setPagination({
            ...pagination,
            total: pagination.total - 1,
          });
        }

        return true;
      } catch (err: any) {
        handleError(err);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError, pagination]
  );

  /**
   * タスク完了状態を切り替え
   * 
   * @param taskId - タスクID
   * @returns 成功したかどうか
   */
  const toggleComplete = useCallback(
    async (taskId: number): Promise<boolean> => {
      try {
        setIsLoading(true);
        setError(null);

        const updatedTask = await taskService.toggleTaskCompletion(taskId);
        
        // 楽観的更新: タスクリストを更新
        setTasks((prev) =>
          prev.map((task) => (task.id === taskId ? updatedTask : task))
        );

        return true;
      } catch (err: any) {
        handleError(err);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * タスクを承認
   * 
   * @param taskId - タスクID
   * @param comment - 承認コメント（任意）
   * @returns 成功したかどうか
   */
  const approveTask = useCallback(
    async (taskId: number): Promise<boolean> => {
      try {
        setIsLoading(true);
        setError(null);

        const updatedTask = await taskService.approveTask(taskId);
        
        // 楽観的更新: タスクリストを更新
        setTasks((prev) =>
          prev.map((task) => (task.id === taskId ? updatedTask : task))
        );

        return true;
      } catch (err: any) {
        handleError(err);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * タスクを却下
   * 
   * @param taskId - タスクID
   * @param comment - 却下理由（必須）
   * @returns 成功したかどうか
   */
  const rejectTask = useCallback(
    async (taskId: number): Promise<boolean> => {
      try {
        setIsLoading(true);
        setError(null);

        const updatedTask = await taskService.rejectTask(taskId);
        
        // 楽観的更新: タスクリストを更新
        setTasks((prev) =>
          prev.map((task) => (task.id === taskId ? updatedTask : task))
        );

        return true;
      } catch (err: any) {
        handleError(err);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * 画像をアップロード
   * 
   * @param taskId - タスクID
   * @param imageUri - 画像URI
   * @returns 成功したかどうか
   */
  const uploadImage = useCallback(
    async (taskId: number, imageUri: string): Promise<boolean> => {
      try {
        setIsLoading(true);
        setError(null);

        const imageData = await taskService.uploadTaskImage(taskId, imageUri);
        if (!imageData || !imageData.image) {
          throw new Error('IMAGE_UPLOAD_FAILED');
        }
        const image = imageData.image;
        
        // 楽観的更新: タスクの画像リストに追加
        setTasks((prev) =>
          prev.map((task) =>
            task.id === taskId
              ? { ...task, images: [...task.images, image] }
              : task
          )
        );

        return true;
      } catch (err: any) {
        handleError(err);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  /**
   * 画像を削除
   * 
   * @param taskId - タスクID
   * @param imageId - 画像ID
   * @returns 成功したかどうか
   */
  const deleteImage = useCallback(
    async (taskId: number, imageId: number): Promise<boolean> => {
      try {
        setIsLoading(true);
        setError(null);

        await taskService.deleteTaskImage(imageId);
        
        // 楽観的更新: タスクの画像リストから削除
        setTasks((prev) =>
          prev.map((task) =>
            task.id === taskId
              ? { ...task, images: task.images.filter((img) => img.id !== imageId) }
              : task
          )
        );

        return true;
      } catch (err: any) {
        handleError(err);
        return false;
      } finally {
        setIsLoading(false);
      }
    },
    [handleError]
  );

  return {
    // 状態
    tasks,
    isLoading,
    error,
    pagination,

    // タスク操作
    fetchTasks,
    createTask,
    updateTask,
    deleteTask,
    toggleComplete,

    // 承認操作
    approveTask,
    rejectTask,

    // 画像操作
    uploadImage,
    deleteImage,

    // ユーティリティ
    clearError,
    refreshTasks,
  };
};
