/**
 * FCM (Firebase Cloud Messaging) カスタムフック
 * アプリ起動時のFCMトークン登録、トークンリフレッシュ処理を管理
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.5
 * @see /home/ktr/mtdev/mobile/src/services/fcm.service.ts
 */
import { useEffect, useState } from 'react';
import messaging from '@react-native-firebase/messaging';
import { fcmService } from '../services/fcm.service';

/**
 * FCMフック戻り値の型
 */
export interface UseFCMReturn {
  /** FCMトークン（初期化中はnull） */
  token: string | null;
  /** パーミッション許可状態 */
  hasPermission: boolean;
  /** 初期化中フラグ */
  isInitializing: boolean;
  /** エラーメッセージ */
  error: string | null;
}

/**
 * FCMトークン登録・リフレッシュを管理するカスタムフック
 * 
 * **使用方法**:
 * ```tsx
 * // App.tsxでログイン後に呼び出し
 * const { token, hasPermission, isInitializing, error } = useFCM();
 * ```
 * 
 * **機能**:
 * 1. アプリ起動時にFCMトークンを取得・登録
 * 2. トークンリフレッシュ時に自動再登録
 * 3. パーミッション状態の監視
 * 
 * **ライフサイクル**:
 * - マウント時: トークン取得・登録
 * - アンマウント時: トークンリフレッシュリスナー解除
 * 
 * @returns {UseFCMReturn} FCM状態
 */
export const useFCM = (): UseFCMReturn => {
  const [token, setToken] = useState<string | null>(null);
  const [hasPermission, setHasPermission] = useState<boolean>(false);
  const [isInitializing, setIsInitializing] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    /**
     * FCMトークンを登録
     */
    const registerFCMToken = async () => {
      try {
        setIsInitializing(true);
        setError(null);

        console.log('[useFCM] Starting FCM token registration...');

        // パーミッションリクエスト + トークン取得 + バックエンド登録
        await fcmService.registerToken();

        // トークン取得（登録成功後に改めて取得）
        const currentToken = await fcmService.getFcmToken();
        
        if (currentToken) {
          setToken(currentToken);
          setHasPermission(true);
          console.log('[useFCM] FCM token registered:', currentToken.substring(0, 20) + '...');
        } else {
          setHasPermission(false);
          console.warn('[useFCM] FCM token not available (permission denied or error)');
        }
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'FCM registration failed';
        console.error('[useFCM] Registration error:', errorMessage);
        setError(errorMessage);
        setHasPermission(false);
      } finally {
        setIsInitializing(false);
      }
    };

    /**
     * トークンリフレッシュイベントリスナー
     * 
     * **発生タイミング**:
     * - アプリ再インストール時
     * - アプリデータクリア時
     * - Firebaseプロジェクト設定変更時
     * 
     * **注意**: onTokenRefresh()はunsubscribe関数を返すため、
     * クリーンアップ時に必ず呼び出す必要があります。
     */
    const unsubscribeTokenRefresh = messaging().onTokenRefresh(async (newToken) => {
      console.log('[useFCM] Token refreshed:', newToken.substring(0, 20) + '...');
      
      try {
        setToken(newToken);
        
        // 新しいトークンをバックエンドに登録（既存トークンは自動的に非アクティブ化）
        await fcmService.registerToken();
        
        console.log('[useFCM] Refreshed token registered successfully');
      } catch (err) {
        const errorMessage = err instanceof Error ? err.message : 'Token refresh registration failed';
        console.error('[useFCM] Token refresh registration error:', errorMessage);
        setError(errorMessage);
      }
    });

    // 初期登録実行
    registerFCMToken();

    // クリーンアップ: トークンリフレッシュリスナー解除
    return () => {
      console.log('[useFCM] Unsubscribing token refresh listener');
      unsubscribeTokenRefresh();
    };
  }, []); // 空の依存配列 = マウント時のみ実行

  return {
    token,
    hasPermission,
    isInitializing,
    error,
  };
};
