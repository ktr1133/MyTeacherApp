/**
 * プロフィールサービステスト
 */

import profileService from '../profile.service';
import api from '../api';
import * as storage from '../../utils/storage';
import { STORAGE_KEYS } from '../../utils/constants';
import { User } from '../../types/user.types';

// モック化
jest.mock('../api');
jest.mock('../../utils/storage');

const mockApi = api as jest.Mocked<typeof api>;
const mockStorage = storage as jest.Mocked<typeof storage>;

describe('ProfileService', () => {
  const mockUser: User = {
    id: 1,
    username: 'testuser',
    name: 'Test User',
    email: 'test@example.com',
    avatar_path: null,
    bio: 'Test bio',
    timezone: 'Asia/Tokyo',
    theme: 'adult',
    group_id: null,
    group_edit_flg: false,
    auth_provider: 'breeze',
    cognito_sub: null,
    created_at: '2025-12-06T00:00:00.000Z',
    updated_at: '2025-12-06T00:00:00.000Z',
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getProfile', () => {
    it('プロフィール情報を正常に取得できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          data: mockUser,
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await profileService.getProfile();

      expect(mockApi.get).toHaveBeenCalledWith('/profile/edit');
      expect(result).toEqual(mockUser);
      expect(mockStorage.setItem).toHaveBeenCalledWith(
        STORAGE_KEYS.USER_DATA,
        JSON.stringify(mockUser)
      );
    });

    it('認証エラー時に適切なエラーコードをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({
        response: { status: 401 },
      });

      await expect(profileService.getProfile()).rejects.toThrow('AUTH_REQUIRED');
    });

    it('ネットワークエラー時にキャッシュからフォールバックする', async () => {
      mockApi.get.mockRejectedValueOnce({ message: 'Network Error' });
      mockStorage.getItem.mockResolvedValueOnce(JSON.stringify(mockUser));

      const result = await profileService.getProfile();

      expect(result).toEqual(mockUser);
    });

    it('ネットワークエラーでキャッシュもない場合にエラーをスローする', async () => {
      mockApi.get.mockRejectedValueOnce({ message: 'Network Error' });
      mockStorage.getItem.mockResolvedValueOnce(null);

      await expect(profileService.getProfile()).rejects.toThrow('NETWORK_ERROR');
    });

    it('success=falseの場合にエラーコードをスローする', async () => {
      const mockResponse = {
        data: {
          success: false,
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      await expect(profileService.getProfile()).rejects.toThrow('PROFILE_FETCH_FAILED');
    });
  });

  describe('getCachedProfile', () => {
    it('キャッシュされたプロフィール情報を取得できる', async () => {
      mockStorage.getItem.mockResolvedValueOnce(JSON.stringify(mockUser));

      const result = await profileService.getCachedProfile();

      expect(mockStorage.getItem).toHaveBeenCalledWith(STORAGE_KEYS.USER_DATA);
      expect(result).toEqual(mockUser);
    });

    it('キャッシュが存在しない場合にnullを返す', async () => {
      mockStorage.getItem.mockResolvedValueOnce(null);

      const result = await profileService.getCachedProfile();

      expect(result).toBeNull();
    });

    it('パースエラー時にnullを返す', async () => {
      mockStorage.getItem.mockResolvedValueOnce('invalid json');

      const result = await profileService.getCachedProfile();

      expect(result).toBeNull();
    });
  });

  describe('clearProfileCache', () => {
    it('プロフィールキャッシュをクリアできる', async () => {
      await profileService.clearProfileCache();

      expect(mockStorage.removeItem).toHaveBeenCalledWith(STORAGE_KEYS.USER_DATA);
    });
  });
});
