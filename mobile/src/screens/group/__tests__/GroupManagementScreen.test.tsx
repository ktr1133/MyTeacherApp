/**
 * GroupManagementScreen テスト
 * 
 * グループ管理画面のUIとナビゲーションをテスト
 */
import { render, screen, fireEvent } from '@testing-library/react-native';
import { NavigationContainer } from '@react-navigation/native';
import GroupManagementScreen from '../GroupManagementScreen';
import { useTheme } from '../../../contexts/ThemeContext';
import { useAuth } from '../../../contexts/AuthContext';

// モック
jest.mock('../../../contexts/ThemeContext');
jest.mock('../../../contexts/AuthContext');
jest.mock('@react-navigation/native', () => ({
  ...jest.requireActual('@react-navigation/native'),
  useNavigation: () => ({
    navigate: jest.fn(),
  }),
}));

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

  beforeEach(() => {
    jest.clearAllMocks();
    (useTheme as jest.Mock).mockReturnValue({ theme: 'parent' });
    (useAuth as jest.Mock).mockReturnValue({ user: mockUser });
  });

  const renderScreen = () => {
    return render(
      <NavigationContainer>
        <GroupManagementScreen />
      </NavigationContainer>
    );
  };

  /**
   * Test 1: 初期表示
   */
  it('初期状態でグループ情報が表示される', () => {
    renderScreen();

    expect(screen.getByText('グループ管理')).toBeTruthy();
    expect(screen.getByText('グループ情報')).toBeTruthy();
    expect(screen.getByText('テストグループ')).toBeTruthy();
    expect(screen.getByText('グループマスター')).toBeTruthy();
  });

  /**
   * Test 2: 管理メニュー表示
   */
  it('管理メニューが表示される', () => {
    renderScreen();

    expect(screen.getByText('管理メニュー')).toBeTruthy();
    expect(screen.getByText('タスクスケジュール管理')).toBeTruthy();
    expect(screen.getByText('定期的に実行するタスクを設定')).toBeTruthy();
  });

  /**
   * Test 3: グループマスター権限の表示
   */
  it('グループマスター権限で全メニューが表示される', () => {
    renderScreen();

    expect(screen.getByText('タスクスケジュール管理')).toBeTruthy();
    expect(screen.getByText('メンバー管理')).toBeTruthy();
    expect(screen.getByText('グループ設定')).toBeTruthy();
  });

  /**
   * Test 4: メンバー権限の表示
   */
  it('メンバー権限でスケジュール管理のみ表示される', () => {
    (useAuth as jest.Mock).mockReturnValue({
      user: { ...mockUser, group_edit_flg: false },
    });

    renderScreen();

    expect(screen.getByText('タスクスケジュール管理')).toBeTruthy();
    expect(screen.queryByText('メンバー管理')).toBeNull();
    expect(screen.queryByText('グループ設定')).toBeNull();
  });

  /**
   * Test 5: 子供テーマ表示
   */
  it('子供テーマで適切な文言を表示する', () => {
    (useTheme as jest.Mock).mockReturnValue({ theme: 'child' });

    renderScreen();

    expect(screen.getByText('グループかんり')).toBeTruthy();
    expect(screen.getByText('グループじょうほう')).toBeTruthy();
    expect(screen.getByText('タスクスケジュールかんり')).toBeTruthy();
  });

  /**
   * Test 6: タスクスケジュール管理ボタン
   */
  it('タスクスケジュール管理ボタンをタップできる', () => {
    const mockNavigate = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      navigate: mockNavigate,
    });

    renderScreen();

    const scheduleButton = screen.getByText('タスクスケジュール管理');
    fireEvent.press(scheduleButton.parent!);

    expect(mockNavigate).toHaveBeenCalledWith('ScheduledTaskList', { groupId: 1 });
  });

  /**
   * Test 7: 準備中バッジ表示
   */
  it('未実装機能に準備中バッジが表示される', () => {
    renderScreen();

    const comingSoonBadges = screen.getAllByText('準備中');
    expect(comingSoonBadges.length).toBeGreaterThanOrEqual(2); // メンバー管理とグループ設定
  });

  /**
   * Test 8: 説明セクション表示
   */
  it('グループ管理の説明が表示される', () => {
    renderScreen();

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
  it('メンバー権限で適切な説明が表示される', () => {
    (useAuth as jest.Mock).mockReturnValue({
      user: { ...mockUser, group_edit_flg: false },
    });

    renderScreen();

    expect(
      screen.getByText(
        '現在はメンバー権限のため、タスクスケジュールの閲覧のみ可能です。'
      )
    ).toBeTruthy();
  });

  /**
   * Test 10: グループIDなしの場合
   */
  it('グループIDがない場合にボタンが無効化される', () => {
    (useAuth as jest.Mock).mockReturnValue({
      user: { ...mockUser, group_id: null },
    });

    const mockNavigate = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      navigate: mockNavigate,
    });

    renderScreen();

    const scheduleButton = screen.getByText('タスクスケジュール管理');
    fireEvent.press(scheduleButton.parent!);

    // group_idがnullの場合、navigateは呼ばれない
    expect(mockNavigate).not.toHaveBeenCalled();
  });
});
