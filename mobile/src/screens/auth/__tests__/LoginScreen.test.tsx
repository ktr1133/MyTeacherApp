/**
 * LoginScreenコンポーネントテスト
 * 
 * Phase 2.B-2: ログイン画面のUIテスト
 * - 初期表示
 * - 入力フィールド
 * - バリデーション
 * - ログイン処理
 * - エラー表示
 */

import { render, fireEvent, waitFor } from '@testing-library/react-native';
import LoginScreen from '../LoginScreen';
import { AuthProvider } from '../../../contexts/AuthContext';
import { AvatarProvider } from '../../../contexts/AvatarContext';
import { ThemeProvider } from '../../../contexts/ThemeContext';
import { authService } from '../../../services/auth.service';

// モック設定
jest.mock('../../../services/auth.service');
jest.mock('@react-navigation/native', () => ({
  useNavigation: () => ({
    replace: jest.fn(),
    navigate: jest.fn(),
  }),
}));
// @expo/vector-icons は jest-expo が自動モック

const mockedAuthService = authService as jest.Mocked<typeof authService>;

describe('LoginScreen', () => {
  const mockNavigate = jest.fn();
  const mockNavigation = {
    navigate: mockNavigate,
    replace: jest.fn(),
    goBack: jest.fn(),
    reset: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    
    // authServiceモック
    mockedAuthService.login.mockResolvedValue({
      token: 'dummy-token',
      user: {
        id: 1,
        name: 'testuser',
        email: 'test@example.com',
        avatar_url: undefined,
        created_at: '2025-12-01T00:00:00Z',
      },
    });
    mockedAuthService.isAuthenticated.mockResolvedValue(false);
  });

  const renderComponent = () => render(
    <AuthProvider>
      <ThemeProvider>
        <AvatarProvider>
          <LoginScreen navigation={mockNavigation} />
        </AvatarProvider>
      </ThemeProvider>
    </AuthProvider>
  );

  describe('初期表示', () => {
    it('ログインフォームが正しく表示される', () => {
      // Act
      const { getByPlaceholderText, getByText } = renderComponent();

      // Assert
      expect(getByText('MyTeacher')).toBeTruthy();
      expect(getByText('ログイン')).toBeTruthy();
      expect(getByPlaceholderText('ユーザー名')).toBeTruthy();
      expect(getByPlaceholderText('パスワード')).toBeTruthy();
      expect(getByText('アカウントをお持ちでないですか？')).toBeTruthy();
      expect(getByText('新規登録')).toBeTruthy();
    });

    it('パスワードフィールドは初期状態で非表示である', () => {
      // Act
      const { getByPlaceholderText } = renderComponent();
      const passwordInput = getByPlaceholderText('パスワード');

      // Assert
      expect(passwordInput.props.secureTextEntry).toBe(true);
    });
  });

  describe('入力フィールド', () => {
    it('ユーザー名を入力できる', () => {
      // Arrange
      const { getByPlaceholderText } = renderComponent();
      const usernameInput = getByPlaceholderText('ユーザー名');

      // Act
      fireEvent.changeText(usernameInput, 'test_user');

      // Assert
      expect(usernameInput.props.value).toBe('test_user');
    });

    it('パスワードを入力できる', () => {
      // Arrange
      const { getByPlaceholderText } = renderComponent();
      const passwordInput = getByPlaceholderText('パスワード');

      // Act
      fireEvent.changeText(passwordInput, 'password123');

      // Assert
      expect(passwordInput.props.value).toBe('password123');
    });

    it('パスワード表示切り替えボタンが機能する', () => {
      // Arrange
      const { getByPlaceholderText, getByTestId } = renderComponent();
      const passwordInput = getByPlaceholderText('パスワード');

      // Assert - 初期状態は非表示
      expect(passwordInput.props.secureTextEntry).toBe(true);

      // Act - 表示切り替え
      const toggleButton = getByTestId('toggle-password-visibility');
      fireEvent.press(toggleButton);

      // Assert - パスワード表示
      expect(passwordInput.props.secureTextEntry).toBe(false);

      // Act - 再度切り替え
      fireEvent.press(toggleButton);

      // Assert - パスワード非表示
      expect(passwordInput.props.secureTextEntry).toBe(true);
    });
  });

  describe('バリデーション', () => {
    it('ユーザー名とパスワードが空の場合はエラーを表示する', async () => {
      // Arrange
      const { getByText, findByText } = renderComponent();
      const loginButton = getByText('ログイン');

      // Act
      fireEvent.press(loginButton);

      // Assert
      const errorMessage = await findByText('ユーザー名とパスワードを入力してください');
      expect(errorMessage).toBeTruthy();
      expect(mockedAuthService.login).not.toHaveBeenCalled();
    });

    it('ユーザー名のみ入力の場合はエラーを表示する', async () => {
      // Arrange
      const { getByPlaceholderText, getByText, findByText } = renderComponent();
      const usernameInput = getByPlaceholderText('ユーザー名');
      const loginButton = getByText('ログイン');

      // Act
      fireEvent.changeText(usernameInput, 'test_user');
      fireEvent.press(loginButton);

      // Assert
      const errorMessage = await findByText('ユーザー名とパスワードを入力してください');
      expect(errorMessage).toBeTruthy();
      expect(mockedAuthService.login).not.toHaveBeenCalled();
    });

    it('パスワードのみ入力の場合はエラーを表示する', async () => {
      // Arrange
      const { getByPlaceholderText, getByText, findByText } = renderComponent();
      const passwordInput = getByPlaceholderText('パスワード');
      const loginButton = getByText('ログイン');

      // Act
      fireEvent.changeText(passwordInput, 'password123');
      fireEvent.press(loginButton);

      // Assert
      const errorMessage = await findByText('ユーザー名とパスワードを入力してください');
      expect(errorMessage).toBeTruthy();
      expect(mockedAuthService.login).not.toHaveBeenCalled();
    });
  });

  describe('ログイン処理', () => {
    it('正常にログインできる', async () => {
      // Arrange
      mockedAuthService.login.mockResolvedValue({
        token: 'dummy-token',
        user: {
          id: 1,
          name: 'test_user',
          email: 'test@example.com',
          avatar_url: undefined,
          created_at: '2025-12-01T00:00:00Z',
        },
      });
      const { getByPlaceholderText, getByText } = renderComponent();
      const usernameInput = getByPlaceholderText('ユーザー名');
      const passwordInput = getByPlaceholderText('パスワード');
      const loginButton = getByText('ログイン');

      // Act
      fireEvent.changeText(usernameInput, 'test_user');
      fireEvent.changeText(passwordInput, 'password123');
      fireEvent.press(loginButton);

      // Assert
      await waitFor(() => {
        expect(mockedAuthService.login).toHaveBeenCalledWith('test_user', 'password123');
      });
    });

    it('ログイン中はローディングインジケーターを表示する', async () => {
      // Arrange
      let resolveLogin: any;
      mockedAuthService.login.mockImplementation(
        () => new Promise((resolve) => { resolveLogin = resolve; })
      );
      const { getByPlaceholderText, getByText, queryByTestId } = renderComponent();
      const usernameInput = getByPlaceholderText('ユーザー名');
      const passwordInput = getByPlaceholderText('パスワード');
      const loginButton = getByText('ログイン');

      // Act
      fireEvent.changeText(usernameInput, 'test_user');
      fireEvent.changeText(passwordInput, 'password123');
      fireEvent.press(loginButton);

      // Assert - ローディング中
      await waitFor(() => {
        expect(queryByTestId('loading-indicator')).toBeTruthy();
      });

      // Assert - ローディング完了後
      resolveLogin({ success: true, user: { id: 1, username: 'test_user', email: 'test@example.com', theme: 'adult' } });
      await waitFor(() => {
        expect(queryByTestId('loading-indicator')).toBeNull();
      }, { timeout: 3000 }); // タイムアウトを3秒に延長（ThemeProvider初期化待ち）
    });

    it('ログイン失敗時にエラーメッセージを表示する', async () => {
      // Arrange
      const mockError = {
        response: {
          data: {
            message: 'ユーザー名またはパスワードが間違っています',
          },
        },
      };
      mockedAuthService.login.mockRejectedValue(mockError);
      const { getByPlaceholderText, getByText, findByText } = renderComponent();
      const usernameInput = getByPlaceholderText('ユーザー名');
      const passwordInput = getByPlaceholderText('パスワード');
      const loginButton = getByText('ログイン');

      // Act
      fireEvent.changeText(usernameInput, 'test_user');
      fireEvent.changeText(passwordInput, 'wrong_password');
      fireEvent.press(loginButton);

      // Assert
      const errorMessage = await findByText('ユーザー名またはパスワードが間違っています');
      expect(errorMessage).toBeTruthy();
    });

    it('ネットワークエラー時にデフォルトエラーメッセージを表示する', async () => {
      // Arrange
      const mockError = new Error('Network Error');
      mockedAuthService.login.mockRejectedValue(mockError);
      const { getByPlaceholderText, getByText, findByText } = renderComponent();
      const usernameInput = getByPlaceholderText('ユーザー名');
      const passwordInput = getByPlaceholderText('パスワード');
      const loginButton = getByText('ログイン');

      // Act
      fireEvent.changeText(usernameInput, 'test_user');
      fireEvent.changeText(passwordInput, 'password123');
      fireEvent.press(loginButton);

      // Assert
      const errorMessage = await findByText('ログインに失敗しました');
      expect(errorMessage).toBeTruthy();
    });
  });

  describe('ナビゲーション', () => {
    it('新規登録リンクをタップするとRegisterScreenに遷移する', () => {
      // Arrange
      const { getByText } = renderComponent();
      const registerLink = getByText('新規登録');

      // Act
      fireEvent.press(registerLink);

      // Assert
      expect(mockNavigate).toHaveBeenCalledWith('Register');
    });
  });

  describe('アクセシビリティ', () => {
    it('すべての入力フィールドにaccessibilityLabelが設定されている', () => {
      // Act
      const { getByPlaceholderText } = renderComponent();
      const usernameInput = getByPlaceholderText('ユーザー名');
      const passwordInput = getByPlaceholderText('パスワード');

      // Assert
      expect(usernameInput.props.accessibilityLabel).toBeTruthy();
      expect(passwordInput.props.accessibilityLabel).toBeTruthy();
    });

    it('ログインボタンが表示されている', () => {
      // Act
      const { getByText } = renderComponent();
      const loginButton = getByText('ログイン');

      // Assert - TouchableOpacityは暗黙的にaccessibilityRole='button'を持つ
      expect(loginButton).toBeTruthy();
    });
  });
});
