/**
 * useScheduledTasks フックのテスト
 */
import { renderHook, act } from '@testing-library/react-native';
import { useScheduledTasks } from '../useScheduledTasks';
import scheduledTaskService from '../../services/scheduledTask.service';

// scheduledTaskServiceをモック
jest.mock('../../services/scheduledTask.service');
jest.mock('react-native/Libraries/Alert/Alert', () => ({
  alert: jest.fn(),
}));

// ThemeContextをモック
jest.mock('../../contexts/ThemeContext', () => ({
  ThemeProvider: ({ children }: any) => children,
  useTheme: () => ({ theme: 'adult', isLoading: false, refreshTheme: jest.fn() }),
}));

describe('useScheduledTasks', () => {
  const mockScheduledTasks = [
    {
      id: 1,
      group_id: 1,
      task_type: 'task',
      title: 'スケジュールタスク1',
      description: '説明1',
      span: 1,
      priority: 3,
      reward: 10,
      requires_approval: false,
      requires_image: false,
      schedule: {
        type: 'daily',
        execution_time: '09:00',
        days_of_week: null,
        day_of_month: null,
        skip_holidays: false,
      },
      assignment: {
        method: 'all',
        user_ids: null,
      },
      is_active: true,
      next_execution_at: '2025-12-09T09:00:00Z',
      last_execution_at: null,
      created_at: '2025-12-01T00:00:00Z',
      updated_at: '2025-12-01T00:00:00Z',
    },
  ];

  const mockFormData = {
    users: [
      { id: 1, name: 'テストユーザー1' },
      { id: 2, name: 'テストユーザー2' },
    ],
    scheduled_task: null,
  };

  const mockExecutionHistory = [
    {
      id: 1,
      scheduled_group_task_id: 1,
      execution_time: '2025-12-08T09:00:00Z',
      status: 'success',
      created_task_id: 101,
      deleted_task_id: null,
      error_message: null,
      created_at: '2025-12-08T09:00:00Z',
      updated_at: '2025-12-08T09:00:00Z',
    },
    {
      id: 2,
      scheduled_group_task_id: 1,
      execution_time: '2025-12-07T09:00:00Z',
      status: 'success',
      created_task_id: 100,
      deleted_task_id: null,
      error_message: null,
      created_at: '2025-12-07T09:00:00Z',
      updated_at: '2025-12-07T09:00:00Z',
    },
  ];

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getScheduledTasks', () => {
    it('スケジュールタスク一覧を取得できる', async () => {
      (scheduledTaskService.getScheduledTasks as jest.Mock).mockResolvedValue(mockScheduledTasks);

      const { result } = renderHook(() => useScheduledTasks());

      expect(result.current.scheduledTasks).toEqual([]);
      expect(result.current.isLoading).toBe(false);

      await act(async () => {
        await result.current.getScheduledTasks(1);
      });

      expect(scheduledTaskService.getScheduledTasks).toHaveBeenCalledWith(1);
      expect(result.current.scheduledTasks).toEqual(mockScheduledTasks);
      expect(result.current.isLoading).toBe(false);
      expect(result.current.error).toBeNull();
    });

    it('エラー時はエラーメッセージが設定される', async () => {
      const mockError = new Error('NETWORK_ERROR');
      (scheduledTaskService.getScheduledTasks as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      await act(async () => {
        await result.current.getScheduledTasks(1);
      });

      expect(result.current.error).toBeTruthy();
      expect(result.current.scheduledTasks).toEqual([]);
    });
  });

  describe('getCreateFormData', () => {
    it('作成フォームデータを取得できる', async () => {
      (scheduledTaskService.getCreateFormData as jest.Mock).mockResolvedValue(mockFormData);

      const { result } = renderHook(() => useScheduledTasks());

      await act(async () => {
        await result.current.getCreateFormData(1);
      });

      expect(scheduledTaskService.getCreateFormData).toHaveBeenCalledWith(1);
      expect(result.current.formData).toEqual(mockFormData);
      expect(result.current.error).toBeNull();
    });

    it('エラー時はnullが返される', async () => {
      const mockError = new Error('PERMISSION_DENIED');
      (scheduledTaskService.getCreateFormData as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      await act(async () => {
        const data = await result.current.getCreateFormData(1);
        expect(data).toBeNull();
      });

      expect(result.current.formData).toBeNull();
      expect(result.current.error).toBeTruthy();
    });
  });

  describe('createScheduledTask', () => {
    it('スケジュールタスクを作成できる', async () => {
      const newTask = mockScheduledTasks[0];
      (scheduledTaskService.createScheduledTask as jest.Mock).mockResolvedValue(newTask);

      const { result } = renderHook(() => useScheduledTasks());

      const requestData = {
        title: 'スケジュールタスク1',
        schedules: [
          {
            type: 'daily' as const,
            time: '09:00',
          },
        ],
        start_date: '2025-12-09',
      };

      let createdTask: any;
      await act(async () => {
        createdTask = await result.current.createScheduledTask(requestData);
      });

      expect(scheduledTaskService.createScheduledTask).toHaveBeenCalledWith(requestData);
      expect(createdTask).toEqual(newTask);
      expect(result.current.scheduledTasks).toContain(newTask);
    });

    it('エラー時はnullが返される', async () => {
      const mockError = new Error('VALIDATION_ERROR');
      (scheduledTaskService.createScheduledTask as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      const requestData = {
        title: '',
        schedules: [],
        start_date: '2025-12-09',
      };

      let createdTask: any;
      await act(async () => {
        createdTask = await result.current.createScheduledTask(requestData);
      });

      expect(createdTask).toBeNull();
      expect(result.current.error).toBeTruthy();
    });
  });

  describe('getEditFormData', () => {
    it('編集フォームデータを取得できる', async () => {
      const editFormData = {
        scheduled_task: mockScheduledTasks[0],
        group_members: [
          { id: 1, name: 'テストユーザー1' },
          { id: 2, name: 'テストユーザー2' },
        ],
      };
      (scheduledTaskService.getEditFormData as jest.Mock).mockResolvedValue(editFormData);

      const { result } = renderHook(() => useScheduledTasks());

      let data: any;
      await act(async () => {
        data = await result.current.getEditFormData(1);
      });

      expect(scheduledTaskService.getEditFormData).toHaveBeenCalledWith(1);
      expect(data).toEqual(editFormData);
      expect(result.current.error).toBeNull();
    });

    it('エラー時はnullが返される', async () => {
      const mockError = new Error('NOT_FOUND');
      (scheduledTaskService.getEditFormData as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      await act(async () => {
        const data = await result.current.getEditFormData(999);
        expect(data).toBeNull();
      });

      expect(result.current.formData).toBeNull();
      expect(result.current.error).toBeTruthy();
    });
  });

  describe('updateScheduledTask', () => {
    it('スケジュールタスクを更新できる', async () => {
      const updatedTask = { ...mockScheduledTasks[0], title: '更新されたタスク' };
      (scheduledTaskService.updateScheduledTask as jest.Mock).mockResolvedValue(updatedTask);

      const { result } = renderHook(() => useScheduledTasks());

      // 既存タスクを設定
      await act(async () => {
        (scheduledTaskService.getScheduledTasks as jest.Mock).mockResolvedValue(mockScheduledTasks);
        await result.current.getScheduledTasks(1);
      });

      const updateData = {
        title: '更新されたタスク',
        schedules: [
          {
            type: 'daily' as const,
            time: '09:00',
          },
        ],
        start_date: '2025-12-09',
      };

      let result_task: any;
      await act(async () => {
        result_task = await result.current.updateScheduledTask(1, updateData);
      });

      expect(scheduledTaskService.updateScheduledTask).toHaveBeenCalledWith(1, updateData);
      expect(result_task).toEqual(updatedTask);
      expect(result.current.scheduledTasks[0].title).toBe('更新されたタスク');
    });

    it('エラー時はnullが返される', async () => {
      const mockError = new Error('PERMISSION_DENIED');
      (scheduledTaskService.updateScheduledTask as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      const updateData = {
        title: '更新されたタスク',
        schedules: [],
        start_date: '2025-12-09',
      };

      let result_task: any;
      await act(async () => {
        result_task = await result.current.updateScheduledTask(1, updateData);
      });

      expect(result_task).toBeNull();
      expect(result.current.error).toBeTruthy();
    });
  });

  describe('deleteScheduledTask', () => {
    it('スケジュールタスクを削除できる', async () => {
      (scheduledTaskService.deleteScheduledTask as jest.Mock).mockResolvedValue(undefined);

      const { result } = renderHook(() => useScheduledTasks());

      // 既存タスクを設定
      await act(async () => {
        (scheduledTaskService.getScheduledTasks as jest.Mock).mockResolvedValue(mockScheduledTasks);
        await result.current.getScheduledTasks(1);
      });

      expect(result.current.scheduledTasks).toHaveLength(1);

      let success: boolean = false;
      await act(async () => {
        success = await result.current.deleteScheduledTask(1);
      });

      expect(scheduledTaskService.deleteScheduledTask).toHaveBeenCalledWith(1);
      expect(success).toBe(true);
      expect(result.current.scheduledTasks).toHaveLength(0);
    });

    it('エラー時はfalseが返される', async () => {
      const mockError = new Error('NOT_FOUND');
      (scheduledTaskService.deleteScheduledTask as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      let success: boolean = true;
      await act(async () => {
        success = await result.current.deleteScheduledTask(999);
      });

      expect(success).toBe(false);
      expect(result.current.error).toBeTruthy();
    });
  });

  describe('pauseScheduledTask', () => {
    it('スケジュールタスクを一時停止できる', async () => {
      const pausedTask = { ...mockScheduledTasks[0], is_active: false };
      (scheduledTaskService.pauseScheduledTask as jest.Mock).mockResolvedValue(pausedTask);

      const { result } = renderHook(() => useScheduledTasks());

      // 既存タスクを設定
      await act(async () => {
        (scheduledTaskService.getScheduledTasks as jest.Mock).mockResolvedValue(mockScheduledTasks);
        await result.current.getScheduledTasks(1);
      });

      let result_task: any;
      await act(async () => {
        result_task = await result.current.pauseScheduledTask(1);
      });

      expect(scheduledTaskService.pauseScheduledTask).toHaveBeenCalledWith(1);
      expect(result_task).toEqual(pausedTask);
      expect(result.current.scheduledTasks[0].is_active).toBe(false);
    });

    it('エラー時はnullが返される', async () => {
      const mockError = new Error('PERMISSION_DENIED');
      (scheduledTaskService.pauseScheduledTask as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      let result_task: any;
      await act(async () => {
        result_task = await result.current.pauseScheduledTask(1);
      });

      expect(result_task).toBeNull();
      expect(result.current.error).toBeTruthy();
    });
  });

  describe('resumeScheduledTask', () => {
    it('スケジュールタスクを再開できる', async () => {
      const resumedTask = { ...mockScheduledTasks[0], is_active: true };
      (scheduledTaskService.resumeScheduledTask as jest.Mock).mockResolvedValue(resumedTask);

      const { result } = renderHook(() => useScheduledTasks());

      // 停止中のタスクを設定
      const pausedTask = { ...mockScheduledTasks[0], is_active: false };
      await act(async () => {
        (scheduledTaskService.getScheduledTasks as jest.Mock).mockResolvedValue([pausedTask]);
        await result.current.getScheduledTasks(1);
      });

      let result_task: any;
      await act(async () => {
        result_task = await result.current.resumeScheduledTask(1);
      });

      expect(scheduledTaskService.resumeScheduledTask).toHaveBeenCalledWith(1);
      expect(result_task).toEqual(resumedTask);
      expect(result.current.scheduledTasks[0].is_active).toBe(true);
    });

    it('エラー時はnullが返される', async () => {
      const mockError = new Error('NOT_FOUND');
      (scheduledTaskService.resumeScheduledTask as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      let result_task: any;
      await act(async () => {
        result_task = await result.current.resumeScheduledTask(999);
      });

      expect(result_task).toBeNull();
      expect(result.current.error).toBeTruthy();
    });
  });

  describe('getExecutionHistory', () => {
    it('実行履歴を取得できる', async () => {
      (scheduledTaskService.getExecutionHistory as jest.Mock).mockResolvedValue(mockExecutionHistory);

      const { result } = renderHook(() => useScheduledTasks());

      await act(async () => {
        await result.current.getExecutionHistory(1);
      });

      expect(scheduledTaskService.getExecutionHistory).toHaveBeenCalledWith(1);
      expect(result.current.executionHistory).toEqual(mockExecutionHistory);
      expect(result.current.executionHistory).toHaveLength(2);
      expect(result.current.error).toBeNull();
    });

    it('エラー時は空配列が返される', async () => {
      const mockError = new Error('NOT_FOUND');
      (scheduledTaskService.getExecutionHistory as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      await act(async () => {
        const history = await result.current.getExecutionHistory(999);
        expect(history).toEqual([]);
      });

      expect(result.current.executionHistory).toEqual([]);
      expect(result.current.error).toBeTruthy();
    });
  });

  describe('clearError', () => {
    it('エラーをクリアできる', async () => {
      const mockError = new Error('NETWORK_ERROR');
      (scheduledTaskService.getScheduledTasks as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => useScheduledTasks());

      // エラーを発生させる
      await act(async () => {
        await result.current.getScheduledTasks(1);
      });

      expect(result.current.error).toBeTruthy();

      // エラーをクリア
      act(() => {
        result.current.clearError();
      });

      expect(result.current.error).toBeNull();
    });
  });

  describe('refreshScheduledTasks', () => {
    it('スケジュールタスクを再取得できる', async () => {
      (scheduledTaskService.getScheduledTasks as jest.Mock).mockResolvedValue(mockScheduledTasks);

      const { result } = renderHook(() => useScheduledTasks());

      // 初回取得
      await act(async () => {
        await result.current.getScheduledTasks(1);
      });

      expect(result.current.scheduledTasks).toEqual(mockScheduledTasks);

      // データを更新
      const updatedTasks = [
        { ...mockScheduledTasks[0], title: '更新されたタスク' },
      ];
      (scheduledTaskService.getScheduledTasks as jest.Mock).mockResolvedValue(updatedTasks);

      // 再取得
      await act(async () => {
        await result.current.refreshScheduledTasks(1);
      });

      expect(scheduledTaskService.getScheduledTasks).toHaveBeenCalledTimes(2);
      expect(result.current.scheduledTasks).toEqual(updatedTasks);
    });
  });
});
