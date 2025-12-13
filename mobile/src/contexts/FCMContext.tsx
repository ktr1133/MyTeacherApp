/**
 * FCM (Firebase Cloud Messaging) コンテキスト
 * アプリ全体でFCM初期化状態を共有し、ログイン後に自動的にトークン登録を実行
 * 
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.5
 * @see /home/ktr/mtdev/mobile/src/hooks/useFCM.ts
 */
import React, { createContext, useContext, ReactNode, useEffect } from 'react';
import { useFCM, UseFCMReturn } from '../hooks/useFCM';
import { useAuth } from './AuthContext';
import { fcmService } from '../services/fcm.service';

/**
 * FCMコンテキスト型定義
 */
interface FCMContextType extends UseFCMReturn {}

const FCMContext = createContext<FCMContextType | undefined>(undefined);

/**
 * FCMプロバイダー
 * 
 * **機能**:
 * 1. ログイン後に自動的にFCMトークン登録
 * 2. ログアウト時に自動的にFCMトークン削除
 * 3. FCM初期化状態をアプリ全体で共有
 * 
 * **配置場所**: App.tsx で AuthProvider の直下に配置
 * ```tsx
 * <AuthProvider>
 *   <FCMProvider>
 *     <ThemeProvider>
 *       ...
 *     </ThemeProvider>
 *   </FCMProvider>
 * </AuthProvider>
 * ```
 */
export const FCMProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const { isAuthenticated, loading: authLoading } = useAuth();
  const fcmState = useFCM();
  const [wasAuthenticated, setWasAuthenticated] = React.useState<boolean | null>(null);

  /**
   * ログイン状態の変更を監視
   * 
   * **処理フロー**:
   * - ログイン後（isAuthenticated: false → true）: トークン登録（useFCMが自動実行）
   * - ログアウト後（isAuthenticated: true → false）: トークン削除
   */
  useEffect(() => {
    // 認証状態のロード中は何もしない
    if (authLoading) {
      console.log('[FCMContext] Waiting for auth state...');
      return;
    }

    // 初回レンダリング時は前回の認証状態を記録するのみ
    if (wasAuthenticated === null) {
      setWasAuthenticated(isAuthenticated);
      return;
    }

    // ログアウト検出: 認証状態が true → false に変化した場合のみ
    if (wasAuthenticated && !isAuthenticated) {
      console.log('[FCMContext] User logged out, unregistering FCM token...');
      fcmService.unregisterToken().catch((error) => {
        console.error('[FCMContext] Failed to unregister FCM token:', error);
      });
    } else if (!wasAuthenticated && isAuthenticated) {
      // ログイン検出: FCMトークンをバックエンドに登録
      console.log('[FCMContext] User logged in, registering FCM token to backend...');
      fcmService.registerToken().catch((error) => {
        console.error('[FCMContext] Failed to register FCM token:', error);
      });
    }

    // 現在の認証状態を記録
    setWasAuthenticated(isAuthenticated);
  }, [isAuthenticated, authLoading, wasAuthenticated]);

  return (
    <FCMContext.Provider value={fcmState}>
      {children}
    </FCMContext.Provider>
  );
};

/**
 * FCMコンテキストフック
 * 
 * **使用例**:
 * ```tsx
 * const { token, hasPermission, isInitializing, error } = useFCMContext();
 * 
 * if (isInitializing) {
 *   return <Text>FCM初期化中...</Text>;
 * }
 * 
 * if (!hasPermission) {
 *   return <Text>Push通知が無効です</Text>;
 * }
 * ```
 */
export const useFCMContext = (): FCMContextType => {
  const context = useContext(FCMContext);
  if (context === undefined) {
    throw new Error('useFCMContext must be used within an FCMProvider');
  }
  return context;
};
