/**
 * useProfile Hook テスト
 */

import { renderHook, act, waitFor } from '@testing-library/react-native';
import { useProfile } from '../useProfile';
import { profileService } from '../../services/profile.service';
import { User } from '../../types/user.types';

// ProfileServiceのモック
jest.mock('../../services/profile.service');
const mockProfileService = profileService as jest.Mocked<typeof profileService>;

describe('useProfile', () => {
  const mockProfile: User = {
    id: 1,
    username: 'testuser',
    name: 'Test User',
    email: 'test@example.com',
    avatar_path: null,
    timezone: 'Asia/Tokyo',
    theme: 'adult',
    group_id: null,
    group_edit_flg: false,
    auth_provider: 'sanctum',
    cognito_sub: null,
    created_at: '2025-12-06T00:00:00.000Z',
    updated_at: '2025-12-06T00:00:00.000Z',
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('getProfile', () => {
    it('プロフィール情報を取得できる', async () => {
      mockProfileService.getProfile.mockResolvedValue(mockProfile);

      const { result } = renderHook(() => useProfile('adult'));

      expect(result.current.isLoading).toBe(false);

      let profile: User | undefined;
      await act(async () => {
        profile = await result.current.getProfile();
      });

      expect(mockProfileService.getProfile).toHaveBeenCalled();
      expect(profile).toEqual(mockProfile);
      expect(result.current.profile).toEqual(mockProfile);
      expect(result.current.error).toBeNull();
    });

    it('エラー時にエラーメッセージを設定する', async () => {
      mockProfileService.getProfile.mockRejectedValue(new Error('PROFILE_FETCH_FAILED'));

      const { result } = renderHook(() => useProfile('adult'));

      await act(async () => {
        try {
          await result.current.getProfile();
        } catch (err) {
          // エラーは想定内
        }
      });

      expect(result.current.error).not.toBeNull();
      expect(result.current.profile).toBeNull();
    });

    it('child themeで適切なエラーメッセージを返す', async () => {
      mockProfileService.getProfile.mockRejectedValue(new Error('PROFILE_FETCH_FAILED'));

      const { result } = renderHook(() => useProfile('child'));

      await act(async () => {
        try {
          await result.current.getProfile();
        } catch (err) {
          // エラーは想定内
        }
      });

      expect(result.current.error).toContain('じぶんのじょうほう');
    });
  });

  describe('updateProfile', () => {
    it('プロフィール情報を更新できる', async () => {
      const updateData = {
        username: 'updated',
        email: 'updated@example.com',
        name: 'Updated User',
      };
      const updatedProfile = { ...mockProfile, ...updateData };

      mockProfileService.updateProfile.mockResolvedValue(updatedProfile);

      const { result } = renderHook(() => useProfile('adult'));

      let profile: User | undefined;
      await act(async () => {
        profile = await result.current.updateProfile(updateData);
      });

      expect(mockProfileService.updateProfile).toHaveBeenCalledWith(updateData);
      expect(profile).toEqual(updatedProfile);
      expect(result.current.profile).toEqual(updatedProfile);
    });

    it('更新エラー時にエラーメッセージを設定する', async () => {
      mockProfileService.updateProfile.mockRejectedValue(new Error('PROFILE_UPDATE_FAILED'));

      const { result } = renderHook(() => useProfile('adult'));

      await act(async () => {
        try {
          await result.current.updateProfile({ username: 'test' });
        } catch (err) {
          // エラーは想定内
        }
      });

      expect(result.current.error).not.toBeNull();
    });
  });

  describe('deleteProfile', () => {
    it('プロフィールを削除できる', async () => {
      mockProfileService.deleteProfile.mockResolvedValue();

      const { result } = renderHook(() => useProfile('adult'));

      await act(async () => {
        await result.current.deleteProfile();
      });

      expect(mockProfileService.deleteProfile).toHaveBeenCalled();
      expect(result.current.profile).toBeNull();
    });

    it('削除エラー時にエラーメッセージを設定する', async () => {
      mockProfileService.deleteProfile.mockRejectedValue(new Error('PROFILE_DELETE_FAILED'));

      const { result } = renderHook(() => useProfile('adult'));

      await act(async () => {
        try {
          await result.current.deleteProfile();
        } catch (err) {
          // エラーは想定内
        }
      });

      expect(result.current.error).not.toBeNull();
    });
  });

  describe('getTimezoneSettings', () => {
    it('タイムゾーン設定を取得できる', async () => {
      const mockTimezoneData = {
        timezone: 'Asia/Tokyo',
        timezones: [
          { value: 'Asia/Tokyo', label: '東京' },
          { value: 'America/New_York', label: 'ニューヨーク' },
        ],
      };

      mockProfileService.getTimezoneSettings.mockResolvedValue(mockTimezoneData);

      const { result } = renderHook(() => useProfile('adult'));

      let data: typeof mockTimezoneData | undefined;
      await act(async () => {
        data = await result.current.getTimezoneSettings();
      });

      expect(mockProfileService.getTimezoneSettings).toHaveBeenCalled();
      expect(data).toEqual(mockTimezoneData);
    });
  });

  describe('updateTimezone', () => {
    it('タイムゾーンを更新できる', async () => {
      const newTimezone = 'America/New_York';
      mockProfileService.updateTimezone.mockResolvedValue({ timezone: newTimezone });

      const { result } = renderHook(() => useProfile('adult'));

      let data: { timezone: string } | undefined;
      await act(async () => {
        data = await result.current.updateTimezone(newTimezone);
      });

      expect(mockProfileService.updateTimezone).toHaveBeenCalledWith(newTimezone);
      expect(data?.timezone).toBe(newTimezone);
    });
  });

  describe('getCachedProfile', () => {
    it('キャッシュされたプロフィールを取得できる', async () => {
      mockProfileService.getCachedProfile.mockResolvedValue(mockProfile);

      const { result } = renderHook(() => useProfile('adult'));

      await act(async () => {
        await result.current.getCachedProfile();
      });

      expect(mockProfileService.getCachedProfile).toHaveBeenCalled();
      expect(result.current.profile).toEqual(mockProfile);
    });
  });

  describe('clearProfileCache', () => {
    it('プロフィールキャッシュをクリアできる', async () => {
      mockProfileService.clearProfileCache.mockResolvedValue();
      mockProfileService.getProfile.mockResolvedValue(mockProfile);

      const { result } = renderHook(() => useProfile('adult'));

      // 最初にプロフィールを取得
      await act(async () => {
        await result.current.getProfile();
      });

      expect(result.current.profile).toEqual(mockProfile);

      // キャッシュクリア
      await act(async () => {
        await result.current.clearProfileCache();
      });

      expect(mockProfileService.clearProfileCache).toHaveBeenCalled();
      expect(result.current.profile).toBeNull();
    });
  });

  describe('updatePassword', () => {
    it('パスワードを更新できる', async () => {
      const mockResponse = { message: 'パスワードを更新しました' };
      mockProfileService.updatePassword.mockResolvedValue(mockResponse);

      const { result } = renderHook(() => useProfile('adult'));

      let response: { message: string } | undefined;
      await act(async () => {
        response = await result.current.updatePassword(
          'oldpassword123',
          'newpassword456',
          'newpassword456'
        );
      });

      expect(mockProfileService.updatePassword).toHaveBeenCalledWith(
        'oldpassword123',
        'newpassword456',
        'newpassword456'
      );
      expect(response).toEqual(mockResponse);
      expect(result.current.error).toBeNull();
    });

    it('現在のパスワード不一致時にエラーメッセージを設定する', async () => {
      mockProfileService.updatePassword.mockRejectedValue(
        new Error('CURRENT_PASSWORD_INCORRECT')
      );

      const { result } = renderHook(() => useProfile('adult'));

      await act(async () => {
        try {
          await result.current.updatePassword('wrongpassword', 'newpassword456', 'newpassword456');
        } catch (err) {
          // エラーは想定内
        }
      });

      expect(result.current.error).not.toBeNull();
      expect(result.current.error).toContain('パスワード');
    });

    it('child themeで適切なエラーメッセージを返す', async () => {
      mockProfileService.updatePassword.mockRejectedValue(
        new Error('CURRENT_PASSWORD_INCORRECT')
      );

      const { result } = renderHook(() => useProfile('child'));

      await act(async () => {
        try {
          await result.current.updatePassword('wrongpassword', 'newpassword456', 'newpassword456');
        } catch (err) {
          // エラーは想定内
        }
      });

      expect(result.current.error).toContain('パスワード');
    });
  });
});
