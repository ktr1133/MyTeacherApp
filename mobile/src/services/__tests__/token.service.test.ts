/**
 * TokenService テストスイート
 * 
 * トークンサービスのAPI通信テスト
 */

import * as TokenService from '../../services/token.service';
import api from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { STORAGE_KEYS } from '../../utils/constants';

// APIモック
jest.mock('../../services/api');
const mockedApi = api as jest.Mocked<typeof api>;

describe('TokenService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    AsyncStorage.clear();
  });

  describe('getTokenBalance', () => {
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
      mockedApi.get.mockResolvedValueOnce({
        data: { data: { balance: mockBalance } },
      });

      // Act
      const result = await TokenService.getTokenBalance();

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/tokens/balance');
      expect(result).toEqual(mockBalance);
    });

    it('API エラー時は例外をスロー', async () => {
      // Arrange
      mockedApi.get.mockRejectedValueOnce(new Error('Network error'));

      // Act & Assert
      await expect(TokenService.getTokenBalance()).rejects.toThrow('Network error');
    });
  });

  describe('getTokenHistory', () => {
    it('トークン履歴を取得できる（デフォルトページ）', async () => {
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
      mockedApi.get.mockResolvedValueOnce({
        data: { data: mockHistory },
      });

      // Act
      const result = await TokenService.getTokenHistory();

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/tokens/history', {
        params: { page: 1, per_page: 20 },
      });
      expect(result).toEqual(mockHistory);
    });

    it('ページ番号を指定してトークン履歴を取得できる', async () => {
      // Arrange
      const mockHistory = {
        transactions: [],
        pagination: {
          current_page: 2,
          per_page: 20,
          total: 25,
          last_page: 2,
        },
      };
      mockedApi.get.mockResolvedValueOnce({
        data: { data: mockHistory },
      });

      // Act
      const result = await TokenService.getTokenHistory(2, 20);

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/tokens/history', {
        params: { page: 2, per_page: 20 },
      });
      expect(result.pagination.current_page).toBe(2);
    });
  });

  describe('getTokenPackages', () => {
    it('トークンパッケージ一覧を取得できる', async () => {
      // Arrange
      const mockPackages = [
        {
          id: 1,
          name: 'スターターパック',
          token_amount: 500000,
          price: 500,
          stripe_price_id: 'price_xxx',
          description: '初めての方におすすめ',
        },
        {
          id: 2,
          name: 'スタンダードパック',
          token_amount: 1000000,
          price: 900,
          stripe_price_id: 'price_yyy',
          description: 'お得な基本パック',
          discount_rate: 10,
        },
      ];
      mockedApi.get.mockResolvedValueOnce({
        data: { data: { packages: mockPackages } },
      });

      // Act
      const result = await TokenService.getTokenPackages();

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/tokens/packages');
      expect(result).toEqual(mockPackages);
      expect(result).toHaveLength(2);
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
      mockedApi.post.mockResolvedValueOnce({
        data: { data: mockRequest },
      });

      // Act
      const result = await TokenService.createPurchaseRequest(1);

      // Assert
      expect(mockedApi.post).toHaveBeenCalledWith('/tokens/purchase-requests', {
        package_id: 1,
      });
      expect(result).toEqual(mockRequest);
    });
  });

  describe('getPurchaseRequests', () => {
    it('購入リクエスト一覧を取得できる', async () => {
      // Arrange
      const mockRequests = [
        {
          id: 1,
          package_id: 1,
          package_name: 'スターターパック',
          token_amount: 500000,
          price: 500,
          status: 'pending' as const,
          created_at: '2025-12-07T10:00:00+09:00',
        },
      ];
      mockedApi.get.mockResolvedValueOnce({
        data: { data: { requests: mockRequests } },
      });

      // Act
      const result = await TokenService.getPurchaseRequests();

      // Assert
      expect(mockedApi.get).toHaveBeenCalledWith('/tokens/purchase-requests');
      expect(result).toEqual(mockRequests);
    });
  });

  describe('approvePurchaseRequest', () => {
    it('購入リクエストを承認できる', async () => {
      // Arrange
      mockedApi.put.mockResolvedValueOnce({ data: { success: true } });

      // Act
      await TokenService.approvePurchaseRequest(1);

      // Assert
      expect(mockedApi.put).toHaveBeenCalledWith('/tokens/purchase-requests/1/approve');
    });
  });

  describe('rejectPurchaseRequest', () => {
    it('購入リクエストを却下できる', async () => {
      // Arrange
      mockedApi.put.mockResolvedValueOnce({ data: { success: true } });

      // Act
      await TokenService.rejectPurchaseRequest(1);

      // Assert
      expect(mockedApi.put).toHaveBeenCalledWith('/tokens/purchase-requests/1/reject');
    });
  });

  describe('getCachedTokenBalance', () => {
    it('キャッシュされた残高を取得できる', async () => {
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
      await AsyncStorage.setItem(STORAGE_KEYS.TOKEN_BALANCE, JSON.stringify(mockBalance));

      // Act
      const result = await TokenService.getCachedTokenBalance();

      // Assert
      expect(result).toEqual(mockBalance);
    });

    it('キャッシュが存在しない場合は null を返す', async () => {
      // Act
      const result = await TokenService.getCachedTokenBalance();

      // Assert
      expect(result).toBeNull();
    });

    it('破損したキャッシュの場合は null を返す', async () => {
      // Arrange
      await AsyncStorage.setItem(STORAGE_KEYS.TOKEN_BALANCE, 'invalid json');

      // Act
      const result = await TokenService.getCachedTokenBalance();

      // Assert
      expect(result).toBeNull();
    });
  });

  describe('cacheTokenBalance', () => {
    it('トークン残高をキャッシュに保存できる', async () => {
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

      // Act
      await TokenService.cacheTokenBalance(mockBalance);

      // Assert
      const cached = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN_BALANCE);
      expect(cached).not.toBeNull();
      expect(JSON.parse(cached!)).toEqual(mockBalance);
    });
  });

  describe('clearTokenBalanceCache', () => {
    it('トークン残高キャッシュをクリアできる', async () => {
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
      await AsyncStorage.setItem(STORAGE_KEYS.TOKEN_BALANCE, JSON.stringify(mockBalance));

      // Act
      await TokenService.clearTokenBalanceCache();

      // Assert
      const cached = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN_BALANCE);
      expect(cached).toBeNull();
    });
  });
});
