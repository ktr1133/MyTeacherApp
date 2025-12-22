/**
 * GroupManagementScreen テスト
 * 
 * グループ管理画面のUIとナビゲーションをテスト
 */
import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import GroupManagementScreen from '../GroupManagementScreen';
import { ColorSchemeProvider } from '../../../contexts/ColorSchemeContext';
import { AuthProvider } from '../../../contexts/AuthContext';
import { ThemeProvider } from '../../../contexts/ThemeContext';
import { useAuth } from '../../../contexts/AuthContext';

const Stack = createNativeStackNavigator();

// GroupService全体をモック
jest.mock('../../../services/group.service', () => ({
  getGroupInfo: jest.fn(async () => ({
    data: {
      group: {
        id: 1,
        name: 'テストグループ',
        master_user_id: 1,
        created_at: '2025-01-01T00:00:00Z',
        updated_at: '2025-01-01T00:00:00Z',
      },
      members: [
        {
          id: 1,
          username: 'testuser',
          group_edit_flg: true,
          theme: 'adult',
        },
      ],
      task_usage: {
        total_tasks: 10,
        completed_tasks: 5,
        pending_tasks: 5,
      },
    },
  })),
  updateGroup: jest.fn(),
  addMember: jest.fn(),
  removeMember: jest.fn(),
  updatePermission: jest.fn(),
  toggleTheme: jest.fn(),
  transferMaster: jest.fn(),
}));

// モック
jest.mock('../../../contexts/AuthContext');
jest.mock('../../../hooks/useThemedColors', () => ({
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
jest.mock('@react-navigation/native', () => {
  const actualNav = jest.requireActual('@react-navigation/native');
  return {
    ...actualNav,
    useNavigation: () => ({
      navigate: jest.fn(),
    }),
    useFocusEffect: (callback: any) => {
      // テスト環境では即座に実行
      callback();
    },
  };
});

describe('GroupManagementScreen', () => {
  const mockUser = {
    id: 1,
    username: 'testuser',
    group_id: 1,
    group_edit_flg: true,
    group: {
      id: 1,
      name: 'テストグループ',
    },
  };

  const mockGroupData = {
    data: {
      group: {
        id: 1,
        name: 'テストグループ',
        master_user_id: 1,
        created_at: '2025-01-01T00:00:00Z',
        updated_at: '2025-01-01T00:00:00Z',
      },
      members: [
        {
          id: 1,
          username: 'testuser',
          group_edit_flg: true,
          theme: 'adult',
        },
      ],
      task_usage: {
        total_tasks: 10,
        completed_tasks: 5,
        pending_tasks: 5,
      },
    },
  };

  const mockThemeContext = {
    theme: 'adult' as const,
    setTheme: jest.fn(),
    isLoading: false,
    refreshTheme: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    jest.spyOn(require('../../../contexts/ThemeContext'), 'useTheme').mockReturnValue(mockThemeContext);
    (useAuth as jest.Mock).mockReturnValue({ user: mockUser });
  });

  const renderScreen = () => {
    return render(
      <ColorSchemeProvider>
        <AuthProvider>
          <ThemeProvider>
            <NavigationContainer>
              <Stack.Navigator>
                <Stack.Screen name="GroupManagement" component={GroupManagementScreen} />
              </Stack.Navigator>
            </NavigationContainer>
          </ThemeProvider>
        </AuthProvider>
      </ColorSchemeProvider>
    );
  };

  /**
   * Test 1: 初期表示
   */
  it('初期状態でグループ情報が表示される', async () => {
    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('グループ管理')).toBeTruthy();
    });
    
    expect(screen.getByText('グループ情報')).toBeTruthy();
    expect(screen.getByText('テストグループ')).toBeTruthy();
    expect(screen.getByText('グループマスター')).toBeTruthy();
  });

  /**
   * Test 2: 管理メニュー表示
   */
  it('管理メニューが表示される', async () => {
    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('管理メニュー')).toBeTruthy();
    });
    
    expect(screen.getByText('タスクスケジュール管理')).toBeTruthy();
    expect(screen.getByText('定期的に実行するタスクを設定')).toBeTruthy();
  });

  /**
   * Test 3: グループマスター権限の表示
   */
  it('グループマスター権限で全メニューが表示される', async () => {
    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('タスクスケジュール管理')).toBeTruthy();
    });
    
    expect(screen.getByText('メンバー管理')).toBeTruthy();
    expect(screen.getByText('グループ設定')).toBeTruthy();
  });

  /**
   * Test 4: メンバー権限の表示
   */
  it('メンバー権限でスケジュール管理のみ表示される', async () => {
    (useAuth as jest.Mock).mockReturnValue({
      user: { ...mockUser, group_edit_flg: false },
    });

    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('タスクスケジュール管理')).toBeTruthy();
    });
    
    expect(screen.queryByText('メンバー管理')).toBeNull();
    expect(screen.queryByText('グループ設定')).toBeNull();
  });

  /**
   * Test 5: 子供テーマ表示
   */
  it('子供テーマで適切な文言を表示する', async () => {
    const mockChildTheme = { theme: 'child', setTheme: jest.fn(), isLoading: false, refreshTheme: jest.fn() };
    jest.spyOn(require('../../../contexts/ThemeContext'), 'useTheme').mockReturnValue(mockChildTheme);

    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('グループかんり')).toBeTruthy();
    });
    
    expect(screen.getByText('グループじょうほう')).toBeTruthy();
    expect(screen.getByText('タスクスケジュールかんり')).toBeTruthy();
  });

  /**
   * Test 6: タスクスケジュール管理ボタン
   */
  it('タスクスケジュール管理ボタンをタップできる', async () => {
    const mockNavigate = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      navigate: mockNavigate,
    });

    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('タスクスケジュール管理')).toBeTruthy();
    });

    const scheduleButton = screen.getByText('タスクスケジュール管理');
    fireEvent.press(scheduleButton.parent!);

    expect(mockNavigate).toHaveBeenCalledWith('ScheduledTaskList', { groupId: 1 });
  });

  /**
   * Test 7: 準備中バッジ表示
   */
  it('未実装機能に準備中バッジが表示される', async () => {
    renderScreen();

    await waitFor(() => {
      const comingSoonBadges = screen.getAllByText('準備中');
      expect(comingSoonBadges.length).toBeGreaterThanOrEqual(2); // メンバー管理とグループ設定
    });
  });

  /**
   * Test 8: 説明セクション表示
   */
  it('グループ管理の説明が表示される', async () => {
    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('グループ管理について')).toBeTruthy();
    });

    expect(screen.getByText('グループ管理について')).toBeTruthy();
    expect(
      screen.getByText(
        'グループマスターは、メンバーの管理やタスクスケジュールの設定ができます。'
      )
    ).toBeTruthy();
  });

  /**
   * Test 9: メンバー向け説明表示
   */
  it('メンバー権限で適切な説明が表示される', async () => {
    (useAuth as jest.Mock).mockReturnValue({
      user: { ...mockUser, group_edit_flg: false },
    });

    renderScreen();

    await waitFor(() => {
      expect(
        screen.getByText(
          '現在はメンバー権限のため、タスクスケジュールの閲覧のみ可能です。'
        )
      ).toBeTruthy();
    });
  });

  /**
   * Test 10: グループIDなしの場合
   */
  it('グループIDがない場合にボタンが無効化される', async () => {
    (useAuth as jest.Mock).mockReturnValue({
      user: { ...mockUser, group_id: null },
    });

    const mockNavigate = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      navigate: mockNavigate,
    });

    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('タスクスケジュール管理')).toBeTruthy();
    });

    const scheduleButton = screen.getByText('タスクスケジュール管理');
    fireEvent.press(scheduleButton.parent!);

    // group_idがnullの場合、navigateは呼ばれない
    expect(mockNavigate).not.toHaveBeenCalled();
  });
});
