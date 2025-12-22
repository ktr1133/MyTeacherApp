/**
 * FCMContext Unit Tests
 * FCMコンテキストのテスト（ログイン/ログアウト時のトークン管理）
 * 
 * @see /home/ktr/mtdev/mobile/src/contexts/FCMContext.tsx
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - Phase 2.B-7.5
 */
import React from 'react';
import { renderHook, waitFor, act } from '@testing-library/react-native';
import { FCMProvider, useFCMContext } from '../FCMContext';
import { useFCM } from '../../hooks/useFCM';
import { fcmService } from '../../services/fcm.service';

// モック設定
jest.mock('../../hooks/useFCM');
jest.mock('../../services/fcm.service');
jest.mock('../AuthContext', () => ({
  useAuth: jest.fn(),
}));

import { useAuth } from '../AuthContext';

describe('FCMContext', () => {
  // モックデータ
  const mockFCMState = {
    token: 'mock-fcm-token',
    hasPermission: true,
    isInitializing: false,
    error: null,
  };

  beforeEach(() => {
    jest.clearAllMocks();

    // デフォルトモック設定
    (useFCM as jest.Mock).mockReturnValue(mockFCMState);
    (fcmService.registerToken as jest.Mock).mockResolvedValue(undefined);
    (fcmService.unregisterToken as jest.Mock).mockResolvedValue(undefined);
  });

  describe('Provider初期化', () => {
    /**
     * テストケース1: Provides FCM state to children
     * 
     * **検証項目**:
     * - FCMProviderが子コンポーネントにFCMステートを提供すること
     * - useFCMContext()でステートが取得できること
     */
    it('should provide FCM state from useFCM hook', async () => {
      (useAuth as jest.Mock).mockReturnValue({
        isAuthenticated: true,
        loading: false,
      });

      const wrapper = ({ children }: { children: React.ReactNode }) => (
        <FCMProvider>{children}</FCMProvider>
      );

      const { result } = renderHook(() => useFCMContext(), { wrapper });

      // FCMステート確認
      expect(result.current.token).toBe('mock-fcm-token');
      expect(result.current.hasPermission).toBe(true);
      expect(result.current.isInitializing).toBe(false);
      expect(result.current.error).toBeNull();
    });

    /**
     * テストケース2: Throws error when used outside provider
     * 
     * **検証項目**:
     * - FCMProvider外でuseFCMContext()を呼び出すとエラーがスローされること
     */
    it('should throw error when useFCMContext is used outside FCMProvider', () => {
      // エラーをコンソールに出力しないようにする
      const consoleError = jest.spyOn(console, 'error').mockImplementation();

      expect(() => {
        renderHook(() => useFCMContext());
      }).toThrow('useFCMContext must be used within an FCMProvider');

      consoleError.mockRestore();
    });
  });

  describe('ログイン時のトークン登録', () => {
    /**
     * テストケース3: Does not register token during auth loading
     * 
     * **検証項目**:
     * - 認証状態のロード中はトークン登録処理が実行されないこと
     */
    it('should not register token while auth is loading', async () => {
      (useAuth as jest.Mock).mockReturnValue({
        isAuthenticated: false,
        loading: true, // ロード中
      });

      const wrapper = ({ children }: { children: React.ReactNode }) => (
        <FCMProvider>{children}</FCMProvider>
      );

      renderHook(() => useFCMContext(), { wrapper });

      // トークン削除が呼び出されないことを確認
      await waitFor(() => {
        expect(fcmService.unregisterToken).not.toHaveBeenCalled();
      });
    });

    /**
     * テストケース4: Registers token on login (false → true)
     * 
     * **検証項目**:
     * - ログイン時（isAuthenticated: false → true）、useFCMが自動的にトークン登録すること
     * - FCMProviderは登録処理を実行しないこと（useFCMに委譲）
     */
    it('should log when user logs in (token registration handled by useFCM)', async () => {
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation();

      // 初期状態: 未ログイン
      const { rerender } = renderHook(
        ({ isAuth }) => {
          (useAuth as jest.Mock).mockReturnValue({
            isAuthenticated: isAuth,
            loading: false,
          });
          return useFCMContext();
        },
        {
          wrapper: ({ children }: { children: React.ReactNode }) => (
            <FCMProvider>{children}</FCMProvider>
          ),
          initialProps: { isAuth: false },
        }
      );

      // ログイン状態に変更
      await act(async () => {
        rerender({ isAuth: true });
      });

      // ログ出力確認（トークン登録実行）
      await waitFor(() => {
        expect(consoleSpy).toHaveBeenCalledWith(
          '[FCMContext] User logged in, registering FCM token to backend...'
        );
      });

      // registerToken()が呼び出されたこと
      expect(fcmService.registerToken).toHaveBeenCalledTimes(1);

      consoleSpy.mockRestore();
    });
  });

  describe('ログアウト時のトークン削除', () => {
    /**
     * テストケース5: Unregisters token on logout (true → false)
     * 
     * **検証項目**:
     * - ログアウト時（isAuthenticated: true → false）、fcmService.unregisterToken()が呼び出されること
     * - トークンがバックエンドから削除されること
     */
    it('should unregister FCM token when user logs out', async () => {
      // 初期状態: ログイン済み
      const mockUseAuth = useAuth as jest.Mock;
      mockUseAuth.mockReturnValue({
        isAuthenticated: true,
        loading: false,
      });

      const { rerender } = renderHook(() => useFCMContext(), {
        wrapper: ({ children }: { children: React.ReactNode }) => (
          <FCMProvider>{children}</FCMProvider>
        ),
      });

      // 初回レンダリング完了を待つ
      await waitFor(() => {
        expect(mockUseAuth).toHaveBeenCalled();
      });

      // ログアウト状態に変更
      mockUseAuth.mockReturnValue({
        isAuthenticated: false,
        loading: false,
      });

      await act(async () => {
        rerender({});
      });

      // トークン削除確認
      await waitFor(() => {
        expect(fcmService.unregisterToken).toHaveBeenCalledTimes(1);
      });
    });

    /**
     * テストケース6: Handles unregister token errors gracefully
     * 
     * **検証項目**:
     * - unregisterToken()エラー時、エラーログが出力されること
     * - アプリがクラッシュしないこと
     */
    it('should handle unregisterToken errors gracefully', async () => {
      const consoleError = jest.spyOn(console, 'error').mockImplementation();
      const mockError = new Error('Network error during token unregistration');
      (fcmService.unregisterToken as jest.Mock).mockRejectedValue(mockError);

      // 初期状態: ログイン済み
      const mockUseAuth = useAuth as jest.Mock;
      mockUseAuth.mockReturnValue({
        isAuthenticated: true,
        loading: false,
      });

      const { rerender } = renderHook(() => useFCMContext(), {
        wrapper: ({ children }: { children: React.ReactNode }) => (
          <FCMProvider>{children}</FCMProvider>
        ),
      });

      // 初回レンダリング完了を待つ
      await waitFor(() => {
        expect(mockUseAuth).toHaveBeenCalled();
      });

      // ログアウト状態に変更
      mockUseAuth.mockReturnValue({
        isAuthenticated: false,
        loading: false,
      });

      await act(async () => {
        rerender({});
      });

      // エラーログ確認
      await waitFor(() => {
        expect(consoleError).toHaveBeenCalledWith(
          '[FCMContext] Failed to unregister FCM token:',
          mockError
        );
      });

      consoleError.mockRestore();
    });
  });

  describe('エッジケース', () => {
    /**
     * テストケース7: Does not unregister when staying logged out
     * 
     * **検証項目**:
     * - ログアウト状態が継続する場合、unregisterToken()が呼び出されないこと
     */
    it('should not call unregisterToken when staying logged out', async () => {
      // 初期状態: ログアウト
      const { rerender } = renderHook(
        ({ isAuth }) => {
          (useAuth as jest.Mock).mockReturnValue({
            isAuthenticated: isAuth,
            loading: false,
          });
          return useFCMContext();
        },
        {
          wrapper: ({ children }: { children: React.ReactNode }) => (
            <FCMProvider>{children}</FCMProvider>
          ),
          initialProps: { isAuth: false },
        }
      );

      // ログアウト状態を維持（false → false）
      await act(async () => {
        rerender({ isAuth: false });
      });

      // unregisterToken()が呼び出されないこと
      expect(fcmService.unregisterToken).not.toHaveBeenCalled();
    });

    /**
     * テストケース8: Does not unregister when staying logged in
     * 
     * **検証項目**:
     * - ログイン状態が継続する場合、unregisterToken()が呼び出されないこと
     */
    it('should not call unregisterToken when staying logged in', async () => {
      // 初期状態: ログイン済み
      const { rerender } = renderHook(
        ({ isAuth }) => {
          (useAuth as jest.Mock).mockReturnValue({
            isAuthenticated: isAuth,
            loading: false,
          });
          return useFCMContext();
        },
        {
          wrapper: ({ children }: { children: React.ReactNode }) => (
            <FCMProvider>{children}</FCMProvider>
          ),
          initialProps: { isAuth: true },
        }
      );

      // ログイン状態を維持（true → true）
      await act(async () => {
        rerender({ isAuth: true });
      });

      // unregisterToken()が呼び出されないこと
      expect(fcmService.unregisterToken).not.toHaveBeenCalled();
    });
  });
});
