/**
 * HeaderNotificationIcon コンポーネントのテスト
 * 
 * @see /home/ktr/mtdev/mobile/src/components/common/HeaderNotificationIcon.tsx
 */

import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import HeaderNotificationIcon from '../../../src/components/common/HeaderNotificationIcon';
import { useNotifications } from '../../../src/hooks/useNotifications';
import { useNavigation } from '@react-navigation/native';
import { ColorSchemeProvider } from '../../../src/contexts/ColorSchemeContext';

// モック
jest.mock('../../../src/hooks/useNotifications');
jest.mock('../../../src/hooks/useThemedColors', () => ({
  useThemedColors: jest.fn(() => ({
    colors: {
      background: '#FFFFFF',
      text: {
        primary: '#111827',
        secondary: '#6B7280',
        tertiary: '#9CA3AF',
        disabled: '#D1D5DB',
      },
      card: '#FFFFFF',
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
    },
    accent: {
      primary: '#007AFF',
      gradient: ['#007AFF', '#5856D6'],
    },
  })),
}));
jest.mock('../../../src/utils/responsive', () => ({
  useResponsive: () => ({
    width: 375,
    height: 812,
    deviceSize: 'md',
    isPortrait: true,
    isLandscape: false,
  }),
  getFontSize: (size: number) => size,
  getSpacing: (size: number) => size,
  getBorderRadius: (size: number) => size,
}));
jest.mock('../../../src/hooks/useChildTheme', () => ({
  useChildTheme: () => false,
}));
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
}));

const mockUseNotifications = useNotifications as jest.MockedFunction<typeof useNotifications>;
const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;

describe('HeaderNotificationIcon', () => {
  const mockNavigate = jest.fn();

  const renderScreen = (component: React.ReactElement) => {
    return render(
      <ColorSchemeProvider>
        {component}
      </ColorSchemeProvider>
    );
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockUseNavigation.mockReturnValue({
      navigate: mockNavigate,
    } as any);
  });

  describe('バッジ表示', () => {
    it('未読通知が0件の場合、バッジが表示されない', () => {
      mockUseNotifications.mockReturnValue({
        unreadCount: 0,
        notifications: [],
        isLoading: false,
        error: null,
        fetchNotifications: jest.fn(),
        markAsRead: jest.fn(),
        markAllAsRead: jest.fn(),
        clearError: jest.fn(),
      });

      const { queryByTestId } = renderScreen(<HeaderNotificationIcon />);

      expect(queryByTestId('notification-badge')).toBeNull();
    });

    it('未読通知が1件の場合、バッジが「1」と表示される', () => {
      mockUseNotifications.mockReturnValue({
        unreadCount: 1,
        notifications: [],
        isLoading: false,
        error: null,
        fetchNotifications: jest.fn(),
        markAsRead: jest.fn(),
        markAllAsRead: jest.fn(),
        clearError: jest.fn(),
      });

      const { getByTestId, getByText } = renderScreen(<HeaderNotificationIcon />);

      expect(getByTestId('notification-badge')).toBeTruthy();
      expect(getByText('1')).toBeTruthy();
    });

    it('未読通知が99件の場合、バッジが「99」と表示される', () => {
      mockUseNotifications.mockReturnValue({
        unreadCount: 99,
        notifications: [],
        isLoading: false,
        error: null,
        fetchNotifications: jest.fn(),
        markAsRead: jest.fn(),
        markAllAsRead: jest.fn(),
        clearError: jest.fn(),
      });

      const { getByTestId, getByText } = renderScreen(<HeaderNotificationIcon />);

      expect(getByTestId('notification-badge')).toBeTruthy();
      expect(getByText('99')).toBeTruthy();
    });

    it('未読通知が100件以上の場合、バッジが「99+」と表示される', () => {
      mockUseNotifications.mockReturnValue({
        unreadCount: 150,
        notifications: [],
        isLoading: false,
        error: null,
        fetchNotifications: jest.fn(),
        markAsRead: jest.fn(),
        markAllAsRead: jest.fn(),
        clearError: jest.fn(),
      });

      const { getByTestId, getByText } = renderScreen(<HeaderNotificationIcon />);

      expect(getByTestId('notification-badge')).toBeTruthy();
      expect(getByText('99+')).toBeTruthy();
    });
  });

  describe('ナビゲーション', () => {
    it('アイコンをタップすると通知一覧画面に遷移する', async () => {
      mockUseNotifications.mockReturnValue({
        unreadCount: 5,
        notifications: [],
        isLoading: false,
        error: null,
        fetchNotifications: jest.fn(),
        markAsRead: jest.fn(),
        markAllAsRead: jest.fn(),
        clearError: jest.fn(),
      });

      const { getByTestId } = renderScreen(<HeaderNotificationIcon />);

      fireEvent.press(getByTestId('header-notification-icon'));

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('NotificationList');
      });
    });
  });

  describe('アクセシビリティ', () => {
    it('適切なaccessibilityLabelが設定されている', () => {
      mockUseNotifications.mockReturnValue({
        unreadCount: 3,
        notifications: [],
        isLoading: false,
        error: null,
        fetchNotifications: jest.fn(),
        markAsRead: jest.fn(),
        markAllAsRead: jest.fn(),
        clearError: jest.fn(),
      });

      const { getByLabelText } = renderScreen(<HeaderNotificationIcon />);

      expect(getByLabelText('通知')).toBeTruthy();
    });

    it('適切なaccessibilityHintが設定されている', () => {
      mockUseNotifications.mockReturnValue({
        unreadCount: 7,
        notifications: [],
        isLoading: false,
        error: null,
        fetchNotifications: jest.fn(),
        markAsRead: jest.fn(),
        markAllAsRead: jest.fn(),
        clearError: jest.fn(),
      });

      const { getByA11yHint } = renderScreen(<HeaderNotificationIcon />);

      expect(getByA11yHint('未読通知7件')).toBeTruthy();
    });
  });
});
