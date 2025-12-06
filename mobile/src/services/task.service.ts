/**
 * タスク管理サービス
 * 
 * Laravel API（/api/tasks/*）との通信を担当
 * ビジネスロジック: データ整形、エラーハンドリング
 */

import api from './api';
import {
  Task,
  TaskListResponse,
  CreateTaskData,
  UpdateTaskData,
  TaskResponse,
  ToggleTaskResponse,
  ApprovalResponse,
  ImageUploadResponse,
  ErrorResponse,
  TaskFilters,
} from '../types/task.types';

/**
 * タスクサービスクラス
 */
class TaskService {
  /**
   * タスク一覧を取得
   * 
   * @param filters - フィルター条件（status, page, per_page）
   * @returns タスク一覧とページネーション情報
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async getTasks(filters?: TaskFilters): Promise<TaskListResponse['data']> {
    try {
      console.log('[TaskService] getTasks called, filters:', filters);
      console.log('[TaskService] API base URL:', api.defaults.baseURL);
      
      const response = await api.get<TaskListResponse>('/tasks', {
        params: filters,
      });

      console.log('[TaskService] getTasks response status:', response.status);
      console.log('[TaskService] getTasks response data:', JSON.stringify(response.data).substring(0, 200));

      if (!response.data.success) {
        console.error('[TaskService] getTasks failed: success=false');
        throw new Error('TASK_FETCH_FAILED');
      }

      return response.data.data;
    } catch (error: any) {
      console.error('[TaskService] getTasks error:', error);
      console.error('[TaskService] getTasks error message:', error.message);
      console.error('[TaskService] getTasks error response:', error.response);
      
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * タスクを作成
   * 
   * @param data - タスク作成データ
   * @returns 作成されたタスク
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async createTask(data: CreateTaskData): Promise<Task> {
    try {
      const response = await api.post<TaskResponse>('/tasks', data);

      if (!response.data.success || !response.data.data) {
        throw new Error('TASK_CREATE_FAILED');
      }

      return response.data.data.task;
    } catch (error: any) {
      if (error.response?.status === 422) {
        const errors = error.response.data.errors;
        if (errors?.title) {
          throw new Error('TITLE_REQUIRED');
        }
        throw new Error('VALIDATION_ERROR');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      // error.messageがエラーコードの場合、既に変換済みなのでそのまま再スロー
      if (error.message && (
        error.message.startsWith('TASK_') || 
        error.message.startsWith('VALIDATION_') || 
        error.message.startsWith('TITLE_')
      )) {
        throw error;
      }
      if (!error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('TASK_CREATE_FAILED');
    }
  }

  /**
   * タスクを更新
   * 
   * @param taskId - タスクID
   * @param data - 更新データ
   * @returns 更新されたタスク
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async updateTask(taskId: number, data: UpdateTaskData): Promise<Task> {
    try {
      const response = await api.put<TaskResponse>(`/tasks/${taskId}`, data);

      if (!response.data.success || !response.data.data) {
        throw new Error('TASK_UPDATE_FAILED');
      }

      return response.data.data.task;
    } catch (error: any) {
      if (error.response?.status === 422) {
        throw new Error('VALIDATION_ERROR');
      }
      if (error.response?.status === 404) {
        throw new Error('TASK_NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && !error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('TASK_UPDATE_FAILED');
    }
  }

  /**
   * タスクを削除
   * 
   * @param taskId - タスクID
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async deleteTask(taskId: number): Promise<void> {
    try {
      const response = await api.delete<TaskResponse>(`/tasks/${taskId}`);

      if (!response.data.success) {
        throw new Error('TASK_DELETE_FAILED');
      }
    } catch (error: any) {
      if (error.response?.status === 404) {
        throw new Error('TASK_NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && !error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('TASK_DELETE_FAILED');
    }
  }

  /**
   * タスクの完了状態を切り替え
   * 
   * @param taskId - タスクID
   * @returns 更新されたタスク
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async toggleTaskCompletion(taskId: number): Promise<Task> {
    try {
      const response = await api.patch<ToggleTaskResponse>(
        `/tasks/${taskId}/toggle`
      );

      if (!response.data.success || !response.data.data) {
        throw new Error('TASK_UPDATE_FAILED');
      }

      return response.data.data.task;
    } catch (error: any) {
      if (error.response?.status === 404) {
        throw new Error('TASK_NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && !error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('TASK_UPDATE_FAILED');
    }
  }

  /**
   * タスクを承認
   * 
   * @param taskId - タスクID
   * @returns 承認されたタスク
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async approveTask(taskId: number): Promise<Task> {
    try {
      const response = await api.post<ApprovalResponse>(
        `/tasks/${taskId}/approve`
      );

      if (!response.data.success || !response.data.data) {
        throw new Error('TASK_UPDATE_FAILED');
      }

      return response.data.data.task;
    } catch (error: any) {
      if (error.response?.status === 404) {
        throw new Error('TASK_NOT_FOUND');
      }
      if (error.response?.status === 403) {
        throw new Error('APPROVAL_NOT_ALLOWED');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && !error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('TASK_UPDATE_FAILED');
    }
  }

  /**
   * タスクを却下
   * 
   * @param taskId - タスクID
   * @returns 却下されたタスク
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async rejectTask(taskId: number): Promise<Task> {
    try {
      const response = await api.post<ApprovalResponse>(
        `/tasks/${taskId}/reject`
      );

      if (!response.data.success || !response.data.data) {
        throw new Error('TASK_UPDATE_FAILED');
      }

      return response.data.data.task;
    } catch (error: any) {
      if (error.response?.status === 404) {
        throw new Error('TASK_NOT_FOUND');
      }
      if (error.response?.status === 403) {
        throw new Error('APPROVAL_NOT_ALLOWED');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && !error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('TASK_UPDATE_FAILED');
    }
  }

  /**
   * タスクに画像をアップロード
   * 
   * @param taskId - タスクID
   * @param imageUri - 画像URI（ローカルファイルパス）
   * @returns アップロードされた画像情報
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async uploadTaskImage(taskId: number, imageUri: string): Promise<ImageUploadResponse['data']> {
    try {
      const formData = new FormData();
      
      // React Nativeでの画像アップロード形式
      const filename = imageUri.split('/').pop() || 'image.jpg';
      const match = /\.(\w+)$/.exec(filename);
      const type = match ? `image/${match[1]}` : 'image/jpeg';

      formData.append('image', {
        uri: imageUri,
        type,
        name: filename,
      } as any);

      const response = await api.post<ImageUploadResponse>(
        `/tasks/${taskId}/images`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );

      if (!response.data.success || !response.data.data) {
        throw new Error('IMAGE_UPLOAD_FAILED');
      }

      return response.data.data;
    } catch (error: any) {
      if (error.response?.status === 422) {
        throw new Error('INVALID_IMAGE_FORMAT');
      }
      if (error.response?.status === 404) {
        throw new Error('TASK_NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && !error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('IMAGE_UPLOAD_FAILED');
    }
  }

  /**
   * タスク画像を削除
   * 
   * @param imageId - 画像ID
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async deleteTaskImage(imageId: number): Promise<void> {
    try {
      const response = await api.delete<TaskResponse>(`/task-images/${imageId}`);

      if (!response.data.success) {
        throw new Error('IMAGE_DELETE_FAILED');
      }
    } catch (error: any) {
      if (error.response?.status === 404) {
        throw new Error('TASK_NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && !error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('IMAGE_DELETE_FAILED');
    }
  }

  /**
   * タスクを検索
   * 
   * @param query - 検索クエリ（タイトル・説明で部分一致）
   * @param filters - 追加フィルター条件
   * @returns 検索結果のタスク一覧とページネーション情報
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async searchTasks(query: string, filters?: Omit<TaskFilters, 'q'>): Promise<TaskListResponse['data']> {
    try {
      const response = await api.get<TaskListResponse>('/tasks', {
        params: {
          q: query,
          ...filters,
        },
      });

      if (!response.data.success) {
        throw new Error('TASK_SEARCH_FAILED');
      }

      return response.data.data;
    } catch (error: any) {
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }
}

// シングルトンインスタンスをエクスポート
export const taskService = new TaskService();
export default taskService;
