/**
 * 承認待ち一覧フック
 * 
 * ApprovalServiceを呼び出し、状態管理とテーマに応じたエラーメッセージ表示を担当
 * 無限スクロール対応、楽観的更新でUXを向上
 * 
 * 参照:
 * - 要件定義: /home/ktr/mtdev/definitions/mobile/PendingApprovalsScreen.md
 * - 既存実装: /home/ktr/mtdev/mobile/src/hooks/useTasks.ts
 */

import { useState, useCallback } from 'react';
import approvalService from '../services/approval.service';
import { useTheme } from '../contexts/ThemeContext';
import { getErrorMessage } from '../utils/errorMessages';
import { ApprovalItem } from '../types/approval.types';

/**
 * ページネーション情報
 */
interface PaginationInfo {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number | null;
  to: number | null;
}

/**
 * 承認待ち一覧フックの戻り値型
 */
interface UsePendingApprovalsReturn {
  // 状態
  approvals: ApprovalItem[];
  isLoading: boolean;
  isLoadingMore: boolean; // 追加読み込み中フラグ
  hasMore: boolean; // さらに読み込めるか
  error: string | null;
  pagination: PaginationInfo | null;

  // 操作
  fetchApprovals: () => Promise<void>;
  loadMoreApprovals: () => Promise<void>; // 無限スクロール用
  refreshApprovals: () => Promise<void>;
  approveTaskItem: (taskId: number) => Promise<boolean>;
  rejectTaskItem: (taskId: number, reason?: string) => Promise<boolean>;
  approveTokenItem: (purchaseRequestId: number) => Promise<boolean>;
  rejectTokenItem: (purchaseRequestId: number, reason?: string) => Promise<boolean>;

  // ユーティリティ
  clearError: () => void;
}

/**
 * 承認待ち一覧カスタムフック
 * 
 * ApprovalServiceを呼び出し、UIに必要な状態管理とエラーハンドリングを提供
 * テーマに応じたエラーメッセージを自動変換
 * 
 * @example
 * ```tsx
 * const { 
 *   approvals, 
 *   isLoading, 
 *   error, 
 *   fetchApprovals, 
 *   approveTaskItem 
 * } = usePendingApprovals();
 * 
 * useEffect(() => {
 *   fetchApprovals();
 * }, []);
 * ```
 */
export const usePendingApprovals = (): UsePendingApprovalsReturn => {
  const { theme } = useTheme();
  const [approvals, setApprovals] = useState<ApprovalItem[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isLoadingMore, setIsLoadingMore] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [pagination, setPagination] = useState<PaginationInfo | null>(null);

  // さらに読み込めるかを判定
  const hasMore = pagination ? pagination.current_page < pagination.last_page : false;

  /**
   * エラーをテーマに応じたメッセージに変換してセット
   */
  const handleError = useCallback(
    (error: any) => {
      const errorMessage = error.message || 'UNKNOWN_ERROR';
      const localizedMessage = getErrorMessage(errorMessage, theme);
      setError(localizedMessage);
    },
    [theme]
  );

  /**
   * エラーをクリア
   */
  const clearError = useCallback(() => {
    setError(null);
  }, []);

  /**
   * 承認待ち一覧を取得（初回）
   */
  const fetchApprovals = useCallback(async (): Promise<void> => {
    try {
      setIsLoading(true);
      setError(null);

      const response = await approvalService.getPendingApprovals({ page: 1, per_page: 15 });
      setApprovals(response.approvals);
      setPagination(response.pagination);
    } catch (err: any) {
      console.error('[usePendingApprovals] fetchApprovals error:', err);
      console.error('[usePendingApprovals] fetchApprovals error message:', err.message);
      handleError(err);
      setApprovals([]);
      setPagination(null);
    } finally {
      setIsLoading(false);
    }
  }, [handleError]);

  /**
   * 承認待ち一覧を追加読み込み（無限スクロール）
   */
  const loadMoreApprovals = useCallback(async (): Promise<void> => {
    if (!hasMore || isLoadingMore || isLoading) {
      return;
    }

    try {
      setIsLoadingMore(true);
      setError(null);

      const nextPage = pagination!.current_page + 1;
      const response = await approvalService.getPendingApprovals({
        page: nextPage,
        per_page: 15,
      });

      setApprovals((prev) => [...prev, ...response.approvals]);
      setPagination(response.pagination);
    } catch (err: any) {
      console.error('[usePendingApprovals] loadMoreApprovals error:', err);
      handleError(err);
    } finally {
      setIsLoadingMore(false);
    }
  }, [hasMore, isLoadingMore, isLoading, pagination, handleError]);

  /**
   * 承認待ち一覧をリフレッシュ（Pull-to-Refresh用）
   */
  const refreshApprovals = useCallback(async (): Promise<void> => {
    try {
      setError(null);

      const response = await approvalService.getPendingApprovals({ page: 1, per_page: 15 });
      setApprovals(response.approvals);
      setPagination(response.pagination);
    } catch (err: any) {
      console.error('[usePendingApprovals] refreshApprovals error:', err);
      handleError(err);
    }
  }, [handleError]);

  /**
   * タスクを承認（楽観的更新）
   * 
   * @param taskId - タスクID
   * @returns 成功したらtrue
   */
  const approveTaskItem = useCallback(
    async (taskId: number): Promise<boolean> => {
      try {
        // 楽観的更新: UIから即座に削除
        const optimisticApprovals = approvals.filter(
          (item) => !(item.type === 'task' && item.id === taskId)
        );
        setApprovals(optimisticApprovals);

        // API呼び出し
        await approvalService.approveTask(taskId);

        // ページネーション情報を更新（total -1）
        if (pagination) {
          setPagination({
            ...pagination,
            total: Math.max(0, pagination.total - 1),
          });
        }

        return true;
      } catch (err: any) {
        console.error('[usePendingApprovals] approveTaskItem error:', err);
        handleError(err);

        // ロールバック: データを再取得
        await refreshApprovals();
        return false;
      }
    },
    [approvals, pagination, handleError, refreshApprovals]
  );

  /**
   * タスクを却下（楽観的更新）
   * 
   * @param taskId - タスクID
   * @param reason - 却下理由（任意）
   * @returns 成功したらtrue
   */
  const rejectTaskItem = useCallback(
    async (taskId: number, reason?: string): Promise<boolean> => {
      try {
        // 楽観的更新: UIから即座に削除
        const optimisticApprovals = approvals.filter(
          (item) => !(item.type === 'task' && item.id === taskId)
        );
        setApprovals(optimisticApprovals);

        // API呼び出し
        await approvalService.rejectTask(taskId, reason);

        // ページネーション情報を更新（total -1）
        if (pagination) {
          setPagination({
            ...pagination,
            total: Math.max(0, pagination.total - 1),
          });
        }

        return true;
      } catch (err: any) {
        console.error('[usePendingApprovals] rejectTaskItem error:', err);
        handleError(err);

        // ロールバック: データを再取得
        await refreshApprovals();
        return false;
      }
    },
    [approvals, pagination, handleError, refreshApprovals]
  );

  /**
   * トークン購入申請を承認（楽観的更新）
   * 
   * @param purchaseRequestId - 購入リクエストID
   * @returns 成功したらtrue
   */
  const approveTokenItem = useCallback(
    async (purchaseRequestId: number): Promise<boolean> => {
      try {
        // 楽観的更新: UIから即座に削除
        const optimisticApprovals = approvals.filter(
          (item) => !(item.type === 'token' && item.id === purchaseRequestId)
        );
        setApprovals(optimisticApprovals);

        // API呼び出し
        await approvalService.approveTokenPurchase(purchaseRequestId);

        // ページネーション情報を更新（total -1）
        if (pagination) {
          setPagination({
            ...pagination,
            total: Math.max(0, pagination.total - 1),
          });
        }

        return true;
      } catch (err: any) {
        console.error('[usePendingApprovals] approveTokenItem error:', err);
        handleError(err);

        // ロールバック: データを再取得
        await refreshApprovals();
        return false;
      }
    },
    [approvals, pagination, handleError, refreshApprovals]
  );

  /**
   * トークン購入申請を却下（楽観的更新）
   * 
   * @param purchaseRequestId - 購入リクエストID
   * @param reason - 却下理由（任意）
   * @returns 成功したらtrue
   */
  const rejectTokenItem = useCallback(
    async (purchaseRequestId: number, reason?: string): Promise<boolean> => {
      try {
        // 楽観的更新: UIから即座に削除
        const optimisticApprovals = approvals.filter(
          (item) => !(item.type === 'token' && item.id === purchaseRequestId)
        );
        setApprovals(optimisticApprovals);

        // API呼び出し
        await approvalService.rejectTokenPurchase(purchaseRequestId, reason);

        // ページネーション情報を更新（total -1）
        if (pagination) {
          setPagination({
            ...pagination,
            total: Math.max(0, pagination.total - 1),
          });
        }

        return true;
      } catch (err: any) {
        console.error('[usePendingApprovals] rejectTokenItem error:', err);
        handleError(err);

        // ロールバック: データを再取得
        await refreshApprovals();
        return false;
      }
    },
    [approvals, pagination, handleError, refreshApprovals]
  );

  return {
    // 状態
    approvals,
    isLoading,
    isLoadingMore,
    hasMore,
    error,
    pagination,

    // 操作
    fetchApprovals,
    loadMoreApprovals,
    refreshApprovals,
    approveTaskItem,
    rejectTaskItem,
    approveTokenItem,
    rejectTokenItem,

    // ユーティリティ
    clearError,
  };
};
