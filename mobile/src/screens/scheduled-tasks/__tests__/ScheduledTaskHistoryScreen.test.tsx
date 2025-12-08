/**
 * ScheduledTaskHistoryScreen のテスト
 * 
 * スケジュールタスク実行履歴画面のUIテスト
 */
import { render, screen, waitFor } from '@testing-library/react-native';
import { NavigationContainer } from '@react-navigation/native';
import ScheduledTaskHistoryScreen from '../ScheduledTaskHistoryScreen';
import { useScheduledTasks } from '../../../hooks/useScheduledTasks';
import { useTheme } from '../../../contexts/ThemeContext';

// モック
jest.mock('../../../hooks/useScheduledTasks');
jest.mock('../../../contexts/ThemeContext');
jest.mock('@react-navigation/native', () => ({
  ...jest.requireActual('@react-navigation/native'),
  useNavigation: () => ({
    navigate: jest.fn(),
  }),
  useRoute: () => ({
    params: { scheduledTaskId: 1, title: '毎週月曜日のゴミ出し' },
  }),
}));

const mockExecutionHistory = [
  {
    id: 1,
    scheduled_task_id: 1,
    executed_at: '2025-01-13T09:00:00Z',
    status: 'success' as const,
    created_task_id: 101,
    deleted_task_id: null,
    note: null,
    error_message: null,
    created_at: '2025-01-13T09:00:00Z',
    updated_at: '2025-01-13T09:00:00Z',
  },
  {
    id: 2,
    scheduled_task_id: 1,
    executed_at: '2025-01-06T09:00:00Z',
    status: 'failed' as const,
    created_task_id: null,
    deleted_task_id: null,
    note: null,
    error_message: 'ネットワークエラー',
    created_at: '2025-01-06T09:00:00Z',
    updated_at: '2025-01-06T09:00:00Z',
  },
  {
    id: 3,
    scheduled_task_id: 1,
    executed_at: '2024-12-30T09:00:00Z',
    status: 'skipped' as const,
    created_task_id: null,
    deleted_task_id: null,
    note: '祝日のためスキップ',
    error_message: null,
    created_at: '2024-12-30T09:00:00Z',
    updated_at: '2024-12-30T09:00:00Z',
  },
];

describe('ScheduledTaskHistoryScreen', () => {
  const mockGetExecutionHistory = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    (useTheme as jest.Mock).mockReturnValue({ theme: 'parent' });
    (useScheduledTasks as jest.Mock).mockReturnValue({
      executionHistory: [],
      isLoading: false,
      error: null,
      getExecutionHistory: mockGetExecutionHistory,
    });
  });

  const renderScreen = () => {
    return render(
      <NavigationContainer>
        <ScheduledTaskHistoryScreen />
      </NavigationContainer>
    );
  };

  /**
   * Test 1: 空状態の表示
   */
  it('実行履歴がない場合に空状態を表示する', () => {
    renderScreen();

    expect(screen.getByText('実行履歴なし')).toBeTruthy();
    expect(screen.getByText('このスケジュールはまだ実行されていません。')).toBeTruthy();
  });

  /**
   * Test 2: ローディング状態
   */
  it('ローディング中にインジケーターを表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      executionHistory: [],
      isLoading: true,
      error: null,
      getExecutionHistory: mockGetExecutionHistory,
    });

    renderScreen();

    expect(screen.getByText('読み込み中...')).toBeTruthy();
  });

  /**
   * Test 3: エラー状態
   */
  it('エラーが発生した場合にエラーメッセージを表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      executionHistory: [],
      isLoading: false,
      error: 'データ取得エラー',
      getExecutionHistory: mockGetExecutionHistory,
    });

    renderScreen();

    expect(screen.getByText('エラーが発生しました')).toBeTruthy();
    expect(screen.getByText('データ取得エラー')).toBeTruthy();
    expect(screen.getByText('再試行')).toBeTruthy();
  });

  /**
   * Test 4: 実行履歴一覧表示
   */
  it('実行履歴一覧を表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      executionHistory: mockExecutionHistory,
      isLoading: false,
      error: null,
      getExecutionHistory: mockGetExecutionHistory,
    });

    renderScreen();

    expect(screen.getByText('実行履歴')).toBeTruthy();
    expect(screen.getByText('毎週月曜日のゴミ出し')).toBeTruthy();
    expect(screen.getByText('全 3 件')).toBeTruthy();
  });

  /**
   * Test 5: 成功ステータス表示
   */
  it('成功ステータスを正しく表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      executionHistory: mockExecutionHistory,
      isLoading: false,
      error: null,
      getExecutionHistory: mockGetExecutionHistory,
    });

    renderScreen();

    expect(screen.getByText('✅')).toBeTruthy();
    expect(screen.getByText('成功')).toBeTruthy();
    expect(screen.getByText(/タスクID: 101/)).toBeTruthy();
  });

  /**
   * Test 6: 失敗ステータス表示
   */
  it('失敗ステータスとエラーメッセージを表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      executionHistory: mockExecutionHistory,
      isLoading: false,
      error: null,
      getExecutionHistory: mockGetExecutionHistory,
    });

    renderScreen();

    expect(screen.getByText('❌')).toBeTruthy();
    expect(screen.getByText('失敗')).toBeTruthy();
    expect(screen.getByText('ネットワークエラー')).toBeTruthy();
  });

  /**
   * Test 7: スキップステータス表示
   */
  it('スキップステータスと備考を表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      executionHistory: mockExecutionHistory,
      isLoading: false,
      error: null,
      getExecutionHistory: mockGetExecutionHistory,
    });

    renderScreen();

    expect(screen.getByText('⏭️')).toBeTruthy();
    expect(screen.getByText('スキップ')).toBeTruthy();
    expect(screen.getByText('祝日のためスキップ')).toBeTruthy();
  });

  /**
   * Test 8: 子供テーマでの表示
   */
  it('子供テーマで適切な文言を表示する', () => {
    (useTheme as jest.Mock).mockReturnValue({ theme: 'child' });
    (useScheduledTasks as jest.Mock).mockReturnValue({
      executionHistory: mockExecutionHistory,
      isLoading: false,
      error: null,
      getExecutionHistory: mockGetExecutionHistory,
    });

    renderScreen();

    expect(screen.getByText('じっこうきろく')).toBeTruthy();
    expect(screen.getByText('せいこう')).toBeTruthy();
    expect(screen.getByText('しっぱい')).toBeTruthy();
  });

  /**
   * Test 9: 初回マウント時のデータ取得
   */
  it('初回マウント時に実行履歴を取得する', async () => {
    renderScreen();

    await waitFor(() => {
      expect(mockGetExecutionHistory).toHaveBeenCalledWith(1);
    });
  });

  /**
   * Test 10: 備考の表示
   */
  it('備考を表示する', () => {
    (useScheduledTasks as jest.Mock).mockReturnValue({
      executionHistory: mockExecutionHistory,
      isLoading: false,
      error: null,
      getExecutionHistory: mockGetExecutionHistory,
    });

    renderScreen();

    // スキップ理由が備考として表示されることを確認
    expect(screen.getByText('祝日のためスキップ')).toBeTruthy();
  });
});
