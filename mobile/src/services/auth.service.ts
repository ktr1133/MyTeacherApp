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
   * Phase 6A: 同意記録機能実装済み
   * Phase 5-2: 13歳未満の場合は保護者同意待ち
   */
  async register(
    email: string,
    password: string,
    name: string,
    privacyConsent: boolean,
    termsConsent: boolean,
    birthdate?: string,
    parentEmail?: string
  ): Promise<AuthResponse> {
    const payload: any = {
      username: email.split('@')[0], // usernameはメールアドレスの@前を使用
      email,
      password,
      password_confirmation: password,
      timezone: 'Asia/Tokyo',
      privacy_policy_consent: privacyConsent ? '1' : '0',
      terms_consent: termsConsent ? '1' : '0',
    };
    
    // Phase 5-2: 生年月日と保護者メールアドレス
    if (birthdate) {
      payload.birthdate = birthdate;
    }
    if (parentEmail) {
      payload.parent_email = parentEmail;
    }
    
    const response = await api.post<AuthResponse>('/auth/register', payload);
    
    // Phase 5-2: 保護者同意待ちの場合はトークン未発行
    if (response.data.requires_parent_consent) {
      return response.data;
    }
    
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
