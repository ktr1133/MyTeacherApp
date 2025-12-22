/**
 * ScheduledTaskEditScreen のテスト
 * 
 * スケジュールタスク編集画面のUIテスト
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react-native';
import { NavigationContainer } from '@react-navigation/native';
import { Alert } from 'react-native';
import ScheduledTaskEditScreen from '../ScheduledTaskEditScreen';
import { useScheduledTasks } from '../../../hooks/useScheduledTasks';
import { useTheme } from '../../../contexts/ThemeContext';
import { ColorSchemeProvider } from '../../../contexts/ColorSchemeContext';

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
    params: { scheduledTaskId: 1, groupId: 1 },
  }),
  useFocusEffect: jest.fn((callback) => callback()),
}));

// Alert.alertモック
jest.spyOn(Alert, 'alert');

describe('ScheduledTaskEditScreen', () => {
  const mockGetEditFormData = jest.fn();
  const mockUpdateScheduledTask = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    (useTheme as jest.Mock).mockReturnValue({ theme: 'parent' });
    (useScheduledTasks as jest.Mock).mockReturnValue({
      getEditFormData: mockGetEditFormData,
      updateScheduledTask: mockUpdateScheduledTask,
      isLoading: false,
      error: null,
    });
  });

  const mockScheduledTaskData = {
    scheduled_task: {
      id: 1,
      group_id: 1,
      title: '毎週月曜日のゴミ出し',
      description: '燃えるゴミを出す',
      requires_image: true,
      requires_approval: false,
      reward: 5,
      schedules: [
        {
          type: 'weekly',
          time: '08:00',
          days: [1], // 月曜日
        },
      ],
      due_duration_days: 1,
      due_duration_hours: 0,
      start_date: '2025-01-01',
      end_date: '2025-12-31',
      skip_holidays: true,
      execute_on_next_business_day: true,
      tags: ['家事', 'ゴミ'],
    },
  };

  const renderScreen = () => {
    return render(
      <ColorSchemeProvider>
        <NavigationContainer>
          <ScheduledTaskEditScreen />
        </NavigationContainer>
      </ColorSchemeProvider>
    );
  };

  /**
   * Test 1: データ読み込み
   */
  it('初期表示時にデータを読み込む', async () => {
    mockGetEditFormData.mockResolvedValue(mockScheduledTaskData);

    renderScreen();

    await waitFor(() => {
      expect(mockGetEditFormData).toHaveBeenCalledWith(1);
    });
  });

  /**
   * Test 2: フォーム初期表示
   */
  it('読み込んだデータをフォームに表示する', async () => {
    mockGetEditFormData.mockResolvedValue(mockScheduledTaskData);

    renderScreen();

    await waitFor(() => {
      expect(screen.getByDisplayValue('毎週月曜日のゴミ出し')).toBeTruthy();
      expect(screen.getByDisplayValue('燃えるゴミを出す')).toBeTruthy();
      expect(screen.getByDisplayValue('家事, ゴミ')).toBeTruthy();
    });
  });

  /**
   * Test 3: タイトル編集
   */
  it('タイトルを編集できる', async () => {
    mockGetEditFormData.mockResolvedValue(mockScheduledTaskData);

    renderScreen();

    await waitFor(() => {
      const titleInput = screen.getByDisplayValue('毎週月曜日のゴミ出し');
      fireEvent.changeText(titleInput, '毎週火曜日のゴミ出し');
      expect(titleInput.props.value).toBe('毎週火曜日のゴミ出し');
    });
  });

  /**
   * Test 4: バリデーション - タイトルクリア
   */
  it('タイトルをクリアした場合にエラーを表示する', async () => {
    mockGetEditFormData.mockResolvedValue(mockScheduledTaskData);

    renderScreen();

    await waitFor(() => {
      const titleInput = screen.getByDisplayValue('毎週月曜日のゴミ出し');
      fireEvent.changeText(titleInput, '');
    });

    const submitButton = screen.getByText('更新');
    fireEvent.press(submitButton);

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        'エラー',
        'タイトルを入力してください'
      );
    });
  });

  /**
   * Test 5: 更新処理成功
   */
  it('正しいデータで更新処理を呼び出す', async () => {
    mockGetEditFormData.mockResolvedValue(mockScheduledTaskData);
    mockUpdateScheduledTask.mockResolvedValue(true);

    renderScreen();

    await waitFor(() => {
      expect(screen.getByDisplayValue('毎週月曜日のゴミ出し')).toBeTruthy();
    });

    // タイトル変更
    const titleInput = screen.getByDisplayValue('毎週月曜日のゴミ出し');
    fireEvent.changeText(titleInput, '毎週火曜日のゴミ出し');

    // 更新ボタン押下
    const submitButton = screen.getByText('更新');
    fireEvent.press(submitButton);

    await waitFor(() => {
      expect(mockUpdateScheduledTask).toHaveBeenCalledWith(1, expect.any(Object));
    });
  });

  /**
   * Test 6: ローディング状態（データ読み込み中）
   */
  it('データ読み込み中にインジケーターを表示する', () => {
    mockGetEditFormData.mockImplementation(() => new Promise(() => {})); // 永続的なPromise

    (useScheduledTasks as jest.Mock).mockReturnValue({
      getEditFormData: mockGetEditFormData,
      updateScheduledTask: mockUpdateScheduledTask,
      isLoading: true,
      error: null,
    });

    renderScreen();

    expect(screen.getByText('読み込み中...')).toBeTruthy();
  });

  /**
   * Test 7: ローディング状態（更新中）
   */
  it('更新処理が実行される', async () => {
    mockGetEditFormData.mockResolvedValue(mockScheduledTaskData);
    mockUpdateScheduledTask.mockResolvedValue(true);

    renderScreen();

    await waitFor(() => {
      expect(screen.getByDisplayValue('毎週月曜日のゴミ出し')).toBeTruthy();
    });

    const submitButton = screen.getByText('更新');
    fireEvent.press(submitButton);

    await waitFor(() => {
      expect(mockUpdateScheduledTask).toHaveBeenCalled();
    });
  });

  /**
   * Test 8: エラー表示
   */
  it('エラーが発生した場合にエラーメッセージを表示する', async () => {
    mockGetEditFormData.mockResolvedValue(mockScheduledTaskData);

    (useScheduledTasks as jest.Mock).mockReturnValue({
      getEditFormData: mockGetEditFormData,
      updateScheduledTask: mockUpdateScheduledTask,
      isLoading: false,
      error: 'ネットワークエラー',
    });

    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('ネットワークエラー')).toBeTruthy();
    });
  });

  /**
   * Test 9: 子供テーマ表示
   */
  it('子供テーマで適切な文言を表示する', async () => {
    (useTheme as jest.Mock).mockReturnValue({ theme: 'child' });
    mockGetEditFormData.mockResolvedValue(mockScheduledTaskData);

    renderScreen();

    await waitFor(() => {
      expect(screen.getByText(/きほんじょうほう/)).toBeTruthy();
      expect(screen.getByText(/こうしん/)).toBeTruthy();
      expect(screen.getByText(/やめる/)).toBeTruthy();
    });
  });

  /**
   * Test 10: キャンセルボタン
   */
  it('キャンセルボタン押下時に画面を閉じる', async () => {
    mockGetEditFormData.mockResolvedValue(mockScheduledTaskData);
    const mockGoBack = jest.fn();
    jest.spyOn(require('@react-navigation/native'), 'useNavigation').mockReturnValue({
      goBack: mockGoBack,
      navigate: jest.fn(),
    });

    renderScreen();

    await waitFor(() => {
      expect(screen.getByText('キャンセル')).toBeTruthy();
    });

    const cancelButton = screen.getByText('キャンセル');
    fireEvent.press(cancelButton);

    expect(mockGoBack).toHaveBeenCalled();
  });
});
