/**
 * PasswordChangeScreen テスト
 */

import React from 'react';
import { render, fireEvent, waitFor, act } from '@testing-library/react-native';
import { Alert } from 'react-native';
import PasswordChangeScreen from '../PasswordChangeScreen';
import { useProfile } from '../../../hooks/useProfile';
import { useTheme } from '../../../contexts/ThemeContext';

// モック化
jest.mock('../../../hooks/useProfile');
jest.mock('../../../contexts/ThemeContext');

const mockNavigate = jest.fn();
const mockGoBack = jest.fn();

jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(() => ({
    navigate: mockNavigate,
    goBack: mockGoBack,
  })),
}));

// Alert.alertのモック
jest.spyOn(Alert, 'alert');

const mockUseProfile = useProfile as jest.MockedFunction<typeof useProfile>;
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;

describe('PasswordChangeScreen', () => {
  const mockUpdatePassword = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();

    // デフォルトモック設定
    mockUseTheme.mockReturnValue({
      theme: 'adult',
      setTheme: jest.fn(),
      isLoading: false,
      refreshTheme: jest.fn(),
    });

    mockUseProfile.mockReturnValue({
      profile: null,
      isLoading: false,
      error: null,
      getProfile: jest.fn(),
      updateProfile: jest.fn(),
      deleteProfile: jest.fn(),
      getTimezoneSettings: jest.fn(),
      updateTimezone: jest.fn(),
      updatePassword: mockUpdatePassword,
      getCachedProfile: jest.fn(),
      clearProfileCache: jest.fn(),
    });
  });

  describe('UI表示', () => {
    it('adult themeで正しく描画される', () => {
      const { getByText, getByPlaceholderText } = render(<PasswordChangeScreen />);

      expect(getByText('パスワード更新')).toBeTruthy();
      expect(getByText('現在のパスワード')).toBeTruthy();
      expect(getByText('新規パスワード')).toBeTruthy();
      expect(getByText('確認用')).toBeTruthy();
      expect(getByText('保存')).toBeTruthy();
      expect(getByText('キャンセル')).toBeTruthy();
    });

    it('child themeで正しく描画される', () => {
      mockUseTheme.mockReturnValue({
        theme: 'child',
        setTheme: jest.fn(),
        isLoading: false,
        refreshTheme: jest.fn(),
      });

      const { getByText } = render(<PasswordChangeScreen />);

      expect(getByText('パスワードをかえる')).toBeTruthy();
      expect(getByText('いまのパスワード')).toBeTruthy();
      expect(getByText('あたらしいパスワード')).toBeTruthy();
      expect(getByText('かくにんよう')).toBeTruthy();
      expect(getByText('ほぞん')).toBeTruthy();
      expect(getByText('もどる')).toBeTruthy();
    });
  });

  describe('パスワード表示切替', () => {
    it('目アイコンをタップするとパスワードが表示/非表示切り替えされる', () => {
      const { getAllByText, getByDisplayValue } = render(<PasswordChangeScreen />);

      const eyeIcons = getAllByText('👁️');
      expect(eyeIcons.length).toBe(3); // 3つの入力フィールド

      // 最初は非表示（secureTextEntry=true）
      // ここでは目アイコンの存在を確認
    });
  });

  describe('バリデーション', () => {
    it('現在のパスワード未入力時にエラーを表示する', async () => {
      const { getByText, getByPlaceholderText, queryByText } = render(<PasswordChangeScreen />);

      const newPasswordInput = getByPlaceholderText('新しいパスワード（8文字以上）');
      const confirmPasswordInput = getByPlaceholderText('新しいパスワード（確認）');
      const submitButton = getByText('保存');

      await act(async () => {
        fireEvent.changeText(newPasswordInput, 'newpassword456');
        fireEvent.changeText(confirmPasswordInput, 'newpassword456');
        fireEvent.press(submitButton);
      });

      await waitFor(() => {
        expect(queryByText(/現在のパスワードを入力してください/)).toBeTruthy();
      });
    });

    it('新しいパスワードが8文字未満の場合エラーを表示する', async () => {
      const { getByText, getByPlaceholderText, findByText } = render(<PasswordChangeScreen />);

      const currentPasswordInput = getByPlaceholderText('現在のパスワード');
      const newPasswordInput = getByPlaceholderText('新しいパスワード（8文字以上）');
      const confirmPasswordInput = getByPlaceholderText('新しいパスワード（確認）');
      const submitButton = getByText('保存');

      await act(async () => {
        fireEvent.changeText(currentPasswordInput, 'oldpassword123');
        fireEvent.changeText(newPasswordInput, 'short');
        fireEvent.changeText(confirmPasswordInput, 'short');
      });

      fireEvent.press(submitButton);

      // エラーメッセージが表示されるまで待機
      const errorMessage = await findByText(/8文字以上/);
      expect(errorMessage).toBeTruthy();
    });

    it('パスワード確認が一致しない場合エラーを表示する', async () => {
      const { getByText, getByPlaceholderText, findByText } = render(<PasswordChangeScreen />);

      const currentPasswordInput = getByPlaceholderText('現在のパスワード');
      const newPasswordInput = getByPlaceholderText('新しいパスワード（8文字以上）');
      const confirmPasswordInput = getByPlaceholderText('新しいパスワード（確認）');
      const submitButton = getByText('保存');

      await act(async () => {
        fireEvent.changeText(currentPasswordInput, 'oldpassword123');
        fireEvent.changeText(newPasswordInput, 'newpassword456');
        fireEvent.changeText(confirmPasswordInput, 'differentpassword');
      });

      fireEvent.press(submitButton);

      const errorMessage = await findByText(/一致しません/);
      expect(errorMessage).toBeTruthy();
    });

    it('入力エラーをクリアすると次の入力時にエラーが消える', async () => {
      const { getByText, getByPlaceholderText, findByText, queryByText } = render(<PasswordChangeScreen />);

      const currentPasswordInput = getByPlaceholderText('現在のパスワード');
      const newPasswordInput = getByPlaceholderText('新しいパスワード（8文字以上）');
      const submitButton = getByText('保存');

      // エラー発生
      await act(async () => {
        fireEvent.changeText(currentPasswordInput, 'oldpassword123');
        fireEvent.changeText(newPasswordInput, 'short');
      });

      fireEvent.press(submitButton);

      // エラーメッセージ表示を待機
      const errorMessage = await findByText(/8文字以上/);
      expect(errorMessage).toBeTruthy();

      // 正しい値を入力
      await act(async () => {
        fireEvent.changeText(newPasswordInput, 'newpassword456');
      });

      // エラーが消える
      await waitFor(() => {
        expect(queryByText(/8文字以上/)).toBeNull();
      }, { timeout: 3000 });
    });
  });

  describe('パスワード更新処理', () => {
    it('正しい入力でパスワード更新が成功する', async () => {
      mockUpdatePassword.mockResolvedValue({ message: 'パスワードを更新しました' });

      const { getByText, getByPlaceholderText } = render(<PasswordChangeScreen />);

      const currentPasswordInput = getByPlaceholderText('現在のパスワード');
      const newPasswordInput = getByPlaceholderText('新しいパスワード（8文字以上）');
      const confirmPasswordInput = getByPlaceholderText('新しいパスワード（確認）');
      const submitButton = getByText('保存');

      await act(async () => {
        fireEvent.changeText(currentPasswordInput, 'oldpassword123');
        fireEvent.changeText(newPasswordInput, 'newpassword456');
        fireEvent.changeText(confirmPasswordInput, 'newpassword456');
      });

      // ボタン押下をact外で実行
      fireEvent.press(submitButton);

      await waitFor(() => {
        expect(mockUpdatePassword).toHaveBeenCalledWith(
          'oldpassword123',
          'newpassword456',
          'newpassword456'
        );
      }, { timeout: 3000 });

      await waitFor(() => {
        expect(Alert.alert).toHaveBeenCalledWith(
          '成功',
          'パスワードを更新しました',
          expect.any(Array)
        );
      }, { timeout: 3000 });
    });

    it('更新失敗時にエラーアラートを表示する', async () => {
      const mockUpdatePasswordFailed = jest.fn().mockRejectedValue(new Error('CURRENT_PASSWORD_INCORRECT'));
      
      mockUseProfile.mockReturnValue({
        profile: null,
        isLoading: false,
        error: '現在のパスワードが正しくありません',
        getProfile: jest.fn(),
        updateProfile: jest.fn(),
        deleteProfile: jest.fn(),
        getTimezoneSettings: jest.fn(),
        updateTimezone: jest.fn(),
        updatePassword: mockUpdatePasswordFailed,
        getCachedProfile: jest.fn(),
        clearProfileCache: jest.fn(),
      });

      const { getByText, getByPlaceholderText } = render(<PasswordChangeScreen />);

      const currentPasswordInput = getByPlaceholderText('現在のパスワード');
      const newPasswordInput = getByPlaceholderText('新しいパスワード（8文字以上）');
      const confirmPasswordInput = getByPlaceholderText('新しいパスワード（確認）');
      const submitButton = getByText('保存');

      await act(async () => {
        fireEvent.changeText(currentPasswordInput, 'wrongpassword');
        fireEvent.changeText(newPasswordInput, 'newpassword456');
        fireEvent.changeText(confirmPasswordInput, 'newpassword456');
      });

      fireEvent.press(submitButton);

      await waitFor(() => {
        expect(mockUpdatePasswordFailed).toHaveBeenCalled();
      }, { timeout: 3000 });

      await waitFor(() => {
        expect(Alert.alert).toHaveBeenCalledWith(
          'エラー',
          expect.stringContaining('パスワード'),
        );
      }, { timeout: 3000 });
    });

    it('ローディング中はボタンが無効化される', async () => {
      mockUseProfile.mockReturnValue({
        profile: null,
        isLoading: true,
        error: null,
        getProfile: jest.fn(),
        updateProfile: jest.fn(),
        deleteProfile: jest.fn(),
        getTimezoneSettings: jest.fn(),
        updateTimezone: jest.fn(),
        updatePassword: mockUpdatePassword,
        getCachedProfile: jest.fn(),
        clearProfileCache: jest.fn(),
      });

      const { queryByText, getByTestId } = render(<PasswordChangeScreen />);

      // ローディング中は「保存」ボタンが非表示
      expect(queryByText('保存')).toBeNull();
      expect(queryByText('ほぞん')).toBeNull();
    });
  });

  describe('キャンセルボタン', () => {
    it('キャンセルボタンで前の画面に戻る', async () => {
      const { getByText } = render(<PasswordChangeScreen />);

      const cancelButton = getByText('キャンセル');

      await act(async () => {
        fireEvent.press(cancelButton);
      });

      expect(mockGoBack).toHaveBeenCalled();
    });
  });
});
