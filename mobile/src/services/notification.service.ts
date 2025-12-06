import api from './api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { STORAGE_KEYS } from '../utils/constants';

/**
 * 通知型定義
 */
export interface Notification {
  id: number;
  title: string;
  body: string;
  type: 'info' | 'important' | 'system';
  priority: 'important' | 'normal' | 'low';
  is_read: boolean;
  created_at: string;
  updated_at: string;
  template?: {
    id: number;
    title: string;
    body: string;
    sender?: {
      id: number;
      username: string;
    };
  };
}

/**
 * ページネーション情報
 */
export interface NotificationPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

/**
 * 通知一覧レスポンス
 */
export interface NotificationListResponse {
  success: boolean;
  data: Notification[];
  pagination: NotificationPagination;
  unread_count: number;
}

/**
 * 未読件数レスポンス
 */
export interface UnreadCountResponse {
  success: boolean;
  unread_count: number;
}

/**
 * 通知サービス
 * 
 * 通知の取得、既読管理、検索機能を提供
 * Laravel API通信専用（Firebase Push通知はuseNotifications Hookで実装）
 */
export const notificationService = {
  /**
   * 通知一覧を取得
   * 
   * @param page ページ番号（デフォルト: 1）
   * @returns 通知一覧とページネーション情報
   */
  async getNotifications(page: number = 1): Promise<NotificationListResponse> {
    const response = await api.get<NotificationListResponse>(`/notifications?page=${page}`);
    
    // キャッシュに保存（最初のページのみ）
    if (page === 1) {
      await AsyncStorage.setItem(
        STORAGE_KEYS.NOTIFICATIONS_CACHE,
        JSON.stringify({
          data: response.data.data,
          timestamp: Date.now(),
        })
      );
    }
    
    return response.data;
  },

  /**
   * キャッシュから通知一覧を取得
   * 
   * @returns キャッシュされた通知一覧（なければnull）
   */
  async getCachedNotifications(): Promise<Notification[] | null> {
    try {
      const cached = await AsyncStorage.getItem(STORAGE_KEYS.NOTIFICATIONS_CACHE);
      if (!cached) return null;

      const { data, timestamp } = JSON.parse(cached);
      
      // 5分以内のキャッシュのみ有効
      const CACHE_EXPIRY = 5 * 60 * 1000;
      if (Date.now() - timestamp > CACHE_EXPIRY) {
        await AsyncStorage.removeItem(STORAGE_KEYS.NOTIFICATIONS_CACHE);
        return null;
      }

      return data;
    } catch (error) {
      console.error('[notificationService] Cache retrieval failed:', error);
      return null;
    }
  },

  /**
   * 通知の詳細を取得
   * 
   * @param notificationId 通知ID
   * @returns 通知の詳細
   */
  async getNotificationDetail(notificationId: number): Promise<Notification> {
    const response = await api.get<{ success: boolean; data: Notification }>(
      `/notifications/${notificationId}`
    );
    return response.data.data;
  },

  /**
   * 通知を既読にする
   * 
   * @param notificationId 通知ID
   */
  async markAsRead(notificationId: number): Promise<void> {
    await api.post(`/notifications/${notificationId}/read`);
    
    // キャッシュをクリア（既読状態が変わるため）
    await AsyncStorage.removeItem(STORAGE_KEYS.NOTIFICATIONS_CACHE);
  },

  /**
   * すべての通知を既読にする
   */
  async markAllAsRead(): Promise<void> {
    await api.post('/notifications/read-all');
    
    // キャッシュをクリア
    await AsyncStorage.removeItem(STORAGE_KEYS.NOTIFICATIONS_CACHE);
  },

  /**
   * 未読件数を取得
   * 
   * @returns 未読件数
   */
  async getUnreadCount(): Promise<number> {
    const response = await api.get<UnreadCountResponse>('/notifications/unread-count');
    return response.data.unread_count;
  },

  /**
   * 通知を検索
   * 
   * @param query 検索クエリ
   * @param page ページ番号（デフォルト: 1）
   * @returns 検索結果
   */
  async searchNotifications(query: string, page: number = 1): Promise<NotificationListResponse> {
    const response = await api.get<NotificationListResponse>(
      `/notifications/search?q=${encodeURIComponent(query)}&page=${page}`
    );
    return response.data;
  },

  /**
   * 通知キャッシュをクリア
   */
  async clearCache(): Promise<void> {
    await AsyncStorage.removeItem(STORAGE_KEYS.NOTIFICATIONS_CACHE);
  },
};
