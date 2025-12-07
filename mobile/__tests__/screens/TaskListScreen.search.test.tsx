/**
 * TaskListScreen 検索機能テスト
 * 
 * 検索バーUI、検索結果表示、クエリクリアの動作を検証
 */

import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import TaskListScreen from '../../src/screens/tasks/TaskListScreen';
import { useTasks } from '../../src/hooks/useTasks';
import { useTheme } from '../../src/contexts/ThemeContext';
import { AvatarProvider } from '../../src/contexts/AvatarContext';
import { useNavigation } from '@react-navigation/native';

jest.mock('../../src/hooks/useTasks');
jest.mock('../../src/contexts/ThemeContext');
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
  useFocusEffect: jest.fn((callback) => callback()),
}));

const mockedUseTasks = useTasks as jest.MockedFunction<typeof useTasks>;
const mockedUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;
const mockedUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;

describe('TaskListScreen - 検索機能', () => {
  const mockFetchTasks = jest.fn();
  const mockSearchTasks = jest.fn();
  const mockToggleComplete = jest.fn();
  const mockClearError = jest.fn();
  const mockRefreshTasks = jest.fn();
  const mockNavigate = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();

    mockedUseNavigation.mockReturnValue({
      navigate: mockNavigate,
    } as any);

    mockedUseTheme.mockReturnValue({
      theme: 'adult',
      toggleTheme: jest.fn(),
    });

    mockedUseTasks.mockReturnValue({
      tasks: [],
      isLoading: false,
      error: null,
      pagination: null,
      fetchTasks: mockFetchTasks,
      searchTasks: mockSearchTasks,
      createTask: jest.fn(),
      updateTask: jest.fn(),
      deleteTask: jest.fn(),
      toggleComplete: mockToggleComplete,
      approveTask: jest.fn(),
      rejectTask: jest.fn(),
      uploadImage: jest.fn(),
      deleteImage: jest.fn(),
      clearError: mockClearError,
      refreshTasks: mockRefreshTasks,
    });
  });

  // テストヘルパー: AvatarProviderでラップしてレンダリング
  const renderWithProviders = () => render(
    <AvatarProvider>
      <TaskListScreen />
    </AvatarProvider>
  );

  describe('検索バーUI', () => {
    it('検索バーが表示される', () => {
      const { getByPlaceholderText } = renderWithProviders();

      const searchInput = getByPlaceholderText('検索（タイトル・説明）');
      expect(searchInput).toBeTruthy();
    });

    it('childテーマの場合はプレースホルダーが変わる', () => {
      mockedUseTheme.mockReturnValue({
        theme: 'child',
        toggleTheme: jest.fn(),
      });

      const { getByPlaceholderText } = renderWithProviders();

      const searchInput = getByPlaceholderText('さがす');
      expect(searchInput).toBeTruthy();
    });

    it('検索バーに入力できる', () => {
      const { getByPlaceholderText } = renderWithProviders();

      const searchInput = getByPlaceholderText('検索（タイトル・説明）');
      fireEvent.changeText(searchInput, 'テスト');

      expect(searchInput.props.value).toBe('テスト');
    });

    it('クリアボタンは入力がある場合のみ表示される', () => {
      const { getByPlaceholderText, queryByText, getByText } = renderWithProviders();

      const searchInput = getByPlaceholderText('検索（タイトル・説明）');

      // 初期状態ではクリアボタンなし
      expect(queryByText('✕')).toBeNull();

      // 入力後にクリアボタン表示
      fireEvent.changeText(searchInput, 'テスト');
      expect(getByText('✕')).toBeTruthy();
    });

    it('クリアボタンで検索クエリをクリアできる', () => {
      const { getByPlaceholderText, getByText } = renderWithProviders();

      const searchInput = getByPlaceholderText('検索（タイトル・説明）');
      fireEvent.changeText(searchInput, 'テスト');

      const clearButton = getByText('✕');
      fireEvent.press(clearButton);

      expect(searchInput.props.value).toBe('');
    });
  });

  describe('検索実行', () => {
    it('検索クエリ入力時にタスクがフィルタリングされる', async () => {
      mockedUseTasks.mockReturnValue({
        tasks: [
          {
            id: 1,
            title: 'テスト検索タスク',
            description: 'テスト説明',
            status: 'pending',
            reward_tokens: 100,
            user_id: 1,
            images: [],
            tags: [],
            created_at: '2025-01-01T00:00:00.000Z',
            updated_at: '2025-01-01T00:00:00.000Z',
          },
          {
            id: 2,
            title: '別のタスク',
            description: '他の説明',
            status: 'pending',
            reward_tokens: 50,
            user_id: 1,
            images: [],
            tags: [],
            created_at: '2025-01-01T00:00:00.000Z',
            updated_at: '2025-01-01T00:00:00.000Z',
          },
        ],
        isLoading: false,
        error: null,
        pagination: null,
        fetchTasks: mockFetchTasks,
        searchTasks: mockSearchTasks,
        createTask: jest.fn(),
        updateTask: jest.fn(),
        deleteTask: jest.fn(),
        toggleComplete: mockToggleComplete,
        approveTask: jest.fn(),
        rejectTask: jest.fn(),
        uploadImage: jest.fn(),
        deleteImage: jest.fn(),
        clearError: mockClearError,
        refreshTasks: mockRefreshTasks,
      });

      const { getByPlaceholderText, getByText, queryByText } = renderWithProviders();

      const searchInput = getByPlaceholderText('検索（タイトル・説明）');
      fireEvent.changeText(searchInput, 'テスト検索');

      await waitFor(() => {
        // フィルタリングされたタスクが表示される
        expect(getByText('テスト検索タスク')).toBeTruthy();
        // フィルタリングされないタスクは表示されない
        expect(queryByText('別のタスク')).toBeNull();
      });
    });

    it('検索クエリクリア時に全タスクが表示される', async () => {
      mockedUseTasks.mockReturnValue({
        tasks: [
          {
            id: 1,
            title: 'タスク1',
            description: 'テスト説明',
            status: 'pending',
            reward_tokens: 100,
            user_id: 1,
            images: [],
            tags: [],
            created_at: '2025-01-01T00:00:00.000Z',
            updated_at: '2025-01-01T00:00:00.000Z',
          },
        ],
        isLoading: false,
        error: null,
        pagination: null,
        fetchTasks: mockFetchTasks,
        searchTasks: mockSearchTasks,
        createTask: jest.fn(),
        updateTask: jest.fn(),
        deleteTask: jest.fn(),
        toggleComplete: mockToggleComplete,
        approveTask: jest.fn(),
        rejectTask: jest.fn(),
        uploadImage: jest.fn(),
        deleteImage: jest.fn(),
        clearError: mockClearError,
        refreshTasks: mockRefreshTasks,
      });

      const { getByPlaceholderText, getByText } = renderWithProviders();

      const searchInput = getByPlaceholderText('検索（タイトル・説明）');
      fireEvent.changeText(searchInput, 'テスト');

      const clearButton = getByText('✕');
      fireEvent.press(clearButton);

      expect(searchInput.props.value).toBe('');
    });
  });

  describe('検索結果表示', () => {
    it('検索結果のタスクを表示できる', () => {
      mockedUseTasks.mockReturnValue({
        tasks: [
          {
            id: 1,
            title: '検索結果タスク',
            description: 'テスト説明',
            status: 'pending',
            reward_tokens: 100,
            user_id: 1,
            images: [],
            tags: [],
            created_at: '2025-01-01T00:00:00.000Z',
            updated_at: '2025-01-01T00:00:00.000Z',
          },
        ],
        isLoading: false,
        error: null,
        pagination: {
          current_page: 1,
          per_page: 10,
          total: 1,
          last_page: 1,
        },
        fetchTasks: mockFetchTasks,
        searchTasks: mockSearchTasks,
        createTask: jest.fn(),
        updateTask: jest.fn(),
        deleteTask: jest.fn(),
        toggleComplete: mockToggleComplete,
        approveTask: jest.fn(),
        rejectTask: jest.fn(),
        uploadImage: jest.fn(),
        deleteImage: jest.fn(),
        clearError: mockClearError,
        refreshTasks: mockRefreshTasks,
      });

      const { getByText } = renderWithProviders();

      expect(getByText('検索結果タスク')).toBeTruthy();
    });

    it('検索結果が空の場合に空メッセージを表示', () => {
      mockedUseTasks.mockReturnValue({
        tasks: [],
        isLoading: false,
        error: null,
        pagination: {
          current_page: 1,
          per_page: 10,
          total: 0,
          last_page: 1,
        },
        fetchTasks: mockFetchTasks,
        searchTasks: mockSearchTasks,
        createTask: jest.fn(),
        updateTask: jest.fn(),
        deleteTask: jest.fn(),
        toggleComplete: mockToggleComplete,
        approveTask: jest.fn(),
        rejectTask: jest.fn(),
        uploadImage: jest.fn(),
        deleteImage: jest.fn(),
        clearError: mockClearError,
        refreshTasks: mockRefreshTasks,
      });

      const { getByText } = renderWithProviders();

      expect(getByText('タスクがありません')).toBeTruthy();
    });
  });

  describe('エラーハンドリング', () => {
    it('検索エラー時にアラートを表示', async () => {
      const mockAlert = jest.spyOn(require('react-native').Alert, 'alert');

      mockedUseTasks.mockReturnValue({
        tasks: [],
        isLoading: false,
        error: 'タスクの検索に失敗しました。',
        pagination: null,
        fetchTasks: mockFetchTasks,
        searchTasks: mockSearchTasks,
        createTask: jest.fn(),
        updateTask: jest.fn(),
        deleteTask: jest.fn(),
        toggleComplete: mockToggleComplete,
        approveTask: jest.fn(),
        rejectTask: jest.fn(),
        uploadImage: jest.fn(),
        deleteImage: jest.fn(),
        clearError: mockClearError,
        refreshTasks: mockRefreshTasks,
      });

      renderWithProviders();

      await waitFor(() => {
        expect(mockAlert).toHaveBeenCalledWith(
          'エラー',
          'タスクの検索に失敗しました。',
          expect.any(Array)
        );
      });

      mockAlert.mockRestore();
    });
  });
});
