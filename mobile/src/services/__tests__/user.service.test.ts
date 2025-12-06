/**
 * ユーザーサービステスト
 */

import userService, { CurrentUserResponse } from '../user.service';
import api from '../api';
import * as storage from '../../utils/storage';
import { STORAGE_KEYS } from '../../utils/constants';

// モック化
jest.mock('../api');
jest.mock('../../utils/storage');

const mockApi = api as jest.Mocked<typeof api>;
const mockStorage = storage as jest.Mocked<typeof storage>;

describe('UserService', () => {
  const mockCurrentUser: CurrentUserResponse['data'] = {
    id: 1,
    username: 'testuser',
    name: 'Test User',
    theme: 'adult',
    group_id: null,
    group_edit_flg: false,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getCurrentUser', () => {
    it('現在のユーザー情報を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: mockCurrentUser,
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await userService.getCurrentUser();

      expect(mockApi.get).toHaveBeenCalledWith('/user/current');
      expect(result).toEqual(mockCurrentUser);
      expect(mockStorage.setItem).toHaveBeenCalledWith(
        STORAGE_KEYS.CURRENT_USER,
        JSON.stringify(mockCurrentUser)
      );
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(userService.getCurrentUser()).rejects.toThrow('AUTH_REQUIRED');
    });

    it('ネットワークエラー時にキャッシュからフォールバックする', async () => {
      mockApi.get.mockRejectedValueOnce({ message: 'Network Error' });
      mockStorage.getItem.mockResolvedValueOnce(JSON.stringify(mockCurrentUser));

      const result = await userService.getCurrentUser();

      expect(result).toEqual(mockCurrentUser);
    });

    it('ネットワークエラーでキャッシュもない場合にエラーをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({ message: 'Network Error' });
      mockStorage.getItem.mockResolvedValueOnce(null);

      await expect(userService.getCurrentUser()).rejects.toThrow('NETWORK_ERROR');
    });

    it('success=falseの場合にエラーコードをスローする', async () => {
      const mockResponse = {
        data: {
          success: false,
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      await expect(userService.getCurrentUser()).rejects.toThrow('USER_FETCH_FAILED');
    });

    it('子供向けテーマのユーザー情報を取得できる', async () => {
      const childUser = { ...mockCurrentUser, theme: 'child' as const };
      const mockResponse = {
        data: {
          success: true,
          data: childUser,
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await userService.getCurrentUser();

      expect(result.theme).toBe('child');
    });

    it('グループ所属ユーザーの情報を取得できる', async () => {
      const groupUser = {
        ...mockCurrentUser,
        group_id: 5,
        group_edit_flg: true,
      };
      const mockResponse = {
        data: {
          success: true,
          data: groupUser,
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await userService.getCurrentUser();

      expect(result.group_id).toBe(5);
      expect(result.group_edit_flg).toBe(true);
    });
  });

  describe('getCachedCurrentUser', () => {
    it('キャッシュされたユーザー情報を取得できる', async () => {
      mockStorage.getItem.mockResolvedValueOnce(JSON.stringify(mockCurrentUser));

      const result = await userService.getCachedCurrentUser();

      expect(mockStorage.getItem).toHaveBeenCalledWith(STORAGE_KEYS.CURRENT_USER);
      expect(result).toEqual(mockCurrentUser);
    });

    it('キャッシュが存在しない場合にnullを返す', async () => {
      mockStorage.getItem.mockResolvedValueOnce(null);

      const result = await userService.getCachedCurrentUser();

      expect(result).toBeNull();
    });

    it('パースエラー時にnullを返す', async () => {
      mockStorage.getItem.mockResolvedValueOnce('invalid json');

      const result = await userService.getCachedCurrentUser();

      expect(result).toBeNull();
    });
  });

  describe('clearCurrentUserCache', () => {
    it('ユーザー情報キャッシュをクリアできる', async () => {
      await userService.clearCurrentUserCache();

      expect(mockStorage.removeItem).toHaveBeenCalledWith(STORAGE_KEYS.CURRENT_USER);
    });
  });
});
