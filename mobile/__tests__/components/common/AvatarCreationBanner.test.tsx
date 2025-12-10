/**
 * AvatarCreationBanner コンポーネントのテスト
 * 
 * @see /home/ktr/mtdev/mobile/src/components/common/AvatarCreationBanner.tsx
 */

import React from 'react';
import { render, fireEvent, waitFor } from '@testing-library/react-native';
import AvatarCreationBanner from '../../../src/components/common/AvatarCreationBanner';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../../../src/contexts/ThemeContext';

// モック
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
}));
jest.mock('../../../src/contexts/ThemeContext', () => ({
  useTheme: jest.fn(),
}));
jest.mock('../../../src/utils/responsive', () => ({
  useResponsive: () => ({
    width: 375,
    height: 812,
    deviceSize: 'md',
    isPortrait: true,
    isLandscape: false,
  }),
  getFontSize: (size: number) => size,
  getSpacing: (size: number) => size,
  getBorderRadius: (size: number) => size,
}));

const mockUseNavigation = useNavigation as jest.MockedFunction<typeof useNavigation>;
const mockUseTheme = useTheme as jest.MockedFunction<typeof useTheme>;

describe('AvatarCreationBanner', () => {
  const mockNavigate = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    mockUseNavigation.mockReturnValue({
      navigate: mockNavigate,
    } as any);
  });

  describe('表示内容', () => {
    it('大人テーマの場合、適切なメッセージが表示される', () => {
      mockUseTheme.mockReturnValue({
        theme: 'adult',
        setTheme: jest.fn(),
      });

      const { getByText } = render(<AvatarCreationBanner />);

      expect(getByText('あなただけのサポートアバターを作成しましょう！')).toBeTruthy();
      expect(getByText('タスク完了時に応援してくれるキャラクターを作成できます')).toBeTruthy();
    });

    it('子供テーマの場合、適切なメッセージが表示される', () => {
      mockUseTheme.mockReturnValue({
        theme: 'child',
        setTheme: jest.fn(),
      });

      const { getByText } = render(<AvatarCreationBanner />);

      expect(getByText('あなただけのサポートキャラをつくろう！')).toBeTruthy();
      expect(getByText('たのしくタスクをかんせいできるよ！')).toBeTruthy();
    });
  });

  describe('ナビゲーション', () => {
    beforeEach(() => {
      mockUseTheme.mockReturnValue({
        theme: 'adult',
        setTheme: jest.fn(),
      });
    });

    it('バナーをタップするとアバター作成画面に遷移する', async () => {
      const { getByTestId } = render(<AvatarCreationBanner />);

      fireEvent.press(getByTestId('avatar-creation-banner'));

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('AvatarCreate');
      });
    });

    it('カスタムonPressが指定されている場合、それが実行される', async () => {
      const mockOnPress = jest.fn();
      const { getByTestId } = render(<AvatarCreationBanner onPress={mockOnPress} />);

      fireEvent.press(getByTestId('avatar-creation-banner'));

      await waitFor(() => {
        expect(mockOnPress).toHaveBeenCalled();
        expect(mockNavigate).not.toHaveBeenCalled();
      });
    });
  });

  describe('アクセシビリティ', () => {
    beforeEach(() => {
      mockUseTheme.mockReturnValue({
        theme: 'adult',
        setTheme: jest.fn(),
      });
    });

    it('適切なaccessibilityLabelが設定されている', () => {
      const { getByLabelText } = render(<AvatarCreationBanner />);

      expect(getByLabelText('アバター作成バナー')).toBeTruthy();
    });

    it('適切なaccessibilityHintが設定されている', () => {
      const { getByA11yHint } = render(<AvatarCreationBanner />);

      expect(getByA11yHint('タップしてアバターを作成')).toBeTruthy();
    });
  });

  describe('スタイル', () => {
    beforeEach(() => {
      mockUseTheme.mockReturnValue({
        theme: 'adult',
        setTheme: jest.fn(),
      });
    });

    it('バナーが正しく描画される', () => {
      const { getByTestId } = render(<AvatarCreationBanner />);

      const banner = getByTestId('avatar-creation-banner');
      expect(banner).toBeTruthy();
      expect(banner.props.accessibilityLabel).toBe('アバター作成バナー');
    });
  });
});
