/**
 * 認証サービステスト
 * 
 * Phase 2.B-2: モバイル認証機能のユニットテスト
 * - authService.login()
 * - authService.logout()
 * - authService.isAuthenticated()
 * - authService.getCurrentUser()
 */

import { authService } from '../auth.service';
import * as storage from '../../utils/storage';
import api from '../api';

// モック設定
jest.mock('../api');
jest.mock('../../utils/storage');

const mockedApi = api as jest.Mocked<typeof api>;
const mockedStorage = storage as jest.Mocked<typeof storage>;

describe('authService', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('login', () => {
    it('正しいusername/passwordでログインし、トークンとユーザー情報を保存する', async () => {
      // Arrange
      const mockResponse = {
        data: {
          token: 'test-token-123',
          user: {
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            username: 'test_user',
            avatar_url: null,
            created_at: '2025-12-06T00:00:00Z',
          },
        },
      };
      mockedApi.post.mockResolvedValue(mockResponse);
      mockedStorage.setItem.mockResolvedValue();

      // Act
      const result = await authService.login('test_user', 'password123');

      // Assert
      expect(mockedApi.post).toHaveBeenCalledWith('/auth/login', {
        username: 'test_user',
        password: 'password123',
      });
      expect(mockedStorage.setItem).toHaveBeenCalledWith('jwt_token', 'test-token-123');
      expect(mockedStorage.setItem).toHaveBeenCalledWith(
        'user_data',
        JSON.stringify(mockResponse.data.user)
      );
      expect(result).toEqual(mockResponse.data);
    });

    it('APIエラー時は例外をスローする', async () => {
      // Arrange
      const mockError = new Error('Network Error');
      mockedApi.post.mockRejectedValue(mockError);

      // Act & Assert
      await expect(
        authService.login('test_user', 'wrong_password')
      ).rejects.toThrow('Network Error');
    });

    it('401エラー時は認証エラーとして処理される', async () => {
      // Arrange
      const mockError = {
        response: {
          status: 401,
          data: {
            message: 'ユーザー名またはパスワードが間違っています',
          },
        },
      };
      mockedApi.post.mockRejectedValue(mockError);

      // Act & Assert
      await expect(
        authService.login('test_user', 'wrong_password')
      ).rejects.toEqual(mockError);
    });
  });

  describe('register', () => {
    it('新規登録し、トークンとユーザー情報を保存する', async () => {
      // Arrange
      const mockResponse = {
        data: {
          token: 'new-user-token-456',
          user: {
            id: 2,
            name: 'New User',
            email: 'new@example.com',
            username: 'new_user',
            avatar_url: null,
            created_at: '2025-12-06T00:00:00Z',
          },
        },
      };
      mockedApi.post.mockResolvedValue(mockResponse);
      mockedStorage.setItem.mockResolvedValue();

      // Act
      const result = await authService.register('new@example.com', 'password123', 'New User');

      // Assert
      expect(mockedApi.post).toHaveBeenCalledWith('/auth/register', {
        email: 'new@example.com',
        password: 'password123',
        password_confirmation: 'password123',
        username: 'new',
        timezone: 'Asia/Tokyo',
        terms_consent: '0',
        privacy_policy_consent: '0',
      });
      expect(mockedStorage.setItem).toHaveBeenCalledWith('jwt_token', 'new-user-token-456');
      expect(result).toEqual(mockResponse.data);
    });

    it('登録失敗時は例外をスローする', async () => {
      // Arrange
      const mockError = {
        response: {
          status: 422,
          data: {
            errors: {
              email: ['このメールアドレスは既に使用されています'],
            },
          },
        },
      };
      mockedApi.post.mockRejectedValue(mockError);

      // Act & Assert
      await expect(
        authService.register('existing@example.com', 'password123', 'Test')
      ).rejects.toEqual(mockError);
    });
  });

  describe('logout', () => {
    it('ログアウトAPIを呼び出し、ローカルストレージをクリアする', async () => {
      // Arrange
      mockedApi.post.mockResolvedValue({ data: {} });
      mockedStorage.removeItem.mockResolvedValue();

      // Act
      await authService.logout();

      // Assert
      expect(mockedApi.post).toHaveBeenCalledWith('/auth/logout');
      expect(mockedStorage.removeItem).toHaveBeenCalledWith('jwt_token');
      expect(mockedStorage.removeItem).toHaveBeenCalledWith('user_data');
    });

    it('API失敗時もローカルストレージはクリアされる', async () => {
      // Arrange
      const mockError = new Error('Network Error');
      mockedApi.post.mockRejectedValue(mockError);
      mockedStorage.removeItem.mockResolvedValue();

      // Act
      await authService.logout();

      // Assert
      expect(mockedStorage.removeItem).toHaveBeenCalledWith('jwt_token');
      expect(mockedStorage.removeItem).toHaveBeenCalledWith('user_data');
    });
  });

  describe('getCurrentUser', () => {
    it('保存済みユーザー情報を取得できる', async () => {
      // Arrange
      const mockUser = {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        username: 'test_user',
      };
      mockedStorage.getItem.mockResolvedValue(JSON.stringify(mockUser));

      // Act
      const result = await authService.getCurrentUser();

      // Assert
      expect(mockedStorage.getItem).toHaveBeenCalledWith('user_data');
      expect(result).toEqual(mockUser);
    });

    it('ユーザー情報が保存されていない場合nullを返す', async () => {
      // Arrange
      mockedStorage.getItem.mockResolvedValue(null);

      // Act
      const result = await authService.getCurrentUser();

      // Assert
      expect(result).toBeNull();
    });
  });

  describe('isAuthenticated', () => {
    it('トークンが保存されている場合trueを返す', async () => {
      // Arrange
      mockedStorage.getItem.mockResolvedValue('test-token-123');

      // Act
      const result = await authService.isAuthenticated();

      // Assert
      expect(mockedStorage.getItem).toHaveBeenCalledWith('jwt_token');
      expect(result).toBe(true);
    });

    it('トークンが保存されていない場合falseを返す', async () => {
      // Arrange
      mockedStorage.getItem.mockResolvedValue(null);

      // Act
      const result = await authService.isAuthenticated();

      // Assert
      expect(result).toBe(false);
    });

    it('空文字列の場合falseを返す', async () => {
      // Arrange
      mockedStorage.getItem.mockResolvedValue('');

      // Act
      const result = await authService.isAuthenticated();

      // Assert
      expect(result).toBe(false);
    });
  });

  describe('統合シナリオ', () => {
    it('ログイン→ログアウトのフローが正常に動作する', async () => {
      // Arrange
      const mockLoginResponse = {
        data: {
          token: 'login-token',
          user: { id: 1, name: 'Test User', email: 'test@example.com' },
        },
      };
      mockedApi.post.mockResolvedValueOnce(mockLoginResponse); // login
      mockedApi.post.mockResolvedValueOnce({ data: {} }); // logout
      mockedStorage.setItem.mockResolvedValue();
      mockedStorage.removeItem.mockResolvedValue();

      // Act - ログイン
      await authService.login('test_user', 'password123');

      // Act - ログアウト
      await authService.logout();

      // Assert
      expect(mockedStorage.setItem).toHaveBeenCalledWith('jwt_token', 'login-token');
      expect(mockedStorage.removeItem).toHaveBeenCalledWith('jwt_token');
      expect(mockedStorage.removeItem).toHaveBeenCalledWith('user_data');
    });
  });
});
