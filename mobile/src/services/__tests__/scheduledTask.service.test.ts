/**
 * スケジュールタスクサービステスト
 * 
 * 目標カバレッジ: 95%以上
 * テスト範囲: API通信、エラーハンドリング、データ変換
 * 
 * @see /home/ktr/mtdev/mobile/src/services/scheduledTask.service.ts
 * @see /home/ktr/mtdev/definitions/mobile/ScheduledTaskManagement.md
 */

import scheduledTaskService from '../scheduledTask.service';
import api from '../api';
import {
  ScheduledTask,
  ScheduledTaskRequest,
  ScheduledTaskFormData,
  ScheduledTaskHistoryResponse,
} from '../../types/scheduled-task.types';

// APIモジュールをモック化
jest.mock('../api');
const mockApi = api as jest.Mocked<typeof api>;

describe('ScheduledTaskService', () => {
  // テスト用データ
  const mockScheduledTask: ScheduledTask = {
    id: 1,
    group_id: 1,
    created_by: 1,
    title: '毎週月曜日のゴミ出し',
    description: '可燃ゴミを出してください',
    requires_image: false,
    reward: 100,
    requires_approval: false,
    assigned_user_id: null,
    auto_assign: false,
    schedules: [
      {
        type: 'weekly',
        time: '09:00',
        days: [1],
      },
    ],
    due_duration_days: 0,
    due_duration_hours: 0,
    start_date: '2025-12-09',
    end_date: null,
    skip_holidays: true,
    move_to_next_business_day: false,
    delete_incomplete_previous: true,
    tags: ['家事', '掃除'],
    is_active: true,
    paused_at: null,
    created_at: '2025-12-08T10:00:00Z',
    updated_at: '2025-12-08T10:00:00Z',
  };

  const mockFormData: ScheduledTaskFormData = {
    group_members: [
      { id: 1, name: '山田太郎', username: 'taro' },
      { id: 2, name: '山田花子', username: 'hanako' },
    ],
    tags: ['家事', '掃除', '勉強'],
    defaults: {
      reward: 0,
      requires_image: false,
      requires_approval: false,
      skip_holidays: false,
      move_to_next_business_day: false,
      delete_incomplete_previous: true,
      start_date: '2025-12-09',
    },
  };

  const mockHistoryData: ScheduledTaskHistoryResponse = {
    scheduled_task: {
      id: 1,
      title: '毎週月曜日のゴミ出し',
    },
    executions: [
      {
        id: 1,
        scheduled_task_id: 1,
        created_task_id: 123,
        deleted_task_id: null,
        executed_at: '2025-12-09T09:00:00+09:00',
        status: 'success',
        note: 'タスク3件作成完了',
        error_message: null,
      },
      {
        id: 2,
        scheduled_task_id: 1,
        created_task_id: null,
        deleted_task_id: null,
        executed_at: '2025-11-25T09:00:00+09:00',
        status: 'skipped',
        note: '祝日のためスキップ',
        error_message: null,
      },
    ],
  };

  const mockScheduledTaskRequest: ScheduledTaskRequest = {
    title: '毎週月曜日のゴミ出し',
    description: '可燃ゴミを出してください',
    requires_image: false,
    reward: 100,
    requires_approval: false,
    assigned_user_id: null,
    schedules: [
      {
        type: 'weekly',
        time: '09:00',
        days: [1],
      },
    ],
    due_duration_days: 0,
    due_duration_hours: 0,
    start_date: '2025-12-09',
    end_date: null,
    skip_holidays: true,
    move_to_next_business_day: false,
    tags: ['家事', '掃除'],
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getScheduledTasks', () => {
    it('スケジュールタスク一覧を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          message: 'スケジュールタスク一覧を取得しました。',
          data: {
            scheduled_tasks: [mockScheduledTask],
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await scheduledTaskService.getScheduledTasks(1);

      expect(mockApi.get).toHaveBeenCalledWith('/scheduled-tasks', { params: { group_id: 1 } });
      expect(result).toHaveLength(1);
      expect(result[0].id).toBe(1);
      expect(result[0].title).toBe('毎週月曜日のゴミ出し');
    });

    it('groupIdなしでスケジュールタスク一覧を取得できる', async () => {
      const mockResponse = {
        data: {
          message: 'スケジュールタスク一覧を取得しました。',
          data: {
            scheduled_tasks: [],
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await scheduledTaskService.getScheduledTasks();

      expect(mockApi.get).toHaveBeenCalledWith('/scheduled-tasks', { params: undefined });
      expect(result).toHaveLength(0);
    });

    it('401エラーの場合AUTH_REQUIREDをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({ response: { status: 401 } });

      await expect(scheduledTaskService.getScheduledTasks(1)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('403エラーの場合PERMISSION_DENIEDをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({ response: { status: 403 } });

      await expect(scheduledTaskService.getScheduledTasks(1)).rejects.toThrow('PERMISSION_DENIED');
    });

    it('ネットワークエラーの場合NETWORK_ERRORをスローする', async () => {
      mockApi.get.mockRejectedValueOnce(new Error('Network Error'));

      await expect(scheduledTaskService.getScheduledTasks(1)).rejects.toThrow('NETWORK_ERROR');
    });
  });

  describe('getCreateFormData', () => {
    it('作成フォームデータを正常に取得できる', async () => {
      const mockResponse = {
        data: {
          message: 'スケジュールタスク作成情報を取得しました。',
          data: mockFormData,
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await scheduledTaskService.getCreateFormData(1);

      expect(mockApi.get).toHaveBeenCalledWith('/scheduled-tasks/create', { params: { group_id: 1 } });
      expect(result.group_members).toHaveLength(2);
      expect(result.tags).toContain('家事');
    });

    it('401エラーの場合AUTH_REQUIREDをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({ response: { status: 401 } });

      await expect(scheduledTaskService.getCreateFormData(1)).rejects.toThrow('AUTH_REQUIRED');
    });
  });

  describe('createScheduledTask', () => {
    it('スケジュールタスクを正常に作成できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: 'スケジュールタスクを作成しました。',
          data: {
            scheduled_task: {
              id: 1,
              title: '毎週月曜日のゴミ出し',
            },
          },
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await scheduledTaskService.createScheduledTask(mockScheduledTaskRequest);

      expect(mockApi.post).toHaveBeenCalledWith('/scheduled-tasks', mockScheduledTaskRequest);
      expect(result.id).toBe(1);
      expect(result.title).toBe('毎週月曜日のゴミ出し');
    });

    it('422エラーの場合VALIDATION_ERRORをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({ response: { status: 422 } });

      await expect(scheduledTaskService.createScheduledTask(mockScheduledTaskRequest)).rejects.toThrow(
        'VALIDATION_ERROR'
      );
    });

    it('401エラーの場合AUTH_REQUIREDをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({ response: { status: 401 } });

      await expect(scheduledTaskService.createScheduledTask(mockScheduledTaskRequest)).rejects.toThrow(
        'AUTH_REQUIRED'
      );
    });
  });

  describe('getEditFormData', () => {
    it('編集フォームデータを正常に取得できる', async () => {
      const mockResponse = {
        data: {
          message: 'スケジュールタスク編集情報を取得しました。',
          data: {
            scheduled_task: mockScheduledTask,
            group_members: [{ id: 1, name: '山田太郎' }],
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await scheduledTaskService.getEditFormData(1);

      expect(mockApi.get).toHaveBeenCalledWith('/scheduled-tasks/1/edit');
      expect(result.scheduled_task.id).toBe(1);
      expect(result.group_members).toHaveLength(1);
    });

    it('404エラーの場合NOT_FOUNDをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({ response: { status: 404 } });

      await expect(scheduledTaskService.getEditFormData(999)).rejects.toThrow('NOT_FOUND');
    });

    it('403エラーの場合PERMISSION_DENIEDをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({ response: { status: 403 } });

      await expect(scheduledTaskService.getEditFormData(1)).rejects.toThrow('PERMISSION_DENIED');
    });
  });

  describe('updateScheduledTask', () => {
    it('スケジュールタスクを正常に更新できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: 'スケジュールタスクを更新しました。',
          data: {
            scheduled_task: {
              id: 1,
              title: '毎週月曜日のゴミ出し',
            },
          },
        },
      };
      mockApi.put.mockResolvedValueOnce(mockResponse);

      const result = await scheduledTaskService.updateScheduledTask(1, mockScheduledTaskRequest);

      expect(mockApi.put).toHaveBeenCalledWith('/scheduled-tasks/1', mockScheduledTaskRequest);
      expect(result.id).toBe(1);
    });

    it('422エラーの場合VALIDATION_ERRORをスローする', async () => {
      mockApi.put.mockRejectedValueOnce({ response: { status: 422 } });

      await expect(
        scheduledTaskService.updateScheduledTask(1, mockScheduledTaskRequest)
      ).rejects.toThrow('VALIDATION_ERROR');
    });

    it('404エラーの場合NOT_FOUNDをスローする', async () => {
      mockApi.put.mockRejectedValueOnce({ response: { status: 404 } });

      await expect(
        scheduledTaskService.updateScheduledTask(999, mockScheduledTaskRequest)
      ).rejects.toThrow('NOT_FOUND');
    });
  });

  describe('deleteScheduledTask', () => {
    it('スケジュールタスクを正常に削除できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: 'スケジュールタスクを削除しました。',
        },
      };
      mockApi.delete.mockResolvedValueOnce(mockResponse);

      await scheduledTaskService.deleteScheduledTask(1);

      expect(mockApi.delete).toHaveBeenCalledWith('/scheduled-tasks/1');
    });

    it('404エラーの場合NOT_FOUNDをスローする', async () => {
      mockApi.delete.mockRejectedValueOnce({ response: { status: 404 } });

      await expect(scheduledTaskService.deleteScheduledTask(999)).rejects.toThrow('NOT_FOUND');
    });

    it('403エラーの場合PERMISSION_DENIEDをスローする', async () => {
      mockApi.delete.mockRejectedValueOnce({ response: { status: 403 } });

      await expect(scheduledTaskService.deleteScheduledTask(1)).rejects.toThrow('PERMISSION_DENIED');
    });
  });

  describe('pauseScheduledTask', () => {
    it('スケジュールタスクを正常に一時停止できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: 'スケジュールタスクを一時停止しました。',
          data: {
            scheduled_task: {
              id: 1,
              title: '毎週月曜日のゴミ出し',
              is_active: false,
            },
          },
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await scheduledTaskService.pauseScheduledTask(1);

      expect(mockApi.post).toHaveBeenCalledWith('/scheduled-tasks/1/pause');
      expect(result.is_active).toBe(false);
    });

    it('404エラーの場合NOT_FOUNDをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({ response: { status: 404 } });

      await expect(scheduledTaskService.pauseScheduledTask(999)).rejects.toThrow('NOT_FOUND');
    });
  });

  describe('resumeScheduledTask', () => {
    it('スケジュールタスクを正常に再開できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: 'スケジュールタスクを再開しました。',
          data: {
            scheduled_task: {
              id: 1,
              title: '毎週月曜日のゴミ出し',
              is_active: true,
            },
          },
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await scheduledTaskService.resumeScheduledTask(1);

      expect(mockApi.post).toHaveBeenCalledWith('/scheduled-tasks/1/resume');
      expect(result.is_active).toBe(true);
    });

    it('404エラーの場合NOT_FOUNDをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({ response: { status: 404 } });

      await expect(scheduledTaskService.resumeScheduledTask(999)).rejects.toThrow('NOT_FOUND');
    });
  });

  describe('getExecutionHistory', () => {
    it('実行履歴を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          message: '実行履歴を取得しました。',
          data: mockHistoryData,
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await scheduledTaskService.getExecutionHistory(1);

      expect(mockApi.get).toHaveBeenCalledWith('/scheduled-tasks/1/history');
      expect(result).toHaveLength(2);
      expect(result[0].status).toBe('success');
      expect(result[1].status).toBe('skipped');
    });

    it('404エラーの場合NOT_FOUNDをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({ response: { status: 404 } });

      await expect(scheduledTaskService.getExecutionHistory(999)).rejects.toThrow('NOT_FOUND');
    });

    it('403エラーの場合PERMISSION_DENIEDをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({ response: { status: 403 } });

      await expect(scheduledTaskService.getExecutionHistory(1)).rejects.toThrow('PERMISSION_DENIED');
    });

    it('ネットワークエラーの場合NETWORK_ERRORをスローする', async () => {
      mockApi.get.mockRejectedValueOnce(new Error('Network Error'));

      await expect(scheduledTaskService.getExecutionHistory(1)).rejects.toThrow('NETWORK_ERROR');
    });
  });
});
