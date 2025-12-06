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
}

// シングルトンインスタンスをエクスポート
export const profileService = new ProfileService();
export default profileService;
