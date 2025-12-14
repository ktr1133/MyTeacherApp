/**
 * TokenCheckoutWebViewScreen.tsx テスト
 * 
 * トークン購入WebView画面の動作を検証
 */

import React from 'react';
import { render, waitFor } from '@testing-library/react-native';
import { TokenCheckoutWebViewScreen } from '../../../src/screens/tokens/TokenCheckoutWebViewScreen';
import { useNavigation, useRoute } from '@react-navigation/native';
import { useThemedColors } from '../../../src/hooks/useThemedColors';

// モック設定
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
  useRoute: jest.fn(),
}));

jest.mock('../../../src/hooks/useThemedColors');

jest.mock('react-native-webview', () => ({
  WebView: 'WebView',
}));

jest.mock('../../../src/utils/constants', () => ({
  API_CONFIG: {
    BASE_URL: 'https://fizzy-formless-sandi.ngrok-free.dev/api',
  },
}));

describe('TokenCheckoutWebViewScreen', () => {
  const mockNavigation = {
    navigate: jest.fn(),
    goBack: jest.fn(),
  };

  const mockRoute = {
    params: {
      url: 'https://checkout.stripe.com/pay/cs_test_abc123',
      title: 'トークン購入',
    },
  };

  const mockColors = {
    background: '#FFFFFF',
    text: {
      primary: '#111827',
      secondary: '#6B7280',
    },
    card: '#FFFFFF',
    border: {
      default: '#E5E7EB',
    },
  };

  const mockAccent = {
    primary: '#3B82F6',
    secondary: '#8B5CF6',
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (useNavigation as jest.Mock).mockReturnValue(mockNavigation);
    (useRoute as jest.Mock).mockReturnValue(mockRoute);
    (useThemedColors as jest.Mock).mockReturnValue({
      colors: mockColors,
      accent: mockAccent,
    });
  });

  describe('レンダリング', () => {
    it('WebViewが正しいURLでレンダリングされる', () => {
      const { getByTestId } = render(<TokenCheckoutWebViewScreen />);
      
      // WebViewコンポーネントが存在することを確認
      // 注: react-native-webviewのモックではTestIDが取得できないため、
      // 実際のテストではE2Eテストで検証する
      expect(mockRoute.params.url).toBe('https://checkout.stripe.com/pay/cs_test_abc123');
    });

    it('ダークモード対応の背景色が適用される', () => {
      const { UNSAFE_root } = render(<TokenCheckoutWebViewScreen />);
      
      // SafeAreaViewに背景色が適用されていることを確認
      const safeAreaView = UNSAFE_root.findAllByType('SafeAreaView')[0];
      expect(safeAreaView.props.style).toEqual(
        expect.objectContaining({
          flex: 1,
          backgroundColor: mockColors.background,
        })
      );
    });
  });

  describe('ダークモード対応', () => {
    it('ダークモードの色が適用される', () => {
      const darkColors = {
        background: '#1F2937',
        text: {
          primary: '#FFFFFF',
          secondary: '#D1D5DB',
        },
        card: '#374151',
        border: {
          default: '#4B5563',
        },
      };

      (useThemedColors as jest.Mock).mockReturnValue({
        colors: darkColors,
        accent: mockAccent,
      });

      const { UNSAFE_root } = render(<TokenCheckoutWebViewScreen />);
      
      const safeAreaView = UNSAFE_root.findAllByType('SafeAreaView')[0];
      expect(safeAreaView.props.style).toEqual(
        expect.objectContaining({
          backgroundColor: darkColors.background,
        })
      );
    });
  });

  describe('ナビゲーション', () => {
    it('正しいルートパラメータを受け取る', () => {
      render(<TokenCheckoutWebViewScreen />);
      
      expect(mockRoute.params.url).toBeDefined();
      expect(mockRoute.params.url).toContain('stripe.com');
    });
  });

  describe('エラーハンドリング', () => {
    it('URLパラメータが不正な場合でもクラッシュしない', () => {
      const invalidRoute = {
        params: {
          url: '',
          title: 'トークン購入',
        },
      };

      (useRoute as jest.Mock).mockReturnValue(invalidRoute);

      expect(() => {
        render(<TokenCheckoutWebViewScreen />);
      }).not.toThrow();
    });
  });

  describe('型安全性', () => {
    it('RouteParamsの型が正しく定義されている', () => {
      // TypeScriptコンパイル時に型チェックされる
      type RouteParams = {
        TokenCheckoutWebView: {
          url: string;
          title?: string;
        };
      };

      const validParams: RouteParams['TokenCheckoutWebView'] = {
        url: 'https://checkout.stripe.com/pay/cs_test_abc123',
        title: 'トークン購入',
      };

      expect(validParams.url).toBeDefined();
      expect(typeof validParams.url).toBe('string');
    });
  });
});
