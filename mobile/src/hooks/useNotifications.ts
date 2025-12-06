import { useState, useCallback, useEffect } from 'react';
import {
  notificationService,
  Notification,
  NotificationPagination,
} from '../services/notification.service';
import { useTheme } from '../contexts/ThemeContext';
import { getErrorMessage } from '../utils/errorMessages';

/**
 * 通知管理Hook
 * 
 * 通知の取得、既読管理、検索、ページネーションを提供
 * Firebase Push通知の受信・表示機能も含む
 */
export const useNotifications = () => {
  const { theme } = useTheme();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [pagination, setPagination] = useState<NotificationPagination | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isRefreshing, setIsRefreshing] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  /**
   * 通知一覧を取得
   * 
   * @param page ページ番号（デフォルト: 1）
   * @param useCache キャッシュを使用するか（デフォルト: true）
   */
  const fetchNotifications = useCallback(
    async (page: number = 1, useCache: boolean = true) => {
      try {
        setIsLoading(true);
        setError(null);

        // キャッシュから取得を試みる（1ページ目のみ）
        if (page === 1 && useCache) {
          const cached = await notificationService.getCachedNotifications();
          if (cached) {
            setNotifications(cached);
            setIsLoading(false);
            // バックグラウンドで最新データを取得
            notificationService.getNotifications(1).then((response) => {
              setNotifications(response.data);
              setPagination(response.pagination);
              setUnreadCount(response.unread_count);
            });
            return;
          }
        }

        // APIから取得
        const response = await notificationService.getNotifications(page);
        
        if (page === 1) {
          setNotifications(response.data);
        } else {
          // ページネーション: 既存データに追加
          setNotifications((prev) => [...prev, ...response.data]);
        }
        
        setPagination(response.pagination);
        setUnreadCount(response.unread_count);
      } catch (err: any) {
        console.error('[useNotifications] Fetch failed:', err);
        setError(getErrorMessage(theme, 'NOTIFICATION_FETCH_FAILED'));
      } finally {
        setIsLoading(false);
      }
    },
    [theme]
  );

  /**
   * 通知一覧をリフレッシュ（Pull-to-Refresh用）
   */
  const refreshNotifications = useCallback(async () => {
    try {
      setIsRefreshing(true);
      setError(null);

      const response = await notificationService.getNotifications(1);
      setNotifications(response.data);
      setPagination(response.pagination);
      setUnreadCount(response.unread_count);
    } catch (err: any) {
      console.error('[useNotifications] Refresh failed:', err);
      setError(getErrorMessage(theme, 'NOTIFICATION_FETCH_FAILED'));
    } finally {
      setIsRefreshing(false);
    }
  }, [theme]);

  /**
   * 次のページを読み込む
   */
  const loadMore = useCallback(async () => {
    if (!pagination || pagination.current_page >= pagination.last_page) {
      return;
    }

    await fetchNotifications(pagination.current_page + 1, false);
  }, [pagination, fetchNotifications]);

  /**
   * 通知を既読にする
   * 
   * @param notificationId 通知ID
   */
  const markAsRead = useCallback(
    async (notificationId: number) => {
      try {
        await notificationService.markAsRead(notificationId);

        // 楽観的更新（Optimistic Update）
        setNotifications((prev) =>
          prev.map((n) =>
            n.id === notificationId ? { ...n, is_read: true } : n
          )
        );
        setUnreadCount((prev) => Math.max(0, prev - 1));
      } catch (err: any) {
        console.error('[useNotifications] Mark as read failed:', err);
        setError(getErrorMessage(theme, 'NOTIFICATION_UPDATE_FAILED'));
        // エラー時はデータを再取得
        await fetchNotifications(1, false);
      }
    },
    [theme, fetchNotifications]
  );

  /**
   * すべての通知を既読にする
   */
  const markAllAsRead = useCallback(async () => {
    try {
      await notificationService.markAllAsRead();

      // 楽観的更新
      setNotifications((prev) =>
        prev.map((n) => ({ ...n, is_read: true }))
      );
      setUnreadCount(0);
    } catch (err: any) {
      console.error('[useNotifications] Mark all as read failed:', err);
      setError(getErrorMessage(theme, 'NOTIFICATION_UPDATE_FAILED'));
      // エラー時はデータを再取得
      await fetchNotifications(1, false);
    }
  }, [theme, fetchNotifications]);

  /**
   * 未読件数を取得
   */
  const fetchUnreadCount = useCallback(async () => {
    try {
      const count = await notificationService.getUnreadCount();
      setUnreadCount(count);
    } catch (err: any) {
      console.error('[useNotifications] Fetch unread count failed:', err);
    }
  }, []);

  /**
   * 通知を検索
   * 
   * @param query 検索クエリ
   * @param page ページ番号（デフォルト: 1）
   */
  const searchNotifications = useCallback(
    async (query: string, page: number = 1) => {
      try {
        setIsLoading(true);
        setError(null);

        const response = await notificationService.searchNotifications(query, page);
        
        if (page === 1) {
          setNotifications(response.data);
        } else {
          setNotifications((prev) => [...prev, ...response.data]);
        }
        
        setPagination(response.pagination);
      } catch (err: any) {
        console.error('[useNotifications] Search failed:', err);
        setError(getErrorMessage(theme, 'NOTIFICATION_SEARCH_FAILED'));
      } finally {
        setIsLoading(false);
      }
    },
    [theme]
  );

  /**
   * 初回マウント時に通知一覧を取得
   */
  useEffect(() => {
    fetchNotifications(1, true);
  }, []);

  return {
    notifications,
    unreadCount,
    pagination,
    isLoading,
    isRefreshing,
    error,
    fetchNotifications,
    refreshNotifications,
    loadMore,
    markAsRead,
    markAllAsRead,
    fetchUnreadCount,
    searchNotifications,
  };
};
