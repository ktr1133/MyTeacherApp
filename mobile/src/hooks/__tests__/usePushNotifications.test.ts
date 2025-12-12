/**
 * usePushNotifications Hook Unit Tests
 * Push通知受信カスタムフックのテスト
 * 
 * @see /home/ktr/mtdev/mobile/src/hooks/usePushNotifications.ts
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - 8.2.2 Push通知受信
 */
import { renderHook, waitFor, act } from '@testing-library/react-native';
import { Alert } from 'react-native';
import messaging, { FirebaseMessagingTypes } from '@react-native-firebase/messaging';
import { usePushNotifications } from '../usePushNotifications';

// モック設定
jest.mock('@react-native-firebase/messaging');

// ナビゲーションモック
const mockNavigate = jest.fn();
jest.mock('@react-navigation/native', () => ({
  useNavigation: () => ({
    navigate: mockNavigate,
  }),
}));

describe('usePushNotifications Hook', () => {
  // モック関数のセットアップ
  const mockOnMessage = jest.fn();
  const mockOnNotificationOpenedApp = jest.fn();
  const mockGetInitialNotification = jest.fn();
  const mockUnsubscribeForeground = jest.fn();
  const mockUnsubscribeBackground = jest.fn();
  const mockAlert = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    
    // Alertモック
    Alert.alert = mockAlert;

    // メッセージングモック設定
    (messaging as jest.MockedFunction<typeof messaging>).mockReturnValue({
      onMessage: mockOnMessage.mockReturnValue(mockUnsubscribeForeground),
      onNotificationOpenedApp: mockOnNotificationOpenedApp.mockReturnValue(
        mockUnsubscribeBackground
      ),
      getInitialNotification: mockGetInitialNotification.mockResolvedValue(null),
    } as any);
  });

  describe('初期化処理', () => {
    /**
     * テストケース1: Initializes notification listeners on mount
     * 
     * **検証項目**:
     * - マウント時に3つのリスナーが登録されること
     *   - onMessage (フォアグラウンド)
     *   - onNotificationOpenedApp (バックグラウンド)
     *   - getInitialNotification (終了状態)
     */
    it('should initialize all notification listeners on mount', async () => {
      const { result } = renderHook(() => usePushNotifications());

      // 初期化完了確認
      expect(result.current.isInitialized).toBe(true);

      // リスナー登録確認
      await waitFor(() => {
        expect(mockOnMessage).toHaveBeenCalledTimes(1);
        expect(mockOnNotificationOpenedApp).toHaveBeenCalledTimes(1);
        expect(mockGetInitialNotification).toHaveBeenCalledTimes(1);
      });
    });
  });

  describe('フォアグラウンド通知処理', () => {
    /**
     * テストケース2: Displays alert on foreground notification
     * 
     * **検証項目**:
     * - フォアグラウンドで通知受信時、Alert.alert()が呼び出されること
     * - 通知タイトル・本文が正しく表示されること
     */
    it('should display alert when receiving foreground notification', async () => {
      let foregroundCallback: ((message: FirebaseMessagingTypes.RemoteMessage) => void) | null =
        null;

      // onMessage()のモック: コールバック関数を保存
      mockOnMessage.mockImplementation(
        (callback: (message: FirebaseMessagingTypes.RemoteMessage) => void) => {
          foregroundCallback = callback;
          return mockUnsubscribeForeground;
        }
      );

      renderHook(() => usePushNotifications());

      // リスナー登録確認
      await waitFor(() => {
        expect(mockOnMessage).toHaveBeenCalledTimes(1);
        expect(foregroundCallback).not.toBeNull();
      });

      // フォアグラウンド通知をシミュレート
      const mockMessage: FirebaseMessagingTypes.RemoteMessage = {
        messageId: 'msg-001',
        notification: {
          title: 'タスクが割り当てられました',
          body: '新しいタスク「レポート作成」が割り当てられました',
        },
        data: {
          task_id: '123',
        },
      } as any;

      await act(async () => {
        if (foregroundCallback) {
          foregroundCallback(mockMessage);
        }
      });

      // Alert表示確認
      expect(mockAlert).toHaveBeenCalledWith(
        'タスクが割り当てられました',
        '新しいタスク「レポート作成」が割り当てられました',
        expect.any(Array),
        expect.any(Object)
      );
    });

    /**
     * テストケース3: Navigates to correct screen when "開く" button is pressed
     * 
     * **検証項目**:
     * - Alert.alert()の「開く」ボタン押下時、正しい画面に遷移すること
     * - task_id存在時、TaskDetailScreenへ遷移すること
     */
    it('should navigate to TaskDetail when alert "開く" is pressed', async () => {
      let foregroundCallback: ((message: FirebaseMessagingTypes.RemoteMessage) => void) | null =
        null;

      mockOnMessage.mockImplementation(
        (callback: (message: FirebaseMessagingTypes.RemoteMessage) => void) => {
          foregroundCallback = callback;
          return mockUnsubscribeForeground;
        }
      );

      renderHook(() => usePushNotifications());

      await waitFor(() => {
        expect(foregroundCallback).not.toBeNull();
      });

      // フォアグラウンド通知（task_id付き）
      const mockMessage: FirebaseMessagingTypes.RemoteMessage = {
        messageId: 'msg-002',
        notification: {
          title: 'タスク完了',
          body: 'タスクが承認されました',
        },
        data: {
          task_id: '456',
        },
      } as any;

      await act(async () => {
        if (foregroundCallback) {
          foregroundCallback(mockMessage);
        }
      });

      // Alert.alert()の「開く」ボタンコールバックを取得
      const alertCall = mockAlert.mock.calls[0];
      const buttons = alertCall[2]; // 第3引数がボタン配列
      const openButton = buttons.find((btn: any) => btn.text === '開く');

      // 「開く」ボタン押下をシミュレート
      await act(async () => {
        openButton.onPress();
      });

      // TaskDetailへの遷移確認
      expect(mockNavigate).toHaveBeenCalledWith('TaskDetail', { taskId: '456' });
    });
  });

  describe('バックグラウンド通知処理', () => {
    /**
     * テストケース4: Navigates when background notification is tapped
     * 
     * **検証項目**:
     * - バックグラウンド通知タップ時、正しい画面に遷移すること
     * - notification_id存在時、NotificationDetailScreenへ遷移すること
     */
    it('should navigate to NotificationDetail when background notification is tapped', async () => {
      let backgroundCallback:
        | ((message: FirebaseMessagingTypes.RemoteMessage) => void)
        | null = null;

      // onNotificationOpenedApp()のモック
      mockOnNotificationOpenedApp.mockImplementation(
        (callback: (message: FirebaseMessagingTypes.RemoteMessage) => void) => {
          backgroundCallback = callback;
          return mockUnsubscribeBackground;
        }
      );

      renderHook(() => usePushNotifications());

      await waitFor(() => {
        expect(mockOnNotificationOpenedApp).toHaveBeenCalledTimes(1);
        expect(backgroundCallback).not.toBeNull();
      });

      // バックグラウンド通知タップをシミュレート（notification_id付き）
      const mockMessage: FirebaseMessagingTypes.RemoteMessage = {
        messageId: 'msg-003',
        notification: {
          title: 'トークン購入完了',
          body: '1,000トークンが追加されました',
        },
        data: {
          notification_id: 'notif-789',
        },
      } as any;

      await act(async () => {
        if (backgroundCallback) {
          backgroundCallback(mockMessage);
        }
      });

      // NotificationDetailへの遷移確認
      expect(mockNavigate).toHaveBeenCalledWith('NotificationDetail', {
        notificationId: 'notif-789',
      });
    });

    /**
     * テストケース5: Navigates to GroupDetail when group_id is present
     * 
     * **検証項目**:
     * - group_id存在時、GroupDetailScreenへ遷移すること
     * - notification_idよりgroup_idが優先されないこと（優先度確認）
     */
    it('should navigate to GroupDetail when group_id is present (no notification_id)', async () => {
      let backgroundCallback:
        | ((message: FirebaseMessagingTypes.RemoteMessage) => void)
        | null = null;

      mockOnNotificationOpenedApp.mockImplementation(
        (callback: (message: FirebaseMessagingTypes.RemoteMessage) => void) => {
          backgroundCallback = callback;
          return mockUnsubscribeBackground;
        }
      );

      renderHook(() => usePushNotifications());

      await waitFor(() => {
        expect(backgroundCallback).not.toBeNull();
      });

      // バックグラウンド通知タップ（group_id付き）
      const mockMessage: FirebaseMessagingTypes.RemoteMessage = {
        messageId: 'msg-004',
        notification: {
          title: 'グループタスク追加',
          body: 'グループに新しいタスクが追加されました',
        },
        data: {
          group_id: 'group-abc123',
        },
      } as any;

      await act(async () => {
        if (backgroundCallback) {
          backgroundCallback(mockMessage);
        }
      });

      // GroupDetailへの遷移確認
      expect(mockNavigate).toHaveBeenCalledWith('GroupDetail', {
        groupId: 'group-abc123',
      });
    });
  });

  describe('終了状態からの起動処理', () => {
    /**
     * テストケース6: Navigates when app is opened from quit state by notification
     * 
     * **検証項目**:
     * - アプリ終了状態から通知タップで起動した際、正しい画面に遷移すること
     * - getInitialNotification()で取得した通知データが処理されること
     * - 遷移実行が1秒遅延されること（ナビゲーションスタック初期化待ち）
     */
    it('should navigate after delay when app opens from quit state', async () => {
      jest.useFakeTimers();

      // getInitialNotification()のモック: 通知データを返す
      const mockInitialMessage: FirebaseMessagingTypes.RemoteMessage = {
        messageId: 'msg-005',
        notification: {
          title: '重要な通知',
          body: '緊急タスクが割り当てられました',
        },
        data: {
          task_id: '999',
        },
      } as any;

      mockGetInitialNotification.mockResolvedValue(mockInitialMessage);

      renderHook(() => usePushNotifications());

      // getInitialNotification()呼び出し確認
      await waitFor(() => {
        expect(mockGetInitialNotification).toHaveBeenCalledTimes(1);
      });

      // 1秒進める（遅延処理をスキップ）
      act(() => {
        jest.advanceTimersByTime(1000);
      });

      // TaskDetailへの遷移確認
      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('TaskDetail', { taskId: '999' });
      });

      jest.useRealTimers();
    });

    /**
     * テストケース7: Does not navigate when app opens normally (not from notification)
     * 
     * **検証項目**:
     * - 通常起動時（通知経由でない）、画面遷移が実行されないこと
     * - getInitialNotification()がnullを返した場合、navigateが呼び出されないこと
     */
    it('should not navigate when app opens normally without notification', async () => {
      // getInitialNotification()のモック: null返却
      mockGetInitialNotification.mockResolvedValue(null);

      renderHook(() => usePushNotifications());

      // getInitialNotification()呼び出し確認
      await waitFor(() => {
        expect(mockGetInitialNotification).toHaveBeenCalledTimes(1);
      });

      // navigate()が呼び出されていないことを確認
      expect(mockNavigate).not.toHaveBeenCalled();
    });
  });

  describe('クリーンアップ処理', () => {
    /**
     * テストケース8: Cleans up listeners on unmount
     * 
     * **検証項目**:
     * - アンマウント時に2つのリスナーがクリーンアップされること
     *   - フォアグラウンドリスナー
     *   - バックグラウンドリスナー
     */
    it('should cleanup all listeners on unmount', async () => {
      const { unmount } = renderHook(() => usePushNotifications());

      // リスナー登録確認
      await waitFor(() => {
        expect(mockOnMessage).toHaveBeenCalledTimes(1);
        expect(mockOnNotificationOpenedApp).toHaveBeenCalledTimes(1);
      });

      // アンマウント実行
      unmount();

      // unsubscribe()呼び出し確認
      expect(mockUnsubscribeForeground).toHaveBeenCalledTimes(1);
      expect(mockUnsubscribeBackground).toHaveBeenCalledTimes(1);
    });
  });

  describe('ナビゲーション優先度', () => {
    /**
     * テストケース9: notification_id takes precedence over task_id
     * 
     * **検証項目**:
     * - notification_idとtask_idが両方存在する場合、notification_idが優先されること
     * - NotificationDetailScreenへ遷移すること
     */
    it('should prioritize notification_id over task_id', async () => {
      let backgroundCallback:
        | ((message: FirebaseMessagingTypes.RemoteMessage) => void)
        | null = null;

      mockOnNotificationOpenedApp.mockImplementation(
        (callback: (message: FirebaseMessagingTypes.RemoteMessage) => void) => {
          backgroundCallback = callback;
          return mockUnsubscribeBackground;
        }
      );

      renderHook(() => usePushNotifications());

      await waitFor(() => {
        expect(backgroundCallback).not.toBeNull();
      });

      // notification_id + task_id両方存在
      const mockMessage: FirebaseMessagingTypes.RemoteMessage = {
        messageId: 'msg-006',
        notification: {
          title: '複合通知',
          body: 'テスト',
        },
        data: {
          notification_id: 'notif-priority',
          task_id: 'task-should-be-ignored',
        },
      } as any;

      await act(async () => {
        if (backgroundCallback) {
          backgroundCallback(mockMessage);
        }
      });

      // NotificationDetailへの遷移確認（task_idは無視）
      expect(mockNavigate).toHaveBeenCalledWith('NotificationDetail', {
        notificationId: 'notif-priority',
      });
      expect(mockNavigate).not.toHaveBeenCalledWith('TaskDetail', expect.any(Object));
    });

    /**
     * テストケース10: Falls back to NotificationList when no data
     * 
     * **検証項目**:
     * - dataプロパティが空の場合、NotificationListScreenへフォールバックすること
     */
    it('should fallback to NotificationList when no navigation data is provided', async () => {
      let backgroundCallback:
        | ((message: FirebaseMessagingTypes.RemoteMessage) => void)
        | null = null;

      mockOnNotificationOpenedApp.mockImplementation(
        (callback: (message: FirebaseMessagingTypes.RemoteMessage) => void) => {
          backgroundCallback = callback;
          return mockUnsubscribeBackground;
        }
      );

      renderHook(() => usePushNotifications());

      await waitFor(() => {
        expect(backgroundCallback).not.toBeNull();
      });

      // dataプロパティが空の通知
      const mockMessage: FirebaseMessagingTypes.RemoteMessage = {
        messageId: 'msg-007',
        notification: {
          title: 'システム通知',
          body: 'データなし通知',
        },
        data: {},
      } as any;

      await act(async () => {
        if (backgroundCallback) {
          backgroundCallback(mockMessage);
        }
      });

      // NotificationListへフォールバック
      expect(mockNavigate).toHaveBeenCalledWith('NotificationList');
    });
  });
});
