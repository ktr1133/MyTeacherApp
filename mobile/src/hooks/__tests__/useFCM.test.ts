/**
 * useFCM Hook Unit Tests
 * FCM初期化カスタムフックのテスト
 * 
 * @see /home/ktr/mtdev/mobile/src/hooks/useFCM.ts
 * @see /home/ktr/mtdev/definitions/mobile/PushNotification.md - 8.2.1 FCMトークン登録
 */
import { renderHook, waitFor, act } from '@testing-library/react-native';
import messaging from '@react-native-firebase/messaging';
import { useFCM } from '../useFCM';
import { fcmService } from '../../services/fcm.service';

// モック設定
jest.mock('@react-native-firebase/messaging');
jest.mock('../../services/fcm.service');

describe('useFCM Hook', () => {
  // モック関数のセットアップ
  const mockOnTokenRefresh = jest.fn();
  const mockUnsubscribe = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();

    // デフォルトモック設定
    (messaging as jest.MockedFunction<typeof messaging>).mockReturnValue({
      onTokenRefresh: mockOnTokenRefresh.mockReturnValue(mockUnsubscribe),
    } as any);

    (fcmService.registerToken as jest.Mock).mockResolvedValue(true);
    (fcmService.getFcmToken as jest.Mock).mockResolvedValue('mock-fcm-token-12345');
    (fcmService.requestPermission as jest.Mock).mockResolvedValue(true);
  });

  describe('初期化処理', () => {
    /**
     * テストケース1: Initializes FCM on mount
     * 
     * **検証項目**:
     * - マウント時にregisterToken()が呼び出されること
     * - isInitializingがfalseになること
     */
    it('should initialize FCM token registration on mount', async () => {
      const { result } = renderHook(() => useFCM());

      // 初期状態確認
      expect(result.current.isInitializing).toBe(true);
      expect(result.current.token).toBeNull();

      // 初期化完了を待機
      await waitFor(() => {
        expect(result.current.isInitializing).toBe(false);
      });

      // registerToken()呼び出し確認
      expect(fcmService.registerToken).toHaveBeenCalledTimes(1);
    });

    /**
     * テストケース2: Updates token state on successful registration
     * 
     * **検証項目**:
     * - トークン登録成功時、tokenステートが更新されること
     * - getFcmToken()で取得したトークンが設定されること
     */
    it('should update token state after successful registration', async () => {
      const mockToken = 'test-fcm-token-abcd1234';
      (fcmService.getFcmToken as jest.Mock).mockResolvedValue(mockToken);

      const { result } = renderHook(() => useFCM());

      // トークン登録完了を待機
      await waitFor(() => {
        expect(result.current.token).toBe(mockToken);
      });

      // ステート確認
      expect(result.current.isInitializing).toBe(false);
      expect(result.current.error).toBeNull();
      expect(fcmService.getFcmToken).toHaveBeenCalledTimes(1);
    });

    /**
     * テストケース3: Sets hasPermission=true on permission grant
     * 
     * **検証項目**:
     * - requestPermission()成功時、hasPermissionがtrueになること
     * - トークン登録が実行されること
     */
    it('should set hasPermission to true when permission is granted', async () => {
      (fcmService.requestPermission as jest.Mock).mockResolvedValue(true);

      const { result } = renderHook(() => useFCM());

      // パーミッション確認完了を待機
      await waitFor(() => {
        expect(result.current.hasPermission).toBe(true);
      });

      // トークン登録確認
      expect(fcmService.registerToken).toHaveBeenCalledTimes(1);
      expect(result.current.error).toBeNull();
    });

    /**
     * テストケース4: Handles permission denial gracefully
     * 
     * **検証項目**:
     * - requestPermission()失敗時、hasPermissionがfalseになること
     * - トークン登録が実行されないこと
     * - エラーステートが設定されること
     */
    it('should handle permission denial gracefully', async () => {
      (fcmService.requestPermission as jest.Mock).mockResolvedValue(false);
      (fcmService.registerToken as jest.Mock).mockRejectedValue(
        new Error('Permission denied')
      );

      const { result } = renderHook(() => useFCM());

      // 初期化完了を待機
      await waitFor(() => {
        expect(result.current.isInitializing).toBe(false);
      });

      // パーミッション拒否状態確認
      expect(result.current.hasPermission).toBe(false);
      expect(result.current.token).toBeNull();
      expect(result.current.error).toMatch(/Permission denied/i);
    });
  });

  describe('トークンリフレッシュリスナー', () => {
    /**
     * テストケース5: Listens for token refresh events
     * 
     * **検証項目**:
     * - onTokenRefresh()リスナーが登録されること
     * - 新しいトークンを受信した際、registerToken()が呼び出されること
     */
    it('should listen for token refresh events and re-register', async () => {
      const newToken = 'refreshed-fcm-token-xyz789';
      let tokenRefreshCallback: ((token: string) => void) | null = null;

      // onTokenRefresh()のモック: コールバック関数を保存
      mockOnTokenRefresh.mockImplementation((callback: (token: string) => void) => {
        tokenRefreshCallback = callback;
        return mockUnsubscribe;
      });

      const { result } = renderHook(() => useFCM());

      // 初期化完了を待機
      await waitFor(() => {
        expect(result.current.isInitializing).toBe(false);
      });

      // onTokenRefresh()が登録されたことを確認
      expect(mockOnTokenRefresh).toHaveBeenCalledTimes(1);
      expect(tokenRefreshCallback).not.toBeNull();

      // トークンリフレッシュイベントをシミュレート
      await act(async () => {
        if (tokenRefreshCallback) {
          tokenRefreshCallback(newToken);
        }
      });

      // 新しいトークンで再登録されることを確認
      await waitFor(() => {
        expect(fcmService.registerToken).toHaveBeenCalledTimes(2); // 初回 + リフレッシュ
      });
    });

    /**
     * テストケース6: Cleans up listener on unmount
     * 
     * **検証項目**:
     * - アンマウント時にunsubscribe()が呼び出されること
     * - メモリリークが発生しないこと
     */
    it('should cleanup token refresh listener on unmount', async () => {
      const { unmount } = renderHook(() => useFCM());

      // 初期化完了を待機
      await waitFor(() => {
        expect(mockOnTokenRefresh).toHaveBeenCalledTimes(1);
      });

      // アンマウント実行
      unmount();

      // unsubscribe()呼び出し確認
      expect(mockUnsubscribe).toHaveBeenCalledTimes(1);
    });
  });

  describe('エラーハンドリング', () => {
    /**
     * テストケース7: Handles registration errors
     * 
     * **検証項目**:
     * - registerToken()失敗時、errorステートに設定されること
     * - hasPermissionがfalseになること
     */
    it('should set error state when registration fails', async () => {
      const errorMessage = 'FCM registration failed: Network error';
      (fcmService.registerToken as jest.Mock).mockRejectedValue(new Error(errorMessage));

      const { result } = renderHook(() => useFCM());

      // エラー設定完了を待機
      await waitFor(() => {
        expect(result.current.error).toBe(errorMessage);
      });

      // エラー状態確認
      expect(result.current.isInitializing).toBe(false);
      expect(result.current.hasPermission).toBe(false);
      expect(result.current.token).toBeNull();
    });

    /**
     * テストケース8: Handles getFcmToken errors
     * 
     * **検証項目**:
     * - getFcmToken()失敗時、errorステートに設定されること
     * - tokenがnullのままであること
     */
    it('should handle getFcmToken errors gracefully', async () => {
      (fcmService.registerToken as jest.Mock).mockResolvedValue(true);
      (fcmService.getFcmToken as jest.Mock).mockRejectedValue(
        new Error('Token retrieval failed')
      );

      const { result } = renderHook(() => useFCM());

      // エラー設定完了を待機
      await waitFor(() => {
        expect(result.current.error).toMatch(/Token retrieval failed/i);
      });

      // ステート確認
      expect(result.current.token).toBeNull();
      expect(result.current.isInitializing).toBe(false);
    });
  });
});
