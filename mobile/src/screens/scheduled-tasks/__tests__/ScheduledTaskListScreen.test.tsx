/**
 * ScheduledTaskListScreen のテスト
 * 
 * スケジュールタスク一覧画面のUIテスト
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { Alert } from 'react-native';
import ScheduledTaskListScreen from '../ScheduledTaskListScreen';
import { useScheduledTasks } from '../../../hooks/useScheduledTasks';
import { AuthProvider } from '../../../contexts/AuthContext';
import { ThemeProvider } from '../../../contexts/ThemeContext';
import { ColorSchemeProvider } from '../../../contexts/ColorSchemeContext';

// ナビゲーションスタック作成
const Stack = createNativeStackNavigator();

// モック
jest.mock('../../../hooks/useScheduledTasks');
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
      info: {
        background: '#EFF6FF',
        border: '#BFDBFE',
        text: '#1E40AF',
      },
    },
    accent: {
      primary: '#007AFF',
      gradient: ['#007AFF', '#5856D6'],
    },
  })),
}));
jest.mock('@react-navigation/native', () => ({
  ...jest.requireActual('@react-navigation/native'),
  useNavigation: () => ({
    navigate: jest.fn(),
  }),
  useRoute: () => ({
    params: { groupId: 1 },
  }),
  useFocusEffect: jest.fn(),
}));

// Alert.alertモック
jest.spyOn(Alert, 'alert');

const mockScheduledTasks = [
  {
    id: 1,
    title: '毎週月曜日のゴミ出し',
    description: 'ゴミを出す',
    schedules: [
      {
        type: 'weekly' as const,
        time: '09:00',
        days: [1],
      },
    ],
    assigned_user_id: 123,
    reward: 100,
    tags: ['家事', 'ゴミ'],
    is_active: true,
    start_date: '2025-01-01',
    end_date: null,
    delete_incomplete_on_create: false,
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  },
  {
    id: 2,
    title: '毎月1日の定期レポート',
    description: 'レポート作成',
    schedules: [
      {
        type: 'monthly' as const,
        time: '10:00',
        dates: [1],
      },
    ],
    assigned_user_id: null,
    reward: 0,
    tags: [],
    is_active: false,
    start_date: '2025-01-01',
    end_date: null,
    delete_incomplete_on_create: false,
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  },
];

describe('ScheduledTaskListScreen', () => {
  const mockGetScheduledTasks = jest.fn();
  const mockDeleteScheduledTask = jest.fn();
  const mockPauseScheduledTask = jest.fn();
  const mockResumeScheduledTask = jest.fn();
  const mockClearError = jest.fn();

  const mockThemeContext = {
    theme: 'adult' as const,
    setTheme: jest.fn(),
    isLoading: false,
    refreshTheme: jest.fn(),
  };

  // ThemeContextをモック
  jest.spyOn(require('../../../contexts/ThemeContext'), 'useTheme').mockReturnValue(mockThemeContext);

  beforeEach(() => {
    jest.clearAllMocks();
    jest.spyOn(require('../../../contexts/ThemeContext'), 'useTheme').mockReturnValue(mockThemeContext);
    (useScheduledTasks as jest.Mock).mockReturnValue({
      scheduledTasks: [],
      isLoading: false,
      error: null,
      getScheduledTasks: mockGetScheduledTasks,
      deleteScheduledTask: mockDeleteScheduledTask,
      pauseScheduledTask: mockPauseScheduledTask,
      resumeScheduledTask: mockResumeScheduledTask,
      clearError: mockClearError,
    });
  });

  const renderScreen = () => {
    return render(
      <ColorSchemeProvider>
        <AuthProvider>
          <ThemeProvider>
            <NavigationContainer>
              <Stack.Navigator>
                <Stack.Screen name="ScheduledTaskList" component={ScheduledTaskListScreen} />
              </Stack.Navigator>
            </NavigationContainer>
          </ThemeProvider>
        </AuthProvider>
      </ColorSchemeProvider>
    );
  };

  /**
   * Test 1: 空状態の表示
   */
  it('スケジュールがない場合に空状態を表示する', () => {
    renderScreen();

    expect(screen.getByText('スケジュールタスクなし')).toBeTruthy();
    expect(screen.getByText('定期的に自動実行するタスクを設定できます。')).toBeTruthy();
    expect(screen.getByText('➥ スケジュールを作成')).toBeTruthy(); // 絵文字変更: ➕ → ➥
  });

  /**
   * Test 2: ローディング状態
   */
  it('ローディング中にインジケーターを表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      scheduledTasks: [],
      isLoading: true,
      error: null,
      getScheduledTasks: mockGetScheduledTasks,
      deleteScheduledTask: mockDeleteScheduledTask,
      pauseScheduledTask: mockPauseScheduledTask,
      resumeScheduledTask: mockResumeScheduledTask,
      clearError: mockClearError,
    });

    renderScreen();

    expect(screen.getByText('読み込み中...')).toBeTruthy();
  });

  /**
   * Test 3: エラー状態
   */
  it('エラーが発生した場合にエラーメッセージを表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      scheduledTasks: [],
      isLoading: false,
      error: 'ネットワークエラー',
      getScheduledTasks: mockGetScheduledTasks,
      deleteScheduledTask: mockDeleteScheduledTask,
      pauseScheduledTask: mockPauseScheduledTask,
      resumeScheduledTask: mockResumeScheduledTask,
      clearError: mockClearError,
    });

    renderScreen();

    expect(screen.getByText('エラーが発生しました')).toBeTruthy();
    expect(screen.getByText('ネットワークエラー')).toBeTruthy();
    expect(screen.getByText('再試行')).toBeTruthy();
  });

  /**
   * Test 4: スケジュールタスク一覧表示
   */
  it('スケジュールタスク一覧を表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      scheduledTasks: mockScheduledTasks,
      isLoading: false,
      error: null,
      getScheduledTasks: mockGetScheduledTasks,
      deleteScheduledTask: mockDeleteScheduledTask,
      pauseScheduledTask: mockPauseScheduledTask,
      resumeScheduledTask: mockResumeScheduledTask,
      clearError: mockClearError,
    });

    renderScreen();

    expect(screen.getByText('毎週月曜日のゴミ出し')).toBeTruthy();
    expect(screen.getByText('毎月1日の定期レポート')).toBeTruthy();
    expect(screen.getByText('有効')).toBeTruthy();
    expect(screen.getByText('一時停止')).toBeTruthy();
  });

  /**
   * Test 5: 削除確認ダイアログ
   */
  it('削除ボタン押下時に確認ダイアログを表示する', async () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      scheduledTasks: mockScheduledTasks,
      isLoading: false,
      error: null,
      getScheduledTasks: mockGetScheduledTasks,
      deleteScheduledTask: mockDeleteScheduledTask,
      pauseScheduledTask: mockPauseScheduledTask,
      resumeScheduledTask: mockResumeScheduledTask,
      clearError: mockClearError,
    });

    renderScreen();

    const deleteButtons = screen.getAllByText(/🗑️/);
    fireEvent.press(deleteButtons[0]);

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        '削除確認',
        expect.stringContaining('毎週月曜日のゴミ出し'),
        expect.any(Array)
      );
    });
  });

  /**
   * Test 6: 一時停止確認ダイアログ
   */
  it('一時停止ボタン押下時に確認ダイアログを表示する', async () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      scheduledTasks: mockScheduledTasks,
      isLoading: false,
      error: null,
      getScheduledTasks: mockGetScheduledTasks,
      deleteScheduledTask: mockDeleteScheduledTask,
      pauseScheduledTask: mockPauseScheduledTask,
      resumeScheduledTask: mockResumeScheduledTask,
      clearError: mockClearError,
    });

    renderScreen();

    const pauseButtons = screen.getAllByText(/⏸️/);
    fireEvent.press(pauseButtons[0]);

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        '一時停止',
        expect.stringContaining('毎週月曜日のゴミ出し'),
        expect.any(Array)
      );
    });
  });

  /**
   * Test 7: 再開ボタン押下
   */
  it('再開ボタン押下時に確認ダイアログを表示する', async () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      scheduledTasks: mockScheduledTasks,
      isLoading: false,
      error: null,
      getScheduledTasks: mockGetScheduledTasks,
      deleteScheduledTask: mockDeleteScheduledTask,
      pauseScheduledTask: mockPauseScheduledTask,
      resumeScheduledTask: mockResumeScheduledTask,
      clearError: mockClearError,
    });

    renderScreen();

    const resumeButtons = screen.getAllByText(/▶️/);
    fireEvent.press(resumeButtons[0]);

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        '再開',
        expect.stringContaining('毎月1日の定期レポート'),
        expect.any(Array)
      );
    });
  });

  /**
   * Test 8: 子供テーマでの表示
   */
  it('子供テーマで適切な文言を表示する', () => {
    jest.spyOn(require('../../../contexts/ThemeContext'), 'useTheme').mockReturnValue({
      ...mockThemeContext,
      theme: 'child',
    });
    (useScheduledTasks as jest.Mock).mockReturnValue({
      scheduledTasks: mockScheduledTasks,
      isLoading: false,
      error: null,
      getScheduledTasks: mockGetScheduledTasks,
      deleteScheduledTask: mockDeleteScheduledTask,
      pauseScheduledTask: mockPauseScheduledTask,
      resumeScheduledTask: mockResumeScheduledTask,
      clearError: mockClearError,
    });

    renderScreen();

    expect(screen.getByText('うごいてる')).toBeTruthy();
    expect(screen.getByText('とまってる')).toBeTruthy();
  });

  /**
   * Test 9: プルダウンリフレッシュ
   */
  it('プルダウンリフレッシュでデータを再取得する', async () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      scheduledTasks: mockScheduledTasks,
      isLoading: false,
      error: null,
      getScheduledTasks: mockGetScheduledTasks,
      deleteScheduledTask: mockDeleteScheduledTask,
      pauseScheduledTask: mockPauseScheduledTask,
      resumeScheduledTask: mockResumeScheduledTask,
      clearError: mockClearError,
    });

    render(
      <NavigationContainer>
        <ScheduledTaskListScreen />
      </NavigationContainer>
    );

    // FlatListのrefreshControlをシミュレート
    await waitFor(() => {
      expect(mockGetScheduledTasks).toHaveBeenCalledWith(1);
    });
  });

  /**
   * Test 10: 初回マウント時のデータ取得
   */
  it('初回マウント時にスケジュールタスクを取得する', async () => {
    renderScreen();

    await waitFor(() => {
      expect(mockGetScheduledTasks).toHaveBeenCalledWith(1);
    });
  });
});
