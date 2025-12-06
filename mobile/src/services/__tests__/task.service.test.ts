/**
 * タスクサービステスト
 * 
 * 目標カバレッジ: 95%以上
 * テスト範囲: API通信、エラーハンドリング、データ変換
 */

import taskService from '../task.service';
import api from '../api';
import { Task, CreateTaskData, UpdateTaskData } from '../../types/task.types';

// APIモジュールをモック化
jest.mock('../api');
const mockApi = api as jest.Mocked<typeof api>;

describe('TaskService', () => {
  // テスト用データ
  const mockTask: Task = {
    id: 1,
    title: 'テストタスク',
    description: 'テスト説明',
    span: 1,
    due_date: '2025-12-31',
    priority: 3,
    status: 'pending',
    reward: 100,
    requires_approval: false,
    requires_image: false,
    is_group_task: false,
    group_task_id: null,
    assigned_by_user_id: null,
    tags: ['仕事', '重要'],
    images: [],
    created_at: '2025-12-06T00:00:00.000Z',
    updated_at: '2025-12-06T00:00:00.000Z',
  };

  const mockPagination = {
    current_page: 1,
    per_page: 20,
    total: 1,
    last_page: 1,
    from: 1,
    to: 1,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getTasks', () => {
    it('タスク一覧を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            tasks: [mockTask],
            pagination: mockPagination,
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await taskService.getTasks();

      expect(mockApi.get).toHaveBeenCalledWith('/tasks', { params: undefined });
      expect(result.tasks).toHaveLength(1);
      expect(result.tasks[0].id).toBe(1);
      expect(result.pagination.total).toBe(1);
    });

    it('空のタスク一覧を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            tasks: [],
            pagination: { ...mockPagination, total: 0, from: null, to: null },
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await taskService.getTasks();

      expect(result.tasks).toHaveLength(0);
      expect(result.pagination.total).toBe(0);
    });

    it('フィルター条件付きでタスク一覧を取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            tasks: [mockTask],
            pagination: mockPagination,
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const filters = { status: 'pending' as const, page: 2, per_page: 10 };
      await taskService.getTasks(filters);

      expect(mockApi.get).toHaveBeenCalledWith('/tasks', { params: filters });
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(taskService.getTasks()).rejects.toThrow('AUTH_REQUIRED');
    });

    it('API通信エラー時に適切なエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce(new Error('Network Error'));

      await expect(taskService.getTasks()).rejects.toThrow('NETWORK_ERROR');
    });

    it('API通信エラー（メッセージなし）時にデフォルトエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({});

      await expect(taskService.getTasks()).rejects.toThrow('NETWORK_ERROR');
    });

    it('success=falseの場合にエラーコードをスローする', async () => {
      const mockResponse = {
        data: {
          success: false,
          data: { tasks: [], pagination: mockPagination },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      await expect(taskService.getTasks()).rejects.toThrow('TASK_FETCH_FAILED');
    });
  });

  describe('createTask', () => {
    const createData: CreateTaskData = {
      title: '新規タスク',
      description: '新規説明',
      span: 1,
      priority: 3,
    };

    it('タスクを正常に作成できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: { task: mockTask },
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await taskService.createTask(createData);

      expect(mockApi.post).toHaveBeenCalledWith('/tasks', createData);
      expect(result.id).toBe(1);
      expect(result.title).toBe('テストタスク');
    });

    it('バリデーションエラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            errors: {
              title: ['タイトルは必須です'],
            },
          },
        },
      });

      await expect(taskService.createTask(createData)).rejects.toThrow('TITLE_REQUIRED');
    });

    it('バリデーションエラー（エラーメッセージなし）時にデフォルトエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: {
          status: 422,
          data: {},
        },
      });

      await expect(taskService.createTask(createData)).rejects.toThrow('VALIDATION_ERROR');
    });

    it('API通信エラー時にデフォルトエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({});

      await expect(taskService.createTask(createData)).rejects.toThrow('NETWORK_ERROR');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(taskService.createTask(createData)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('success=falseの場合にエラーコードをスローする', async () => {
      const mockResponse = {
        data: {
          success: false,
          message: 'タスク作成失敗',
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await expect(taskService.createTask(createData)).rejects.toThrow('TASK_CREATE_FAILED');
    });
  });

  describe('updateTask', () => {
    const updateData: UpdateTaskData = {
      title: '更新後タイトル',
    };

    it('タスクを正常に更新できる', async () => {
      const updatedTask = { ...mockTask, title: '更新後タイトル' };
      const mockResponse = {
        data: {
          success: true,
          data: { task: updatedTask },
        },
      };
      mockApi.put.mockResolvedValueOnce(mockResponse);

      const result = await taskService.updateTask(1, updateData);

      expect(mockApi.put).toHaveBeenCalledWith('/tasks/1', updateData);
      expect(result.title).toBe('更新後タイトル');
    });

    it('タスクが存在しない場合にエラーコードをスローする', async () => {
      mockApi.put.mockRejectedValueOnce({
        response: { status: 404 },
      });

      await expect(taskService.updateTask(999, updateData)).rejects.toThrow('TASK_NOT_FOUND');
    });

    it('バリデーションエラー時に適切なエラーコードをスローする', async () => {
      mockApi.put.mockRejectedValueOnce({
        response: {
          status: 422,
          data: {
            errors: {
              priority: ['優先度は1から5の間で指定してください'],
            },
          },
        },
      });

      await expect(taskService.updateTask(1, updateData)).rejects.toThrow('VALIDATION_ERROR');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.put.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(taskService.updateTask(1, updateData)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('API通信エラー時にデフォルトエラーコードをスローする', async () => {
      mockApi.put.mockRejectedValueOnce({});

      await expect(taskService.updateTask(1, updateData)).rejects.toThrow('TASK_UPDATE_FAILED');
    });
  });

  describe('deleteTask', () => {
    it('タスクを正常に削除できる', async () => {
      const mockResponse = {
        data: {
          success: true,
        },
      };
      mockApi.delete.mockResolvedValueOnce(mockResponse);

      await taskService.deleteTask(1);

      expect(mockApi.delete).toHaveBeenCalledWith('/tasks/1');
    });

    it('タスクが存在しない場合にエラーコードをスローする', async () => {
      mockApi.delete.mockRejectedValueOnce({
        response: { status: 404 },
      });

      await expect(taskService.deleteTask(999)).rejects.toThrow('TASK_NOT_FOUND');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.delete.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(taskService.deleteTask(1)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('API通信エラー時にデフォルトエラーコードをスローする', async () => {
      mockApi.delete.mockRejectedValueOnce({});

      await expect(taskService.deleteTask(1)).rejects.toThrow('TASK_DELETE_FAILED');
    });
  });

  describe('toggleTaskCompletion', () => {
    it('タスクの完了状態を正常に切り替えられる', async () => {
      const completedTask = { ...mockTask, status: 'completed' as const };
      const mockResponse = {
        data: {
          success: true,
          data: { task: completedTask },
        },
      };
      mockApi.patch.mockResolvedValueOnce(mockResponse);

      const result = await taskService.toggleTaskCompletion(1);

      expect(mockApi.patch).toHaveBeenCalledWith('/tasks/1/toggle');
      expect(result.status).toBe('completed');
    });

    it('タスクが存在しない場合にエラーコードをスローする', async () => {
      mockApi.patch.mockRejectedValueOnce({
        response: { status: 404 },
      });

      await expect(taskService.toggleTaskCompletion(999)).rejects.toThrow('TASK_NOT_FOUND');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.patch.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(taskService.toggleTaskCompletion(1)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('API通信エラー時にデフォルトエラーコードをスローする', async () => {
      mockApi.patch.mockRejectedValueOnce({});

      await expect(taskService.toggleTaskCompletion(1)).rejects.toThrow('TASK_UPDATE_FAILED');
    });
  });

  describe('approveTask', () => {
    it('タスクを正常に承認できる', async () => {
      const approvedTask = { ...mockTask, status: 'approved' as const };
      const mockResponse = {
        data: {
          success: true,
          data: { task: approvedTask },
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await taskService.approveTask(1);

      expect(mockApi.post).toHaveBeenCalledWith('/tasks/1/approve');
      expect(result.status).toBe('approved');
    });

    it('承認権限がない場合にエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 403 },
      });

      await expect(taskService.approveTask(1)).rejects.toThrow('APPROVAL_NOT_ALLOWED');
    });

    it('タスクが存在しない場合にエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 404 },
      });

      await expect(taskService.approveTask(999)).rejects.toThrow('TASK_NOT_FOUND');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(taskService.approveTask(1)).rejects.toThrow('AUTH_REQUIRED');
    });
  });

  describe('rejectTask', () => {
    it('タスクを正常に却下できる', async () => {
      const rejectedTask = { ...mockTask, status: 'rejected' as const };
      const mockResponse = {
        data: {
          success: true,
          data: { task: rejectedTask },
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await taskService.rejectTask(1);

      expect(mockApi.post).toHaveBeenCalledWith('/tasks/1/reject');
      expect(result.status).toBe('rejected');
    });

    it('却下権限がない場合にエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 403 },
      });

      await expect(taskService.rejectTask(1)).rejects.toThrow('APPROVAL_NOT_ALLOWED');
    });

    it('タスクが存在しない場合にエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 404 },
      });

      await expect(taskService.rejectTask(999)).rejects.toThrow('TASK_NOT_FOUND');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(taskService.rejectTask(1)).rejects.toThrow('AUTH_REQUIRED');
    });
  });

  describe('uploadTaskImage', () => {
    it('画像を正常にアップロードできる', async () => {
      const mockImage = {
        id: 1,
        path: 'tasks/1/image.jpg',
        url: 'https://example.com/tasks/1/image.jpg',
      };
      const mockResponse = {
        data: {
          success: true,
          data: { image: mockImage },
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      const result = await taskService.uploadTaskImage(1, 'file:///path/to/image.jpg');

      expect(mockApi.post).toHaveBeenCalledWith(
        '/tasks/1/images',
        expect.any(FormData),
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
      expect(result?.image.url).toBe('https://example.com/tasks/1/image.jpg');
    });

    it('画像ファイルが不正な場合にエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 422 },
      });

      await expect(
        taskService.uploadTaskImage(1, 'file:///invalid.txt')
      ).rejects.toThrow('INVALID_IMAGE_FORMAT');
    });

    it('タスクが存在しない場合にエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 404 },
      });

      await expect(
        taskService.uploadTaskImage(999, 'file:///path/to/image.jpg')
      ).rejects.toThrow('TASK_NOT_FOUND');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(taskService.uploadTaskImage(1, 'file:///path/to/image.jpg')).rejects.toThrow('AUTH_REQUIRED');
    });

    it('API通信エラー時にデフォルトエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({});

      await expect(
        taskService.uploadTaskImage(1, 'file:///path/to/image.jpg')
      ).rejects.toThrow('IMAGE_UPLOAD_FAILED');
    });
  });

  describe('deleteTaskImage', () => {
    it('画像を正常に削除できる', async () => {
      const mockResponse = {
        data: {
          success: true,
        },
      };
      mockApi.delete.mockResolvedValueOnce(mockResponse);

      await taskService.deleteTaskImage(1);

      expect(mockApi.delete).toHaveBeenCalledWith('/task-images/1');
    });

    it('画像が存在しない場合にエラーコードをスローする', async () => {
      mockApi.delete.mockRejectedValueOnce({
        response: { status: 404 },
      });

      await expect(taskService.deleteTaskImage(999)).rejects.toThrow('TASK_NOT_FOUND');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.delete.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(taskService.deleteTaskImage(1)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('API通信エラー時にデフォルトエラーコードをスローする', async () => {
      mockApi.delete.mockRejectedValueOnce({});

      await expect(taskService.deleteTaskImage(1)).rejects.toThrow('IMAGE_DELETE_FAILED');
    });
  });
});
