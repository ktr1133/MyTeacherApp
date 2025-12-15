/**
 * NotificationListScreen テスト
 * 
 * Phase 2.B-5 Step 2 通知機能テスト
 * UI層のテスト（通知一覧画面）
 */

import { render, fireEvent, waitFor, act } from '@testing-library/react-native';
import { Alert } from 'react-native';
import NotificationListScreen from '../NotificationListScreen';
import { useNotifications } from '../../../hooks/useNotifications';
import { useTheme } from '../../../contexts/ThemeContext';
import { useNavigation } from '@react-navigation/native';
import { Notification } from '../../../types/notification.types';

// モック
jest.mock('../../../hooks/useNotifications');
jest.mock('../../../contexts/ThemeContext');
jest.mock('../../../contexts/ColorSchemeContext', () => ({
  useColorScheme: () => ({ colorScheme: 'light', setColorScheme: jest.fn() }),
}));
jest.mock('../../../hooks/useThemedColors', () => ({
  useThemedColors: () => ({
    colors: {
      background: '#FFFFFF',
      surface: '#F9FAFB',
      card: '#FFFFFF',
      text: {
        primary: '#111827',
        secondary: '#6B7280',
        tertiary: '#9CA3AF',
        disabled: '#D1D5DB',
      },
      border: {
        default: '#E5E7EB',
        light: 'rgba(229, 231, 235, 0.5)',
      },
      status: {
        success: '#10B981',
        warning: '#F59E0B',
        error: '#EF4444',
        info: '#3B82F6',
      },
      overlay: 'rgba(0, 0, 0, 0.5)',
    },
    accent: { primary: '#007AFF', gradient: ['#007AFF', '#0056D2'] },
    isDark: false,
    theme: 'adult',
  }),
}));
jest.mock('../../../utils/responsive', () => ({
  useResponsive: () => ({ width: 375, height: 812, isSmall: false, isMedium: true, isLarge: false }),
  getFontSize: (size: number) => size,
  getSpacing: (size: number) => size,
  getBorderRadius: (size: number) => size,
  getShadow: () => ({}),
}));
jest.mock('../../../hooks/useChildTheme', () => ({
  useChildTheme: () => false,
}));
jest.mock('@react-navigation/native', () => ({
  ...jest.requireActual('@react-navigation/native'),
  useNavigation: jest.fn(),
}));

// Alert.alertのモック
jest.spyOn(Alert, 'alert');

const mockUseNotifications = useNotifications as jest.MockedFunction<typeof useNotifications>;
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;
const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;

describe('NotificationListScreen', () => {
  // モック通知データ
  const mockNotifications: Notification[] = [
    {
      id: 1,
      user_id: 1,
      notification_template_id: 1,
      is_read: false,
      read_at: null,
      created_at: '2025-12-15T10:00:00Z',
      updated_at: '2025-12-15T10:00:00Z',
      template: {
        id: 1,
        title: 'タスクが作成されました',
        content: 'タスク「{{title}}」が作成されました。',
        priority: 'normal',
        category: 'task',
      },
    },
    {
      id: 2,
      user_id: 1,
      notification_template_id: 2,
      is_read: true,
      read_at: '2025-12-15T11:00:00Z',
      created_at: '2025-12-15T09:00:00Z',
      updated_at: '2025-12-15T11:00:00Z',
      template: {
        id: 2,
        title: 'タスクが完了しました',
        content: 'タスク「{{title}}」が完了しました。おめでとうございます！',
        priority: 'normal',
        category: 'task',
      },
    },
    {
      id: 3,
      user_id: 1,
      notification_template_id: 3,
      is_read: false,
      read_at: null,
      created_at: '2025-12-15T12:00:00Z',
      updated_at: '2025-12-15T12:00:00Z',
      template: {
        id: 3,
        title: 'システムメンテナンスのお知らせ',
        content: 'メンテナンスを実施します。',
        priority: 'important',
        category: 'system',
      },
    },
  ];

  const mockNotificationsHook = {
    notifications: mockNotifications,
    unreadCount: 2,
    loading: false,
    error: null,
    hasMore: true,
    currentPage: 1,
    totalPages: 3,
    fetchNotifications: jest.fn(),
    fetchUnreadCount: jest.fn(),
    markAsRead: jest.fn(),
    markAllAsRead: jest.fn(),
    searchNotifications: jest.fn(),
    loadMore: jest.fn(),
    refresh: jest.fn(),
    startPolling: jest.fn(),
    stopPolling: jest.fn(),
  };

  const mockThemeContext = {
    theme: 'adult' as const,
    setTheme: jest.fn(),
    isLoading: false,
    refreshTheme: jest.fn(),
  };

  const mockNavigation = {
    navigate: jest.fn(),
    goBack: jest.fn(),
    setOptions: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    jest.useFakeTimers();
    mockUseNotifications.mockReturnValue(mockNotificationsHook);
    mockUseTheme.mockReturnValue(mockThemeContext);
    mockUseNavigation.mockReturnValue(mockNavigation as any);
  });

  afterEach(() => {
    jest.runOnlyPendingTimers();
    jest.useRealTimers();
  });

  it('通知一覧が正しく表示される', async () => {
    const { getByText } = render(<NotificationListScreen />);

    await waitFor(() => {
      expect(getByText('タスクが作成されました')).toBeTruthy();
      expect(getByText('タスクが完了しました')).toBeTruthy();
      expect(getByText('システムメンテナンスのお知らせ')).toBeTruthy();
    });
  });

  it('未読件数バッジが表示される', async () => {
    const { getByText } = render(<NotificationListScreen />);

    await waitFor(() => {
      expect(getByText('未読: 2件')).toBeTruthy();
    });
  });

  it('Pull-to-Refreshで通知をリロードできる', async () => {
    mockNotificationsHook.refresh.mockResolvedValue(undefined);

    const { UNSAFE_getByProps } = render(<NotificationListScreen />);

    // RefreshControlのonRefreshを直接呼び出し
    const refreshControl = UNSAFE_getByProps({ testID: 'refresh-control' });
    
    await act(async () => {
      await refreshControl.props.onRefresh();
    });

    // refresh()が呼ばれたことを確認
    expect(mockNotificationsHook.refresh).toHaveBeenCalled();
  });

  it('検索クエリ入力で300msデバウンス後に検索が実行される', async () => {
    const { getByPlaceholderText } = render(<NotificationListScreen />);

    const searchInput = getByPlaceholderText('通知を検索...');

    await act(async () => {
      fireEvent.changeText(searchInput, 'タスク');
    });

    // 300ms未満では検索されない
    expect(mockNotificationsHook.searchNotifications).not.toHaveBeenCalled();

    // 300ms経過後に検索実行
    await act(async () => {
      jest.advanceTimersByTime(300);
    });

    await waitFor(() => {
      expect(mockNotificationsHook.searchNotifications).toHaveBeenCalledWith('タスク', 1);
    });
  });

  it('検索クエリクリアで通常の一覧取得に戻る', async () => {
    const { getByPlaceholderText } = render(<NotificationListScreen />);

    const searchInput = getByPlaceholderText('通知を検索...');

    // 検索クエリ入力
    await act(async () => {
      fireEvent.changeText(searchInput, 'タスク');
      jest.advanceTimersByTime(300);
    });

    await waitFor(() => {
      expect(mockNotificationsHook.searchNotifications).toHaveBeenCalledWith('タスク', 1);
    });

    // クエリクリア
    await act(async () => {
      fireEvent.changeText(searchInput, '');
      jest.advanceTimersByTime(300);
    });

    await waitFor(() => {
      expect(mockNotificationsHook.fetchNotifications).toHaveBeenCalledWith(1);
    });
  });

  it('"すべて既読"ボタンで確認アラートが表示され、OKで既読化される', async () => {
    // Alert.alert の実装をモック（OKボタン押下をシミュレート）
    (Alert.alert as jest.Mock).mockImplementation(
      (title, message, buttons) => {
        // OKボタン（配列の2番目）のonPressを実行
        if (buttons && buttons[1]?.onPress) {
          buttons[1].onPress();
        }
      }
    );

    const { getByText } = render(<NotificationListScreen />);

    await waitFor(() => {
      expect(getByText('すべて既読にする')).toBeTruthy();
    });

    await act(async () => {
      fireEvent.press(getByText('すべて既読にする'));
    });

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        '確認',
        'すべての通知を既読にしますか？',
        expect.arrayContaining([
          expect.objectContaining({ text: 'キャンセル' }),
          expect.objectContaining({ text: '既読にする' }),
        ])
      );
      expect(mockNotificationsHook.markAllAsRead).toHaveBeenCalled();
    });
  });

  it('通知タップで既読化され、詳細画面に遷移する', async () => {
    mockNotificationsHook.markAsRead.mockResolvedValue();

    const { getByText } = render(<NotificationListScreen />);

    await waitFor(() => {
      expect(getByText('タスクが作成されました')).toBeTruthy();
    });

    // 未読通知（ID: 1）をタップ
    await act(async () => {
      fireEvent.press(getByText('タスクが作成されました'));
    });

    await waitFor(() => {
      expect(mockNotificationsHook.markAsRead).toHaveBeenCalledWith(1);
      expect(mockNavigation.navigate).toHaveBeenCalledWith('NotificationDetail', {
        notificationId: 1,
      });
    });
  });

  it('リスト末尾到達で次ページを読み込む（無限スクロール）', async () => {
    const { getByTestId } = render(<NotificationListScreen />);

    const flatList = getByTestId('notification-list');

    await act(async () => {
      fireEvent(flatList, 'endReached');
    });

    await waitFor(() => {
      expect(mockNotificationsHook.loadMore).toHaveBeenCalled();
    });
  });

  it('エラーメッセージが表示される', async () => {
    mockUseNotifications.mockReturnValue({
      ...mockNotificationsHook,
      error: '通知の取得に失敗しました',
    });

    const { getByText } = render(<NotificationListScreen />);

    await waitFor(() => {
      expect(getByText('通知の取得に失敗しました')).toBeTruthy();
    });
  });

  it('ローディング中にインジケーターが表示される', async () => {
    mockUseNotifications.mockReturnValue({
      ...mockNotificationsHook,
      loading: true,
      notifications: [],
    });

    const { getByTestId } = render(<NotificationListScreen />);

    await waitFor(() => {
      expect(getByTestId('loading-indicator')).toBeTruthy();
    });
  });

  it('child themeで適切なラベルが表示される', async () => {
    mockUseTheme.mockReturnValue({
      ...mockThemeContext,
      theme: 'child',
    });

    const { getByText } = render(<NotificationListScreen />);

    await waitFor(() => {
      expect(getByText('おしらせ')).toBeTruthy();
      expect(getByText('みんなよんだことにする')).toBeTruthy();
    });
  });
});
