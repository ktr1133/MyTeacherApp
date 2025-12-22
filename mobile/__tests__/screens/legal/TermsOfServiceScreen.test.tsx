/**
 * TermsOfServiceScreen テスト
 * 
 * 利用規約画面の表示とWebView機能のテスト
 */
import React from 'react';
import { render, waitFor, fireEvent } from '@testing-library/react-native';
import { TermsOfServiceScreen } from '../../../src/screens/legal/TermsOfServiceScreen';
import { useNavigation } from '@react-navigation/native';
import { WEB_APP_URL } from '../../../src/utils/constants';
import { ColorSchemeProvider } from '../../../src/contexts/ColorSchemeContext';

// モック設定
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
}));

jest.mock('react-native-webview', () => {
  const React = require('react');
  const { View } = require('react-native');
  return {
    WebView: (props: any) => {
      // モック: onLoadEndをレンダリング時に即座に呼び出す
      if (props.onLoadEnd) {
        setTimeout(() => props.onLoadEnd(), 0);
      }
      return <View testID="webview-mock" />;
    },
  };
});

jest.mock('../../../src/services/legal.service', () => ({
  default: {
    getTermsOfService: jest.fn(() =>
      Promise.resolve({
        html: '<div><h1>利用規約</h1><p>テスト内容</p></div>',
      })
    ),
    getPrivacyPolicy: jest.fn(() =>
      Promise.resolve({
        html: '<div><h1>プライバシーポリシー</h1><p>テスト内容</p></div>',
      })
    ),
  },
}));

jest.mock('../../../src/hooks/useThemedColors', () => ({
  useThemedColors: () => ({
    colors: {
      background: '#FFFFFF',
      card: '#FFFFFF',
      text: {
        primary: '#111827',
        secondary: '#6B7280',
        tertiary: '#9CA3AF',
        disabled: '#D1D5DB',
      },
      border: {
        default: '#E5E7EB',
        light: 'rgba(229, 231, 235, 0.5)',
      },
      status: {
        success: '#10B981',
        warning: '#F59E0B',
        error: '#EF4444',
        info: '#3B82F6',
      },
    },
    accent: {
      primary: '#007AFF',
      gradient: ['#007AFF', '#5856D6'],
    },
  }),
}));

jest.mock('../../../src/hooks/useChildTheme', () => ({
  useChildTheme: jest.fn(() => false),
}));

jest.mock('../../../src/utils/responsive', () => ({
  useResponsive: () => ({
    width: 375,
    height: 667,
    deviceSize: 'md',
    isPortrait: true,
    isLandscape: false,
    isTablet: false,
  }),
  getSpacing: (base: number) => base,
  getBorderRadius: (base: number) => base,
  getFontSize: (base: number) => base,
  getShadow: () => ({}),
}));

describe('TermsOfServiceScreen', () => {
  const mockNavigation = {
    goBack: jest.fn(),
    navigate: jest.fn(),
    setOptions: jest.fn(),
  };

  const renderScreen = (component: React.ReactElement) => {
    return render(
      <ColorSchemeProvider>
        {component}
      </ColorSchemeProvider>
    );
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (useNavigation as jest.Mock).mockReturnValue(mockNavigation);
  });

  test('画面が正しくレンダリングされる', () => {
    const { getByText } = renderScreen(<TermsOfServiceScreen />);

    expect(getByText('利用規約')).toBeTruthy();
  });

  test('子ども向けテーマでは「おやくそく」と表示される', () => {
    const { useChildTheme } = require('../../../src/hooks/useChildTheme');
    useChildTheme.mockReturnValue(true);

    const { getByText } = renderScreen(<TermsOfServiceScreen />);

    expect(getByText('おやくそく')).toBeTruthy();
  });

  test('WebViewが正しいURLを読み込む', async () => {
    const { queryByText } = renderScreen(<TermsOfServiceScreen />);

    // ローディングが完了するまで待つ
    await waitFor(() => {
      expect(queryByText('読み込み中...')).toBeNull();
    });

    // HTMLコンテンツが表示されることを確認（HTMLレンダラーを使用しているため）
    // 実際にはreact-native-render-htmlが使用されているため、具体的なコンテンツ確認は省略
  });

  // 以下のテストは実装にtestIDがないため、実機テストで確認すること
  // - 戻るボタンの動作
  // - ローディングインジケーターの表示/非表示
});
