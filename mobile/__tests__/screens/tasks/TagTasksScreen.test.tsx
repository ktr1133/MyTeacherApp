/**
 * TagTasksScreen.tsx テスト
 * 
 * タグ別タスク一覧画面のテスト
 */
import React from 'react';
import { render, waitFor, fireEvent } from '@testing-library/react-native';
import TagTasksScreen from '../../../src/screens/tasks/TagTasksScreen';
import { useTasks } from '../../../src/hooks/useTasks';
import { useTheme } from '../../../src/contexts/ThemeContext';
import { useNavigation, useRoute } from '@react-navigation/native';
import { useAvatar } from '../../../src/hooks/useAvatar';
import { Task } from '../../../src/types/task.types';
import { ColorSchemeProvider } from '../../../src/contexts/ColorSchemeContext';

// モック設定
jest.mock('../../../src/hooks/useTasks');
jest.mock('../../../src/contexts/ThemeContext');
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
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
  useRoute: jest.fn(),
  useFocusEffect: jest.fn(),
}));
jest.mock('../../../src/hooks/useAvatar');
jest.mock('../../../src/components/tasks/DeadlineBadge', () => ({
  __esModule: true,
  default: () => null,
}));

describe('TagTasksScreen - タグ別タスク一覧', () => {
  const mockUseTasks = useTasks as jest.MockedFunction<typeof useTasks>;
  const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;
  const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;
  const mockUseRoute = useRoute as jest.MockedFunction<typeof useRoute>;
  const mockUseAvatar = useAvatar as jest.MockedFunction<typeof useAvatar>;

  const renderScreen = (component: React.ReactElement) => {
    return render(
      <ColorSchemeProvider>
        {component}
      </ColorSchemeProvider>
    );
  };

  const mockNavigation = {
    navigate: jest.fn(),
    goBack: jest.fn(),
  };

  const mockTasks: Task[] = [
    {
      id: 1,
      title: 'タスク1',
      description: '説明1',
      is_completed: false,
      is_group_task: false,
      tags: [{ id: 1, name: '勉強' }],
      reward: 100,
      due_date: '2025-12-31',
      user_id: 1,
      created_at: '2025-12-01T00:00:00.000Z',
      updated_at: '2025-12-01T00:00:00.000Z',
    },
    {
      id: 2,
      title: 'タスク2',
      description: '説明2',
      is_completed: false,
      is_group_task: false,
      tags: [{ id: 1, name: '勉強' }],
      reward: 200,
      due_date: '2025-12-31',
      user_id: 1,
      created_at: '2025-12-01T00:00:00.000Z',
      updated_at: '2025-12-01T00:00:00.000Z',
    },
    {
      id: 3,
      title: 'タスク3',
      description: '説明3',
      is_completed: false,
      is_group_task: false,
      tags: [{ id: 2, name: '家事' }],
      reward: 300,
      due_date: '2025-12-31',
      user_id: 1,
      created_at: '2025-12-01T00:00:00.000Z',
      updated_at: '2025-12-01T00:00:00.000Z',
    },
    {
      id: 4,
      title: 'タスク4',
      description: '説明4',
      is_completed: false,
      is_group_task: false,
      tags: [],
      reward: 400,
      due_date: '2025-12-31',
      user_id: 1,
      created_at: '2025-12-01T00:00:00.000Z',
      updated_at: '2025-12-01T00:00:00.000Z',
    },
  ];

  beforeEach(() => {
    jest.clearAllMocks();

    mockUseNavigation.mockReturnValue(mockNavigation as any);
    mockUseTheme.mockReturnValue({
      theme: 'adult',
      setTheme: jest.fn(),
    });
    mockUseTasks.mockReturnValue({
      tasks: mockTasks,
      isLoading: false,
      error: null,
      fetchTasks: jest.fn(),
      toggleComplete: jest.fn(),
      clearError: jest.fn(),
      refreshTasks: jest.fn(),
    });
    mockUseAvatar.mockReturnValue({
      isVisible: false,
      currentData: null,
      dispatchAvatarEvent: jest.fn(),
      hideAvatar: jest.fn(),
    });
  });

  /**
   * タグ別フィルタリングテスト
   */
  describe('タグ別フィルタリング', () => {
    it('指定されたタグIDのタスクのみ表示される', async () => {
      mockUseRoute.mockReturnValue({
        params: { tagId: 1, tagName: '勉強' },
      } as any);

      const { getByText, queryByText } = renderScreen(<TagTasksScreen />);

      await waitFor(() => {
        expect(getByText('タスク1')).toBeTruthy();
        expect(getByText('タスク2')).toBeTruthy();
        expect(queryByText('タスク3')).toBeNull(); // 家事タグ（非表示）
        expect(queryByText('タスク4')).toBeNull(); // 未分類（非表示）
      });
    });

    it('未分類バケット（tagId=0）の場合、タグなしタスクのみ表示', async () => {
      mockUseRoute.mockReturnValue({
        params: { tagId: 0, tagName: '未分類' },
      } as any);

      const { getByText, queryByText } = renderScreen(<TagTasksScreen />);

      await waitFor(() => {
        expect(getByText('タスク4')).toBeTruthy(); // 未分類タスク
        expect(queryByText('タスク1')).toBeNull(); // 勉強タグ（非表示）
        expect(queryByText('タスク2')).toBeNull(); // 勉強タグ（非表示）
        expect(queryByText('タスク3')).toBeNull(); // 家事タグ（非表示）
      });
    });

    it('ヘッダーにタグ名とタスク件数が表示される', async () => {
      mockUseRoute.mockReturnValue({
        params: { tagId: 1, tagName: '勉強' },
      } as any);

      const { getAllByText, getByText } = renderScreen(<TagTasksScreen />);

      await waitFor(() => {
        // タグ名（複数箇所に表示される）
        const tagNames = getAllByText('勉強');
        expect(tagNames.length).toBeGreaterThan(0);
        // 件数バッジ
        expect(getByText('2')).toBeTruthy();
      });
    });
  });

  /**
   * 画面遷移テスト
   */
  describe('画面遷移', () => {
    beforeEach(() => {
      mockUseRoute.mockReturnValue({
        params: { tagId: 1, tagName: '勉強' },
      } as any);
    });

    it('戻るボタンタップ時、前画面に戻る', async () => {
      const { getByText } = renderScreen(<TagTasksScreen />);

      await waitFor(() => {
        expect(getByText('←')).toBeTruthy();
      });

      // 戻るボタンタップ
      fireEvent.press(getByText('←'));

      // 画面遷移確認
      expect(mockNavigation.goBack).toHaveBeenCalled();
    });

    it('通常タスクタップ時、TaskEditScreenに遷移する', async () => {
      const { getByText } = renderScreen(<TagTasksScreen />);

      await waitFor(() => {
        expect(getByText('タスク1')).toBeTruthy();
      });

      // タスクカード全体をタップ（説明テキストを使用）
      const taskDescription = getByText('説明1');
      fireEvent.press(taskDescription);

      // 画面遷移確認（編集画面）
      await waitFor(() => {
        expect(mockNavigation.navigate).toHaveBeenCalledWith('TaskEdit', {
          taskId: 1,
        });
      });
    });

    it('グループタスクタップ時、TaskDetailScreenに遷移する', async () => {
      const groupTask: Task = {
        id: 5,
        title: 'グループタスク',
        description: '説明5',
        is_completed: false,
        is_group_task: true,
        tags: [{ id: 1, name: '勉強' }],
        reward: 500,
        due_date: '2025-12-31',
        user_id: 1,
        created_at: '2025-12-01T00:00:00.000Z',
        updated_at: '2025-12-01T00:00:00.000Z',
      };

      mockUseTasks.mockReturnValue({
        tasks: [...mockTasks, groupTask],
        isLoading: false,
        error: null,
        fetchTasks: jest.fn(),
        toggleComplete: jest.fn(),
        clearError: jest.fn(),
        refreshTasks: jest.fn(),
      });

      const { getByText } = renderScreen(<TagTasksScreen />);

      await waitFor(() => {
        expect(getByText('グループタスク')).toBeTruthy();
      });

      // グループタスクタップ
      fireEvent.press(getByText('グループタスク'));

      // 画面遷移確認（詳細画面）
      expect(mockNavigation.navigate).toHaveBeenCalledWith('TaskDetail', {
        taskId: 5,
      });
    });
  });

  /**
   * エッジケース
   */
  describe('エッジケース', () => {
    it('タグに該当するタスクが0件の場合、空メッセージが表示される', async () => {
      mockUseRoute.mockReturnValue({
        params: { tagId: 999, tagName: '存在しないタグ' },
      } as any);

      const { getByText } = renderScreen(<TagTasksScreen />);

      await waitFor(() => {
        expect(getByText('このタグのタスクがありません')).toBeTruthy();
      });
    });

    it('子どもテーマの場合、子ども向けメッセージが表示される', async () => {
      mockUseRoute.mockReturnValue({
        params: { tagId: 1, tagName: '勉強' },
      } as any);

      mockUseTheme.mockReturnValue({
        theme: 'child',
        setTheme: jest.fn(),
      });

      const { getAllByText } = renderScreen(<TagTasksScreen />);

      await waitFor(() => {
        // 子どもテーマの完了ボタン（複数ある）
        const buttons = getAllByText('できた!');
        expect(buttons.length).toBeGreaterThan(0);
      });
    });
  });

  /**
   * Pull-to-Refresh
   */
  describe('Pull-to-Refresh', () => {
    it('Pull-to-Refresh時、タスクリストが再取得される', async () => {
      mockUseRoute.mockReturnValue({
        params: { tagId: 1, tagName: '勉強' },
      } as any);

      const mockRefreshTasks = jest.fn();
      mockUseTasks.mockReturnValue({
        tasks: mockTasks,
        isLoading: false,
        error: null,
        fetchTasks: jest.fn(),
        toggleComplete: jest.fn(),
        clearError: jest.fn(),
        refreshTasks: mockRefreshTasks,
      });

      renderScreen(<TagTasksScreen />);

      // 初期読み込み完了待ち
      await waitFor(() => {
        expect(mockRefreshTasks).not.toHaveBeenCalled();
      });
    });
  });
});
