/**
 * useTasks フックのテスト
 */
import { renderHook, act } from '@testing-library/react-native';
import { useTasks } from '../useTasks';
import taskService from '../../services/task.service';

// taskServiceをモック
jest.mock('../../services/task.service');
jest.mock('react-native/Libraries/Alert/Alert', () => ({
  alert: jest.fn(),
}));

// ThemeContextをモック
jest.mock('../../contexts/ThemeContext', () => ({
  ThemeProvider: ({ children }: any) => children,
  useTheme: () => ({ theme: 'adult', isLoading: false, refreshTheme: jest.fn() }),
}));

describe('useTasks', () => {
  const mockTasks = [
    {
      id: 1,
      title: 'テストタスク1',
      description: '説明1',
      span: 1,
      due_date: '2025-12-31',
      priority: 3,
      status: 'pending',
      reward: 10,
      requires_approval: false,
      requires_image: false,
      is_group_task: false,
      group_task_id: null,
      assigned_by_user_id: null,
      tags: [],
      images: [],
      created_at: '2025-12-01T00:00:00Z',
      updated_at: '2025-12-01T00:00:00Z',
    },
    {
      id: 2,
      title: 'テストタスク2',
      description: '説明2',
      span: 2,
      due_date: '2026-01-15',
      priority: 2,
      status: 'completed',
      reward: 20,
      requires_approval: true,
      requires_image: false,
      is_group_task: false,
      group_task_id: null,
      assigned_by_user_id: null,
      tags: [],
      images: [],
      created_at: '2025-12-01T00:00:00Z',
      updated_at: '2025-12-01T00:00:00Z',
    },
  ];

  const mockPagination = {
    current_page: 1,
    per_page: 10,
    total: 2,
    last_page: 1,
    from: 1,
    to: 2,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('fetchTasks', () => {
    it('タスク一覧を取得できる', async () => {
      (taskService.getTasks as jest.Mock).mockResolvedValue({
        tasks: mockTasks,
        pagination: mockPagination,
      });

      const { result } = renderHook(() => useTasks());

      expect(result.current.tasks).toEqual([]);
      expect(result.current.isLoading).toBe(false);

      await act(async () => {
        await result.current.fetchTasks();
      });

      expect(taskService.getTasks).toHaveBeenCalledWith(undefined);
      expect(result.current.tasks).toEqual(mockTasks);
      expect(result.current.pagination).toEqual(mockPagination);
      expect(result.current.isLoading).toBe(false);
    });

    it('フィルター条件を指定してタスクを取得できる', async () => {
      (taskService.getTasks as jest.Mock).mockResolvedValue({
        tasks: [mockTasks[0]],
        pagination: { ...mockPagination, total: 1 },
      });

      const { result } = renderHook(() => useTasks());

      await act(async () => {
        await result.current.fetchTasks({ status: 'pending' });
      });

      expect(taskService.getTasks).toHaveBeenCalledWith({ status: 'pending' });
      expect(result.current.tasks).toHaveLength(1);
    });

    it('エラー時はエラーメッセージが設定される', async () => {
      const mockError = new Error('NETWORK_ERROR');
      (taskService.getTasks as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useTasks());

      await act(async () => {
        await result.current.fetchTasks();
      });

      expect(result.current.error).toBeTruthy();
      expect(result.current.tasks).toEqual([]);
    });
  });

  describe('createTask', () => {
    it('タスクを作成できる', async () => {
      const newTask = { ...mockTasks[0], id: 3, title: '新規タスク' };
      (taskService.createTask as jest.Mock).mockResolvedValue(newTask);

      const { result } = renderHook(() => useTasks());

      // 既存タスクを設定
      await act(async () => {
        (taskService.getTasks as jest.Mock).mockResolvedValue({
          tasks: mockTasks,
          pagination: mockPagination,
        });
        await result.current.fetchTasks();
      });

      // 新規タスク作成
      let createdTask: any;
      await act(async () => {
        createdTask = await result.current.createTask({
          title: '新規タスク',
          span: 1,
        });
      });

      expect(taskService.createTask).toHaveBeenCalledWith({
        title: '新規タスク',
        span: 1,
      });
      expect(createdTask).toEqual(newTask);
      expect(result.current.tasks).toHaveLength(3);
      expect(result.current.tasks[0]).toEqual(newTask);
    });

    it('エラー時はnullを返す', async () => {
      (taskService.createTask as jest.Mock).mockRejectedValue(new Error('TASK_CREATE_FAILED'));

      const { result } = renderHook(() => useTasks());

      let createdTask: any;
      await act(async () => {
        createdTask = await result.current.createTask({
          title: '新規タスク',
          span: 1,
        });
      });

      expect(createdTask).toBeNull();
      expect(result.current.error).toBeTruthy();
    });
  });

  describe('updateTask', () => {
    it('タスクを更新できる', async () => {
      const updatedTask = { ...mockTasks[0], title: '更新後タスク' };
      (taskService.updateTask as jest.Mock).mockResolvedValue(updatedTask);

      const { result } = renderHook(() => useTasks());

      // 既存タスクを設定
      await act(async () => {
        (taskService.getTasks as jest.Mock).mockResolvedValue({
          tasks: mockTasks,
          pagination: mockPagination,
        });
        await result.current.fetchTasks();
      });

      // タスク更新
      let updated: any;
      await act(async () => {
        updated = await result.current.updateTask(1, { title: '更新後タスク' });
      });

      expect(taskService.updateTask).toHaveBeenCalledWith(1, { title: '更新後タスク' });
      expect(updated).toEqual(updatedTask);
      expect(result.current.tasks[0].title).toBe('更新後タスク');
    });
  });

  describe('deleteTask', () => {
    it('タスクを削除できる', async () => {
      (taskService.deleteTask as jest.Mock).mockResolvedValue(undefined);

      const { result } = renderHook(() => useTasks());

      // 既存タスクを設定
      await act(async () => {
        (taskService.getTasks as jest.Mock).mockResolvedValue({
          tasks: mockTasks,
          pagination: mockPagination,
        });
        await result.current.fetchTasks();
      });

      expect(result.current.tasks).toHaveLength(2);

      // タスク削除
      let success: boolean = false;
      await act(async () => {
        success = await result.current.deleteTask(1);
      });

      expect(taskService.deleteTask).toHaveBeenCalledWith(1);
      expect(success).toBe(true);
      expect(result.current.tasks).toHaveLength(1);
      expect(result.current.tasks[0].id).toBe(2);
    });
  });

  describe('toggleComplete', () => {
    it('タスク完了状態を切り替えられる', async () => {
      const completedTask = { ...mockTasks[0], status: 'completed' };
      (taskService.toggleTaskCompletion as jest.Mock).mockResolvedValue(completedTask);

      const { result } = renderHook(() => useTasks());

      // 既存タスクを設定
      await act(async () => {
        (taskService.getTasks as jest.Mock).mockResolvedValue({
          tasks: mockTasks,
          pagination: mockPagination,
        });
        await result.current.fetchTasks();
      });

      // 完了切り替え
      let success: boolean = false;
      await act(async () => {
        success = await result.current.toggleComplete(1);
      });

      expect(taskService.toggleTaskCompletion).toHaveBeenCalledWith(1);
      expect(success).toBe(true);
      // Note: Task型にstatusプロパティは存在しない（is_completedを使用）
    });
  });

  describe('approveTask', () => {
    it('タスクを承認できる', async () => {
      const approvedTask = { ...mockTasks[1], status: 'approved' };
      (taskService.approveTask as jest.Mock).mockResolvedValue(approvedTask);

      const { result } = renderHook(() => useTasks());

      // 既存タスクを設定
      await act(async () => {
        (taskService.getTasks as jest.Mock).mockResolvedValue({
          tasks: mockTasks,
          pagination: mockPagination,
        });
        await result.current.fetchTasks();
      });

      // 承認
      let success: boolean = false;
      await act(async () => {
        success = await result.current.approveTask(2);
      });

      expect(taskService.approveTask).toHaveBeenCalledWith(2);
      expect(success).toBe(true);
      // Note: Task型にstatusプロパティは存在しない
    });
  });

  describe('rejectTask', () => {
    it('タスクを却下できる', async () => {
      const rejectedTask = { ...mockTasks[1], status: 'rejected' };
      (taskService.rejectTask as jest.Mock).mockResolvedValue(rejectedTask);

      const { result } = renderHook(() => useTasks());

      // 既存タスクを設定
      await act(async () => {
        (taskService.getTasks as jest.Mock).mockResolvedValue({
          tasks: mockTasks,
          pagination: mockPagination,
        });
        await result.current.fetchTasks();
      });

      // 却下
      let success: boolean = false;
      await act(async () => {
        success = await result.current.rejectTask(2);
      });

      expect(taskService.rejectTask).toHaveBeenCalledWith(2);
      expect(success).toBe(true);
      // Note: Task型にstatusプロパティは存在しない
    });
  });

  describe('uploadImage', () => {
    it('画像をアップロードできる', async () => {
      const mockImage = { id: 1, path: 'test.jpg', url: 'http://test.com/test.jpg' };
      (taskService.uploadTaskImage as jest.Mock).mockResolvedValue({ image: mockImage });

      const { result } = renderHook(() => useTasks());

      // 既存タスクを設定
      await act(async () => {
        (taskService.getTasks as jest.Mock).mockResolvedValue({
          tasks: mockTasks,
          pagination: mockPagination,
        });
        await result.current.fetchTasks();
      });

      // 画像アップロード
      let success: boolean = false;
      await act(async () => {
        success = await result.current.uploadImage(1, 'file:///test.jpg');
      });

      expect(taskService.uploadTaskImage).toHaveBeenCalledWith(1, 'file:///test.jpg');
      expect(success).toBe(true);
      expect(result.current.tasks[0].images).toHaveLength(1);
      expect(result.current.tasks[0].images[0]).toEqual(mockImage);
    });
  });

  describe('deleteImage', () => {
    it('画像を削除できる', async () => {
      const taskWithImage = {
        ...mockTasks[0],
        images: [{ id: 1, path: 'test.jpg', url: 'http://test.com/test.jpg' }],
      };
      (taskService.deleteTaskImage as jest.Mock).mockResolvedValue(undefined);

      const { result } = renderHook(() => useTasks());

      // 画像付きタスクを設定
      await act(async () => {
        (taskService.getTasks as jest.Mock).mockResolvedValue({
          tasks: [taskWithImage, mockTasks[1]],
          pagination: mockPagination,
        });
        await result.current.fetchTasks();
      });

      expect(result.current.tasks[0].images).toHaveLength(1);

      // 画像削除
      let success: boolean = false;
      await act(async () => {
        success = await result.current.deleteImage(1, 1);
      });

      expect(taskService.deleteTaskImage).toHaveBeenCalledWith(1);
      expect(success).toBe(true);
      expect(result.current.tasks[0].images).toHaveLength(0);
    });
  });

  describe('clearError', () => {
    it('エラーをクリアできる', async () => {
      (taskService.getTasks as jest.Mock).mockRejectedValue(new Error('NETWORK_ERROR'));

      const { result } = renderHook(() => useTasks());

      await act(async () => {
        await result.current.fetchTasks();
      });

      expect(result.current.error).toBeTruthy();

      act(() => {
        result.current.clearError();
      });

      expect(result.current.error).toBeNull();
    });
  });

  describe('refreshTasks', () => {
    it('現在のフィルターでタスクを再取得できる', async () => {
      (taskService.getTasks as jest.Mock).mockResolvedValue({
        tasks: mockTasks,
        pagination: mockPagination,
      });

      const { result } = renderHook(() => useTasks());

      // 初回取得（フィルター付き）
      await act(async () => {
        await result.current.fetchTasks({ status: 'pending' });
      });

      expect(taskService.getTasks).toHaveBeenCalledWith({ status: 'pending' });

      // 再取得
      await act(async () => {
        await result.current.refreshTasks();
      });

      expect(taskService.getTasks).toHaveBeenCalledWith({ status: 'pending' });
      expect(taskService.getTasks).toHaveBeenCalledTimes(2);
    });
  });
});
