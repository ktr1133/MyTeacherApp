/**
 * usePendingApprovals フックのテスト
 */
import { renderHook, act, waitFor } from '@testing-library/react-native';
import { usePendingApprovals } from '../usePendingApprovals';
import approvalService from '../../services/approval.service';

// approvalServiceをモック
jest.mock('../../services/approval.service');

// ThemeContextをモック
jest.mock('../../contexts/ThemeContext', () => ({
  ThemeProvider: ({ children }: any) => children,
  useTheme: () => ({ theme: 'adult', isLoading: false, refreshTheme: jest.fn() }),
}));

describe('usePendingApprovals', () => {
  const mockTaskApproval = {
    id: 1,
    type: 'task' as const,
    title: 'テストタスク',
    requester_name: '太郎',
    requester_id: 10,
    requested_at: '2025-12-09T10:00:00Z',
    description: 'テスト説明',
    reward: 1000,
    has_images: true,
    images_count: 2,
    due_date: '2025-12-10T23:59:59Z',
    model: {
      id: 1,
      title: 'テストタスク',
      description: 'テスト説明',
      span: 1,
      due_date: '2025-12-10',
      priority: 3,
      is_completed: false,
      completed_at: null,
      reward: 1000,
      requires_approval: true,
      requires_image: true,
      is_group_task: false,
      group_task_id: null,
      assigned_by_user_id: null,
      tags: [],
      images: [],
      created_at: '2025-12-09T10:00:00Z',
      updated_at: '2025-12-09T10:00:00Z',
    },
  };

  const mockTokenApproval = {
    id: 2,
    type: 'token' as const,
    package_name: 'スタンダードパック',
    requester_name: '花子',
    requester_id: 11,
    requested_at: '2025-12-09T11:00:00Z',
    token_amount: 10000,
    price: 500,
    model: {
      id: 2,
      package_id: 1,
      status: 'pending' as const,
      created_at: '2025-12-09T11:00:00Z',
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

  describe('fetchApprovals', () => {
    it('承認待ち一覧を取得できる', async () => {
      (approvalService.getPendingApprovals as jest.Mock).mockResolvedValue({
        approvals: [mockTaskApproval, mockTokenApproval],
        pagination: mockPagination,
      });

      const { result } = renderHook(() => usePendingApprovals());

      expect(result.current.approvals).toEqual([]);
      expect(result.current.isLoading).toBe(false);

      await act(async () => {
        await result.current.fetchApprovals();
      });

      expect(approvalService.getPendingApprovals).toHaveBeenCalledWith({ page: 1, per_page: 15 });
      expect(result.current.approvals).toEqual([mockTaskApproval, mockTokenApproval]);
      expect(result.current.pagination).toEqual(mockPagination);
      expect(result.current.isLoading).toBe(false);
    });

    it('エラー時はエラーメッセージが設定される', async () => {
      const mockError = new Error('APPROVAL_FETCH_FAILED');
      (approvalService.getPendingApprovals as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      expect(result.current.error).toBeTruthy();
      expect(result.current.approvals).toEqual([]);
    });
  });

  describe('loadMoreApprovals', () => {
    it('次ページを読み込める', async () => {
      const page1Data = {
        approvals: [mockTaskApproval],
        pagination: { ...mockPagination, current_page: 1, last_page: 2 },
      };

      const page2Data = {
        approvals: [mockTokenApproval],
        pagination: { ...mockPagination, current_page: 2, last_page: 2 },
      };

      (approvalService.getPendingApprovals as jest.Mock)
        .mockResolvedValueOnce(page1Data)
        .mockResolvedValueOnce(page2Data);

      const { result } = renderHook(() => usePendingApprovals());

      // 初回取得
      await act(async () => {
        await result.current.fetchApprovals();
      });

      expect(result.current.approvals).toHaveLength(1);
      expect(result.current.hasMore).toBe(true);

      // 次ページ読み込み
      await act(async () => {
        await result.current.loadMoreApprovals();
      });

      expect(approvalService.getPendingApprovals).toHaveBeenCalledWith({ page: 2, per_page: 15 });
      expect(result.current.approvals).toHaveLength(2);
      expect(result.current.hasMore).toBe(false);
    });

    it('最終ページでは追加読み込みしない', async () => {
      (approvalService.getPendingApprovals as jest.Mock).mockResolvedValue({
        approvals: [mockTaskApproval],
        pagination: { ...mockPagination, current_page: 1, last_page: 1 },
      });

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      expect(result.current.hasMore).toBe(false);

      const callCount = (approvalService.getPendingApprovals as jest.Mock).mock.calls.length;

      await act(async () => {
        await result.current.loadMoreApprovals();
      });

      // API呼び出しが増えていないことを確認
      expect((approvalService.getPendingApprovals as jest.Mock).mock.calls.length).toBe(callCount);
    });
  });

  describe('refreshApprovals', () => {
    it('一覧をリフレッシュできる', async () => {
      const initialData = {
        approvals: [mockTaskApproval],
        pagination: mockPagination,
      };

      const refreshedData = {
        approvals: [mockTaskApproval, mockTokenApproval],
        pagination: { ...mockPagination, total: 2 },
      };

      (approvalService.getPendingApprovals as jest.Mock)
        .mockResolvedValueOnce(initialData)
        .mockResolvedValueOnce(refreshedData);

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      expect(result.current.approvals).toHaveLength(1);

      await act(async () => {
        await result.current.refreshApprovals();
      });

      expect(result.current.approvals).toHaveLength(2);
    });
  });

  describe('approveTaskItem', () => {
    it('タスクを承認できる（楽観的更新）', async () => {
      (approvalService.getPendingApprovals as jest.Mock).mockResolvedValue({
        approvals: [mockTaskApproval, mockTokenApproval],
        pagination: mockPagination,
      });

      (approvalService.approveTask as jest.Mock).mockResolvedValue({
        success: true,
        message: '承認しました',
      });

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      expect(result.current.approvals).toHaveLength(2);

      // 承認実行
      let success: boolean = false;
      await act(async () => {
        success = await result.current.approveTaskItem(1);
      });

      expect(success).toBe(true);
      expect(approvalService.approveTask).toHaveBeenCalledWith(1);
      expect(result.current.approvals).toHaveLength(1); // 楽観的更新で削除
      expect(result.current.approvals[0].type).toBe('token'); // タスクが削除されトークンのみ残る
    });

    it('承認失敗時はロールバックされる', async () => {
      (approvalService.getPendingApprovals as jest.Mock).mockResolvedValue({
        approvals: [mockTaskApproval, mockTokenApproval],
        pagination: mockPagination,
      });

      (approvalService.approveTask as jest.Mock).mockRejectedValue(new Error('TASK_APPROVE_FAILED'));

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      expect(result.current.approvals).toHaveLength(2);

      // 承認実行（失敗）
      let success: boolean = true;
      await act(async () => {
        success = await result.current.approveTaskItem(1);
      });

      expect(success).toBe(false);
      
      // ロールバックのため再取得が呼ばれる
      await waitFor(() => {
        expect(approvalService.getPendingApprovals).toHaveBeenCalledTimes(2);
      });
    });
  });

  describe('rejectTaskItem', () => {
    it('タスクを却下できる（楽観的更新）', async () => {
      (approvalService.getPendingApprovals as jest.Mock).mockResolvedValue({
        approvals: [mockTaskApproval, mockTokenApproval],
        pagination: mockPagination,
      });

      (approvalService.rejectTask as jest.Mock).mockResolvedValue({
        success: true,
        message: '却下しました',
      });

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      // 却下実行（理由付き）
      let success: boolean = false;
      await act(async () => {
        success = await result.current.rejectTaskItem(1, '理由: テスト');
      });

      expect(success).toBe(true);
      expect(approvalService.rejectTask).toHaveBeenCalledWith(1, '理由: テスト');
      expect(result.current.approvals).toHaveLength(1);
    });

    it('却下理由なしでも却下できる', async () => {
      (approvalService.getPendingApprovals as jest.Mock).mockResolvedValue({
        approvals: [mockTaskApproval],
        pagination: mockPagination,
      });

      (approvalService.rejectTask as jest.Mock).mockResolvedValue({
        success: true,
        message: '却下しました',
      });

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      let success: boolean = false;
      await act(async () => {
        success = await result.current.rejectTaskItem(1);
      });

      expect(success).toBe(true);
      expect(approvalService.rejectTask).toHaveBeenCalledWith(1, undefined);
    });
  });

  describe('approveTokenItem', () => {
    it('トークン購入申請を承認できる（楽観的更新）', async () => {
      (approvalService.getPendingApprovals as jest.Mock).mockResolvedValue({
        approvals: [mockTokenApproval],
        pagination: mockPagination,
      });

      (approvalService.approveTokenPurchase as jest.Mock).mockResolvedValue({
        success: true,
        message: '承認しました',
      });

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      let success: boolean = false;
      await act(async () => {
        success = await result.current.approveTokenItem(2);
      });

      expect(success).toBe(true);
      expect(approvalService.approveTokenPurchase).toHaveBeenCalledWith(2);
      expect(result.current.approvals).toHaveLength(0);
    });
  });

  describe('rejectTokenItem', () => {
    it('トークン購入申請を却下できる（楽観的更新）', async () => {
      (approvalService.getPendingApprovals as jest.Mock).mockResolvedValue({
        approvals: [mockTokenApproval],
        pagination: mockPagination,
      });

      (approvalService.rejectTokenPurchase as jest.Mock).mockResolvedValue({
        success: true,
        message: '却下しました',
      });

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      let success: boolean = false;
      await act(async () => {
        success = await result.current.rejectTokenItem(2, '却下理由');
      });

      expect(success).toBe(true);
      expect(approvalService.rejectTokenPurchase).toHaveBeenCalledWith(2, '却下理由');
      expect(result.current.approvals).toHaveLength(0);
    });
  });

  describe('clearError', () => {
    it('エラーをクリアできる', async () => {
      const mockError = new Error('APPROVAL_FETCH_FAILED');
      (approvalService.getPendingApprovals as jest.Mock).mockRejectedValue(mockError);

      const { result } = renderHook(() => usePendingApprovals());

      await act(async () => {
        await result.current.fetchApprovals();
      });

      expect(result.current.error).toBeTruthy();

      act(() => {
        result.current.clearError();
      });

      expect(result.current.error).toBeNull();
    });
  });
});
