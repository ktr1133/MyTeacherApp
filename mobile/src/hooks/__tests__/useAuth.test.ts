/**
 * useAuthフックテスト
 * 
 * Phase 2.B-2: 認証状態管理フックのテスト
 * - 初期状態の確認
 * - ログイン処理
 * - ログアウト処理
 * - 登録処理
 * - エラーハンドリング
 */

import { renderHook, act, waitFor } from '@testing-library/react-native';
import { useAuth } from '../useAuth';
import { authService } from '../../services/auth.service';

// モック設定
jest.mock('../../services/auth.service');
jest.mock('@react-navigation/native', () => ({
  useNavigation: () => ({
    replace: jest.fn(),
    navigate: jest.fn(),
  }),
}));

const mockedAuthService = authService as jest.Mocked<typeof authService>;

describe('useAuth', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('初期化', () => {
    it('初期状態はloading=true、user=nullである', () => {
      // Arrange
      mockedAuthService.isAuthenticated.mockResolvedValue(false);
      mockedAuthService.getCurrentUser.mockResolvedValue(null);

      // Act
      const { result } = renderHook(() => useAuth());

      // Assert
      expect(result.current.loading).toBe(true);
      expect(result.current.user).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
    });

    it('マウント時にcheckAuth()を呼び出す', async () => {
      // Arrange
      const mockUser = {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        username: 'test_user',
        avatar_url: null,
        created_at: '2025-12-06T00:00:00Z',
      };
      mockedAuthService.isAuthenticated.mockResolvedValue(true);
      mockedAuthService.getCurrentUser.mockResolvedValue(mockUser);

      // Act
      const { result } = renderHook(() => useAuth());

      // Assert
      await waitFor(() => {
        expect(result.current.loading).toBe(false);
        expect(result.current.isAuthenticated).toBe(true);
        expect(result.current.user).toEqual(mockUser);
      });
    });

    it('認証情報がない場合はloading=false、user=nullになる', async () => {
      // Arrange
      mockedAuthService.isAuthenticated.mockResolvedValue(false);
      mockedAuthService.getCurrentUser.mockResolvedValue(null);

      // Act
      const { result } = renderHook(() => useAuth());

      // Assert
      await waitFor(() => {
        expect(result.current.loading).toBe(false);
        expect(result.current.isAuthenticated).toBe(false);
        expect(result.current.user).toBeNull();
      });
    });
  });

  describe('login', () => {
    it('正常にログインできる', async () => {
      // Arrange
      const mockLoginResponse = {
        token: 'test-token',
        user: {
          id: 1,
          name: 'Test User',
          email: 'test@example.com',
          username: 'test_user',
          avatar_url: null,
          created_at: '2025-12-06T00:00:00Z',
        },
      };
      mockedAuthService.isAuthenticated.mockResolvedValue(false);
      mockedAuthService.getCurrentUser.mockResolvedValue(null);
      mockedAuthService.login.mockResolvedValue(mockLoginResponse);

      // Act
      const { result } = renderHook(() => useAuth());
      await waitFor(() => expect(result.current.loading).toBe(false));

      await act(async () => {
        await result.current.login('test_user', 'password123');
      });

      // Assert
      expect(mockedAuthService.login).toHaveBeenCalledWith('test_user', 'password123');
      await waitFor(() => {
        expect(result.current.user).toEqual(mockLoginResponse.user);
        expect(result.current.isAuthenticated).toBe(true);
      });
    });

    it('ログイン失敗時にエラー情報を返す', async () => {
      // Arrange
      const mockError = new Error('ユーザー名またはパスワードが間違っています');
      mockedAuthService.isAuthenticated.mockResolvedValue(false);
      mockedAuthService.getCurrentUser.mockResolvedValue(null);
      mockedAuthService.login.mockRejectedValue(mockError);

      // Act
      const { result } = renderHook(() => useAuth());
      await waitFor(() => expect(result.current.loading).toBe(false));

      let loginResult;
      await act(async () => {
        loginResult = await result.current.login('test_user', 'wrong_password');
      });

      // Assert - フックはエラーをキャッチして{success: false}を返す
      expect(loginResult).toEqual({
        success: false,
        error: 'ログインに失敗しました',
      });
      expect(result.current.isAuthenticated).toBe(false);
      expect(result.current.user).toBeNull();
    });

    it('ログイン中はloading状態にならない（UIがloading管理）', async () => {
      // Arrange
      mockedAuthService.isAuthenticated.mockResolvedValue(false);
      mockedAuthService.getCurrentUser.mockResolvedValue(null);
      mockedAuthService.login.mockImplementation(
        () => new Promise((resolve) => setTimeout(resolve, 100))
      );

      // Act
      const { result } = renderHook(() => useAuth());
      await waitFor(() => expect(result.current.loading).toBe(false));

      const loginPromise = act(async () => {
        await result.current.login('test_user', 'password123');
      });

      // Assert - フック自体のloadingは変化しない（画面側でローディング管理）
      expect(result.current.loading).toBe(false);
      await loginPromise;
    });
  });

  describe('register', () => {
    it('正常に新規登録できる', async () => {
      // Arrange
      const mockRegisterResponse = {
        token: 'new-token',
        user: {
          id: 2,
          name: 'New User',
          email: 'new@example.com',
          username: 'new_user',
          avatar_url: null,
          created_at: '2025-12-06T00:00:00Z',
        },
      };
      mockedAuthService.isAuthenticated.mockResolvedValue(false);
      mockedAuthService.getCurrentUser.mockResolvedValue(null);
      mockedAuthService.register.mockResolvedValue(mockRegisterResponse);

      // Act
      const { result } = renderHook(() => useAuth());
      await waitFor(() => expect(result.current.loading).toBe(false));

      await act(async () => {
        await result.current.register('new@example.com', 'password123', 'New User');
      });

      // Assert
      expect(mockedAuthService.register).toHaveBeenCalledWith(
        'new@example.com',
        'password123',
        'New User'
      );
      await waitFor(() => {
        expect(result.current.user).toEqual(mockRegisterResponse.user);
        expect(result.current.isAuthenticated).toBe(true);
      });
    });

    it('登録失敗時にエラー情報を返す', async () => {
      // Arrange
      const mockError = new Error('このメールアドレスは既に使用されています');
      mockedAuthService.isAuthenticated.mockResolvedValue(false);
      mockedAuthService.getCurrentUser.mockResolvedValue(null);
      mockedAuthService.register.mockRejectedValue(mockError);

      // Act
      const { result } = renderHook(() => useAuth());
      await waitFor(() => expect(result.current.loading).toBe(false));

      let registerResult;
      await act(async () => {
        registerResult = await result.current.register('existing@example.com', 'password123', 'Test');
      });

      // Assert - フックはエラーをキャッチして{success: false}を返す
      expect(registerResult).toEqual({
        success: false,
        error: '登録に失敗しました',
      });
      expect(result.current.isAuthenticated).toBe(false);
      expect(result.current.user).toBeNull();
    });
  });

  describe('logout', () => {
    it('正常にログアウトできる', async () => {
      // Arrange
      const mockUser = {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        username: 'test_user',
        avatar_url: null,
        created_at: '2025-12-06T00:00:00Z',
      };
      mockedAuthService.isAuthenticated.mockResolvedValue(true);
      mockedAuthService.getCurrentUser.mockResolvedValue(mockUser);
      mockedAuthService.logout.mockResolvedValue();

      // Act
      const { result } = renderHook(() => useAuth());
      await waitFor(() => expect(result.current.user).toEqual(mockUser));

      await act(async () => {
        await result.current.logout();
      });

      // Assert
      expect(mockedAuthService.logout).toHaveBeenCalled();
      expect(result.current.user).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
    });

    it('ログアウト失敗時もローカル状態はクリアされる', async () => {
      // Arrange
      const mockUser = {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        username: 'test_user',
        avatar_url: null,
        created_at: '2025-12-06T00:00:00Z',
      };
      mockedAuthService.isAuthenticated.mockResolvedValue(true);
      mockedAuthService.getCurrentUser.mockResolvedValue(mockUser);
      mockedAuthService.logout.mockRejectedValue(new Error('Network Error'));

      // Act
      const { result } = renderHook(() => useAuth());
      await waitFor(() => expect(result.current.user).toEqual(mockUser));

      await act(async () => {
        await result.current.logout();
      });

      // Assert - エラーでもfinallyでローカル状態はクリアされる
      expect(result.current.user).toBeNull();
      expect(result.current.isAuthenticated).toBe(false);
    });
  });

  describe('統合シナリオ', () => {
    it('未認証→ログイン→ログアウトのフロー', async () => {
      // Arrange
      const mockLoginResponse = {
        token: 'test-token',
        user: {
          id: 1,
          name: 'Test User',
          email: 'test@example.com',
          username: 'test_user',
          avatar_url: null,
          created_at: '2025-12-06T00:00:00Z',
        },
      };
      mockedAuthService.isAuthenticated.mockResolvedValue(false);
      mockedAuthService.getCurrentUser.mockResolvedValue(null);
      mockedAuthService.login.mockResolvedValue(mockLoginResponse);
      mockedAuthService.logout.mockResolvedValue();

      // Act - 初期状態（未認証）
      const { result } = renderHook(() => useAuth());
      await waitFor(() => expect(result.current.loading).toBe(false));
      expect(result.current.isAuthenticated).toBe(false);

      // Act - ログイン
      await act(async () => {
        await result.current.login('test_user', 'password123');
      });

      // Assert - ログイン後
      await waitFor(() => {
        expect(result.current.isAuthenticated).toBe(true);
        expect(result.current.user).toEqual(mockLoginResponse.user);
      });

      // Act - ログアウト
      await act(async () => {
        await result.current.logout();
      });

      // Assert - ログアウト後
      expect(result.current.isAuthenticated).toBe(false);
      expect(result.current.user).toBeNull();
    });
  });
});
