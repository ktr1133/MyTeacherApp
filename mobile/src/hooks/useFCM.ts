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
     * FCMトークンを取得（バックエンド登録なし）
     * 
     * ログイン前にトークン取得のみ実行し、ログイン後にFCMContextが登録を実行する
     */
    const initializeFCM = async () => {
      try {
        setIsInitializing(true);
        setError(null);

        console.log('[useFCM] Initializing FCM (no backend registration)...');

        // パーミッションリクエスト + トークン取得のみ（バックエンド登録はしない）
        const hasPermissionGranted = await fcmService.requestPermission();
        
        if (!hasPermissionGranted) {
          console.warn('[useFCM] Permission denied');
          setHasPermission(false);
          return;
        }

        // トークン取得（ローカルストレージに保存）
        const currentToken = await fcmService.getFcmToken();
        
        if (currentToken) {
          setToken(currentToken);
          setHasPermission(true);
          console.log('[useFCM] FCM token obtained (not registered yet):', currentToken.substring(0, 20) + '...');
        } else {
          setHasPermission(false);
          console.warn('[useFCM] FCM token not available (permission denied or error)');
        }
      } catch (err: any) {
        const errorMessage = err instanceof Error ? err.message : 'FCM initialization failed';
        console.error('[useFCM] Initialization error:', errorMessage);
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
     * **注意**: トークンリフレッシュ時もバックエンド登録はFCMContextが実行
     */
    const unsubscribeTokenRefresh = messaging().onTokenRefresh(async (newToken) => {
      console.log('[useFCM] Token refreshed:', newToken.substring(0, 20) + '...');
      
      try {
        setToken(newToken);
        // トークンをローカルストレージに保存（バックエンド登録はFCMContextが実行）
        await fcmService.getFcmToken();
        
        console.log('[useFCM] Refreshed token saved locally');
      } catch (err: any) {
        const errorMessage = err instanceof Error ? err.message : 'Token refresh failed';
        console.error('[useFCM] Token refresh error:', errorMessage);
        setError(errorMessage);
      }
    });

    // 初期化実行（トークン取得のみ、登録はしない）
    initializeFCM();

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
