/**
 * ナビゲーション統合テスト
 * 
 * Profile → GroupManagement → ScheduledTaskList の導線を検証
 * 各画面のボタンとナビゲーション呼び出しをテスト
 */
import { render, screen, fireEvent } from '@testing-library/react-native';
import { NavigationContainer } from '@react-navigation/native';
import ProfileScreen from '../../profile/ProfileScreen';
import GroupManagementScreen from '../GroupManagementScreen';
import { useTheme } from '../../../contexts/ThemeContext';
import { useAuth } from '../../../contexts/AuthContext';
import { useProfile } from '../../../hooks/useProfile';

// モック
jest.mock('../../../contexts/ThemeContext');
jest.mock('../../../contexts/AuthContext');
jest.mock('../../../hooks/useProfile');

describe('Navigation Integration Tests', () => {
  const mockUser = {
    id: 1,
    username: 'testuser',
    email: 'test@example.com',
    group_id: 1,
    group: {
      id: 1,
      name: 'テストグループ',
    },
    group_edit_flg: true,
  };

  const mockProfile = {
    username: 'testuser',
    email: 'test@example.com',
    name: 'テストユーザー',
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (useTheme as jest.Mock).mockReturnValue({ theme: 'parent' });
    (useAuth as jest.Mock).mockReturnValue({ user: mockUser });
    (useProfile as jest.Mock).mockReturnValue({
      profile: mockProfile,
      isLoading: false,
      error: null,
      getProfile: jest.fn(),
      updateProfile: jest.fn(),
      deleteProfile: jest.fn(),
    });
  });

  /**
   * Test 1: ProfileScreen にグループ管理ボタンが存在する
   */
  it('ProfileScreen にグループ管理ボタンが表示される', () => {
    const mockNavigate = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      navigate: mockNavigate,
    });

    render(
      <NavigationContainer>
        <ProfileScreen />
      </NavigationContainer>
    );

    expect(screen.getByText('グループ管理')).toBeTruthy();
  });

  /**
   * Test 2: ProfileScreen からグループ管理画面へのナビゲーション
   */
  it('グループ管理ボタンをタップするとGroupManagement画面に遷移する', () => {
    const mockNavigate = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      navigate: mockNavigate,
    });

    render(
      <NavigationContainer>
        <ProfileScreen />
      </NavigationContainer>
    );

    const groupButton = screen.getByText('グループ管理');
    fireEvent.press(groupButton);

    expect(mockNavigate).toHaveBeenCalledWith('GroupManagement');
  });

  /**
   * Test 3: GroupManagementScreen にタスクスケジュール管理ボタンが存在する
   */
  it('GroupManagementScreen にタスクスケジュール管理ボタンが表示される', () => {
    const mockNavigate = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      navigate: mockNavigate,
    });

    render(
      <NavigationContainer>
        <GroupManagementScreen />
      </NavigationContainer>
    );

    expect(screen.getByText('タスクスケジュール管理')).toBeTruthy();
  });

  /**
   * Test 4: GroupManagementScreen からScheduledTaskList画面へのナビゲーション
   */
  it('タスクスケジュール管理ボタンをタップするとScheduledTaskList画面に遷移する', () => {
    const mockNavigate = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      navigate: mockNavigate,
    });

    render(
      <NavigationContainer>
        <GroupManagementScreen />
      </NavigationContainer>
    );

    const scheduleButton = screen.getByText('タスクスケジュール管理');
    fireEvent.press(scheduleButton.parent!);

    expect(mockNavigate).toHaveBeenCalledWith('ScheduledTaskList', { groupId: 1 });
  });

  /**
   * Test 5: ナビゲーション階層の確認
   */
  it('正しいナビゲーション階層でパラメータが渡される', () => {
    const mockNavigate = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      navigate: mockNavigate,
    });

    // Step 1: ProfileScreen → GroupManagement
    const { unmount } = render(
      <NavigationContainer>
        <ProfileScreen />
      </NavigationContainer>
    );

    fireEvent.press(screen.getByText('グループ管理'));
    expect(mockNavigate).toHaveBeenCalledWith('GroupManagement');

    unmount();

    // Step 2: GroupManagement → ScheduledTaskList
    mockNavigate.mockClear();
    render(
      <NavigationContainer>
        <GroupManagementScreen />
      </NavigationContainer>
    );

    const scheduleButton = screen.getByText('タスクスケジュール管理');
    fireEvent.press(scheduleButton.parent!);

    expect(mockNavigate).toHaveBeenCalledWith('ScheduledTaskList', { groupId: 1 });
  });
});

