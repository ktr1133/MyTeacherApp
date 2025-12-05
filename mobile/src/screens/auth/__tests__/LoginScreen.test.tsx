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

import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import LoginScreen from '../LoginScreen';
import { useAuth } from '../../../hooks/useAuth';

// モック設定
jest.mock('../../../hooks/useAuth');
jest.mock('@react-navigation/native', () => ({
  useNavigation: () => ({
    replace: jest.fn(),
    navigate: jest.fn(),
  }),
}));
// @expo/vector-icons は jest-expo が自動モック

const mockedUseAuth = useAuth as jest.MockedFunction<typeof useAuth>;

describe('LoginScreen', () => {
  const mockLogin = jest.fn();
  const mockNavigate = jest.fn();
  const mockNavigation = {
    navigate: mockNavigate,
    replace: jest.fn(),
    goBack: jest.fn(),
    reset: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    
    // useAuthモック
    mockedUseAuth.mockReturnValue({
      user: null,
      loading: false,
      isAuthenticated: false,
      login: mockLogin,
      register: jest.fn(),
      logout: jest.fn(),
    });
  });

  const renderComponent = () => render(<LoginScreen navigation={mockNavigation} />);

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
      expect(mockLogin).not.toHaveBeenCalled();
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
      expect(mockLogin).not.toHaveBeenCalled();
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
      expect(mockLogin).not.toHaveBeenCalled();
    });
  });

  describe('ログイン処理', () => {
    it('正常にログインできる', async () => {
      // Arrange
      mockLogin.mockResolvedValue(undefined);
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
        expect(mockLogin).toHaveBeenCalledWith('test_user', 'password123');
      });
    });

    it('ログイン中はローディングインジケーターを表示する', async () => {
      // Arrange
      mockLogin.mockImplementation(
        () => new Promise((resolve) => setTimeout(resolve, 100))
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
      await waitFor(() => {
        expect(queryByTestId('loading-indicator')).toBeNull();
      });
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
      mockLogin.mockRejectedValue(mockError);
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
      mockLogin.mockRejectedValue(mockError);
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
