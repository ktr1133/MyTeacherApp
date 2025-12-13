/**
 * プロフィールサービス
 * 
 * Laravel API: /api/v1/profile/* との通信を担当
 * プロフィール編集画面（アカウント管理）専用のサービス
 */

import api from './api';
import { ProfileResponse } from '../types/user.types';
import * as storage from '../utils/storage';
import { STORAGE_KEYS } from '../utils/constants';

/**
 * プロフィールサービスクラス
 */
class ProfileService {
  /**
   * プロフィール編集用のユーザー情報を取得
   * 
   * Laravel API: GET /api/v1/profile/edit
   * 認証必須（Sanctum token）
   * 
   * プロフィール編集画面で使用する詳細なユーザー情報を取得
   * 
   * @returns ユーザー情報（email, avatar_path, bio, timezone等を含む）
   * @throws Error - 認証エラー、ネットワークエラー
   */
  async getProfile(): Promise<ProfileResponse['data']> {
    try {
      const response = await api.get<ProfileResponse>('/profile/edit');

      if (!response.data.success || !response.data.data) {
        throw new Error('PROFILE_FETCH_FAILED');
      }

      // ローカルストレージにも保存（オフライン時のフォールバック用）
      await storage.setItem(
        STORAGE_KEYS.USER_DATA,
        JSON.stringify(response.data.data)
      );

      return response.data.data;
    } catch (error: any) {
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      // error.messageがエラーコードの場合、既に変換済みなのでそのまま再スロー
      if (error.message && error.message.startsWith('PROFILE_')) {
        throw error;
      }
      if (!error.response) {
        // ネットワークエラー時はローカルストレージからフォールバック
        const cachedUser = await this.getCachedProfile();
        if (cachedUser) {
          return cachedUser;
        }
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('PROFILE_FETCH_FAILED');
    }
  }

  /**
   * キャッシュされたプロフィール情報を取得
   * 
   * @returns キャッシュされたユーザー情報、または null
   */
  async getCachedProfile(): Promise<ProfileResponse['data'] | null> {
    try {
      const userData = await storage.getItem(STORAGE_KEYS.USER_DATA);
      return userData ? JSON.parse(userData) : null;
    } catch (error) {
      console.error('Failed to parse cached user data', error);
      return null;
    }
  }

  /**
   * プロフィールキャッシュをクリア
   */
  async clearProfileCache(): Promise<void> {
    await storage.removeItem(STORAGE_KEYS.USER_DATA);
  }

  /**
   * プロフィール情報を更新
   * 
   * Laravel API: PATCH /api/v1/profile
   * 認証必須（Sanctum token）
   * 
   * @param data - 更新するプロフィール情報（username, email, name）
   * @returns 更新されたユーザー情報
   * @throws Error - 認証エラー、バリデーションエラー、ネットワークエラー
   */
  async updateProfile(data: {
    username?: string;
    email?: string;
    name?: string;
  }): Promise<ProfileResponse['data']> {
    try {
      const response = await api.patch<ProfileResponse>('/profile', data);

      if (!response.data.success || !response.data.data) {
        throw new Error('PROFILE_UPDATE_FAILED');
      }

      // キャッシュ更新
      await storage.setItem(
        STORAGE_KEYS.USER_DATA,
        JSON.stringify(response.data.data)
      );

      return response.data.data;
    } catch (error: any) {
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 422) {
        throw new Error('VALIDATION_ERROR');
      }
      if (error.message && error.message.startsWith('PROFILE_')) {
        throw error;
      }
      if (!error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('PROFILE_UPDATE_FAILED');
    }
  }

  /**
   * プロフィール（アカウント）を削除
   * 
   * Laravel API: DELETE /api/v1/profile
   * 認証必須（Sanctum token）
   * 
   * @throws Error - 認証エラー、ネットワークエラー
   */
  async deleteProfile(): Promise<void> {
    try {
      const response = await api.delete('/profile');

      if (!response.data.success) {
        throw new Error('PROFILE_DELETE_FAILED');
      }

      // キャッシュクリア
      await this.clearProfileCache();
      await storage.removeItem(STORAGE_KEYS.CURRENT_USER);
      await storage.removeItem(STORAGE_KEYS.JWT_TOKEN);
    } catch (error: any) {
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && error.message.startsWith('PROFILE_')) {
        throw error;
      }
      if (!error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('PROFILE_DELETE_FAILED');
    }
  }

  /**
   * タイムゾーン設定を取得
   * 
   * Laravel API: GET /api/v1/profile/timezone
   * 認証必須（Sanctum token）
   * 
   * @returns タイムゾーン情報（timezone, timezones配列）
   * @throws Error - 認証エラー、ネットワークエラー
   */
  async getTimezoneSettings(): Promise<{
    timezone: string;
    timezones: Array<{ value: string; label: string }>;
  }> {
    try {
      const response = await api.get('/profile/timezone');

      if (!response.data.success || !response.data.data) {
        throw new Error('TIMEZONE_FETCH_FAILED');
      }

      const data = response.data.data;
      
      // API Response:
      // {
      //   current_timezone: "Asia/Tokyo",
      //   current_timezone_name: "東京",
      //   timezones_grouped: { "アジア": { "Asia/Tokyo": "東京 (UTC+9)", ... }, ... }
      // }
      
      // timezones_groupedをフラット化
      const timezones: Array<{ value: string; label: string }> = [];
      const grouped = data.timezones_grouped || {};
      
      Object.keys(grouped).forEach(region => {
        Object.entries(grouped[region] as Record<string, string>).forEach(([value, label]) => {
          timezones.push({ value, label: label as string });
        });
      });

      return {
        timezone: data.current_timezone || 'Asia/Tokyo',
        timezones: timezones.length > 0 ? timezones : [
          { value: 'Asia/Tokyo', label: '東京 (UTC+9)' }
        ],
      };
    } catch (error: any) {
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.message && error.message.startsWith('TIMEZONE_')) {
        throw error;
      }
      if (!error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('TIMEZONE_FETCH_FAILED');
    }
  }

  /**
   * タイムゾーンを更新
   * 
   * Laravel API: PUT /api/v1/profile/timezone
   * 認証必須（Sanctum token）
   * 
   * @param timezone - タイムゾーン文字列（例: 'Asia/Tokyo'）
   * @returns 更新後のタイムゾーン情報
   * @throws Error - 認証エラー、バリデーションエラー、ネットワークエラー
   */
  async updateTimezone(timezone: string): Promise<{ timezone: string }> {
    try {
      const response = await api.put('/profile/timezone', { timezone });

      if (!response.data.success || !response.data.data) {
        throw new Error('TIMEZONE_UPDATE_FAILED');
      }

      return response.data.data;
    } catch (error: any) {
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 422) {
        throw new Error('VALIDATION_ERROR');
      }
      if (error.message && error.message.startsWith('TIMEZONE_')) {
        throw error;
      }
      if (!error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('TIMEZONE_UPDATE_FAILED');
    }
  }

  /**
   * パスワードを更新
   * 
   * Laravel API: PUT /api/v1/profile/password
   * 認証必須（Sanctum token）
   * 
   * @param currentPassword - 現在のパスワード
   * @param newPassword - 新しいパスワード
   * @param confirmPassword - 新しいパスワード（確認用）
   * @returns 成功メッセージ
   * @throws Error - 認証エラー、バリデーションエラー、ネットワークエラー
   */
  async updatePassword(
    currentPassword: string,
    newPassword: string,
    confirmPassword: string
  ): Promise<{ message: string }> {
    try {
      const response = await api.put('/profile/password', {
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      });

      if (!response.data.success) {
        throw new Error('PASSWORD_UPDATE_FAILED');
      }

      return {
        message: response.data.message || 'パスワードを更新しました',
      };
    } catch (error: any) {
      if (error.response?.status === 401) {
        // 現在のパスワードが間違っている、または認証エラー
        const errorMessage = error.response?.data?.errors?.current_password?.[0];
        if (errorMessage) {
          throw new Error('CURRENT_PASSWORD_INCORRECT');
        }
        throw new Error('AUTH_REQUIRED');
      }
      if (error.response?.status === 422) {
        // バリデーションエラー（パスワード長、確認不一致等）
        throw new Error('VALIDATION_ERROR');
      }
      if (error.message && error.message.startsWith('PASSWORD_')) {
        throw error;
      }
      if (!error.response) {
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('PASSWORD_UPDATE_FAILED');
    }
  }
}

// シングルトンインスタンスをエクスポート
export const profileService = new ProfileService();
export default profileService;

