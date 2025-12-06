/**
 * TaskService.searchTasks() テスト
 * 
 * 検索API（GET /tasks?q={query}）の動作を検証
 */

import { taskService } from '../../src/services/task.service';
import api from '../../src/services/api';

jest.mock('../../src/services/api');

const mockedApi = api as jest.Mocked<typeof api>;

describe('TaskService.searchTasks()', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('正常系', () => {
    it('検索クエリでタスクを取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            tasks: [
              {
                id: 1,
                title: '検索テスト',
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
            pagination: {
              current_page: 1,
              per_page: 10,
              total: 1,
              last_page: 1,
            },
          },
        },
      };

      mockedApi.get.mockResolvedValueOnce(mockResponse);

      const result = await taskService.searchTasks('検索');

      expect(mockedApi.get).toHaveBeenCalledWith('/tasks', {
        params: { q: '検索' },
      });
      expect(result).toEqual(mockResponse.data.data);
      expect(result.tasks).toHaveLength(1);
      expect(result.tasks[0].title).toBe('検索テスト');
    });

    it('検索クエリと追加フィルターでタスクを取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            tasks: [],
            pagination: {
              current_page: 1,
              per_page: 10,
              total: 0,
              last_page: 1,
            },
          },
        },
      };

      mockedApi.get.mockResolvedValueOnce(mockResponse);

      await taskService.searchTasks('テスト', { status: 'completed' });

      expect(mockedApi.get).toHaveBeenCalledWith('/tasks', {
        params: { q: 'テスト', status: 'completed' },
      });
    });

    it('検索結果が空でもエラーにならない', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            tasks: [],
            pagination: {
              current_page: 1,
              per_page: 10,
              total: 0,
              last_page: 1,
            },
          },
        },
      };

      mockedApi.get.mockResolvedValueOnce(mockResponse);

      const result = await taskService.searchTasks('存在しない');

      expect(result.tasks).toHaveLength(0);
      expect(result.pagination.total).toBe(0);
    });
  });

  describe('異常系', () => {
    it('API成功フラグがfalseの場合はTASK_SEARCH_FAILEDエラー', async () => {
      const mockResponse = {
        data: {
          success: false,
          data: { tasks: [], pagination: null },
        },
      };

      mockedApi.get.mockResolvedValueOnce(mockResponse);

      await expect(taskService.searchTasks('エラー')).rejects.toThrow('TASK_SEARCH_FAILED');
    });

    it('401エラーの場合はAUTH_REQUIREDエラー', async () => {
      const error = {
        response: { status: 401 },
        message: 'Unauthorized',
      };

      mockedApi.get.mockRejectedValueOnce(error);

      await expect(taskService.searchTasks('認証')).rejects.toThrow('AUTH_REQUIRED');
    });

    it('ネットワークエラーの場合はNETWORK_ERRORエラー', async () => {
      const error = {
        message: 'Network Error',
      };

      mockedApi.get.mockRejectedValueOnce(error);

      await expect(taskService.searchTasks('ネットワーク')).rejects.toThrow('NETWORK_ERROR');
    });

    it('その他のエラーはそのまま投げる', async () => {
      const error = new Error('UNEXPECTED_ERROR');

      mockedApi.get.mockRejectedValueOnce(error);

      await expect(taskService.searchTasks('予期しない')).rejects.toThrow('UNEXPECTED_ERROR');
    });
  });

  describe('エッジケース', () => {
    it('空文字列の検索クエリでも実行できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            tasks: [],
            pagination: {
              current_page: 1,
              per_page: 10,
              total: 0,
              last_page: 1,
            },
          },
        },
      };

      mockedApi.get.mockResolvedValueOnce(mockResponse);

      const result = await taskService.searchTasks('');

      expect(mockedApi.get).toHaveBeenCalledWith('/tasks', {
        params: { q: '' },
      });
      expect(result.tasks).toHaveLength(0);
    });

    it('特殊文字を含む検索クエリでも実行できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            tasks: [],
            pagination: {
              current_page: 1,
              per_page: 10,
              total: 0,
              last_page: 1,
            },
          },
        },
      };

      mockedApi.get.mockResolvedValueOnce(mockResponse);

      await taskService.searchTasks('# @ $ % &');

      expect(mockedApi.get).toHaveBeenCalledWith('/tasks', {
        params: { q: '# @ $ % &' },
      });
    });
  });
});
