/**
 * プロフィールサービス
 * 
 * Laravel API: /api/v1/profile/* との通信を担当
 * 
 * @note 暫定実装: getProfile()は /api/v1/profile/edit を使用
 *       本来はプロフィール編集画面用のAPIだが、
 *       テーマ情報取得専用API (/api/v1/user/current) が未実装のため流用
 * @todo Laravel側で専用API作成後、getProfile()をgetCurrentUser()にリネームして切り替え
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
   * 現在のユーザープロフィールを取得
   * 
   * Laravel API: GET /api/v1/profile/edit
   * 認証必須（Sanctum token）
   * 
   * @returns ユーザー情報（theme含む）
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
