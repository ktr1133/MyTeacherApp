/**
 * ユーザーサービス
 * 
 * Laravel API: /api/v1/user/* との通信を担当
 * 全画面共通で使用するユーザー情報（テーマ等）を取得
 * Web版のShareThemeMiddlewareに相当
 */

import api from './api';
import * as storage from '../utils/storage';
import { STORAGE_KEYS } from '../utils/constants';

/**
 * 現在のユーザー情報レスポンス型
 * 
 * GET /api/v1/user/current のレスポンス
 */
export interface CurrentUserResponse {
  success: boolean;
  data: {
    id: number;
    username: string;
    name: string;
    theme: 'adult' | 'child';
    group_id: number | null;
    group_edit_flg: boolean;
  };
}

/**
 * ユーザーサービスクラス
 */
class UserService {
  /**
   * 現在のユーザー情報を取得
   * 
   * Laravel API: GET /api/v1/user/current
   * 認証必須（Sanctum token）
   * 
   * 全画面共通で使用するユーザー情報（id, username, name, theme, group_id, group_edit_flg）を取得
   * Web版のShareThemeMiddlewareに相当
   * 
   * @returns 現在のユーザー情報
   * @throws Error - 認証エラー、ネットワークエラー
   */
  async getCurrentUser(): Promise<CurrentUserResponse['data']> {
    try {
      const response = await api.get<CurrentUserResponse>('/user/current');

      if (!response.data.success || !response.data.data) {
        throw new Error('USER_FETCH_FAILED');
      }

      // ローカルストレージにも保存（オフライン時のフォールバック用）
      await storage.setItem(
        STORAGE_KEYS.CURRENT_USER,
        JSON.stringify(response.data.data)
      );

      return response.data.data;
    } catch (error: any) {
      if (error.response?.status === 401) {
        throw new Error('AUTH_REQUIRED');
      }
      // error.messageがエラーコードの場合、既に変換済みなのでそのまま再スロー
      if (error.message && error.message.startsWith('USER_')) {
        throw error;
      }
      if (!error.response) {
        // ネットワークエラー時はローカルストレージからフォールバック
        const cachedUser = await this.getCachedCurrentUser();
        if (cachedUser) {
          return cachedUser;
        }
        throw new Error('NETWORK_ERROR');
      }
      throw new Error('USER_FETCH_FAILED');
    }
  }

  /**
   * キャッシュされた現在のユーザー情報を取得
   * 
   * @returns キャッシュされたユーザー情報、または null
   */
  async getCachedCurrentUser(): Promise<CurrentUserResponse['data'] | null> {
    try {
      const userData = await storage.getItem(STORAGE_KEYS.CURRENT_USER);
      return userData ? JSON.parse(userData) : null;
    } catch (error) {
      console.error('Failed to parse cached current user data', error);
      return null;
    }
  }

  /**
   * 現在のユーザー情報キャッシュをクリア
   */
  async clearCurrentUserCache(): Promise<void> {
    await storage.removeItem(STORAGE_KEYS.CURRENT_USER);
  }
}

// シングルトンインスタンスをエクスポート
export const userService = new UserService();
export default userService;
