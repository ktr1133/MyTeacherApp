/**
 * NotificationDetailScreen テスト
 * 
 * Phase 2.B-5 Step 2 通知機能テスト
 * UI層のテスト（通知詳細画面）
 */

import { render, waitFor } from '@testing-library/react-native';
import NotificationDetailScreen from '../NotificationDetailScreen';
import { notificationService } from '../../../services/notification.service';
import { Notification } from '../../../types/notification.types';
import { AuthProvider } from '../../../contexts/AuthContext';
import { ThemeProvider } from '../../../contexts/ThemeContext';
import { ColorSchemeProvider } from '../../../contexts/ColorSchemeContext';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';

// ナビゲーションスタック作成
const Stack = createNativeStackNavigator();

/**
 * テスト用コンポーネントをプロバイダーでラップ
 */
const renderWithProviders = (component: React.ReactElement) => {
  return render(
    <ColorSchemeProvider>
      <AuthProvider>
        <ThemeProvider>
          <NavigationContainer>
            <Stack.Navigator>
              <Stack.Screen name="NotificationDetail" component={() => component} />
            </Stack.Navigator>
          </NavigationContainer>
        </ThemeProvider>
      </AuthProvider>
    </ColorSchemeProvider>
  );
};

// モック
jest.mock('../../../services/notification.service');
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

// useRoute モック
jest.mock('@react-navigation/native', () => ({
  ...jest.requireActual('@react-navigation/native'),
  useRoute: () => ({
    params: { notificationId: 1 },
  }),
}));

const mockNotificationService = notificationService as jest.Mocked<typeof notificationService>;

describe('NotificationDetailScreen', () => {
  // モック通知データ
  const mockNotification: Notification = {
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
      content: 'タスク「テスト用タスク」が作成されました。期日は2025年12月20日です。',
      priority: 'normal',
      category: 'task',
    },
  };

  const mockAuthContext = {
    isAuthenticated: true,
    loading: false,
    user: { id: 1, email: 'test@example.com', name: 'Test User' },
    token: 'mock-token',
    login: jest.fn(),
    logout: jest.fn(),
    register: jest.fn(),
    forgotPassword: jest.fn(),
    resetPassword: jest.fn(),
    updateProfile: jest.fn(),
  };

  const mockThemeContext = {
    theme: 'adult' as const,
    setTheme: jest.fn(),
    isLoading: false,
    refreshTheme: jest.fn(),
  };

  // AuthContextとThemeContextをプロバイダーでモック
  jest.spyOn(require('../../../contexts/AuthContext'), 'useAuth').mockReturnValue(mockAuthContext);
  jest.spyOn(require('../../../contexts/ThemeContext'), 'useTheme').mockReturnValue(mockThemeContext);

  beforeEach(() => {
    jest.clearAllMocks();
    jest.spyOn(require('../../../contexts/AuthContext'), 'useAuth').mockReturnValue(mockAuthContext);
    jest.spyOn(require('../../../contexts/ThemeContext'), 'useTheme').mockReturnValue(mockThemeContext);

    // デフォルトのAPIレスポンス
    mockNotificationService.getNotificationDetail.mockResolvedValue({
      success: true,
      data: { notification: mockNotification },
    });
    mockNotificationService.markAsRead.mockResolvedValue({
      success: true,
      message: '既読にしました',
    });
  });

  it('通知詳細情報が正しく表示される', async () => {
    const { getByText } = renderWithProviders(<NotificationDetailScreen />);

    await waitFor(() => {
      expect(getByText('タスクが作成されました')).toBeTruthy();
      expect(getByText('タスク「テスト用タスク」が作成されました。期日は2025年12月20日です。')).toBeTruthy();
      expect(getByText('未読')).toBeTruthy();
    });
  });

  it('画面表示時に未読通知を自動的に既読化する', async () => {
    renderWithProviders(<NotificationDetailScreen />);

    await waitFor(() => {
      expect(mockNotificationService.getNotificationDetail).toHaveBeenCalledWith(1);
      expect(mockNotificationService.markAsRead).toHaveBeenCalledWith(1);
    });
  });

  it('既読通知の場合は既読化APIを呼ばない', async () => {
    const readNotification: Notification = {
      ...mockNotification,
      is_read: true,
      read_at: '2025-12-15T11:00:00Z',
    };

    mockNotificationService.getNotificationDetail.mockResolvedValue({
      success: true,
      data: { notification: readNotification },
    });

    const { getByText } = renderWithProviders(<NotificationDetailScreen />);

    await waitFor(() => {
      expect(getByText('既読')).toBeTruthy();
      expect(mockNotificationService.markAsRead).not.toHaveBeenCalled();
    });
  });

  it('重要度が"important"の場合、優先度バッジが表示される', async () => {
    const importantNotification: Notification = {
      ...mockNotification,
      template: {
        ...mockNotification.template!,
        priority: 'important',
      },
    };

    mockNotificationService.getNotificationDetail.mockResolvedValue({
      success: true,
      data: { notification: importantNotification },
    });

    const { getByText } = renderWithProviders(<NotificationDetailScreen />);

    await waitFor(() => {
      expect(getByText('重要')).toBeTruthy();
    });
  });

  it('APIエラー時にエラーメッセージが表示される', async () => {
    mockNotificationService.getNotificationDetail.mockRejectedValue(
      new Error('通知が見つかりません')
    );

    const { getByText } = renderWithProviders(<NotificationDetailScreen />);

    await waitFor(() => {
      expect(getByText('通知が見つかりません')).toBeTruthy();
      expect(getByText('再読み込み')).toBeTruthy();
    });
  });

  it('child themeで適切なラベルが表示される', async () => {
    jest.spyOn(require('../../../contexts/ThemeContext'), 'useTheme').mockReturnValue({
      ...mockThemeContext,
      theme: 'child',
    });

    const { getByText } = renderWithProviders(<NotificationDetailScreen />);

    await waitFor(() => {
      expect(getByText('ないよう')).toBeTruthy();
    });
  });
});
