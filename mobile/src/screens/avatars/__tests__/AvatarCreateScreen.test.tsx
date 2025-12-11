/**
 * AvatarCreateScreen テスト
 * 
 * Phase 2.B-7: アバター管理機能実装
 * 
 * テスト対象:
 * - フォームレンダリング（11セクション）
 * - 入力変更処理
 * - 確認ダイアログ
 * - アバター作成処理
 * - テーマ対応（adult/child）
 */

import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import { Alert } from 'react-native';
import { AvatarCreateScreen } from '../AvatarCreateScreen';
import { useAvatarManagement } from '../../../hooks/useAvatarManagement';
import { useTheme } from '../../../contexts/ThemeContext';
import { useNavigation } from '@react-navigation/native';

// モック
jest.mock('../../../hooks/useAvatarManagement');
jest.mock('../../../contexts/ThemeContext');
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
}));

describe('AvatarCreateScreen', () => {
  const mockCreateAvatar = jest.fn();
  const mockClearError = jest.fn();
  const mockGoBack = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    
    (useAvatarManagement as jest.Mock).mockReturnValue({
      createAvatar: mockCreateAvatar,
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

    // Alert.alertをモック
    jest.spyOn(Alert, 'alert');
  });

  afterEach(() => {
    jest.restoreAllMocks();
  });

  it('フォームが正しくレンダリングされる', () => {
    const { getByText } = render(<AvatarCreateScreen />);

    // ヘッダー確認
    expect(getByText('アバター作成')).toBeTruthy();
    
    // セクションタイトル確認
    expect(getByText('👤 外見の設定')).toBeTruthy();
    expect(getByText('💬 性格の設定')).toBeTruthy();
    expect(getByText('🎨 描画モデルの選択')).toBeTruthy();
    
    // フィールドラベル確認
    expect(getByText('性別')).toBeTruthy();
    expect(getByText('髪型')).toBeTruthy();
    expect(getByText('髪の色')).toBeTruthy();
    expect(getByText('目の色')).toBeTruthy();
    expect(getByText('服装')).toBeTruthy();
    expect(getByText('アクセサリー')).toBeTruthy();
    expect(getByText('体型')).toBeTruthy();
    expect(getByText('口調')).toBeTruthy();
    expect(getByText('熱意')).toBeTruthy();
    expect(getByText('丁寧さ')).toBeTruthy();
    expect(getByText('ユーモア')).toBeTruthy();
    
    // ボタン確認
    expect(getByText('アバターを作成する')).toBeTruthy();
  });

  it('childテーマで適切なUIが表示される', () => {
    (useTheme as jest.Mock).mockReturnValue({
      theme: 'child',
    });

    const { getByText } = render(<AvatarCreateScreen />);

    // child用テキスト確認
    expect(getByText('アバターをつくろう')).toBeTruthy();
    expect(getByText('せんせいのみためとせいかくをえらんでね')).toBeTruthy();
    expect(getByText('アバターをつくる')).toBeTruthy();
  });

  it('作成ボタン押下で確認ダイアログが表示される', () => {
    const { getByText } = render(<AvatarCreateScreen />);

    const createButton = getByText('アバターを作成する');
    fireEvent.press(createButton);

    // Alert.alertが呼ばれたことを確認
    expect(Alert.alert).toHaveBeenCalledWith(
      'アバター作成',
      expect.stringContaining('5,000'),
      expect.any(Array)
    );
  });

  it('確認ダイアログで「はい」を選択すると作成処理が実行される', async () => {
    mockCreateAvatar.mockResolvedValue({
      id: 1,
      generationStatus: 'pending',
    });

    const { getByText } = render(<AvatarCreateScreen />);

    const createButton = getByText('アバターを作成する');
    fireEvent.press(createButton);

    // Alert.alertの「作成」ボタンを実行
    const alertCall = (Alert.alert as jest.Mock).mock.calls[0];
    const createButtonDialog = alertCall[2].find((btn: any) => btn.text === '作成');
    await createButtonDialog.onPress();

    await waitFor(() => {
      expect(mockCreateAvatar).toHaveBeenCalled();
    });
  });

  it('ローディング中はボタンが無効化される', () => {
    (useAvatarManagement as jest.Mock).mockReturnValue({
      createAvatar: mockCreateAvatar,
      isLoading: true,
      error: null,
      clearError: mockClearError,
    });

    const { queryByText, UNSAFE_queryAllByType } = render(<AvatarCreateScreen />);

    // ローディング中は「アバターを作成する」テキストがなく、ActivityIndicatorが表示される
    expect(queryByText('アバターを作成する')).toBeNull();
    // ActivityIndicatorが存在することを確認
    const ActivityIndicator = require('react-native').ActivityIndicator;
    const indicators = UNSAFE_queryAllByType(ActivityIndicator);
    expect(indicators.length).toBeGreaterThan(0);
  });

  it('エラーメッセージが表示される', () => {
    const errorMessage = 'トークンが不足しています';
    (useAvatarManagement as jest.Mock).mockReturnValue({
      createAvatar: mockCreateAvatar,
      isLoading: false,
      error: errorMessage,
      clearError: mockClearError,
    });

    const { getByText } = render(<AvatarCreateScreen />);

    expect(getByText(errorMessage)).toBeTruthy();
  });

  it('作成失敗時にエラーアラートが表示される', async () => {
    mockCreateAvatar.mockRejectedValue(new Error('Creation failed'));

    const { getByText } = render(<AvatarCreateScreen />);

    const createButton = getByText('アバターを作成する');
    fireEvent.press(createButton);

    // 確認ダイアログで「作成」を選択
    const alertCall = (Alert.alert as jest.Mock).mock.calls[0];
    const createButtonDialog = alertCall[2].find((btn: any) => btn.text === '作成');
    await createButtonDialog.onPress();

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        'エラー',
        expect.stringContaining('作成に失敗しました'),
      );
    });
  });
});
