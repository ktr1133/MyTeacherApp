/**
 * useTokens Hook テストスイート
 * 
 * トークン管理Hookのテスト
 */

import { renderHook, act, waitFor } from '@testing-library/react-native';
import { Alert } from 'react-native';
import { useTokens } from '../../hooks/useTokens';
import * as TokenService from '../../services/token.service';

// サービスモック
jest.mock('../../services/token.service');
const mockedTokenService = TokenService as jest.Mocked<typeof TokenService>;

// ThemeContext モック
jest.mock('../../contexts/ThemeContext', () => ({
  useTheme: () => ({ theme: 'adult', setTheme: jest.fn() }),
}));

// Alert モック
jest.spyOn(Alert, 'alert');

describe('useTokens', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('loadBalance', () => {
    it('トークン残高を取得できる', async () => {
      // Arrange
      const mockBalance = {
        id: 1,
        tokenable_type: 'App\\Models\\User',
        tokenable_id: 2,
        balance: 500000,
        free_balance: 1000000,
        paid_balance: 0,
        free_balance_reset_at: '2025-01-01T00:00:00+09:00',
        total_consumed: 500000,
        monthly_consumed: 500000,
        monthly_consumed_reset_at: '2025-01-01T00:00:00+09:00',
        created_at: '2025-01-01T00:00:00+09:00',
        updated_at: '2025-01-01T00:00:00+09:00',
      };
      mockedTokenService.getCachedTokenBalance.mockResolvedValueOnce(null);
      mockedTokenService.getTokenBalance.mockResolvedValueOnce(mockBalance);
      mockedTokenService.cacheTokenBalance.mockResolvedValueOnce();

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      // Assert
      await waitFor(() => {
        expect(result.current.balance).toEqual(mockBalance);
        expect(result.current.isLoading).toBe(false);
      });
    });

    it('キャッシュがある場合は先にキャッシュを表示する', async () => {
      // Arrange
      const cachedBalance = {
        id: 1,
        tokenable_type: 'App\\Models\\User',
        tokenable_id: 2,
        balance: 400000,
        free_balance: 1000000,
        paid_balance: 0,
        free_balance_reset_at: '2025-01-01T00:00:00+09:00',
        total_consumed: 600000,
        monthly_consumed: 600000,
        monthly_consumed_reset_at: '2025-01-01T00:00:00+09:00',
        created_at: '2025-01-01T00:00:00+09:00',
        updated_at: '2025-01-01T00:00:00+09:00',
      };
      const latestBalance = {
        ...cachedBalance,
        balance: 500000,
      };
      
      // 初回呼び出し時のモック設定（loadBalance内で2回呼ばれる）
      mockedTokenService.getCachedTokenBalance
        .mockResolvedValueOnce(cachedBalance) // 1回目: キャッシュ表示用
        .mockResolvedValueOnce(cachedBalance); // 2回目: エラー判定用（この修正により不要だが念のため）
      mockedTokenService.getTokenBalance.mockResolvedValueOnce(latestBalance);
      mockedTokenService.cacheTokenBalance.mockResolvedValueOnce();

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      // Assert（最終的に最新データが表示される）
      await waitFor(() => {
        expect(result.current.balance).toEqual(latestBalance);
        expect(result.current.isLoading).toBe(false);
      });
      
      // キャッシュも呼ばれたことを確認
      expect(mockedTokenService.getCachedTokenBalance).toHaveBeenCalled();
    });

    it('APIエラー時はキャッシュがあればAlertを表示しない', async () => {
      // Arrange
      const cachedBalance = {
        id: 1,
        tokenable_type: 'App\\Models\\User',
        tokenable_id: 2,
        balance: 400000,
        free_balance: 1000000,
        paid_balance: 0,
        free_balance_reset_at: '2025-01-01T00:00:00+09:00',
        total_consumed: 600000,
        monthly_consumed: 600000,
        monthly_consumed_reset_at: '2025-01-01T00:00:00+09:00',
        created_at: '2025-01-01T00:00:00+09:00',
        updated_at: '2025-01-01T00:00:00+09:00',
      };
      
      // キャッシュ取得を2回モック（1回目: 表示用、2回目: エラー判定用）
      mockedTokenService.getCachedTokenBalance
        .mockResolvedValueOnce(cachedBalance) // 1回目: キャッシュ表示
        .mockResolvedValueOnce(cachedBalance); // 2回目: エラーハンドリング内での確認
      mockedTokenService.getTokenBalance.mockRejectedValueOnce(new Error('Network error'));

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      // Assert
      await waitFor(() => {
        expect(result.current.balance).toEqual(cachedBalance);
        expect(Alert.alert).not.toHaveBeenCalled();
      });
    });

    it('APIエラー時にキャッシュがなければAlertを表示', async () => {
      // Arrange
      mockedTokenService.getCachedTokenBalance.mockResolvedValueOnce(null);
      mockedTokenService.getTokenBalance.mockRejectedValueOnce(new Error('Network error'));

      // Act
      renderHook(() => useTokens('adult'));

      // Assert
      await waitFor(() => {
        expect(Alert.alert).toHaveBeenCalledWith(
          'エラー',
          expect.any(String)
        );
      });
    });
  });

  describe('refreshBalance', () => {
    it('トークン残高を強制更新できる', async () => {
      // Arrange
      const mockBalance = {
        id: 1,
        tokenable_type: 'App\\Models\\User',
        tokenable_id: 2,
        balance: 500000,
        free_balance: 1000000,
        paid_balance: 0,
        free_balance_reset_at: '2025-01-01T00:00:00+09:00',
        total_consumed: 500000,
        monthly_consumed: 500000,
        monthly_consumed_reset_at: '2025-01-01T00:00:00+09:00',
        created_at: '2025-01-01T00:00:00+09:00',
        updated_at: '2025-01-01T00:00:00+09:00',
      };
      mockedTokenService.getCachedTokenBalance.mockResolvedValue(null);
      mockedTokenService.getTokenBalance.mockResolvedValue(mockBalance);
      mockedTokenService.cacheTokenBalance.mockResolvedValue();

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      await waitFor(() => {
        expect(result.current.balance).toEqual(mockBalance);
      });

      const updatedBalance = { ...mockBalance, balance: 600000 };
      mockedTokenService.getTokenBalance.mockResolvedValueOnce(updatedBalance);

      await act(async () => {
        await result.current.refreshBalance();
      });

      // Assert
      expect(result.current.balance).toEqual(updatedBalance);
    });
  });

  describe('loadPackages', () => {
    it('トークンパッケージ一覧を取得できる', async () => {
      // Arrange
      const mockPackages = [
        {
          id: 1,
          name: 'スターターパック',
          token_amount: 500000,
          price: 500,
          stripe_price_id: 'price_xxx',
        },
      ];
      mockedTokenService.getCachedTokenBalance.mockResolvedValue(null);
      mockedTokenService.getTokenBalance.mockResolvedValue({} as any);
      mockedTokenService.cacheTokenBalance.mockResolvedValue();
      mockedTokenService.getTokenPackages.mockResolvedValueOnce(mockPackages);

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      await act(async () => {
        await result.current.loadPackages();
      });

      // Assert
      expect(result.current.packages).toEqual(mockPackages);
    });
  });

  // TODO: 取引履歴API実装後に有効化
  describe.skip('loadHistory', () => {
    it('トークン履歴を取得できる', async () => {
      // Arrange
      const mockHistory = {
        transactions: [
          {
            id: 1,
            type: 'purchase' as const,
            amount: 500000,
            balance_after: 500000,
            description: 'スターターパック購入',
            created_at: '2025-12-07T10:00:00+09:00',
          },
        ],
        pagination: {
          current_page: 1,
          per_page: 20,
          total: 1,
          last_page: 1,
        },
      };
      mockedTokenService.getCachedTokenBalance.mockResolvedValue(null);
      mockedTokenService.getTokenBalance.mockResolvedValue({} as any);
      mockedTokenService.cacheTokenBalance.mockResolvedValue();
      // mockedTokenService.getTokenHistory.mockResolvedValueOnce(mockHistory);

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      await act(async () => {
        await result.current.loadHistory();
      });

      // Assert
      expect(result.current.history).toEqual(mockHistory.transactions);
      expect(result.current.hasMoreHistory).toBe(false);
    });

    it('次ページの履歴を追加読み込みできる', async () => {
      // Arrange
      // const page1History = {
      //   transactions: [{ id: 1 } as any],
      //   pagination: { current_page: 1, per_page: 20, total: 25, last_page: 2 },
      // };
      // const page2History = {
      //   transactions: [{ id: 2 } as any],
      //   pagination: { current_page: 2, per_page: 20, total: 25, last_page: 2 },
      // };
      mockedTokenService.getCachedTokenBalance.mockResolvedValue(null);
      mockedTokenService.getTokenBalance.mockResolvedValue({} as any);
      mockedTokenService.cacheTokenBalance.mockResolvedValue();
      // mockedTokenService.getTokenHistory
      //   .mockResolvedValueOnce(page1History)
      //   .mockResolvedValueOnce(page2History);

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      await act(async () => {
        await result.current.loadHistory(1);
      });

      expect(result.current.history).toHaveLength(1);

      await act(async () => {
        await result.current.loadMoreHistory();
      });

      // Assert
      expect(result.current.history).toHaveLength(2);
      expect(result.current.hasMoreHistory).toBe(false);
    });
  });

  describe('createPurchaseRequest', () => {
    it('購入リクエストを作成できる', async () => {
      // Arrange
      const mockRequest = {
        id: 1,
        package_id: 1,
        package_name: 'スターターパック',
        token_amount: 500000,
        price: 500,
        status: 'pending' as const,
        created_at: '2025-12-07T10:00:00+09:00',
      };
      mockedTokenService.getCachedTokenBalance.mockResolvedValue(null);
      mockedTokenService.getTokenBalance.mockResolvedValue({} as any);
      mockedTokenService.cacheTokenBalance.mockResolvedValue();
      mockedTokenService.createPurchaseRequest.mockResolvedValueOnce(mockRequest);

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      let createdRequest;
      await act(async () => {
        createdRequest = await result.current.createPurchaseRequest(1);
      });

      // Assert
      expect(createdRequest).toEqual(mockRequest);
      expect(result.current.purchaseRequests).toContainEqual(mockRequest);
      expect(Alert.alert).toHaveBeenCalledWith('成功', expect.any(String));
    });
  });

  describe('approvePurchaseRequest', () => {
    it('購入リクエストを承認できる', async () => {
      // Arrange
      mockedTokenService.getCachedTokenBalance.mockResolvedValue(null);
      mockedTokenService.getTokenBalance.mockResolvedValue({} as any);
      mockedTokenService.cacheTokenBalance.mockResolvedValue();
      mockedTokenService.approvePurchaseRequest.mockResolvedValueOnce();
      mockedTokenService.getPurchaseRequests.mockResolvedValueOnce([]);

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      await act(async () => {
        await result.current.approvePurchaseRequest(1);
      });

      // Assert
      expect(mockedTokenService.approvePurchaseRequest).toHaveBeenCalledWith(1);
      expect(Alert.alert).toHaveBeenCalledWith('成功', expect.any(String));
    });
  });

  describe('rejectPurchaseRequest', () => {
    it('購入リクエストを却下できる', async () => {
      // Arrange
      mockedTokenService.getCachedTokenBalance.mockResolvedValue(null);
      mockedTokenService.getTokenBalance.mockResolvedValue({} as any);
      mockedTokenService.cacheTokenBalance.mockResolvedValue();
      mockedTokenService.rejectPurchaseRequest.mockResolvedValueOnce();
      mockedTokenService.getPurchaseRequests.mockResolvedValueOnce([]);

      // Act
      const { result } = renderHook(() => useTokens('adult'));

      await act(async () => {
        await result.current.rejectPurchaseRequest(1);
      });

      // Assert
      expect(mockedTokenService.rejectPurchaseRequest).toHaveBeenCalledWith(1);
      expect(Alert.alert).toHaveBeenCalledWith('成功', expect.any(String));
    });
  });
});
