/**
 * 承認サービステスト
 * 
 * 目標カバレッジ: 95%以上
 * テスト範囲: API通信、エラーハンドリング、データ変換
 */

import approvalService from '../approval.service';
import api from '../api';
import {
  TaskApprovalItem,
  TokenApprovalItem,
} from '../../types/approval.types';

// APIモジュールをモック化
jest.mock('../api');
const mockApi = api as jest.Mocked<typeof api>;

describe('ApprovalService', () => {
  // テスト用データ
  const mockTaskApproval: TaskApprovalItem = {
    id: 1,
    type: 'task',
    title: 'テストタスク',
    requester_name: 'テストユーザー',
    requester_id: 2,
    requested_at: '2025-12-06T00:00:00.000Z',
    description: 'テスト説明',
    reward: 100,
    has_images: false,
    images_count: 0,
    due_date: '2025-12-31',
    model: {
      id: 1,
      title: 'テストタスク',
      description: 'テスト説明',
      span: 1,
      due_date: '2025-12-31',
      priority: 3,
      is_completed: false,
      completed_at: null,
      reward: 100,
      requires_approval: true,
      requires_image: false,
      is_group_task: false,
      group_task_id: null,
      assigned_by_user_id: null,
      tags: [],
      images: [],
      created_at: '2025-12-06T00:00:00.000Z',
      updated_at: '2025-12-06T00:00:00.000Z',
    },
  };

  const mockTokenApproval: TokenApprovalItem = {
    id: 1,
    type: 'token',
    package_name: '10,000トークン',
    requester_name: 'テストユーザー',
    requester_id: 2,
    requested_at: '2025-12-06T00:00:00.000Z',
    token_amount: 10000,
    price: 1200,
    model: {
      id: 1,
      package_id: 1,
      status: 'pending',
      created_at: '2025-12-06T00:00:00.000Z',
    },
  };

  const mockPagination = {
    current_page: 1,
    per_page: 15,
    total: 2,
    last_page: 1,
    from: 1,
    to: 2,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getPendingApprovals', () => {
    it('承認待ち一覧を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            approvals: [mockTaskApproval, mockTokenApproval],
            pagination: mockPagination,
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await approvalService.getPendingApprovals();

      expect(mockApi.get).toHaveBeenCalledWith('/tasks/approvals/pending', { params: undefined });
      expect(result.approvals).toHaveLength(2);
      expect(result.approvals[0].type).toBe('task');
      expect(result.approvals[1].type).toBe('token');
      expect(result.pagination.total).toBe(2);
    });

    it('空の承認待ち一覧を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            approvals: [],
            pagination: { ...mockPagination, total: 0, from: null, to: null },
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await approvalService.getPendingApprovals();

      expect(result.approvals).toHaveLength(0);
      expect(result.pagination.total).toBe(0);
    });

    it('ページネーション付きで承認待ち一覧を取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            approvals: [mockTaskApproval],
            pagination: mockPagination,
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const params = { page: 2, per_page: 10 };
      await approvalService.getPendingApprovals(params);

      expect(mockApi.get).toHaveBeenCalledWith('/tasks/approvals/pending', { params });
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(approvalService.getPendingApprovals()).rejects.toThrow('AUTH_REQUIRED');
    });

    it('権限エラー時に適切なエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({
        response: { status: 403 },
      });

      await expect(approvalService.getPendingApprovals()).rejects.toThrow('PERMISSION_DENIED');
    });

    it('API通信エラー時に適切なエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce(new Error('Network Error'));

      await expect(approvalService.getPendingApprovals()).rejects.toThrow('NETWORK_ERROR');
    });

    it('success=falseの場合にエラーコードをスローする', async () => {
      const mockResponse = {
        data: {
          success: false,
          data: { approvals: [], pagination: mockPagination },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      await expect(approvalService.getPendingApprovals()).rejects.toThrow('NETWORK_ERROR');
    });
  });

  describe('getPendingApprovalsCount', () => {
    it('承認待ち件数を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            count: 5,
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await approvalService.getPendingApprovalsCount();

      expect(mockApi.get).toHaveBeenCalledWith('/tasks/approvals/pending-count');
      expect(result).toBe(5);
    });

    it('承認待ちが0件の場合も正常に取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: {
            count: 0,
          },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await approvalService.getPendingApprovalsCount();

      expect(result).toBe(0);
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(approvalService.getPendingApprovalsCount()).rejects.toThrow('AUTH_REQUIRED');
    });

    it('API通信エラー時に適切なエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce(new Error('Network Error'));

      await expect(approvalService.getPendingApprovalsCount()).rejects.toThrow('NETWORK_ERROR');
    });

    it('success=falseの場合にエラーコードをスローする', async () => {
      const mockResponse = {
        data: {
          success: false,
          data: { count: 0 },
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      await expect(approvalService.getPendingApprovalsCount()).rejects.toThrow('NETWORK_ERROR');
    });
  });

  describe('approveTask', () => {
    it('タスクを正常に承認できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: '承認が完了しました',
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await approvalService.approveTask(1);

      expect(mockApi.post).toHaveBeenCalledWith('/tasks/1/approve');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(approvalService.approveTask(1)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('権限エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 403 },
      });

      await expect(approvalService.approveTask(1)).rejects.toThrow('PERMISSION_DENIED');
    });

    it('タスクが既に処理済みの場合に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 422 },
      });

      await expect(approvalService.approveTask(1)).rejects.toThrow('TASK_ALREADY_PROCESSED');
    });

    it('API通信エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce(new Error('Network Error'));

      await expect(approvalService.approveTask(1)).rejects.toThrow('NETWORK_ERROR');
    });

    it('success=falseの場合にエラーコードをスローする', async () => {
      const mockResponse = {
        data: {
          success: false,
          message: 'エラー',
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await expect(approvalService.approveTask(1)).rejects.toThrow('NETWORK_ERROR');
    });
  });

  describe('rejectTask', () => {
    it('タスクを正常に却下できる（理由あり）', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: '却下が完了しました',
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await approvalService.rejectTask(1, '内容が不適切です');

      expect(mockApi.post).toHaveBeenCalledWith('/tasks/1/reject', {
        rejection_reason: '内容が不適切です',
      });
    });

    it('タスクを正常に却下できる（理由なし）', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: '却下が完了しました',
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await approvalService.rejectTask(1);

      expect(mockApi.post).toHaveBeenCalledWith('/tasks/1/reject', {
        reason: undefined,
      });
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(approvalService.rejectTask(1)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('権限エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 403 },
      });

      await expect(approvalService.rejectTask(1)).rejects.toThrow('PERMISSION_DENIED');
    });

    it('タスクが既に処理済みの場合に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 422 },
      });

      await expect(approvalService.rejectTask(1, '理由')).rejects.toThrow('TASK_ALREADY_PROCESSED');
    });

    it('API通信エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce(new Error('Network Error'));

      await expect(approvalService.rejectTask(1)).rejects.toThrow('NETWORK_ERROR');
    });
  });

  describe('approveTokenPurchase', () => {
    it('トークン購入申請を正常に承認できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: '承認が完了しました',
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await approvalService.approveTokenPurchase(1);

      expect(mockApi.post).toHaveBeenCalledWith('/tokens/purchase-requests/1/approve');
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(approvalService.approveTokenPurchase(1)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('権限エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 403 },
      });

      await expect(approvalService.approveTokenPurchase(1)).rejects.toThrow('PERMISSION_DENIED');
    });

    it('申請が見つからない場合に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 404 },
      });

      await expect(approvalService.approveTokenPurchase(1)).rejects.toThrow('PURCHASE_REQUEST_NOT_FOUND');
    });

    it('申請が既に処理済みの場合に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 422 },
      });

      await expect(approvalService.approveTokenPurchase(1)).rejects.toThrow('PURCHASE_ALREADY_PROCESSED');
    });

    it('API通信エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce(new Error('Network Error'));

      await expect(approvalService.approveTokenPurchase(1)).rejects.toThrow('NETWORK_ERROR');
    });

    it('success=falseの場合にエラーコードをスローする', async () => {
      const mockResponse = {
        data: {
          success: false,
          message: 'エラー',
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await expect(approvalService.approveTokenPurchase(1)).rejects.toThrow('NETWORK_ERROR');
    });
  });

  describe('rejectTokenPurchase', () => {
    it('トークン購入申請を正常に却下できる（理由あり）', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: '却下が完了しました',
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await approvalService.rejectTokenPurchase(1, '予算オーバー');

      expect(mockApi.post).toHaveBeenCalledWith('/tokens/purchase-requests/1/reject', {
        rejection_reason: '予算オーバー',
      });
    });

    it('トークン購入申請を正常に却下できる（理由なし）', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: '却下が完了しました',
        },
      };
      mockApi.post.mockResolvedValueOnce(mockResponse);

      await approvalService.rejectTokenPurchase(1);

      expect(mockApi.post).toHaveBeenCalledWith('/tokens/purchase-requests/1/reject', {
        reason: undefined,
      });
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(approvalService.rejectTokenPurchase(1)).rejects.toThrow('AUTH_REQUIRED');
    });

    it('権限エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 403 },
      });

      await expect(approvalService.rejectTokenPurchase(1)).rejects.toThrow('PERMISSION_DENIED');
    });

    it('申請が見つからない場合に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 404 },
      });

      await expect(approvalService.rejectTokenPurchase(1, '理由')).rejects.toThrow('PURCHASE_REQUEST_NOT_FOUND');
    });

    it('申請が既に処理済みの場合に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce({
        response: { status: 422 },
      });

      await expect(approvalService.rejectTokenPurchase(1, '理由')).rejects.toThrow('PURCHASE_ALREADY_PROCESSED');
    });

    it('API通信エラー時に適切なエラーコードをスローする', async () => {
      mockApi.post.mockRejectedValueOnce(new Error('Network Error'));

      await expect(approvalService.rejectTokenPurchase(1)).rejects.toThrow('NETWORK_ERROR');
    });
  });
});
