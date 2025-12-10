/**
 * 承認待ち一覧サービス
 * 
 * Laravel API (/api/tasks/approvals/*, /api/tokens/purchase-requests/*) との通信を担当
 * 
 * 参照:
 * - OpenAPI仕様: /home/ktr/mtdev/docs/api/openapi.yaml
 * - 要件定義: /home/ktr/mtdev/definitions/mobile/PendingApprovalsScreen.md
 */

import api from './api';
import {
  PendingApprovalsResponse,
  TaskApprovalActionResponse,
  TaskRejectActionResponse,
  TokenApprovalActionResponse,
  TokenRejectActionResponse,
  RejectReasonData,
  PendingApprovalsCountResponse,
} from '../types/approval.types';

/**
 * ページネーションパラメータ
 */
interface PaginationParams {
  page?: number;
  per_page?: number;
}

/**
 * 承認待ち一覧サービスクラス
 */
class ApprovalService {
  /**
   * 承認待ち一覧を取得（タスク+トークン統合、日付順ソート）
   * 
   * @param params - ページネーションパラメータ
   * @returns 承認待ちアイテムとページネーション情報
   * @throws Error - エラーコードを投げる（UI層でテーマ変換）
   */
  async getPendingApprovals(
    params?: PaginationParams
  ): Promise<PendingApprovalsResponse['data']> {
    try {
      const response = await api.get<PendingApprovalsResponse>(
        '/tasks/approvals/pending',
        { params }
      );

      if (!response.data.success) {
        console.error('[ApprovalService] getPendingApprovals failed: success=false');
        throw new Error('NETWORK_ERROR');
      }

      return response.data.data;
    } catch (error: any) {
      console.error('[ApprovalService] getPendingApprovals error:', error);
      console.error('[ApprovalService] getPendingApprovals error message:', error.message);
      console.error('[ApprovalService] getPendingApprovals error response:', error.response);

      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED'); // 親ユーザー専用画面
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * 承認待ち件数を取得（バッジ表示用）
   * 
   * @returns 承認待ち件数
   * @throws Error - エラーコードを投げる
   */
  async getPendingApprovalsCount(): Promise<number> {
    try {
      const response = await api.get<PendingApprovalsCountResponse>(
        '/tasks/approvals/pending-count'
      );

      if (!response.data.success) {
        console.error('[ApprovalService] getPendingApprovalsCount failed: success=false');
        throw new Error('NETWORK_ERROR');
      }

      return response.data.data.count;
    } catch (error: any) {
      console.error('[ApprovalService] getPendingApprovalsCount error:', error);

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
   * タスクを承認
   * 
   * @param taskId - タスクID
   * @returns 承認結果
   * @throws Error - エラーコードを投げる
   */
  async approveTask(taskId: number): Promise<TaskApprovalActionResponse> {
    try {
      const response = await api.post<TaskApprovalActionResponse>(
        `/tasks/${taskId}/approve`
      );

      if (!response.data.success) {
        console.error('[ApprovalService] approveTask failed: success=false');
        throw new Error('NETWORK_ERROR');
      }

      return response.data;
    } catch (error: any) {
      console.error('[ApprovalService] approveTask error:', error);

      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.response?.status === 404) {
        throw new Error('TASK_NOT_FOUND');
      }
      if (error.response?.status === 422) {
        throw new Error('TASK_ALREADY_PROCESSED'); // 既に承認済み
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * タスクを却下
   * 
   * @param taskId - タスクID
   * @param reason - 却下理由（任意）
   * @returns 却下結果
   * @throws Error - エラーコードを投げる
   */
  async rejectTask(
    taskId: number,
    reason?: string
  ): Promise<TaskRejectActionResponse> {
    try {
      const data: RejectReasonData = reason ? { rejection_reason: reason } : {};

      const response = await api.post<TaskRejectActionResponse>(
        `/tasks/${taskId}/reject`,
        data
      );

      if (!response.data.success) {
        console.error('[ApprovalService] rejectTask failed: success=false');
        throw new Error('NETWORK_ERROR');
      }

      return response.data;
    } catch (error: any) {
      console.error('[ApprovalService] rejectTask error:', error);

      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.response?.status === 404) {
        throw new Error('TASK_NOT_FOUND');
      }
      if (error.response?.status === 422) {
        throw new Error('TASK_ALREADY_PROCESSED'); // 既に却下済み
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * トークン購入申請を承認
   * 
   * @param purchaseRequestId - 購入リクエストID
   * @returns 承認結果
   * @throws Error - エラーコードを投げる
   */
  async approveTokenPurchase(
    purchaseRequestId: number
  ): Promise<TokenApprovalActionResponse> {
    try {
      const response = await api.post<TokenApprovalActionResponse>(
        `/tokens/purchase-requests/${purchaseRequestId}/approve`
      );

      if (!response.data.success) {
        console.error('[ApprovalService] approveTokenPurchase failed: success=false');
        throw new Error('NETWORK_ERROR');
      }

      return response.data;
    } catch (error: any) {
      console.error('[ApprovalService] approveTokenPurchase error:', error);

      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.response?.status === 404) {
        throw new Error('PURCHASE_REQUEST_NOT_FOUND');
      }
      if (error.response?.status === 422) {
        throw new Error('PURCHASE_ALREADY_PROCESSED'); // 既に承認済み
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }

  /**
   * トークン購入申請を却下
   * 
   * @param purchaseRequestId - 購入リクエストID
   * @param reason - 却下理由（任意）
   * @returns 却下結果
   * @throws Error - エラーコードを投げる
   */
  async rejectTokenPurchase(
    purchaseRequestId: number,
    reason?: string
  ): Promise<TokenRejectActionResponse> {
    try {
      const data: RejectReasonData = reason ? { rejection_reason: reason } : {};

      const response = await api.post<TokenRejectActionResponse>(
        `/tokens/purchase-requests/${purchaseRequestId}/reject`,
        data
      );

      if (!response.data.success) {
        console.error('[ApprovalService] rejectTokenPurchase failed: success=false');
        throw new Error('NETWORK_ERROR');
      }

      return response.data;
    } catch (error: any) {
      console.error('[ApprovalService] rejectTokenPurchase error:', error);

      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 403) {
        throw new Error('PERMISSION_DENIED');
      }
      if (error.response?.status === 404) {
        throw new Error('PURCHASE_REQUEST_NOT_FOUND');
      }
      if (error.response?.status === 422) {
        throw new Error('PURCHASE_ALREADY_PROCESSED'); // 既に却下済み
      }
      if (error.message && error.message !== 'Network Error') {
        throw error;
      }
      throw new Error('NETWORK_ERROR');
    }
  }
}

export default new ApprovalService();
