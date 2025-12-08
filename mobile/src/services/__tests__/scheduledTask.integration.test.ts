/**
 * ScheduledTaskService 統合テスト
 * 
 * 実際のAPIエンドポイントとの統合をテスト
 * ScheduledTaskServiceとバックエンドAPI間のデータフロー検証
 */

import scheduledTaskService from '../scheduledTask.service';
import { ScheduledTaskRequest } from '../../types/scheduled-task.types';

// モック設定（実際のAPI呼び出しではなく、モックレスポンスで検証）
jest.mock('../api', () => ({
  get: jest.fn(),
  post: jest.fn(),
  put: jest.fn(),
  delete: jest.fn(),
}));

import api from '../api';

describe('ScheduledTaskService Integration', () => {
  const mockGroupId = 1;
  const mockScheduledTaskId = 1;

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getScheduledTasks', () => {
    it('スケジュールタスク一覧を正常に取得できる', async () => {
      const mockResponse = {
        status: 200,
        data: {
          message: 'スケジュールタスク一覧を取得しました。',
          data: {
            scheduled_tasks: [
              {
                id: 1,
                group_id: 1,
                title: '毎週月曜日のゴミ出し',
                description: '燃えるゴミを出してください',
                is_active: true,
                schedules: [{ type: 'weekly', time: '09:00', days: [1] }],
                reward: 100,
              },
            ],
          },
        },
      };

      (api.get as jest.Mock).mockResolvedValue(mockResponse);

      const result = await scheduledTaskService.getScheduledTasks(mockGroupId);

      expect(api.get).toHaveBeenCalledWith('/scheduled-tasks', {
        params: { group_id: mockGroupId },
      });
      expect(result).toHaveLength(1);
      expect(result[0].title).toBe('毎週月曜日のゴミ出し');
    });

    it('認証エラー時にAUTH_REQUIREDエラーを投げる', async () => {
      (api.get as jest.Mock).mockRejectedValue({
        response: { status: 401 },
      });

      await expect(
        scheduledTaskService.getScheduledTasks(mockGroupId)
      ).rejects.toThrow('AUTH_REQUIRED');
    });

    it('権限エラー時にPERMISSION_DENIEDエラーを投げる', async () => {
      (api.get as jest.Mock).mockRejectedValue({
        response: { status: 403 },
      });

      await expect(
        scheduledTaskService.getScheduledTasks(mockGroupId)
      ).rejects.toThrow('PERMISSION_DENIED');
    });
  });

  describe('createScheduledTask', () => {
    it('スケジュールタスクを正常に作成できる', async () => {
      const mockRequest: ScheduledTaskRequest = {
        group_id: 1,
        title: '毎週月曜日のゴミ出し',
        description: '燃えるゴミを出してください',
        requires_image: false,
        requires_approval: false,
        reward: 100,
        schedules: [{ type: 'weekly', time: '09:00', days: [1] }],
        due_duration_days: 0,
        due_duration_hours: 0,
        start_date: '2025-12-09',
        skip_holidays: true,
        move_to_next_business_day: false,
        delete_incomplete_previous: true,
        tags: ['家事'],
      };

      const mockResponse = {
        status: 201,
        data: {
          success: true,
          message: 'スケジュールタスクを作成しました。',
          data: {
            scheduled_task: {
              id: 1,
              ...mockRequest,
              is_active: true,
              created_at: '2025-12-08T10:00:00Z',
              updated_at: '2025-12-08T10:00:00Z',
            },
          },
        },
      };

      (api.post as jest.Mock).mockResolvedValue(mockResponse);

      const result = await scheduledTaskService.createScheduledTask(mockRequest);

      expect(api.post).toHaveBeenCalledWith('/scheduled-tasks', mockRequest);
      expect(result.id).toBe(1);
      expect(result.title).toBe('毎週月曜日のゴミ出し');
    });

    it('バリデーションエラー時にVALIDATION_ERRORを投げる', async () => {
      (api.post as jest.Mock).mockRejectedValue({
        response: { status: 422 },
      });

      const invalidRequest = {} as ScheduledTaskRequest;

      await expect(
        scheduledTaskService.createScheduledTask(invalidRequest)
      ).rejects.toThrow('VALIDATION_ERROR');
    });
  });

  describe('updateScheduledTask', () => {
    it('スケジュールタスクを正常に更新できる', async () => {
      const mockRequest: ScheduledTaskRequest = {
        group_id: 1,
        title: '毎週火曜日のゴミ出し',
        description: '燃えるゴミを出してください',
        requires_image: false,
        requires_approval: false,
        reward: 100,
        schedules: [{ type: 'weekly', time: '09:00', days: [2] }],
        due_duration_days: 0,
        due_duration_hours: 0,
        start_date: '2025-12-09',
        skip_holidays: true,
        move_to_next_business_day: false,
        delete_incomplete_previous: true,
        tags: ['家事'],
      };

      const mockResponse = {
        status: 200,
        data: {
          success: true,
          message: 'スケジュールタスクを更新しました。',
          data: {
            scheduled_task: {
              id: mockScheduledTaskId,
              ...mockRequest,
              is_active: true,
              updated_at: '2025-12-08T11:00:00Z',
            },
          },
        },
      };

      (api.put as jest.Mock).mockResolvedValue(mockResponse);

      const result = await scheduledTaskService.updateScheduledTask(
        mockScheduledTaskId,
        mockRequest
      );

      expect(api.put).toHaveBeenCalledWith(
        `/scheduled-tasks/${mockScheduledTaskId}`,
        mockRequest
      );
      expect(result.title).toBe('毎週火曜日のゴミ出し');
    });

    it('存在しないタスクの更新時にNOT_FOUNDを投げる', async () => {
      (api.put as jest.Mock).mockRejectedValue({
        response: { status: 404 },
      });

      await expect(
        scheduledTaskService.updateScheduledTask(999, {} as ScheduledTaskRequest)
      ).rejects.toThrow('NOT_FOUND');
    });
  });

  describe('deleteScheduledTask', () => {
    it('スケジュールタスクを正常に削除できる', async () => {
      const mockResponse = {
        status: 200,
        data: {
          success: true,
          message: 'スケジュールタスクを削除しました。',
        },
      };

      (api.delete as jest.Mock).mockResolvedValue(mockResponse);

      await scheduledTaskService.deleteScheduledTask(mockScheduledTaskId);

      expect(api.delete).toHaveBeenCalledWith(`/scheduled-tasks/${mockScheduledTaskId}`);
    });

    it('存在しないタスクの削除時にNOT_FOUNDを投げる', async () => {
      (api.delete as jest.Mock).mockRejectedValue({
        response: { status: 404 },
      });

      await expect(
        scheduledTaskService.deleteScheduledTask(999)
      ).rejects.toThrow('NOT_FOUND');
    });
  });

  describe('pauseScheduledTask', () => {
    it('スケジュールタスクを正常に一時停止できる', async () => {
      const mockResponse = {
        status: 200,
        data: {
          success: true,
          message: 'スケジュールタスクを一時停止しました。',
          data: {
            scheduled_task: {
              id: mockScheduledTaskId,
              is_active: false,
              paused_at: '2025-12-08T12:00:00Z',
            },
          },
        },
      };

      (api.post as jest.Mock).mockResolvedValue(mockResponse);

      const result = await scheduledTaskService.pauseScheduledTask(mockScheduledTaskId);

      expect(api.post).toHaveBeenCalledWith(`/scheduled-tasks/${mockScheduledTaskId}/pause`);
      expect(result.is_active).toBe(false);
    });
  });

  describe('resumeScheduledTask', () => {
    it('スケジュールタスクを正常に再開できる', async () => {
      const mockResponse = {
        status: 200,
        data: {
          success: true,
          message: 'スケジュールタスクを再開しました。',
          data: {
            scheduled_task: {
              id: mockScheduledTaskId,
              is_active: true,
              paused_at: null,
            },
          },
        },
      };

      (api.post as jest.Mock).mockResolvedValue(mockResponse);

      const result = await scheduledTaskService.resumeScheduledTask(mockScheduledTaskId);

      expect(api.post).toHaveBeenCalledWith(`/scheduled-tasks/${mockScheduledTaskId}/resume`);
      expect(result.is_active).toBe(true);
    });
  });

  describe('getExecutionHistory', () => {
    it('実行履歴を正常に取得できる', async () => {
      const mockResponse = {
        status: 200,
        data: {
          message: '実行履歴を取得しました。',
          data: {
            scheduled_task: {
              id: mockScheduledTaskId,
              title: '毎週月曜日のゴミ出し',
            },
            executions: [
              {
                id: 1,
                scheduled_task_id: mockScheduledTaskId,
                created_task_id: 123,
                deleted_task_id: null,
                executed_at: '2025-12-09T09:00:00Z',
                status: 'success',
                note: 'タスク3件作成',
                error_message: null,
              },
              {
                id: 2,
                scheduled_task_id: mockScheduledTaskId,
                created_task_id: null,
                deleted_task_id: null,
                executed_at: '2025-12-02T09:00:00Z',
                status: 'skipped',
                note: '祝日のためスキップ',
                error_message: null,
              },
            ],
          },
        },
      };

      (api.get as jest.Mock).mockResolvedValue(mockResponse);

      const result = await scheduledTaskService.getExecutionHistory(mockScheduledTaskId);

      expect(api.get).toHaveBeenCalledWith(`/scheduled-tasks/${mockScheduledTaskId}/history`);
      expect(result).toHaveLength(2);
      expect(result[0].status).toBe('success');
      expect(result[1].status).toBe('skipped');
    });

    it('存在しないタスクの履歴取得時にNOT_FOUNDを投げる', async () => {
      (api.get as jest.Mock).mockRejectedValue({
        response: { status: 404 },
      });

      await expect(
        scheduledTaskService.getExecutionHistory(999)
      ).rejects.toThrow('NOT_FOUND');
    });
  });

  describe('getCreateFormData', () => {
    it('作成フォームデータを正常に取得できる', async () => {
      const mockResponse = {
        status: 200,
        data: {
          message: 'スケジュールタスク作成情報を取得しました。',
          data: {
            group_members: [
              { id: 1, name: '山田太郎' },
              { id: 2, name: '佐藤花子' },
            ],
            tags: ['家事', '掃除', '買い物'],
            defaults: {
              requires_image: false,
              requires_approval: false,
              reward: 0,
            },
          },
        },
      };

      (api.get as jest.Mock).mockResolvedValue(mockResponse);

      const result = await scheduledTaskService.getCreateFormData(mockGroupId);

      expect(api.get).toHaveBeenCalledWith('/scheduled-tasks/create', {
        params: { group_id: mockGroupId },
      });
      expect(result.group_members).toHaveLength(2);
      expect(result.tags).toContain('家事');
    });
  });

  describe('getEditFormData', () => {
    it('編集フォームデータを正常に取得できる', async () => {
      const mockResponse = {
        status: 200,
        data: {
          message: 'スケジュールタスク編集情報を取得しました。',
          data: {
            scheduled_task: {
              id: mockScheduledTaskId,
              title: '毎週月曜日のゴミ出し',
              schedules: [{ type: 'weekly', time: '09:00', days: [1] }],
            },
            group_members: [
              { id: 1, name: '山田太郎' },
            ],
          },
        },
      };

      (api.get as jest.Mock).mockResolvedValue(mockResponse);

      const result = await scheduledTaskService.getEditFormData(mockScheduledTaskId);

      expect(api.get).toHaveBeenCalledWith(`/scheduled-tasks/${mockScheduledTaskId}/edit`);
      expect(result.scheduled_task.title).toBe('毎週月曜日のゴミ出し');
      expect(result.group_members).toHaveLength(1);
    });
  });
});
