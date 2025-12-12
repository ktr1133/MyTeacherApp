/**
 * Firebase Cloud Messaging (FCM) サービス
 * デバイストークンの取得・登録・削除を管理
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.5
 * @see /home/ktr/mtdev/docs/api/openapi.yaml - POST/DELETE /profile/fcm-token
 */
import messaging from '@react-native-firebase/messaging';
import { Platform } from 'react-native';
import api from './api';
import * as storage from '../utils/storage';
import { STORAGE_KEYS } from '../utils/constants';

/**
 * FCMパーミッション状態（Firebase Messaging AuthorizationStatusを再エクスポート）
 */
export const AuthorizationStatus = messaging.AuthorizationStatus;

/**
 * FCMサービスインターフェース
 */
export interface IFcmService {
  requestPermission(): Promise<boolean>;
  getFcmToken(): Promise<string | null>;
  registerToken(): Promise<void>;
  unregisterToken(): Promise<void>;
  getDeviceInfo(): Promise<{ deviceName: string; appVersion: string }>;
}

/**
 * FCMサービス実装
 */
class FcmService implements IFcmService {
  /**
   * Push通知パーミッションをリクエスト
   * 
   * @returns true: 許可された, false: 拒否された
   * 
   * **iOS**: 明示的な許可ダイアログ表示
   * **Android**: Android 13以降のみ許可ダイアログ表示（12以前は自動許可）
   */
  async requestPermission(): Promise<boolean> {
    try {
      const authStatus = await messaging().requestPermission();
      
      console.log('[FCM] Permission status:', authStatus);
      
      // iOS: AUTHORIZED (1) または PROVISIONAL (2)
      // Android: AUTHORIZED (1)
      const enabled = 
        authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
        authStatus === messaging.AuthorizationStatus.PROVISIONAL;

      if (enabled) {
        console.log('[FCM] Permission granted');
      } else {
        console.log('[FCM] Permission denied');
      }

      return enabled;
    } catch (error) {
      console.error('[FCM] Permission request failed:', error);
      return false;
    }
  }

  /**
   * FCMデバイストークンを取得
   * 
   * @returns FCMトークン文字列、取得失敗時はnull
   * 
   * **注意**: パーミッション許可後に呼び出すこと
   */
  async getFcmToken(): Promise<string | null> {
    try {
      const token = await messaging().getToken();
      
      if (token) {
        console.log('[FCM] Token obtained:', token.substring(0, 20) + '...');
        // ローカルストレージに保存（ログアウト時の削除用）
        await storage.setItem(STORAGE_KEYS.FCM_TOKEN, token);
        return token;
      } else {
        console.warn('[FCM] Token is empty');
        return null;
      }
    } catch (error) {
      console.error('[FCM] Token fetch failed:', error);
      return null;
    }
  }

  /**
   * デバイス情報を取得
   * 
   * @returns { deviceName, appVersion }
   */
  async getDeviceInfo(): Promise<{ deviceName: string; appVersion: string }> {
    // デバイス名の取得（react-native-device-infoを使用する場合はインストール必要）
    // 現在は簡易実装
    const deviceName = Platform.select({
      ios: 'iOS Device',
      android: 'Android Device',
      default: 'Unknown Device',
    });

    // アプリバージョン（package.jsonから取得する場合はインポート必要）
    const appVersion = '1.0.0'; // TODO: package.jsonから動的取得

    return { deviceName, appVersion };
  }

  /**
   * FCMトークンをバックエンドに登録
   * 
   * **API**: POST /api/v1/profile/fcm-token
   * 
   * **処理フロー**:
   * 1. パーミッションリクエスト
   * 2. FCMトークン取得
   * 3. バックエンドAPI呼び出し（既存トークンは自動的に更新）
   * 
   * **エラー処理**:
   * - パーミッション拒否: 警告ログのみ（例外なし）
   * - トークン取得失敗: 警告ログのみ（例外なし）
   * - API呼び出し失敗: 例外スロー
   */
  async registerToken(): Promise<void> {
    try {
      // 1. パーミッションリクエスト
      const hasPermission = await this.requestPermission();
      
      if (!hasPermission) {
        console.warn('[FCM] Registration skipped: permission denied');
        // パーミッション拒否は致命的エラーではないため、例外をスローしない
        return;
      }

      // 2. FCMトークン取得
      const token = await this.getFcmToken();
      
      if (!token) {
        console.warn('[FCM] Registration skipped: token not available');
        return;
      }

      // 3. デバイス情報取得
      const { deviceName, appVersion } = await this.getDeviceInfo();

      // 4. バックエンドに登録
      const deviceType = Platform.OS === 'ios' ? 'ios' : 'android';
      
      console.log('[FCM] Registering token to backend...', {
        deviceType,
        deviceName,
        appVersion,
      });

      await api.post('/profile/fcm-token', {
        device_token: token,
        device_type: deviceType,
        device_name: deviceName,
        app_version: appVersion,
      });

      console.log('[FCM] Token registered successfully');
    } catch (error) {
      console.error('[FCM] Token registration failed:', error);
      throw error;
    }
  }

  /**
   * FCMトークンをバックエンドから削除（非アクティブ化）
   * 
   * **API**: DELETE /api/v1/profile/fcm-token
   * 
   * **呼び出しタイミング**: ログアウト時
   * 
   * **処理フロー**:
   * 1. ローカルストレージからトークン取得
   * 2. バックエンドAPI呼び出し（is_active = FALSE に更新）
   * 3. ローカルストレージから削除
   * 
   * **エラー処理**:
   * - トークンが存在しない: 警告ログのみ（例外なし）
   * - API呼び出し失敗: エラーログのみ（ローカルストレージは削除）
   */
  async unregisterToken(): Promise<void> {
    try {
      // 1. ローカルストレージからトークン取得
      const token = await storage.getItem(STORAGE_KEYS.FCM_TOKEN);
      
      if (!token) {
        console.warn('[FCM] Unregister skipped: token not found in storage');
        return;
      }

      console.log('[FCM] Unregistering token from backend...');

      // 2. バックエンドから削除
      try {
        await api.delete('/profile/fcm-token', {
          data: {
            device_token: token,
          },
        });
        console.log('[FCM] Token unregistered from backend');
      } catch (error) {
        // バックエンドエラーはログのみ（ローカルストレージは削除）
        console.error('[FCM] Backend unregister failed:', error);
      }

      // 3. ローカルストレージから削除
      await storage.removeItem(STORAGE_KEYS.FCM_TOKEN);
      console.log('[FCM] Token removed from local storage');
    } catch (error) {
      console.error('[FCM] Token unregister failed:', error);
      // ローカルストレージ削除は試みる
      await storage.removeItem(STORAGE_KEYS.FCM_TOKEN).catch(() => {});
    }
  }
}

// シングルトンインスタンスをエクスポート
export const fcmService = new FcmService();
