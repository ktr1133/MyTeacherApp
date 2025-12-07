/**
 * useNotifications Hook
 * 
 * 通知機能の状態管理とビジネスロジックを提供
 * Phase 2.B-5 Step 2で実装（Laravel API完全準拠）
 * 
 * 機能:
 * - 通知一覧取得・管理
 * - 未読件数取得・更新
 * - 個別既読化・全既読化
 * - 通知検索
 * - ページネーション対応
 * - リアルタイム通知ポーリング（30秒間隔）
 */

import { useState, useCallback, useEffect, useRef } from 'react';
import { notificationService } from '../services/notification.service';
import { useAuth } from '../contexts/AuthContext';
import {
  Notification,
  NotificationListResponse,
  UnreadCountResponse,
} from '../types/notification.types';

interface UseNotificationsReturn {
  notifications: Notification[];
  unreadCount: number;
  loading: boolean;
  error: string | null;
  currentPage: number;
  totalPages: number;
  hasMore: boolean;
  fetchNotifications: (page?: number) => Promise<void>;
  fetchUnreadCount: () => Promise<void>;
  markAsRead: (notificationId: number) => Promise<void>;
  markAllAsRead: () => Promise<void>;
  searchNotifications: (query: string, page?: number) => Promise<void>;
  loadMore: () => Promise<void>;
  refresh: () => Promise<void>;
  startPolling: () => void;
  stopPolling: () => void;
}

export const useNotifications = (enablePolling: boolean = false): UseNotificationsReturn => {
  const { isAuthenticated, loading: authLoading } = useAuth();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [totalPages, setTotalPages] = useState<number>(1);
  const [hasMore, setHasMore] = useState<boolean>(false);
  const pollingIntervalRef = useRef<NodeJS.Timeout | null>(null);

  /**
   * 通知一覧取得
   * 
   * @param page ページ番号（デフォルト: 1）
   */
  const fetchNotifications = useCallback(async (page: number = 1): Promise<void> => {
    try {
      setLoading(true);
      setError(null);

      const response: NotificationListResponse = await notificationService.getNotifications(page);

      // デバッグ: レスポンスデータ確認
      console.log('[useNotifications] Response data:', {
        notifications_count: response.data.notifications.length,
        unread_count: response.data.unread_count,
        all_notifications: response.data.notifications.map(n => ({
          id: n.id,
          has_template: n.template != null,
          template_title: n.template?.title,
          template_content: n.template?.content,
        })),
      });

      if (page === 1) {
        // 最初のページは上書き
        setNotifications(response.data.notifications);
      } else {
        // 2ページ目以降は追加
        setNotifications((prev) => [...prev, ...response.data.notifications]);
      }

      setUnreadCount(response.data.unread_count);
      setCurrentPage(page);
      setTotalPages(response.data.pagination.last_page);
      setHasMore(page < response.data.pagination.last_page);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : '通知の取得に失敗しました';
      setError(errorMessage);
      console.error('[useNotifications.fetchNotifications] Error:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  /**
   * 未読件数取得
   */
  const fetchUnreadCount = useCallback(async (): Promise<void> => {
    try {
      const response: UnreadCountResponse = await notificationService.getUnreadCount();
      setUnreadCount(response.count);
    } catch (err) {
      console.error('[useNotifications.fetchUnreadCount] Error:', err);
    }
  }, []);

  /**
   * 通知を既読にする
   * 
   * @param notificationId 通知ID
   */
  const markAsRead = useCallback(async (notificationId: number): Promise<void> => {
    try {
      await notificationService.markAsRead(notificationId);

      // ローカル状態を更新（is_readとread_at両方更新）
      setNotifications((prev) =>
        prev.map((notification) =>
          notification.id === notificationId
            ? { ...notification, is_read: true, read_at: new Date().toISOString() }
            : notification
        )
      );

      // 未読件数を再取得
      await fetchUnreadCount();
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : '既読処理に失敗しました';
      setError(errorMessage);
      console.error('[useNotifications.markAsRead] Error:', err);
    }
  }, [fetchUnreadCount]);

  /**
   * 全通知を既読にする
   */
  const markAllAsRead = useCallback(async (): Promise<void> => {
    try {
      setLoading(true);
      setError(null);

      await notificationService.markAllAsRead();

      // ローカル状態を更新（全てread_atを設定）
      setNotifications((prev) =>
        prev.map((notification) => ({
          ...notification,
          read_at: notification.read_at || new Date().toISOString(),
        }))
      );

      // 未読件数を0にリセット
      setUnreadCount(0);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : '全既読処理に失敗しました';
      setError(errorMessage);
      console.error('[useNotifications.markAllAsRead] Error:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  /**
   * 通知検索
   * 
   * @param query 検索クエリ
   * @param page ページ番号（デフォルト: 1）
   */
  const searchNotifications = useCallback(async (query: string, page: number = 1): Promise<void> => {
    try {
      setLoading(true);
      setError(null);

      const response = await notificationService.searchNotifications(query, page);

      if (page === 1) {
        setNotifications(response.data.notifications);
      } else {
        setNotifications((prev) => [...prev, ...response.data.notifications]);
      }

      setCurrentPage(page);
      setTotalPages(response.data.pagination.last_page);
      setHasMore(page < response.data.pagination.last_page);
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : '検索に失敗しました';
      setError(errorMessage);
      console.error('[useNotifications.searchNotifications] Error:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  /**
   * 次のページを読み込む（無限スクロール用）
   */
  const loadMore = useCallback(async (): Promise<void> => {
    if (!hasMore || loading) return;

    await fetchNotifications(currentPage + 1);
  }, [hasMore, loading, currentPage, fetchNotifications]);

  /**
   * リフレッシュ（最初のページを再取得）
   */
  const refresh = useCallback(async (): Promise<void> => {
    await fetchNotifications(1);
  }, [fetchNotifications]);

  /**
   * ポーリング開始（30秒間隔で未読件数チェック）
   */
  const startPolling = useCallback(() => {
    // 認証チェック
    if (!isAuthenticated) {
      console.log('[useNotifications] Cannot start polling: not authenticated');
      return;
    }

    // 既存のポーリングをクリア
    if (pollingIntervalRef.current) {
      clearInterval(pollingIntervalRef.current);
    }

    console.log('[useNotifications] Starting polling...');

    // 30秒間隔でポーリング
    pollingIntervalRef.current = setInterval(async () => {
      // ポーリング実行前に認証状態再確認
      if (!isAuthenticated) {
        console.log('[useNotifications.polling] Not authenticated, stopping polling...');
        stopPolling();
        return;
      }

      try {
        const response = await notificationService.getUnreadCount();
        const newUnreadCount = response.count;

        // 未読件数が増えた場合は通知一覧を再取得
        if (newUnreadCount > unreadCount) {
          console.log('[useNotifications] New notifications detected, refreshing...');
          await fetchNotifications(1);
        } else {
          // 未読件数のみ更新
          setUnreadCount(newUnreadCount);
        }
      } catch (err: any) {
        console.error('[useNotifications.polling] Error:', err);
        
        // 401エラー（認証エラー）の場合はポーリング停止
        if (err?.response?.status === 401) {
          console.log('[useNotifications.polling] Authentication error (401), stopping polling...');
          stopPolling();
          return;
        }
        
        // その他のエラーの場合もポーリング停止（無限エラーループ防止）
        console.log('[useNotifications.polling] Stopping polling due to error');
        stopPolling();
      }
    }, 30000); // 30秒間隔
  }, [unreadCount, fetchNotifications, isAuthenticated, stopPolling]);

  /**
   * ポーリング停止
   */
  const stopPolling = useCallback(() => {
    if (pollingIntervalRef.current) {
      console.log('[useNotifications] Stopping polling...');
      clearInterval(pollingIntervalRef.current);
      pollingIntervalRef.current = null;
    }
  }, []);

  // 初回マウント時に通知一覧を取得（ポーリングは認証完了後に開始）
  useEffect(() => {
    // 認証チェック完了待機
    if (authLoading) {
      console.log('[useNotifications] Waiting for auth check...');
      return;
    }

    // 認証済みの場合のみ通知取得
    if (isAuthenticated) {
      fetchNotifications(1);

      // enablePollingがtrueの場合のみポーリング開始
      if (enablePolling) {
        startPolling();
      }
    } else {
      console.log('[useNotifications] Not authenticated, skipping notification fetch');
    }

    // クリーンアップ時にポーリング停止
    return () => {
      stopPolling();
    };
  }, [fetchNotifications, enablePolling, startPolling, stopPolling, isAuthenticated, authLoading]);

  return {
    notifications,
    unreadCount,
    loading,
    error,
    currentPage,
    totalPages,
    hasMore,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    searchNotifications,
    loadMore,
    refresh,
    startPolling,
    stopPolling,
  };
};
