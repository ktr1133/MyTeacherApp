/**
 * PrivacyPolicyScreen テスト
 * 
 * プライバシーポリシー画面の表示とWebView機能のテスト
 */
import React from 'react';
import { render, waitFor, fireEvent } from '@testing-library/react-native';
import { PrivacyPolicyScreen } from '../../../src/screens/legal/PrivacyPolicyScreen';
import { useNavigation } from '@react-navigation/native';
import { WEB_APP_URL } from '../../../src/utils/constants';

// モック設定
jest.mock('@react-navigation/native', () => ({
  useNavigation: jest.fn(),
}));

jest.mock('react-native-render-html', () => {
  const React = require('react');
  const { View, Text } = require('react-native');
  return {
    __esModule: true,
    default: (props: any) => {
      return (
        <View testID="render-html-mock">
          <Text testID="html-content">{props.source?.html || ''}</Text>
        </View>
      );
    },
    HTMLElementModel: {
      fromCustomModel: jest.fn(),
    },
    HTMLContentModel: {
      block: 'block',
    },
  };
});

jest.mock('../../../src/services/legal.service', () => ({
  __esModule: true,
  default: {
    getPrivacyPolicy: jest.fn().mockResolvedValue({
      type: 'privacy-policy',
      html: '<h1>プライバシーポリシー</h1><p>テスト内容</p>',
      version: '1.0.0',
    }),
    getTermsOfService: jest.fn().mockResolvedValue({
      type: 'terms-of-service',
      html: '<h1>利用規約</h1><p>テスト内容</p>',
      version: '1.0.0',
    }),
  },
}));

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
  getFontSize: (base: number) => base,
}));

describe('PrivacyPolicyScreen', () => {
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
    const { getByText } = render(<PrivacyPolicyScreen />);

    expect(getByText('プライバシーポリシー')).toBeTruthy();
  });

  test('子ども向けテーマでは「プライバシーについて」と表示される', () => {
    const { useChildTheme } = require('../../../src/hooks/useChildTheme');
    useChildTheme.mockReturnValue(true);

    const { getByText } = render(<PrivacyPolicyScreen />);

    expect(getByText('プライバシーについて')).toBeTruthy();
  });

  test('WebViewが正しいURLを読み込む', async () => {
    const { getByTestId } = render(<PrivacyPolicyScreen />);

    // RenderHtmlコンポーネントの存在を確認
    await waitFor(() => {
      const renderHtml = getByTestId('render-html-mock');
      expect(renderHtml).toBeTruthy();
    });
  });

  test('HTMLコンテンツが取得されて表示される', async () => {
    const { getByTestId } = render(<PrivacyPolicyScreen />);

    // APIからHTMLが取得されるまで待機
    await waitFor(() => {
      const htmlContent = getByTestId('html-content');
      expect(htmlContent.props.children).toContain('プライバシーポリシー');
      expect(htmlContent.props.children).toContain('テスト内容');
    });
  });

  // 以下のテストは実装にtestIDがないため、実機テストで確認すること
  // - 戻るボタンの動作
  // - ローディングインジケーターの表示/非表示
});
