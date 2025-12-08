/**
 * ProfileScreen テスト
 */

import { render, fireEvent, waitFor, act } from '@testing-library/react-native';
import { Alert } from 'react-native';
import ProfileScreen from '../ProfileScreen';
import { useProfile } from '../../../hooks/useProfile';
import { useTheme } from '../../../contexts/ThemeContext';

// モック
jest.mock('../../../hooks/useProfile');
jest.mock('../../../contexts/ThemeContext');

const mockUseProfile = useProfile as jest.MockedFunction<typeof useProfile>;
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;

// Alert.alertのモック
jest.spyOn(Alert, 'alert');

describe('ProfileScreen', () => {
  const mockProfile = {
    id: 1,
    username: 'testuser',
    name: 'Test User',
    email: 'test@example.com',
    avatar_path: null,
    avatar_url: null,
    timezone: 'Asia/Tokyo',
    theme: 'adult' as const,
    group_id: null,
    group_edit_flg: false,
    auth_provider: 'sanctum',
    cognito_sub: null,
    created_at: '2025-12-06T00:00:00.000Z',
    updated_at: '2025-12-06T00:00:00.000Z',
  };

  const mockProfileHook = {
    profile: mockProfile,
    isLoading: false,
    error: null,
    getProfile: jest.fn(),
    updateProfile: jest.fn(),
    deleteProfile: jest.fn(),
    getTimezoneSettings: jest.fn(),
    updateTimezone: jest.fn(),
    updatePassword: jest.fn(),
    getCachedProfile: jest.fn(),
    clearProfileCache: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockUseProfile.mockReturnValue(mockProfileHook);
    mockUseTheme.mockReturnValue({ theme: 'adult', isLoading: false, refreshTheme: jest.fn(), setTheme: jest.fn() });
  });

  it('プロフィール情報を表示する', async () => {
    const { getByText, getByDisplayValue } = render(<ProfileScreen />);

    await waitFor(() => {
      expect(getByText('プロフィール')).toBeTruthy();
      expect(getByDisplayValue('testuser')).toBeTruthy();
      expect(getByDisplayValue('test@example.com')).toBeTruthy();
      expect(getByDisplayValue('Test User')).toBeTruthy();
    });
  });

  it('child themeで適切なラベルを表示する', async () => {
    mockUseTheme.mockReturnValue({ theme: 'child', isLoading: false, refreshTheme: jest.fn(), setTheme: jest.fn() });

    const { getByText } = render(<ProfileScreen />);

    await waitFor(() => {
      expect(getByText('じぶんのじょうほう')).toBeTruthy();
    });
  });

  it('編集ボタンをクリックすると編集モードになる', async () => {
    const { getByText } = render(<ProfileScreen />);

    const editButton = getByText('編集');
    fireEvent.press(editButton);

    await waitFor(() => {
      expect(getByText('保存')).toBeTruthy();
      expect(getByText('キャンセル')).toBeTruthy();
    });
  });

  it('プロフィールを更新できる', async () => {
    mockProfileHook.updateProfile.mockResolvedValue({
      ...mockProfile,
      name: 'Updated User',
    });

    const { getByText, getByDisplayValue } = render(<ProfileScreen />);

    // 編集モードに入る
    fireEvent.press(getByText('編集'));

    await waitFor(() => {
      expect(getByText('保存')).toBeTruthy();
    });

    // 名前を変更
    const nameInput = getByDisplayValue('Test User');
    fireEvent.changeText(nameInput, 'Updated User');

    // 保存
    await act(async () => {
      fireEvent.press(getByText('保存'));
    });

    await waitFor(() => {
      expect(mockProfileHook.updateProfile).toHaveBeenCalledWith({
        username: 'testuser',
        email: 'test@example.com',
        name: 'Updated User',
      });
      expect(Alert.alert).toHaveBeenCalledWith(
        '保存完了',
        'プロフィールを更新しました'
      );
    });
  });

  it('必須フィールドが空の場合にエラーを表示する', async () => {
    const { getByText, getByDisplayValue } = render(<ProfileScreen />);

    // 編集モードに入る
    fireEvent.press(getByText('編集'));

    // ユーザー名を空にする
    const usernameInput = getByDisplayValue('testuser');
    fireEvent.changeText(usernameInput, '');

    // 保存を試みる
    await act(async () => {
      fireEvent.press(getByText('保存'));
    });

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        '入力エラー',
        'ユーザー名とメールアドレスは必須です'
      );
      expect(mockProfileHook.updateProfile).not.toHaveBeenCalled();
    });
  });

  it('キャンセルボタンで編集を取り消せる', async () => {
    const { getByText, getByDisplayValue } = render(<ProfileScreen />);

    // 編集モードに入る
    fireEvent.press(getByText('編集'));

    // 名前を変更
    const nameInput = getByDisplayValue('Test User');
    fireEvent.changeText(nameInput, 'Changed');

    // キャンセル
    fireEvent.press(getByText('キャンセル'));

    await waitFor(() => {
      // 元の値に戻っていることを確認
      expect(getByDisplayValue('Test User')).toBeTruthy();
      expect(getByText('編集')).toBeTruthy();
    });
  });

  it('アカウント削除確認ダイアログを表示する', async () => {
    const { getByText } = render(<ProfileScreen />);

    // 削除ボタンをクリック
    fireEvent.press(getByText('アカウントを削除'));

    await waitFor(() => {
      expect(Alert.alert).toHaveBeenCalledWith(
        'アカウント削除確認',
        'アカウントを削除すると、全てのデータが失われます。本当に削除しますか？',
        expect.any(Array)
      );
    });
  });

  it('ローディング中に表示を切り替える', () => {
    mockUseProfile.mockReturnValue({
      ...mockProfileHook,
      profile: null,
      isLoading: true,
    });

    const { getByText } = render(<ProfileScreen />);

    expect(getByText('読み込み中...')).toBeTruthy();
  });

  it('エラーメッセージを表示する', async () => {
    mockUseProfile.mockReturnValue({
      ...mockProfileHook,
      error: 'プロフィールの取得に失敗しました',
    });

    const { getByText } = render(<ProfileScreen />);

    await waitFor(() => {
      expect(getByText('プロフィールの取得に失敗しました')).toBeTruthy();
    });
  });
});
