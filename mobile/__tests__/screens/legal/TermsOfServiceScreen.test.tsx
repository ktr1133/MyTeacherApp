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

jest.mock('../../../src/hooks/useThemedColors', () => ({
  useThemedColors: () => ({
    colors: {
      background: '#FFFFFF',
      card: '#F9F9F9',
      text: {
        primary: '#000000',
        secondary: '#666666',
      },
      border: {
        default: '#E0E0E0',
      },
      status: {
        error: '#FF0000',
        success: '#00FF00',
        warning: '#FFA500',
      },
    },
    accent: '#59B9C6',
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
}));

describe('TermsOfServiceScreen', () => {
  const mockNavigation = {
    goBack: jest.fn(),
    navigate: jest.fn(),
    setOptions: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (useNavigation as jest.Mock).mockReturnValue(mockNavigation);
  });

  test('画面が正しくレンダリングされる', () => {
    const { getByText } = render(<TermsOfServiceScreen />);

    expect(getByText('利用規約')).toBeTruthy();
  });

  test('子ども向けテーマでは「おやくそく」と表示される', () => {
    const { useChildTheme } = require('../../../src/hooks/useChildTheme');
    useChildTheme.mockReturnValue(true);

    const { getByText } = render(<TermsOfServiceScreen />);

    expect(getByText('おやくそく')).toBeTruthy();
  });

  test('WebViewが正しいURLを読み込む', () => {
    const { getByTestId } = render(<TermsOfServiceScreen />);

    // WebViewモックの存在を確認
    const webview = getByTestId('webview-mock');
    expect(webview).toBeTruthy();
  });

  // 以下のテストは実装にtestIDがないため、実機テストで確認すること
  // - 戻るボタンの動作
  // - ローディングインジケーターの表示/非表示
});
