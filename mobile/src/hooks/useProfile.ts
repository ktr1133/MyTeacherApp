/**
 * useProfile Hook
 * 
 * プロフィール管理（取得・更新・削除）のロジックを提供
 * ProfileServiceを使用してLaravel APIと通信
 */

import { useState, useCallback } from 'react';
import { profileService } from '../services/profile.service';
import { ProfileResponse } from '../types/user.types';
import { getErrorMessage } from '../utils/errorMessages';

/**
 * プロフィール管理Hook
 * 
 * 提供機能:
 * - プロフィール情報取得（getProfile）
 * - プロフィール更新（updateProfile）
 * - プロフィール削除（deleteProfile）
 * - タイムゾーン設定取得（getTimezoneSettings）
 * - タイムゾーン更新（updateTimezone）
 * - エラーハンドリング（テーマ対応メッセージ）
 * 
 * @param theme - テーマ（'adult' | 'child'）
 * @returns プロフィール管理関数、状態、エラー
 */
export const useProfile = (theme: 'adult' | 'child' = 'adult') => {
  const [profile, setProfile] = useState<ProfileResponse['data'] | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  /**
   * プロフィール情報を取得
   */
  const getProfile = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const data = await profileService.getProfile();
      setProfile(data);
      return data;
    } catch (err: any) {
      const errorMessage = getErrorMessage(err.message || 'UNKNOWN_ERROR', theme);
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [theme]);

  /**
   * プロフィール情報を更新
   * 
   * @param data - 更新するプロフィール情報（username, email, name, theme）
   * @returns 更新されたプロフィール情報
   */
  const updateProfile = useCallback(
    async (data: {
      username?: string;
      email?: string;
      name?: string;
      theme?: 'adult' | 'child';
    }): Promise<ProfileResponse['data']> => {
      setIsLoading(true);
      setError(null);

    try {
      // themeのみの更新の場合は専用メソッドを使用
      let updatedProfile: ProfileResponse['data'];
      if (data.theme && Object.keys(data).length === 1) {
        updatedProfile = await profileService.updateTheme(data.theme);
      } else {
        updatedProfile = await profileService.updateProfile(data);
      }
      setProfile(updatedProfile);
      return updatedProfile;
    } catch (err: any) {
      const errorMessage = getErrorMessage(err.message || 'UNKNOWN_ERROR', theme);
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [theme]);

  /**
   * プロフィール（アカウント）を削除
   */
  const deleteProfile = useCallback(async (): Promise<void> => {
    setIsLoading(true);
    setError(null);

    try {
      await profileService.deleteProfile();
      setProfile(null);
    } catch (err: any) {
      const errorMessage = getErrorMessage(err.message || 'UNKNOWN_ERROR', theme);
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [theme]);

  /**
   * タイムゾーン設定を取得
   * 
   * @returns タイムゾーン情報
   */
  const getTimezoneSettings = useCallback(async (): Promise<{
    timezone: string;
    timezones: Array<{ value: string; label: string }>;
  }> => {
    setIsLoading(true);
    setError(null);

    try {
      const data = await profileService.getTimezoneSettings();
      return data;
    } catch (err: any) {
      const errorMessage = getErrorMessage(err.message || 'UNKNOWN_ERROR', theme);
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [theme]);

  /**
   * タイムゾーンを更新
   * 
   * @param timezone - タイムゾーン文字列
   * @returns 更新後のタイムゾーン情報
   */
  const updateTimezone = useCallback(async (timezone: string): Promise<{ timezone: string }> => {
    setIsLoading(true);
    setError(null);

    try {
      const data = await profileService.updateTimezone(timezone);
      return data;
    } catch (err: any) {
      const errorMessage = getErrorMessage(err.message || 'UNKNOWN_ERROR', theme);
      setError(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [theme]);

  /**
   * パスワードを更新
   * 
   * @param currentPassword - 現在のパスワード
   * @param newPassword - 新しいパスワード
   * @param confirmPassword - 新しいパスワード（確認用）
   * @returns 成功メッセージ
   */
  const updatePassword = useCallback(
    async (
      currentPassword: string,
      newPassword: string,
      confirmPassword: string
    ): Promise<{ message: string }> => {
      setIsLoading(true);
      setError(null);

      try {
        const result = await profileService.updatePassword(
          currentPassword,
          newPassword,
          confirmPassword
        );
        return result;
      } catch (err: any) {
        const errorMessage = getErrorMessage(err.message || 'UNKNOWN_ERROR', theme);
        setError(errorMessage);
        throw err;
      } finally {
        setIsLoading(false);
      }
    },
    [theme]
  );

  /**
   * キャッシュされたプロフィール情報を取得
   */
  const getCachedProfile = useCallback(async () => {
    try {
      const cachedData = await profileService.getCachedProfile();
      if (cachedData) {
        setProfile(cachedData);
      }
      return cachedData;
    } catch (err: any) {
      console.error('Failed to get cached profile', err);
      return null;
    }
  }, []);

  /**
   * プロフィールキャッシュをクリア
   */
  const clearProfileCache = useCallback(async () => {
    try {
      await profileService.clearProfileCache();
      setProfile(null);
    } catch (err: any) {
      console.error('Failed to clear profile cache', err);
    }
  }, []);

  return {
    profile,
    isLoading,
    error,
    getProfile,
    updateProfile,
    deleteProfile,
    getTimezoneSettings,
    updateTimezone,
    updatePassword,
    getCachedProfile,
    clearProfileCache,
  };
};

export default useProfile;
