/**
 * 認証サービス
 * ログイン・新規登録・ログアウト機能を提供
 */
import api from './api';
import * as storage from '../utils/storage';
import { STORAGE_KEYS } from '../utils/constants';
import { AuthResponse } from '../types/api.types';

export const authService = {
  /**
   * ログイン（username + password → Sanctum token）
   * 
   * Phase 2.B-2完了: Laravel API /api/auth/login エンドポイント実装済み
   */
  async login(username: string, password: string): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>('/auth/login', {
      username,  // ✅ Laravel側はusername認証（emailではない）
      password,
    });
    
    const { token, user } = response.data;
    await storage.setItem(STORAGE_KEYS.JWT_TOKEN, token);
    await storage.setItem(STORAGE_KEYS.USER_DATA, JSON.stringify(user));
    
    return response.data;
  },

  /**
   * 新規登録
   * 
   * 注意: 現在Laravel側で登録は停止中（abort(404)）
   * Phase 2.B-3以降で開放予定
   */
  async register(
    email: string,
    password: string,
    name: string
  ): Promise<AuthResponse> {
    const response = await api.post<AuthResponse>('/auth/register', {
      email,
      password,
      name,
    });
    
    const { token, user } = response.data;
    await storage.setItem(STORAGE_KEYS.JWT_TOKEN, token);
    await storage.setItem(STORAGE_KEYS.USER_DATA, JSON.stringify(user));
    
    return response.data;
  },

  /**
   * ログアウト（Sanctumトークン削除）
   * 
   * Phase 2.B-2完了: Laravel API /api/auth/logout エンドポイント実装済み
   */
  async logout(): Promise<void> {
    try {
      // FCMトークンを削除（バックエンドで非アクティブ化）
      const fcmToken = await storage.getItem(STORAGE_KEYS.FCM_TOKEN);
      if (fcmToken) {
        try {
          await api.delete('/profile/fcm-token', {
            data: { device_token: fcmToken },
          });
          console.log('[authService] FCM token deleted successfully');
        } catch (error) {
          console.warn('[authService] FCM token deletion failed, continuing logout', error);
        }
      }

      // Laravel側でトークン削除（エラーでも続行）
      await api.post('/auth/logout');
    } catch (error) {
      console.warn('Logout API failed, clearing local storage anyway', error);
    } finally {
      // ローカルストレージからトークン・ユーザー情報削除
      await storage.removeItem(STORAGE_KEYS.JWT_TOKEN);
      await storage.removeItem(STORAGE_KEYS.USER_DATA);
      await storage.removeItem(STORAGE_KEYS.FCM_TOKEN);
    }
  },

  /**
   * 現在のユーザー情報を取得
   */
  async getCurrentUser() {
    const userData = await storage.getItem(STORAGE_KEYS.USER_DATA);
    return userData ? JSON.parse(userData) : null;
  },

  /**
   * 認証状態を確認
   */
  async isAuthenticated(): Promise<boolean> {
    const token = await storage.getItem(STORAGE_KEYS.JWT_TOKEN);
    console.log('[authService] token from storage:', token, 'type:', typeof token);
    const result = token !== null && token !== undefined && token !== '';
    console.log('[authService] isAuthenticated result:', result, 'type:', typeof result);
    return result;
  },

  /**
   * パスワードリセットリクエスト
   * 
   * メールアドレスを送信し、パスワードリセット用のリンクをメールで送信
   * API: POST /api/auth/forgot-password
   */
  async forgotPassword(email: string): Promise<{ message: string }> {
    const response = await api.post<{ message: string }>('/auth/forgot-password', {
      email,
    });
    return response.data;
  },
};
