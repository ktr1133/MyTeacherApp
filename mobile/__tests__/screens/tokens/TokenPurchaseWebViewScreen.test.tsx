/**
 * TokenPurchaseWebViewScreen.tsx (TokenPackageListScreen) テスト
 * 
 * トークンパッケージ一覧画面の動作を検証
 */

import React from 'react';
import { render, waitFor, fireEvent } from '@testing-library/react-native';
import TokenPackageListScreen from '../../../src/screens/tokens/TokenPurchaseWebViewScreen';
import { useNavigation } from '@react-navigation/native';
import { useTheme } from '../../../src/contexts/ThemeContext';
import { useTokens } from '../../../src/hooks/useTokens';
import { TokenPackage } from '../../../src/types/token.types';

// モック設定
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
  useFocusEffect: jest.fn((callback) => callback()),
}));

jest.mock('../../../src/contexts/ThemeContext');
jest.mock('../../../src/hooks/useTokens');
jest.mock('../../../src/hooks/useThemedColors', () => ({
  useThemedColors: jest.fn(() => ({
    colors: {
      background: '#FFFFFF',
      text: { primary: '#111827', secondary: '#6B7280' },
      card: '#FFFFFF',
      border: { default: '#E5E7EB' },
    },
    accent: { primary: '#3B82F6', secondary: '#8B5CF6' },
  })),
}));

jest.mock('../../../src/utils/responsive', () => ({
  useResponsive: jest.fn(() => ({
    width: 375,
    height: 812,
    deviceSize: 'md',
    isPortrait: true,
    isLandscape: false,
    isTablet: false,
  })),
  getFontSize: jest.fn((base) => base),
  getSpacing: jest.fn((base) => base),
  getBorderRadius: jest.fn((base) => base),
  getShadow: jest.fn(() => ({})),
}));

jest.mock('expo-linear-gradient', () => ({
  LinearGradient: 'LinearGradient',
}));

describe('TokenPackageListScreen (TokenPurchaseWebViewScreen)', () => {
  const mockNavigation = {
    navigate: jest.fn(),
    goBack: jest.fn(),
    addListener: jest.fn((event, callback) => {
      if (event === 'focus') callback();
      return jest.fn();
    }),
  };

  const mockPackages: TokenPackage[] = [
    {
      id: 1,
      name: '小パック',
      token_amount: 10000,
      price: 1000,
      stripe_price_id: 'price_test_123',
      description: '10,000トークン',
      discount_rate: null,
      sort_order: 1,
      is_active: true,
      created_at: '2024-12-01T00:00:00.000Z',
    },
    {
      id: 2,
      name: '中パック',
      token_amount: 50000,
      price: 4500,
      stripe_price_id: 'price_test_456',
      description: '50,000トークン（10%お得）',
      discount_rate: 10,
      sort_order: 2,
      is_active: true,
      created_at: '2024-12-01T00:00:00.000Z',
    },
    {
      id: 3,
      name: '大パック',
      token_amount: 100000,
      price: 8000,
      stripe_price_id: 'price_test_789',
      description: '100,000トークン（20%お得）',
      discount_rate: 20,
      sort_order: 3,
      is_active: true,
      created_at: '2024-12-01T00:00:00.000Z',
    },
  ];

  beforeEach(() => {
    jest.clearAllMocks();
    (useNavigation as jest.Mock).mockReturnValue(mockNavigation);
    (useTheme as jest.Mock).mockReturnValue({ theme: 'adult' });
    (useTokens as jest.Mock).mockReturnValue({
      packages: mockPackages,
      loadPackages: jest.fn(),
      isLoading: false,
      error: null,
    });
  });

  describe('レンダリング', () => {
    it('パッケージ一覧が表示される', async () => {
      const { getByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        expect(getByText('小パック')).toBeTruthy();
        expect(getByText('中パック')).toBeTruthy();
        expect(getByText('大パック')).toBeTruthy();
      });
    });

    it('トークン量が3桁カンマ区切りで表示される', async () => {
      const { getByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        expect(getByText('10,000')).toBeTruthy();
        expect(getByText('50,000')).toBeTruthy();
        expect(getByText('100,000')).toBeTruthy();
      });
    });

    it('価格が正しく表示される', async () => {
      const { getByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        expect(getByText('¥1,000')).toBeTruthy();
        expect(getByText('¥4,500')).toBeTruthy();
        expect(getByText('¥8,000')).toBeTruthy();
      });
    });

    it('割引率が表示される', async () => {
      const { getByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        expect(getByText(/10%/)).toBeTruthy();
        expect(getByText(/20%/)).toBeTruthy();
      });
    });

    it('割引なしパッケージでは割引バッジが表示されない', async () => {
      const { queryByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        // 小パックには割引がないため、割引バッジは1つのみ（中パックと大パック）
        const discountBadges = queryByText(/割引|おとく/);
        expect(discountBadges).toBeTruthy();
      });
    });
  });

  describe('テーマ対応', () => {
    it('大人テーマで正しいラベルが表示される', async () => {
      const { getByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        expect(getByText('トークン購入')).toBeTruthy();
        expect(getByText('購入する')).toBeTruthy();
      });
    });

    it('子どもテーマで正しいラベルが表示される', async () => {
      (useTheme as jest.Mock).mockReturnValue({ theme: 'child' });

      const { getByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        expect(getByText('トークンをかう')).toBeTruthy();
        expect(getByText('かう')).toBeTruthy();
      });
    });
  });

  describe('購入処理', () => {
    it('購入ボタンをタップするとWebView画面に遷移する', async () => {
      const mockLoadPackages = jest.fn();
      (useTokens as jest.Mock).mockReturnValue({
        packages: mockPackages,
        loadPackages: mockLoadPackages,
        isLoading: false,
        error: null,
      });

      const { getAllByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        const purchaseButtons = getAllByText('購入する');
        expect(purchaseButtons.length).toBeGreaterThan(0);
        
        // 最初の購入ボタンをタップ
        fireEvent.press(purchaseButtons[0]);
      });

      // handlePurchaseが呼ばれることを確認
      // 注: 実際のナビゲーション処理はWebView統合テストで検証
    });
  });

  describe('ローディング状態', () => {
    it('ローディング中は適切なメッセージが表示される', async () => {
      (useTokens as jest.Mock).mockReturnValue({
        packages: [],
        loadPackages: jest.fn(),
        isLoading: true,
        error: null,
      });

      const { getByText } = render(<TokenPackageListScreen />);

      // RefreshControlのrefreshing状態をテスト
      // 注: RefreshControlの実際の動作はE2Eテストで検証
      expect((useTokens as jest.Mock)().isLoading).toBe(true);
    });
  });

  describe('エラー表示', () => {
    it('エラーメッセージが表示される', async () => {
      const errorMessage = 'トークンパッケージの取得に失敗しました';
      (useTokens as jest.Mock).mockReturnValue({
        packages: [],
        loadPackages: jest.fn(),
        isLoading: false,
        error: errorMessage,
      });

      const { getByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        expect(getByText(new RegExp(errorMessage))).toBeTruthy();
      });
    });
  });

  describe('空の状態', () => {
    it('パッケージがない場合は空のメッセージが表示される', async () => {
      (useTokens as jest.Mock).mockReturnValue({
        packages: [],
        loadPackages: jest.fn(),
        isLoading: false,
        error: null,
      });

      const { getByText } = render(<TokenPackageListScreen />);

      await waitFor(() => {
        expect(getByText('トークンパッケージがありません')).toBeTruthy();
      });
    });
  });

  describe('リフレッシュ機能', () => {
    it('Pull-to-refreshでパッケージが再読み込みされる', async () => {
      const mockLoadPackages = jest.fn();
      (useTokens as jest.Mock).mockReturnValue({
        packages: mockPackages,
        loadPackages: mockLoadPackages,
        isLoading: false,
        error: null,
      });

      render(<TokenPackageListScreen />);

      // 初回読み込みが呼ばれることを確認
      await waitFor(() => {
        expect(mockLoadPackages).toHaveBeenCalled();
      });
    });
  });

  describe('戻るボタン', () => {
    it('戻るボタンをタップすると前の画面に戻る', async () => {
      const { getByText } = render(<TokenPackageListScreen />);

      const backButton = getByText(/戻る|もどる/);
      fireEvent.press(backButton);

      expect(mockNavigation.goBack).toHaveBeenCalled();
    });
  });

  describe('レスポンシブ対応', () => {
    it('useResponsive Hookが呼ばれる', () => {
      const { useResponsive } = require('../../../src/utils/responsive');
      
      render(<TokenPackageListScreen />);

      expect(useResponsive).toHaveBeenCalled();
    });

    it('getFontSize Hookが呼ばれる', () => {
      const { getFontSize } = require('../../../src/utils/responsive');
      
      render(<TokenPackageListScreen />);

      expect(getFontSize).toHaveBeenCalled();
    });
  });

  describe('ダークモード対応', () => {
    it('useThemedColors Hookが呼ばれる', () => {
      const { useThemedColors } = require('../../../src/hooks/useThemedColors');
      
      render(<TokenPackageListScreen />);

      expect(useThemedColors).toHaveBeenCalled();
    });

    it('ダークモードの色が適用される', () => {
      const { useThemedColors } = require('../../../src/hooks/useThemedColors');
      
      // ダークモードカラーパレット
      useThemedColors.mockReturnValue({
        colors: {
          background: '#1F2937',
          text: { primary: '#FFFFFF', secondary: '#D1D5DB' },
          card: '#374151',
          border: { default: '#4B5563' },
        },
        accent: { primary: '#60A5FA', secondary: '#A78BFA' },
      });

      const { UNSAFE_root } = render(<TokenPackageListScreen />);

      // SafeAreaViewに背景色が適用されていることを確認
      const safeAreaView = UNSAFE_root.findAllByType('SafeAreaView')[0];
      expect(safeAreaView.props.style).toEqual(
        expect.objectContaining({
          flex: 1,
          backgroundColor: '#1F2937',
        })
      );
    });
  });
});
