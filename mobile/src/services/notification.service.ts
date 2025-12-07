/**
 * 通知サービス
 * 
 * Laravel API `/api/notifications` との通信を担当
 * Phase 2.B-5 Step 2でLaravel API完全準拠に更新
 * 
 * API Endpoints:
 * - GET    /api/notifications          - 通知一覧（ページネーション対応）
 * - GET    /api/notifications/unread-count - 未読件数
 * - GET    /api/notifications/search   - 検索
 * - POST   /api/notifications/read-all - 全既読化
 * - GET    /api/notifications/{id}     - 詳細取得
 * - PATCH  /api/notifications/{id}/read - 個別既読化
 */

import apiClient from './api';
import {
  NotificationListResponse,
  UnreadCountResponse,
  NotificationDetailResponse,
  MarkAsReadResponse,
  MarkAllAsReadResponse,
  SearchNotificationsResponse,
} from '../types/notification.types';

export const notificationService = {
  /**
   * 通知一覧取得（ページネーション対応）
   * 
   * @param page ページ番号（デフォルト: 1）
   * @returns 通知一覧、総件数、未読件数
   * @throws Error API通信エラー時
   */
  async getNotifications(page: number = 1): Promise<NotificationListResponse> {
    try {
      const response = await apiClient.get<NotificationListResponse>(`/notifications?page=${page}`);
      return response.data;
    } catch (error) {
      console.error('[notificationService.getNotifications] Error:', error);
      throw error;
    }
  },

  /**
   * 未読通知件数取得
   * 
   * @returns 未読件数
   * @throws Error API通信エラー時
   */
  async getUnreadCount(): Promise<UnreadCountResponse> {
    try {
      const response = await apiClient.get<UnreadCountResponse>('/notifications/unread-count');
      return response.data;
    } catch (error) {
      console.error('[notificationService.getUnreadCount] Error:', error);
      throw error;
    }
  },

  /**
   * 通知検索
   * 
   * @param query 検索クエリ（タイトル・メッセージを部分一致検索）
   * @param page ページ番号（デフォルト: 1）
   * @returns 検索結果の通知一覧
   * @throws Error API通信エラー時
   */
  async searchNotifications(query: string, page: number = 1): Promise<SearchNotificationsResponse> {
    try {
      const response = await apiClient.get<SearchNotificationsResponse>(
        `/notifications/search?q=${encodeURIComponent(query)}&page=${page}`
      );
      return response.data;
    } catch (error) {
      console.error('[notificationService.searchNotifications] Error:', error);
      throw error;
    }
  },

  /**
   * 全通知を既読にする
   * 
   * @returns 成功メッセージ
   * @throws Error API通信エラー時
   */
  async markAllAsRead(): Promise<MarkAllAsReadResponse> {
    try {
      const response = await apiClient.post<MarkAllAsReadResponse>('/notifications/read-all');
      return response.data;
    } catch (error) {
      console.error('[notificationService.markAllAsRead] Error:', error);
      throw error;
    }
  },

  /**
   * 通知詳細取得
   * 
   * @param notificationId 通知ID
   * @returns 通知詳細
   * @throws Error API通信エラー時
   */
  async getNotificationDetail(notificationId: number): Promise<NotificationDetailResponse> {
    try {
      const response = await apiClient.get<NotificationDetailResponse>(`/notifications/${notificationId}`);
      return response.data;
    } catch (error) {
      console.error('[notificationService.getNotificationDetail] Error:', error);
      throw error;
    }
  },

  /**
   * 通知を既読にする
   * 
   * @param notificationId 通知ID
   * @returns 成功メッセージ
   * @throws Error API通信エラー時
   */
  async markAsRead(notificationId: number): Promise<MarkAsReadResponse> {
    try {
      const response = await apiClient.patch<MarkAsReadResponse>(`/notifications/${notificationId}/read`);
      return response.data;
    } catch (error) {
      console.error('[notificationService.markAsRead] Error:', error);
      throw error;
    }
  },
};
