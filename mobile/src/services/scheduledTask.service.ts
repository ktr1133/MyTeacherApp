/**
 * スケジュールタスク管理サービス
 * 
 * Laravel API（/api/scheduled-tasks/*）との通信を担当
 * ビジネスロジック: データ整形、エラーハンドリング
 * 
 * @see /home/ktr/mtdev/definitions/mobile/ScheduledTaskManagement.md
 * @see /home/ktr/mtdev/mobile/src/types/scheduled-task.types.ts
 */

import api from './api';
import {
  ScheduledTask,
  ScheduledTaskListResponse,
  ScheduledTaskCreateResponse,
  ScheduledTaskEditResponse,
  ScheduledTaskApiResponse,
  ScheduledTaskHistoryApiResponse,
  ScheduledTaskRequest,
} from '../types/scheduled-task.types';

/**
 * スケジュールタスクサービスクラス
 */
class ScheduledTaskService {
  /**
   * スケジュールタスク一覧を取得
   * 
   * @param groupId - グループID（任意）
   * @returns スケジュールタスク一覧
   * @throws Error - API_ERROR, AUTH_REQUIRED, PERMISSION_DENIED, NETWORK_ERROR
   */
  async getScheduledTasks(groupId?: number): Promise<ScheduledTask[]> {
    try {
      console.log('[ScheduledTaskService] getScheduledTasks called, groupId:', groupId);
      
      const params = groupId ? { group_id: groupId } : undefined;
      const response = await api.get<ScheduledTaskListResponse>('/scheduled-tasks', { params });

      console.log('[ScheduledTaskService] getScheduledTasks response status:', response.status);

      if (!response.data.data?.scheduled_tasks) {
        console.error('[ScheduledTaskService] getScheduledTasks failed: no data');
        throw new Error('API_ERROR');
      }

      return response.data.data.scheduled_tasks;
    } catch (error: any) {
      console.error('[ScheduledTaskService] getScheduledTasks error:', error);
      
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * スケジュールタスク作成フォームデータを取得
   * 
   * @param groupId - グループID（任意）
   * @returns フォーム初期データ（グループメンバー、タグ、デフォルト値）
   * @throws Error - API_ERROR, AUTH_REQUIRED, NETWORK_ERROR
   */
  async getCreateFormData(groupId?: number): Promise<ScheduledTaskCreateResponse['data']> {
    try {
      console.log('[ScheduledTaskService] getCreateFormData called, groupId:', groupId);
      
      const params = groupId ? { group_id: groupId } : undefined;
      const response = await api.get<ScheduledTaskCreateResponse>('/scheduled-tasks/create', { params });

      console.log('[ScheduledTaskService] getCreateFormData response status:', response.status);

      if (!response.data.data) {
        console.error('[ScheduledTaskService] getCreateFormData failed: no data');
        throw new Error('API_ERROR');
      }

      return response.data.data;
    } catch (error: any) {
      console.error('[ScheduledTaskService] getCreateFormData error:', error);
      
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
   * スケジュールタスクを作成
   * 
   * @param data - スケジュールタスク作成データ
   * @throws Error - VALIDATION_ERROR, API_ERROR, AUTH_REQUIRED, NETWORK_ERROR
   */
  async createScheduledTask(data: ScheduledTaskRequest): Promise<void> {
    try {
      console.log('[ScheduledTaskService] createScheduledTask called, data:', data);
      
      const response = await api.post<ScheduledTaskApiResponse>('/scheduled-tasks', data);

      console.log('[ScheduledTaskService] createScheduledTask response status:', response.status);

      if (!response.data.success) {
        console.error('[ScheduledTaskService] createScheduledTask failed: success=false');
        throw new Error('API_ERROR');
      }
    } catch (error: any) {
      console.error('[ScheduledTaskService] createScheduledTask error:', error);
      
      if (error.response?.status === 422) {
        throw new Error('VALIDATION_ERROR');
      }
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
   * スケジュールタスク編集フォームデータを取得
   * 
   * @param id - スケジュールタスクID
   * @returns スケジュールタスク情報とグループメンバー
   * @throws Error - NOT_FOUND, API_ERROR, AUTH_REQUIRED, PERMISSION_DENIED, NETWORK_ERROR
   */
  async getEditFormData(id: number): Promise<ScheduledTaskEditResponse['data']> {
    try {
      console.log('[ScheduledTaskService] getEditFormData called, id:', id);
      
      const response = await api.get<ScheduledTaskEditResponse>(`/scheduled-tasks/${id}/edit`);

      console.log('[ScheduledTaskService] getEditFormData response status:', response.status);

      if (!response.data.data) {
        console.error('[ScheduledTaskService] getEditFormData failed: no data');
        throw new Error('API_ERROR');
      }

      return response.data.data;
    } catch (error: any) {
      console.error('[ScheduledTaskService] getEditFormData error:', error);
      
      if (error.response?.status === 404) {
        throw new Error('NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * スケジュールタスクを更新
   * 
   * @param id - スケジュールタスクID
   * @param data - 更新データ
   * @throws Error - VALIDATION_ERROR, NOT_FOUND, API_ERROR, AUTH_REQUIRED, PERMISSION_DENIED, NETWORK_ERROR
   */
  async updateScheduledTask(id: number, data: ScheduledTaskRequest): Promise<void> {
    try {
      console.log('[ScheduledTaskService] updateScheduledTask called, id:', id, 'data:', data);
      
      const response = await api.put<ScheduledTaskApiResponse>(`/scheduled-tasks/${id}`, data);

      console.log('[ScheduledTaskService] updateScheduledTask response status:', response.status);

      if (!response.data.success) {
        console.error('[ScheduledTaskService] updateScheduledTask failed: success=false');
        throw new Error('API_ERROR');
      }
    } catch (error: any) {
      console.error('[ScheduledTaskService] updateScheduledTask error:', error);
      
      if (error.response?.status === 422) {
        throw new Error('VALIDATION_ERROR');
      }
      if (error.response?.status === 404) {
        throw new Error('NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * スケジュールタスクを削除
   * 
   * @param id - スケジュールタスクID
   * @throws Error - NOT_FOUND, API_ERROR, AUTH_REQUIRED, PERMISSION_DENIED, NETWORK_ERROR
   */
  async deleteScheduledTask(id: number): Promise<void> {
    try {
      console.log('[ScheduledTaskService] deleteScheduledTask called, id:', id);
      
      const response = await api.delete<ScheduledTaskApiResponse>(`/scheduled-tasks/${id}`);

      console.log('[ScheduledTaskService] deleteScheduledTask response status:', response.status);

      if (!response.data.success) {
        console.error('[ScheduledTaskService] deleteScheduledTask failed: success=false');
        throw new Error('API_ERROR');
      }
    } catch (error: any) {
      console.error('[ScheduledTaskService] deleteScheduledTask error:', error);
      
      if (error.response?.status === 404) {
        throw new Error('NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * スケジュールタスクを一時停止
   * 
   * @param id - スケジュールタスクID
   * @throws Error - NOT_FOUND, API_ERROR, AUTH_REQUIRED, PERMISSION_DENIED, NETWORK_ERROR
   */
  async pauseScheduledTask(id: number): Promise<void> {
    try {
      console.log('[ScheduledTaskService] pauseScheduledTask called, id:', id);
      
      const response = await api.post<ScheduledTaskApiResponse>(`/scheduled-tasks/${id}/pause`);

      console.log('[ScheduledTaskService] pauseScheduledTask response status:', response.status);

      if (!response.data.success) {
        console.error('[ScheduledTaskService] pauseScheduledTask failed: success=false');
        throw new Error('API_ERROR');
      }
    } catch (error: any) {
      console.error('[ScheduledTaskService] pauseScheduledTask error:', error);
      
      if (error.response?.status === 404) {
        throw new Error('NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * スケジュールタスクを再開
   * 
   * @param id - スケジュールタスクID
   * @throws Error - NOT_FOUND, API_ERROR, AUTH_REQUIRED, PERMISSION_DENIED, NETWORK_ERROR
   */
  async resumeScheduledTask(id: number): Promise<void> {
    try {
      console.log('[ScheduledTaskService] resumeScheduledTask called, id:', id);
      
      const response = await api.post<ScheduledTaskApiResponse>(`/scheduled-tasks/${id}/resume`);

      console.log('[ScheduledTaskService] resumeScheduledTask response status:', response.status);

      if (!response.data.success) {
        console.error('[ScheduledTaskService] resumeScheduledTask failed: success=false');
        throw new Error('API_ERROR');
      }
    } catch (error: any) {
      console.error('[ScheduledTaskService] resumeScheduledTask error:', error);
      
      if (error.response?.status === 404) {
        throw new Error('NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * スケジュールタスクの実行履歴を取得
   * 
   * @param id - スケジュールタスクID
   * @returns 実行履歴データ（スケジュール基本情報 + 実行履歴リスト）
   * @throws Error - NOT_FOUND, API_ERROR, AUTH_REQUIRED, PERMISSION_DENIED, NETWORK_ERROR
   */
  async getExecutionHistory(id: number): Promise<ScheduledTaskHistoryApiResponse['data']> {
    try {
      console.log('[ScheduledTaskService] getExecutionHistory called, id:', id);
      
      const response = await api.get<ScheduledTaskHistoryApiResponse>(`/scheduled-tasks/${id}/history`);

      console.log('[ScheduledTaskService] getExecutionHistory response status:', response.status);

      if (!response.data.data) {
        console.error('[ScheduledTaskService] getExecutionHistory failed: no data');
        throw new Error('API_ERROR');
      }

      return response.data.data;
    } catch (error: any) {
      console.error('[ScheduledTaskService] getExecutionHistory error:', error);
      
      if (error.response?.status === 404) {
        throw new Error('NOT_FOUND');
      }
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }
}

// シングルトンインスタンスをエクスポート
export default new ScheduledTaskService();
