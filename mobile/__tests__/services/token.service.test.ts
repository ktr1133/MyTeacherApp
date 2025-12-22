/**
 * token.service.ts テスト
 * 
 * トークンサービス（残高取得、パッケージ取得、購入処理）の動作を検証
 */

import MockAdapter from 'axios-mock-adapter';
import AsyncStorage from '@react-native-async-storage/async-storage';
import api from '../../src/services/api';
import * as tokenService from '../../src/services/token.service';
import {
  TokenBalance,
  TokenPackage,
  TokenHistoryStats,
  PurchaseRequest,
} from '../../src/types/token.types';
import { STORAGE_KEYS } from '../../src/utils/constants';

// Axiosモックインスタンス
const mockAxios = new MockAdapter(api);

describe('token.service', () => {
  beforeEach(() => {
    mockAxios.reset();
    AsyncStorage.clear();
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  describe('getTokenBalance()', () => {
    it('トークン残高を取得できる', async () => {
      const mockBalance: TokenBalance = {
        id: 1,
        tokenable_type: 'App\\Models\\User',
        tokenable_id: 123,
        balance: 10000,
        free_balance: 5000,
        paid_balance: 5000,
        free_balance_reset_at: '2025-01-01T00:00:00.000Z',
        total_consumed: 2000,
        monthly_consumed: 500,
        monthly_consumed_reset_at: '2025-01-01T00:00:00.000Z',
        created_at: '2024-12-01T00:00:00.000Z',
        updated_at: '2024-12-14T00:00:00.000Z',
      };

      mockAxios.onGet('/tokens/balance').reply(200, {
        success: true,
        data: {
          balance: mockBalance,
        },
      });

      const result = await tokenService.getTokenBalance();

      expect(result).toEqual(mockBalance);
      expect(result.balance).toBe(10000);
      expect(result.free_balance).toBe(5000);
      expect(result.paid_balance).toBe(5000);
    });

    it('APIエラー時は例外をスローする', async () => {
      mockAxios.onGet('/tokens/balance').reply(500, {
        success: false,
        message: 'Server error',
      });

      await expect(tokenService.getTokenBalance()).rejects.toThrow();
    });
  });

  describe('getTokenHistoryStats()', () => {
    it('トークン履歴統計を取得できる', async () => {
      const mockStats: TokenHistoryStats = {
        monthlyPurchaseAmount: 3000,
        monthlyPurchaseTokens: 30000,
        monthlyUsage: 5000,
      };

      mockAxios.onGet('/tokens/history').reply(200, {
        success: true,
        data: mockStats,
      });

      const result = await tokenService.getTokenHistoryStats();

      expect(result).toEqual(mockStats);
      expect(result.monthlyPurchaseAmount).toBe(3000);
      expect(result.monthlyPurchaseTokens).toBe(30000);
      expect(result.monthlyUsage).toBe(5000);
    });
  });

  describe('getTokenPackages()', () => {
    it('トークンパッケージ一覧を取得できる', async () => {
      const mockPackages: TokenPackage[] = [
        {
          id: 1,
          name: '小パック',
          token_amount: 10000,
          price: 1000,
          stripe_price_id: 'price_test_123',
          description: '10,000トークン',
          discount_rate: null,
          sort_order: 1,
          is_active: true,
          created_at: '2024-12-01T00:00:00.000Z',
        },
        {
          id: 2,
          name: '中パック',
          token_amount: 50000,
          price: 4500,
          stripe_price_id: 'price_test_456',
          description: '50,000トークン（10%お得）',
          discount_rate: 10,
          sort_order: 2,
          is_active: true,
          created_at: '2024-12-01T00:00:00.000Z',
        },
      ];

      mockAxios.onGet('/tokens/packages').reply(200, {
        success: true,
        data: {
          packages: mockPackages,
        },
      });

      const result = await tokenService.getTokenPackages();

      expect(result).toEqual(mockPackages);
      expect(result).toHaveLength(2);
      expect(result[0].name).toBe('小パック');
      expect(result[1].discount_rate).toBe(10);
    });

    it('空の配列が返却される場合も正常に処理できる', async () => {
      mockAxios.onGet('/tokens/packages').reply(200, {
        success: true,
        data: {
          packages: [],
        },
      });

      const result = await tokenService.getTokenPackages();

      expect(result).toEqual([]);
      expect(result).toHaveLength(0);
    });
  });

  describe('createCheckoutSession()', () => {
    it('Stripe Checkout Sessionを作成できる', async () => {
      const packageId = 1;
      const mockSessionUrl = 'https://checkout.stripe.com/pay/cs_test_abc123';

      mockAxios.onPost('/tokens/create-checkout-session', {
        package_id: packageId,
      }).reply(200, {
        success: true,
        data: {
          session_id: 'cs_test_abc123',
          session_url: mockSessionUrl,
        },
      });

      const result = await tokenService.createCheckoutSession(packageId);

      expect(result).toEqual({ url: mockSessionUrl });
      expect(result.url).toContain('stripe.com');
    });

    it('パッケージIDが不正な場合はエラーを返す', async () => {
      const packageId = 9999;

      mockAxios.onPost('/tokens/create-checkout-session').reply(422, {
        success: false,
        message: '指定されたパッケージが見つかりません。',
      });

      await expect(tokenService.createCheckoutSession(packageId)).rejects.toThrow();
    });

    it('Stripe Price IDが未設定の場合はエラーを返す', async () => {
      const packageId = 10;

      mockAxios.onPost('/tokens/create-checkout-session').reply(400, {
        success: false,
        message: 'このパッケージは現在購入できません。',
      });

      await expect(tokenService.createCheckoutSession(packageId)).rejects.toThrow();
    });
  });

  describe('createPurchaseRequest()', () => {
    it('子どもユーザーが購入リクエストを作成できる', async () => {
      const packageId = 1;
      const mockRequest: PurchaseRequest = {
        id: 1,
        package_id: packageId,
        package_name: '小パック',
        token_amount: 10000,
        price: 1000,
        status: 'pending',
        created_at: '2024-12-14T00:00:00.000Z',
      };

      mockAxios.onPost('/tokens/purchase-requests', {
        package_id: packageId,
      }).reply(201, {
        success: true,
        data: mockRequest,
      });

      const result = await tokenService.createPurchaseRequest(packageId);

      expect(result).toEqual(mockRequest);
      expect(result.status).toBe('pending');
      expect(result.package_id).toBe(packageId);
    });
  });

  describe('getPurchaseRequests()', () => {
    it('購入リクエスト一覧を取得できる', async () => {
      const mockRequests: PurchaseRequest[] = [
        {
          id: 1,
          package_id: 1,
          package_name: '小パック',
          token_amount: 10000,
          price: 1000,
          status: 'pending',
          created_at: '2024-12-14T00:00:00.000Z',
        },
        {
          id: 2,
          package_id: 2,
          package_name: '中パック',
          token_amount: 50000,
          price: 4500,
          status: 'approved',
          created_at: '2024-12-13T00:00:00.000Z',
          approved_at: '2024-12-14T00:00:00.000Z',
        },
      ];

      mockAxios.onGet('/tokens/purchase-requests').reply(200, {
        success: true,
        data: {
          requests: mockRequests,
        },
      });

      const result = await tokenService.getPurchaseRequests();

      expect(result).toEqual(mockRequests);
      expect(result).toHaveLength(2);
      expect(result[0].status).toBe('pending');
      expect(result[1].status).toBe('approved');
    });
  });

  describe('approvePurchaseRequest()', () => {
    it('購入リクエストを承認できる', async () => {
      const requestId = 1;

      mockAxios.onPut(`/tokens/purchase-requests/${requestId}/approve`).reply(200, {
        success: true,
        message: '購入リクエストを承認しました。',
      });

      await expect(tokenService.approvePurchaseRequest(requestId)).resolves.toBeUndefined();
    });
  });

  describe('rejectPurchaseRequest()', () => {
    it('購入リクエストを却下できる', async () => {
      const requestId = 1;

      mockAxios.onPut(`/tokens/purchase-requests/${requestId}/reject`).reply(200, {
        success: true,
        message: '購入リクエストを却下しました。',
      });

      await expect(tokenService.rejectPurchaseRequest(requestId)).resolves.toBeUndefined();
    });
  });

  describe('キャッシュ機能', () => {
    describe('cacheTokenBalance()', () => {
      it('トークン残高をキャッシュに保存できる', async () => {
        const mockBalance: TokenBalance = {
          id: 1,
          tokenable_type: 'App\\Models\\User',
          tokenable_id: 123,
          balance: 10000,
          free_balance: 5000,
          paid_balance: 5000,
          free_balance_reset_at: '2025-01-01T00:00:00.000Z',
          total_consumed: 2000,
          monthly_consumed: 500,
          monthly_consumed_reset_at: '2025-01-01T00:00:00.000Z',
          created_at: '2024-12-01T00:00:00.000Z',
          updated_at: '2024-12-14T00:00:00.000Z',
        };

        await tokenService.cacheTokenBalance(mockBalance);

        const cached = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN_BALANCE);
        expect(cached).toBeDefined();
        expect(JSON.parse(cached!)).toEqual(mockBalance);
      });
    });

    describe('getCachedTokenBalance()', () => {
      it('キャッシュされたトークン残高を取得できる', async () => {
        const mockBalance: TokenBalance = {
          id: 1,
          tokenable_type: 'App\\Models\\User',
          tokenable_id: 123,
          balance: 10000,
          free_balance: 5000,
          paid_balance: 5000,
          free_balance_reset_at: '2025-01-01T00:00:00.000Z',
          total_consumed: 2000,
          monthly_consumed: 500,
          monthly_consumed_reset_at: '2025-01-01T00:00:00.000Z',
          created_at: '2024-12-01T00:00:00.000Z',
          updated_at: '2024-12-14T00:00:00.000Z',
        };

        await AsyncStorage.setItem(STORAGE_KEYS.TOKEN_BALANCE, JSON.stringify(mockBalance));

        const result = await tokenService.getCachedTokenBalance();

        expect(result).toEqual(mockBalance);
      });

      it('キャッシュが存在しない場合はnullを返す', async () => {
        const result = await tokenService.getCachedTokenBalance();
        expect(result).toBeNull();
      });
    });

    describe('clearTokenBalanceCache()', () => {
      it('トークン残高キャッシュをクリアできる', async () => {
        const mockBalance: TokenBalance = {
          id: 1,
          tokenable_type: 'App\\Models\\User',
          tokenable_id: 123,
          balance: 10000,
          free_balance: 5000,
          paid_balance: 5000,
          free_balance_reset_at: '2025-01-01T00:00:00.000Z',
          total_consumed: 2000,
          monthly_consumed: 500,
          monthly_consumed_reset_at: '2025-01-01T00:00:00.000Z',
          created_at: '2024-12-01T00:00:00.000Z',
          updated_at: '2024-12-14T00:00:00.000Z',
        };

        await AsyncStorage.setItem(STORAGE_KEYS.TOKEN_BALANCE, JSON.stringify(mockBalance));

        await tokenService.clearTokenBalanceCache();

        const cached = await AsyncStorage.getItem(STORAGE_KEYS.TOKEN_BALANCE);
        expect(cached).toBeNull();
      });
    });
  });
});
