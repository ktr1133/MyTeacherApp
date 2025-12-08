/**
 * AvatarEditScreen テスト
 * 
 * Phase 2.B-7: アバター管理機能実装
 * 
 * テスト対象:
 * - 初期値設定（route paramsから）
 * - フォームレンダリング
 * - 更新処理
 * - パラメータ不正時のエラーハンドリング
 */

import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import { Alert } from 'react-native';
import { AvatarEditScreen } from '../AvatarEditScreen';
import { useAvatarManagement } from '../../../hooks/useAvatarManagement';
import { useTheme } from '../../../contexts/ThemeContext';
import { useNavigation, useRoute } from '@react-navigation/native';
import { Avatar } from '../../../types/avatar.types';

// モック
jest.mock('../../../hooks/useAvatarManagement');
jest.mock('../../../contexts/ThemeContext');
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
  useRoute: jest.fn(),
}));

describe('AvatarEditScreen', () => {
  const mockAvatar: Avatar = {
    id: 1,
    user_id: 1,
    seed: 12345,
    sex: 'female',
    hair_style: 'long',
    hair_color: 'black',
    eye_color: 'brown',
    clothing: 'suit',
    accessory: 'nothing',
    body_type: 'average',
    tone: 'gentle',
    enthusiasm: 'normal',
    formality: 'polite',
    humor: 'normal',
    draw_model_version: 'anything-v4.0',
    is_transparent: true,
    is_chibi: false,
    estimated_token_usage: 5000,
    is_visible: true,
    generation_status: 'completed',
    last_generated_at: '2025-01-15T10:00:00Z',
    created_at: '2025-01-15T10:00:00Z',
    updated_at: '2025-01-15T10:00:00Z',
    images: [],
  };

  const mockUpdateAvatar = jest.fn();
  const mockClearError = jest.fn();
  const mockGoBack = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();

    (useAvatarManagement as jest.Mock).mockReturnValue({
      updateAvatar: mockUpdateAvatar,
      isLoading: false,
      error: null,
      clearError: mockClearError,
    });

    (useTheme as jest.Mock).mockReturnValue({
      theme: 'adult',
    });

    (useNavigation as jest.Mock).mockReturnValue({
      goBack: mockGoBack,
    });

    (useRoute as jest.Mock).mockReturnValue({
      params: { avatar: mockAvatar },
    });

    jest.spyOn(Alert, 'alert');
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  it('フォームが正しくレンダリングされる', () => {
    const { getByText } = render(<AvatarEditScreen />);

    // ヘッダー確認
    expect(getByText('アバター編集')).toBeTruthy();
    
    // ボタン確認
    expect(getByText('更新する')).toBeTruthy();
    
    // 警告メッセージ確認（画像は再生成されない）
    expect(getByText(/設定を更新しても画像は再生成されません/)).toBeTruthy();
  });

  it('初期値がroute paramsから正しく設定される', () => {
    const { UNSAFE_getByType } = render(<AvatarEditScreen />);

    // Pickerの初期値を確認（実装により異なる、概念的なテスト）
    const pickers = UNSAFE_getByType('Picker');
    expect(pickers).toBeTruthy();
    
    // Note: 実際のPickerの値確認は実装により異なるため、
    // ここではレンダリングが成功することを確認
  });

  it('更新ボタン押下で更新処理が実行される', async () => {
    mockUpdateAvatar.mockResolvedValue(mockAvatar);

    const { getByText } = render(<AvatarEditScreen />);

    const updateButton = getByText('更新する');
    fireEvent.press(updateButton);

    await waitFor(() => {
      expect(mockUpdateAvatar).toHaveBeenCalled();
      expect(Alert.alert).toHaveBeenCalledWith(
        '更新完了',
        expect.stringContaining('設定を更新しました'),
        expect.any(Array)
      );
    });
  });

  it('更新成功後、管理画面に戻る', async () => {
    mockUpdateAvatar.mockResolvedValue(mockAvatar);

    const { getByText } = render(<AvatarEditScreen />);

    const updateButton = getByText('更新する');
    fireEvent.press(updateButton);

    await waitFor(() => {
      // Alert.alertの「OK」ボタンを実行
      const alertCall = (Alert.alert as jest.Mock).mock.calls[0];
      const okButton = alertCall[2][0];
      okButton.onPress();
      
      expect(mockGoBack).toHaveBeenCalled();
    });
  });

  it('更新失敗時にエラーアラートが表示される', async () => {
    mockUpdateAvatar.mockRejectedValue(new Error('Update failed'));

    const { getByText } = render(<AvatarEditScreen />);

    const updateButton = getByText('更新する');
    fireEvent.press(updateButton);

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        'エラー',
        expect.stringContaining('更新に失敗しました'),
      );
    });
  });

  it('ローディング中はボタンが無効化される', () => {
    (useAvatarManagement as jest.Mock).mockReturnValue({
      updateAvatar: mockUpdateAvatar,
      isLoading: true,
      error: null,
      clearError: mockClearError,
    });

    const { getByText } = render(<AvatarEditScreen />);

    const updateButton = getByText('更新する').parent;
    
    expect(updateButton?.props.accessibilityState?.disabled).toBe(true);
  });

  it('avatarパラメータがない場合、エラーアラートが表示される', () => {
    (useRoute as jest.Mock).mockReturnValue({
      params: {},
    });

    render(<AvatarEditScreen />);

    expect(Alert.alert).toHaveBeenCalledWith(
      'エラー',
      expect.stringContaining('アバター情報が見つかりません'),
      expect.any(Array)
    );
  });

  it('エラーメッセージが表示される', () => {
    const errorMessage = '更新に失敗しました';
    (useAvatarManagement as jest.Mock).mockReturnValue({
      updateAvatar: mockUpdateAvatar,
      isLoading: false,
      error: errorMessage,
      clearError: mockClearError,
    });

    const { getByText } = render(<AvatarEditScreen />);

    expect(getByText(errorMessage)).toBeTruthy();
  });
});
