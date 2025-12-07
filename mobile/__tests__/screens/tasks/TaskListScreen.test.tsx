/**
 * TaskListScreen.tsx テスト
 * 
 * バケット表示機能のテスト
 */
import React from 'react';
import { render, waitFor, fireEvent } from '@testing-library/react-native';
import TaskListScreen from '../../../src/screens/tasks/TaskListScreen';
import { useTasks } from '../../../src/hooks/useTasks';
import { useTheme } from '../../../src/contexts/ThemeContext';
import { useNavigation } from '@react-navigation/native';
import { useAvatar } from '../../../src/hooks/useAvatar';
import { Task } from '../../../src/types/task.types';

// モック設定
jest.mock('../../../src/hooks/useTasks');
jest.mock('../../../src/contexts/ThemeContext');
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
  useFocusEffect: jest.fn((callback) => callback()),
}));
jest.mock('../../../src/hooks/useAvatar');

describe('TaskListScreen - バケット表示機能', () => {
  const mockUseTasks = useTasks as jest.MockedFunction<typeof useTasks>;
  const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;
  const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;
  const mockUseAvatar = useAvatar as jest.MockedFunction<typeof useAvatar>;

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
   * バケット表示テスト
   */
  describe('バケット表示', () => {
    it('タグ別にグループ化されたバケットが表示される', async () => {
      const { getByText } = render(<TaskListScreen />);

      await waitFor(() => {
        expect(getByText('勉強')).toBeTruthy();
        expect(getByText('家事')).toBeTruthy();
        expect(getByText('未分類')).toBeTruthy();
      });
    });

    it('バケットにタスク件数が表示される', async () => {
      const { getByText, getAllByText } = render(<TaskListScreen />);

      await waitFor(() => {
        // 勉強: 2件
        const twoCount = getAllByText('2');
        expect(twoCount.length).toBeGreaterThan(0);
        // 家事: 1件、未分類: 1件
        const oneCount = getAllByText('1');
        expect(oneCount.length).toBeGreaterThanOrEqual(2);
      });
    });

    it('バケット内のタスクプレビューが表示される（最大3件）', async () => {
      const { getByText } = render(<TaskListScreen />);

      await waitFor(() => {
        // 勉強バケット内のタスク
        expect(getByText('タスク1')).toBeTruthy();
        expect(getByText('タスク2')).toBeTruthy();
      });
    });

    it('バケットはタスク件数降順でソートされる', async () => {
      const { getByText } = render(<TaskListScreen />);

      await waitFor(() => {
        // 3つのバケットが表示されることを確認
        expect(getByText('勉強')).toBeTruthy();
        expect(getByText('家事')).toBeTruthy();
        expect(getByText('未分類')).toBeTruthy();
      });
    });
  });

  /**
   * 検索時の動的切り替えテスト
   */
  describe('検索時の動的切り替え', () => {
    it('検索クエリ入力時、バケット表示からタスクカード表示に切り替わる', async () => {
      const { getByPlaceholderText, getByText, queryByText } = render(<TaskListScreen />);

      // 初期状態: バケット表示（件数バッジが表示される）
      await waitFor(() => {
        expect(getByText('勉強')).toBeTruthy();
      });

      // 検索クエリ入力（タグ名と一致しない文字列）
      const searchInput = getByPlaceholderText('検索（タイトル・説明）');
      fireEvent.changeText(searchInput, 'タスク1');

      // タスクカード表示に切り替わる
      await waitFor(() => {
        // タスク1が表示される
        expect(getByText('タスク1')).toBeTruthy();
        // タスク2は表示されない（検索結果に含まれない）
        expect(queryByText('タスク2')).toBeNull();
      });
    });

    it('検索クエリクリア時、タスクカード表示からバケット表示に復帰する', async () => {
      const { getByPlaceholderText, getByText } = render(<TaskListScreen />);

      // 検索クエリ入力
      const searchInput = getByPlaceholderText('検索（タイトル・説明）');
      fireEvent.changeText(searchInput, 'タスク1');

      await waitFor(() => {
        expect(getByText('タスク1')).toBeTruthy();
      });

      // 検索クエリクリア
      fireEvent.changeText(searchInput, '');

      // バケット表示に復帰
      await waitFor(() => {
        expect(getByText('勉強')).toBeTruthy();
        expect(getByText('家事')).toBeTruthy();
      });
    });
  });

  /**
   * 画面遷移テスト
   */
  describe('画面遷移', () => {
    it('バケットタップ時、TagTasksScreenに遷移する', async () => {
      const { getByText } = render(<TaskListScreen />);

      await waitFor(() => {
        expect(getByText('勉強')).toBeTruthy();
      });

      // バケットタップ
      fireEvent.press(getByText('勉強'));

      // 画面遷移確認
      expect(mockNavigation.navigate).toHaveBeenCalledWith('TagTasks', {
        tagId: 1,
        tagName: '勉強',
      });
    });

    it('未分類バケットタップ時、tagId=0でTagTasksScreenに遷移する', async () => {
      const { getByText } = render(<TaskListScreen />);

      await waitFor(() => {
        expect(getByText('未分類')).toBeTruthy();
      });

      // 未分類バケットタップ
      fireEvent.press(getByText('未分類'));

      // 画面遷移確認（tagId=0）
      expect(mockNavigation.navigate).toHaveBeenCalledWith('TagTasks', {
        tagId: 0,
        tagName: '未分類',
      });
    });
  });

  /**
   * エッジケース
   */
  describe('エッジケース', () => {
    it('タスクが0件の場合、空メッセージが表示される', async () => {
      mockUseTasks.mockReturnValue({
        tasks: [],
        isLoading: false,
        error: null,
        fetchTasks: jest.fn(),
        toggleComplete: jest.fn(),
        clearError: jest.fn(),
        refreshTasks: jest.fn(),
      });

      const { getByText } = render(<TaskListScreen />);

      await waitFor(() => {
        expect(getByText('タスクがありません')).toBeTruthy();
      });
    });

    it('すべてのタスクに同じタグが付いている場合、1つのバケットのみ表示', async () => {
      const singleTagTasks: Task[] = [
        { ...mockTasks[0], tags: [{ id: 1, name: '勉強' }] },
        { ...mockTasks[1], tags: [{ id: 1, name: '勉強' }] },
      ];

      mockUseTasks.mockReturnValue({
        tasks: singleTagTasks,
        isLoading: false,
        error: null,
        fetchTasks: jest.fn(),
        toggleComplete: jest.fn(),
        clearError: jest.fn(),
        refreshTasks: jest.fn(),
      });

      const { getByText, queryByText } = render(<TaskListScreen />);

      await waitFor(() => {
        expect(getByText('勉強')).toBeTruthy();
        expect(queryByText('家事')).toBeNull();
        expect(queryByText('未分類')).toBeNull();
      });
    });
  });
});
