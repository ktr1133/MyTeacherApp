/**
 * 通知サービステスト
 * 
 * Phase 2.B-5 Step 2: 通知機能のユニットテスト
 * - notificationService.getNotifications()
 * - notificationService.getUnreadCount()
 * - notificationService.searchNotifications()
 * - notificationService.markAllAsRead()
 * - notificationService.getNotificationDetail()
 * - notificationService.markAsRead()
 * - エラーハンドリング
 * - 401エラー時の動作
 * 
 * @see /home/ktr/mtdev/mobile/src/services/notification.service.ts
 * @see /home/ktr/mtdev/docs/reports/mobile/2025-12-07-phase2-b5-step2-notification-completion-report.md
 */

import { notificationService } from '../notification.service';
import apiClient from '../api';
import {
  NotificationListResponse,
  UnreadCountResponse,
  NotificationDetailResponse,
  MarkAsReadResponse,
  MarkAllAsReadResponse,
  SearchNotificationsResponse,
} from '../../types/notification.types';

// モック設定
jest.mock('../api');

const mockedApiClient = apiClient as jest.Mocked<typeof apiClient>;

describe('notificationService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // console.errorのモック（エラーログを抑制）
    jest.spyOn(console, 'error').mockImplementation(() => {});
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  describe('getNotifications', () => {
    it('通知一覧を取得し、ページネーション情報を含むレスポンスを返す', async () => {
      // Arrange
      const mockResponse: NotificationListResponse = {
        success: true,
        data: {
          notifications: [
            {
              id: 1,
              user_id: 1,
              notification_template_id: 1,
              is_read: false,
              read_at: null,
              created_at: '2025-12-07T10:00:00Z',
              updated_at: '2025-12-07T10:00:00Z',
              template: {
                id: 1,
                title: 'テスト通知',
                content: 'テスト通知の本文',
                priority: 'normal',
                category: 'system',
              },
            },
          ],
          unread_count: 5,
          pagination: {
            total: 50,
            per_page: 20,
            current_page: 1,
            last_page: 3,
            from: 1,
            to: 20,
          },
        },
      };
      mockedApiClient.get.mockResolvedValue({ data: mockResponse });

      // Act
      const result = await notificationService.getNotifications(1);

      // Assert
      expect(mockedApiClient.get).toHaveBeenCalledWith('/notifications?page=1');
      expect(result).toEqual(mockResponse);
      expect(result.data.notifications).toHaveLength(1);
      expect(result.data.unread_count).toBe(5);
      expect(result.data.pagination.current_page).toBe(1);
    });

    it('ページ番号を指定しない場合、デフォルトでpage=1を使用する', async () => {
      // Arrange
      const mockResponse: NotificationListResponse = {
        success: true,
        data: {
          notifications: [],
          unread_count: 0,
          pagination: {
            total: 0,
            per_page: 20,
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
          },
        },
      };
      mockedApiClient.get.mockResolvedValue({ data: mockResponse });

      // Act
      await notificationService.getNotifications();

      // Assert
      expect(mockedApiClient.get).toHaveBeenCalledWith('/notifications?page=1');
    });

    it('API通信エラー時、エラーをスローしてログ出力する', async () => {
      // Arrange
      const mockError = new Error('Network error');
      mockedApiClient.get.mockRejectedValue(mockError);

      // Act & Assert
      await expect(notificationService.getNotifications(1)).rejects.toThrow('Network error');
      expect(console.error).toHaveBeenCalledWith(
        '[notificationService.getNotifications] Error:',
        mockError
      );
    });
  });

  describe('getUnreadCount', () => {
    it('未読通知件数を取得して返す', async () => {
      // Arrange
      const mockResponse: UnreadCountResponse = {
        success: true,
        count: 10,
      };
      mockedApiClient.get.mockResolvedValue({ data: mockResponse });

      // Act
      const result = await notificationService.getUnreadCount();

      // Assert
      expect(mockedApiClient.get).toHaveBeenCalledWith('/notifications/unread-count');
      expect(result).toEqual(mockResponse);
      expect(result.count).toBe(10);
    });

    it('API通信エラー時、エラーをスローしてログ出力する', async () => {
      // Arrange
      const mockError = new Error('Server error');
      mockedApiClient.get.mockRejectedValue(mockError);

      // Act & Assert
      await expect(notificationService.getUnreadCount()).rejects.toThrow('Server error');
      expect(console.error).toHaveBeenCalledWith(
        '[notificationService.getUnreadCount] Error:',
        mockError
      );
    });
  });

  describe('searchNotifications', () => {
    it('検索クエリとページ番号を指定して通知を検索する', async () => {
      // Arrange
      const mockResponse: SearchNotificationsResponse = {
        success: true,
        data: {
          notifications: [
            {
              id: 2,
              user_id: 1,
              notification_template_id: 2,
              is_read: true,
              read_at: '2025-12-07T11:00:00Z',
              created_at: '2025-12-07T09:00:00Z',
              updated_at: '2025-12-07T11:00:00Z',
              template: {
                id: 2,
                title: 'タスク承認',
                content: 'タスクが承認されました',
                priority: 'important',
                category: 'task_approved',
              },
            },
          ],
          pagination: {
            total: 1,
            per_page: 20,
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
          },
        },
      };
      mockedApiClient.get.mockResolvedValue({ data: mockResponse });

      // Act
      const result = await notificationService.searchNotifications('タスク', 1);

      // Assert
      expect(mockedApiClient.get).toHaveBeenCalledWith(
        '/notifications/search?q=%E3%82%BF%E3%82%B9%E3%82%AF&page=1'
      );
      expect(result).toEqual(mockResponse);
      expect(result.data.notifications).toHaveLength(1);
    });

    it('ページ番号を指定しない場合、デフォルトでpage=1を使用する', async () => {
      // Arrange
      const mockResponse: SearchNotificationsResponse = {
        success: true,
        data: {
          notifications: [],
          pagination: {
            total: 0,
            per_page: 20,
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
          },
        },
      };
      mockedApiClient.get.mockResolvedValue({ data: mockResponse });

      // Act
      await notificationService.searchNotifications('test');

      // Assert
      expect(mockedApiClient.get).toHaveBeenCalledWith('/notifications/search?q=test&page=1');
    });

    it('API通信エラー時、エラーをスローしてログ出力する', async () => {
      // Arrange
      const mockError = new Error('Search failed');
      mockedApiClient.get.mockRejectedValue(mockError);

      // Act & Assert
      await expect(notificationService.searchNotifications('query', 1)).rejects.toThrow('Search failed');
      expect(console.error).toHaveBeenCalledWith(
        '[notificationService.searchNotifications] Error:',
        mockError
      );
    });
  });

  describe('markAllAsRead', () => {
    it('全通知を既読にして成功メッセージを返す', async () => {
      // Arrange
      const mockResponse: MarkAllAsReadResponse = {
        success: true,
        message: '全ての通知を既読にしました',
      };
      mockedApiClient.post.mockResolvedValue({ data: mockResponse });

      // Act
      const result = await notificationService.markAllAsRead();

      // Assert
      expect(mockedApiClient.post).toHaveBeenCalledWith('/notifications/read-all');
      expect(result).toEqual(mockResponse);
      expect(result.success).toBe(true);
    });

    it('API通信エラー時、エラーをスローしてログ出力する', async () => {
      // Arrange
      const mockError = new Error('Failed to mark all as read');
      mockedApiClient.post.mockRejectedValue(mockError);

      // Act & Assert
      await expect(notificationService.markAllAsRead()).rejects.toThrow('Failed to mark all as read');
      expect(console.error).toHaveBeenCalledWith(
        '[notificationService.markAllAsRead] Error:',
        mockError
      );
    });
  });

  describe('getNotificationDetail', () => {
    it('通知IDを指定して詳細情報を取得する', async () => {
      // Arrange
      const mockResponse: NotificationDetailResponse = {
        success: true,
        data: {
          notification: {
            id: 3,
            user_id: 1,
            notification_template_id: 3,
            is_read: false,
            read_at: null,
            created_at: '2025-12-07T12:00:00Z',
            updated_at: '2025-12-07T12:00:00Z',
            template: {
              id: 3,
              title: 'トークン残量低下',
              content: 'トークン残量が少なくなっています',
              priority: 'info',
              category: 'token_low',
            },
          },
        },
      };
      mockedApiClient.get.mockResolvedValue({ data: mockResponse });

      // Act
      const result = await notificationService.getNotificationDetail(3);

      // Assert
      expect(mockedApiClient.get).toHaveBeenCalledWith('/notifications/3');
      expect(result).toEqual(mockResponse);
      expect(result.data.notification.id).toBe(3);
    });

    it('API通信エラー時、エラーをスローしてログ出力する', async () => {
      // Arrange
      const mockError = new Error('Notification not found');
      mockedApiClient.get.mockRejectedValue(mockError);

      // Act & Assert
      await expect(notificationService.getNotificationDetail(999)).rejects.toThrow('Notification not found');
      expect(console.error).toHaveBeenCalledWith(
        '[notificationService.getNotificationDetail] Error:',
        mockError
      );
    });
  });

  describe('markAsRead', () => {
    it('通知IDを指定して既読化して成功メッセージを返す', async () => {
      // Arrange
      const mockResponse: MarkAsReadResponse = {
        success: true,
        message: '通知を既読にしました',
        data: {
          notification: {
            id: 4,
            user_id: 1,
            notification_template_id: 4,
            is_read: true,
            read_at: '2025-12-07T13:00:00Z',
            created_at: '2025-12-07T10:00:00Z',
            updated_at: '2025-12-07T13:00:00Z',
            template: null,
          },
        },
      };
      mockedApiClient.patch.mockResolvedValue({ data: mockResponse });

      // Act
      const result = await notificationService.markAsRead(4);

      // Assert
      expect(mockedApiClient.patch).toHaveBeenCalledWith('/notifications/4/read');
      expect(result).toEqual(mockResponse);
      expect(result.data.notification.is_read).toBe(true);
    });

    it('API通信エラー時、エラーをスローしてログ出力する', async () => {
      // Arrange
      const mockError = new Error('Failed to mark as read');
      mockedApiClient.patch.mockRejectedValue(mockError);

      // Act & Assert
      await expect(notificationService.markAsRead(5)).rejects.toThrow('Failed to mark as read');
      expect(console.error).toHaveBeenCalledWith(
        '[notificationService.markAsRead] Error:',
        mockError
      );
    });
  });

  describe('エラーハンドリング（401エラー）', () => {
    it('401エラー時、エラーをスローしてログ出力する', async () => {
      // Arrange
      const mockError = {
        response: {
          status: 401,
          data: {
            success: false,
            message: 'Unauthorized',
          },
        },
      };
      mockedApiClient.get.mockRejectedValue(mockError);

      // Act & Assert
      await expect(notificationService.getUnreadCount()).rejects.toEqual(mockError);
      expect(console.error).toHaveBeenCalledWith(
        '[notificationService.getUnreadCount] Error:',
        mockError
      );
    });
  });
});
