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

  describe('updateProfile', () => {
    it('プロフィール情報を更新できる', async () => {
      const updateData = {
        username: 'updated',
        email: 'updated@example.com',
        name: 'Updated User',
      };

      const updatedUser = { ...mockUser, ...updateData };
      const mockResponse = {
        data: {
          success: true,
          data: updatedUser,
        },
      };
      mockApi.patch.mockResolvedValueOnce(mockResponse);

      const result = await profileService.updateProfile(updateData);

      expect(mockApi.patch).toHaveBeenCalledWith('/profile', updateData);
      expect(result).toEqual(updatedUser);
      expect(mockStorage.setItem).toHaveBeenCalledWith(
        STORAGE_KEYS.USER_DATA,
        JSON.stringify(updatedUser)
      );
    });

    it('認証エラー時にAUTH_REQUIREDエラーを投げる', async () => {
      mockApi.patch.mockRejectedValueOnce({ response: { status: 401 } });

      await expect(
        profileService.updateProfile({ username: 'test' })
      ).rejects.toThrow('AUTH_REQUIRED');
    });

    it('バリデーションエラー時にVALIDATION_ERRORエラーを投げる', async () => {
      mockApi.patch.mockRejectedValueOnce({ response: { status: 422 } });

      await expect(
        profileService.updateProfile({ email: 'invalid' })
      ).rejects.toThrow('VALIDATION_ERROR');
    });
  });

  describe('deleteProfile', () => {
    it('プロフィールを削除できる', async () => {
      const mockResponse = {
        data: {
          success: true,
        },
      };
      mockApi.delete.mockResolvedValueOnce(mockResponse);

      await profileService.deleteProfile();

      expect(mockApi.delete).toHaveBeenCalledWith('/profile');
      expect(mockStorage.removeItem).toHaveBeenCalledWith(STORAGE_KEYS.USER_DATA);
      expect(mockStorage.removeItem).toHaveBeenCalledWith(STORAGE_KEYS.CURRENT_USER);
      expect(mockStorage.removeItem).toHaveBeenCalledWith(STORAGE_KEYS.JWT_TOKEN);
    });

    it('認証エラー時にAUTH_REQUIREDエラーを投げる', async () => {
      mockApi.delete.mockRejectedValueOnce({ response: { status: 401 } });

      await expect(profileService.deleteProfile()).rejects.toThrow('AUTH_REQUIRED');
    });
  });

  describe('getTimezoneSettings', () => {
    it('タイムゾーン設定を取得できる', async () => {
      const mockData = {
        timezone: 'Asia/Tokyo',
        timezones: [
          { value: 'Asia/Tokyo', label: '東京 (UTC+9)' },
        ],
      };
      const mockResponse = {
        data: {
          success: true,
          data: mockData,
        },
      };
      mockApi.get.mockResolvedValueOnce(mockResponse);

      const result = await profileService.getTimezoneSettings();

      expect(mockApi.get).toHaveBeenCalledWith('/profile/timezone');
      expect(result).toEqual(mockData);
    });

    it('認証エラー時にAUTH_REQUIREDエラーを投げる', async () => {
      mockApi.get.mockRejectedValueOnce({ response: { status: 401 } });

      await expect(profileService.getTimezoneSettings()).rejects.toThrow('AUTH_REQUIRED');
    });
  });

  describe('updateTimezone', () => {
    it('タイムゾーンを更新できる', async () => {
      const newTimezone = 'America/New_York';
      const mockResponse = {
        data: {
          success: true,
          data: { timezone: newTimezone },
        },
      };
      mockApi.put.mockResolvedValueOnce(mockResponse);

      const result = await profileService.updateTimezone(newTimezone);

      expect(mockApi.put).toHaveBeenCalledWith('/profile/timezone', { timezone: newTimezone });
      expect(result.timezone).toBe(newTimezone);
    });

    it('バリデーションエラー時にVALIDATION_ERRORエラーを投げる', async () => {
      mockApi.put.mockRejectedValueOnce({ response: { status: 422 } });

      await expect(
        profileService.updateTimezone('Invalid/Timezone')
      ).rejects.toThrow('VALIDATION_ERROR');
    });
  });

  describe('updatePassword', () => {
    it('パスワードを正常に更新できる', async () => {
      const mockResponse = {
        data: {
          success: true,
          message: 'パスワードを更新しました',
        },
      };
      mockApi.put.mockResolvedValueOnce(mockResponse);

      const result = await profileService.updatePassword(
        'oldpassword123',
        'newpassword456',
        'newpassword456'
      );

      expect(mockApi.put).toHaveBeenCalledWith('/profile/password', {
        current_password: 'oldpassword123',
        password: 'newpassword456',
        password_confirmation: 'newpassword456',
      });
      expect(result.message).toBe('パスワードを更新しました');
    });

    it('現在のパスワード不一致時にCURRENT_PASSWORD_INCORRECTエラーを投げる', async () => {
      mockApi.put.mockRejectedValueOnce({
        response: {
          status: 401,
          data: {
            errors: {
              current_password: ['現在のパスワードが正しくありません'],
            },
          },
        },
      });

      await expect(
        profileService.updatePassword('wrongpassword', 'newpassword456', 'newpassword456')
      ).rejects.toThrow('CURRENT_PASSWORD_INCORRECT');
    });

    it('バリデーションエラー時にVALIDATION_ERRORエラーを投げる', async () => {
      mockApi.put.mockRejectedValueOnce({ response: { status: 422 } });

      await expect(
        profileService.updatePassword('oldpassword123', 'short', 'short')
      ).rejects.toThrow('VALIDATION_ERROR');
    });

    it('認証エラー時にAUTH_REQUIREDエラーを投げる', async () => {
      mockApi.put.mockRejectedValueOnce({ response: { status: 401 } });

      await expect(
        profileService.updatePassword('oldpassword123', 'newpassword456', 'newpassword456')
      ).rejects.toThrow('AUTH_REQUIRED');
    });

    it('ネットワークエラー時にNETWORK_ERRORエラーを投げる', async () => {
      mockApi.put.mockRejectedValueOnce({ message: 'Network Error' });

      await expect(
        profileService.updatePassword('oldpassword123', 'newpassword456', 'newpassword456')
      ).rejects.toThrow('NETWORK_ERROR');
    });

    it('サーバーエラー時にPASSWORD_UPDATE_FAILEDエラーを投げる', async () => {
      mockApi.put.mockRejectedValueOnce({ response: { status: 500 } });

      await expect(
        profileService.updatePassword('oldpassword123', 'newpassword456', 'newpassword456')
      ).rejects.toThrow('PASSWORD_UPDATE_FAILED');
    });
  });
});
