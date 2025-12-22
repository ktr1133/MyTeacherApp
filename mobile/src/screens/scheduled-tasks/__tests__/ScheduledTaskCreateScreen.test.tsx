/**
 * ScheduledTaskCreateScreen のテスト
 * 
 * スケジュールタスク作成画面のUIテスト
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react-native';
import { NavigationContainer } from '@react-navigation/native';
import { Alert } from 'react-native';
import ScheduledTaskCreateScreen from '../ScheduledTaskCreateScreen';
import { useScheduledTasks } from '../../../hooks/useScheduledTasks';
import { useTheme } from '../../../contexts/ThemeContext';
import { ColorSchemeProvider } from '../../../contexts/ColorSchemeContext';
import { ThemeProvider } from '../../../contexts/ThemeContext';

// モック
jest.mock('../../../hooks/useScheduledTasks');
jest.mock('../../../contexts/ThemeContext');
jest.mock('../../../hooks/useThemedColors', () => ({
  useThemedColors: jest.fn(() => ({
    colors: {
      background: '#FFFFFF',
      text: '#000000',
      card: '#F5F5F5',
      border: '#E0E0E0',
      notification: '#FF0000',
      primary: '#007AFF',
    },
    accent: {
      primary: '#007AFF',
      secondary: '#5856D6',
      success: '#34C759',
      warning: '#FF9500',
      error: '#FF3B30',
    },
  })),
}));
jest.mock('@react-navigation/native', () => ({
  ...jest.requireActual('@react-navigation/native'),
  useNavigation: () => ({
    navigate: jest.fn(),
    goBack: jest.fn(),
  }),
  useRoute: () => ({
    params: { groupId: 1 },
  }),
  useFocusEffect: jest.fn(),
}));

// Alert.alertモック
jest.spyOn(Alert, 'alert');

describe('ScheduledTaskCreateScreen', () => {
  const mockCreateScheduledTask = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    (useTheme as jest.Mock).mockReturnValue({ theme: 'parent' });
    (useScheduledTasks as jest.Mock).mockReturnValue({
      createScheduledTask: mockCreateScheduledTask,
      isLoading: false,
      error: null,
    });
  });

  const renderScreen = () => {
    return render(
      <ThemeProvider>
        <ColorSchemeProvider>
          <NavigationContainer>
            <ScheduledTaskCreateScreen />
          </NavigationContainer>
        </ColorSchemeProvider>
      </ThemeProvider>
    );
  };

  /**
   * Test 1: 初期表示
   */
  it('初期状態で基本情報セクションが表示される', () => {
    renderScreen();

    expect(screen.getByText(/基本情報/)).toBeTruthy();
    expect(screen.getByText(/スケジュール設定/)).toBeTruthy();
    expect(screen.getByText(/期限設定/)).toBeTruthy();
    expect(screen.getByText(/実行期間/)).toBeTruthy();
    expect(screen.getByText(/その他設定/)).toBeTruthy();
    expect(screen.getByText(/タグ/)).toBeTruthy();
  });

  /**
   * Test 2: タイトル入力
   */
  it('タイトルを入力できる', () => {
    renderScreen();

    const titleInput = screen.getByPlaceholderText('タスクのタイトル');
    fireEvent.changeText(titleInput, '毎週月曜日のゴミ出し');

    expect(titleInput.props.value).toBe('毎週月曜日のゴミ出し');
  });

  /**
   * Test 3: スケジュール追加
   */
  it('スケジュールを追加できる', () => {
    renderScreen();

    const addButton = screen.getByText(/スケジュールを追加/);
    fireEvent.press(addButton);

    // 2つのスケジュールカードが表示される（初期1つ + 追加1つ）
    const scheduleCards = screen.getAllByText(/タイプ/);
    expect(scheduleCards.length).toBeGreaterThanOrEqual(2);
  });

  /**
   * Test 4: バリデーション - タイトル未入力
   */
  it('タイトル未入力時にエラーを表示する', async () => {
    renderScreen();

    const submitButton = screen.getByText('作成');
    fireEvent.press(submitButton);

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        'エラー',
        'タイトルを入力してください'
      );
    });
  });

  /**
   * Test 5: 作成処理成功
   */
  it('正しいデータで作成処理を呼び出す', async () => {
    mockCreateScheduledTask.mockResolvedValue(true);

    renderScreen();

    // タイトル入力
    const titleInput = screen.getByPlaceholderText('タスクのタイトル');
    fireEvent.changeText(titleInput, '毎週月曜日のゴミ出し');

    // 作成ボタン押下
    const submitButton = screen.getByText('作成');
    fireEvent.press(submitButton);

    await waitFor(() => {
      expect(mockCreateScheduledTask).toHaveBeenCalled();
    });
  });

  /**
   * Test 6: ローディング状態
   */
  it('ローディング中にインジケーターを表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      createScheduledTask: mockCreateScheduledTask,
      isLoading: true,
      error: null,
    });

    renderScreen();

    expect(screen.getByText('作成中...')).toBeTruthy();
  });

  /**
   * Test 7: エラー表示
   */
  it('エラーが発生した場合にエラーメッセージを表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      createScheduledTask: mockCreateScheduledTask,
      isLoading: false,
      error: 'ネットワークエラー',
    });

    renderScreen();

    expect(screen.getByText('ネットワークエラー')).toBeTruthy();
  });

  /**
   * Test 8: 子供テーマ表示
   */
  it('子供テーマで適切な文言を表示する', () => {
    (useTheme as jest.Mock).mockReturnValue({ theme: 'child' });

    renderScreen();

    expect(screen.getByText(/きほんじょうほう/)).toBeTruthy();
    expect(screen.getByText(/つくる/)).toBeTruthy();
    expect(screen.getByText(/やめる/)).toBeTruthy();
  });

  /**
   * Test 9: 画像必須トグル
   */
  it('画像必須フラグを切り替えられる', () => {
    renderScreen();

    const imageSwitches = screen.getAllByRole('switch');
    const imageSwitch = imageSwitches[0]; // 最初のSwitch（画像添付必須）

    expect(imageSwitch.props.value).toBe(false);
    fireEvent(imageSwitch, 'valueChange', true);
    expect(imageSwitch.props.value).toBe(true);
  });

  /**
   * Test 10: キャンセルボタン
   */
  it('キャンセルボタン押下時に画面を閉じる', () => {
    const mockGoBack = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      goBack: mockGoBack,
      navigate: jest.fn(),
    });

    renderScreen();

    const cancelButton = screen.getByText('キャンセル');
    fireEvent.press(cancelButton);

    expect(mockGoBack).toHaveBeenCalled();
  });
});
