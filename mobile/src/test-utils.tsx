/**
 * テスト用ヘルパー関数
 * 
 * 全てのContextプロバイダーでラップしたコンポーネントをレンダリング
 */
import React from 'react';
import { render, RenderOptions } from '@testing-library/react-native';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import { AvatarProvider } from './contexts/AvatarContext';

/**
 * 全てのプロバイダーでラップするラッパー
 */
const AllTheProviders: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  return (
    <AuthProvider>
      <ThemeProvider>
        <AvatarProvider>
          {children}
        </AvatarProvider>
      </ThemeProvider>
    </AuthProvider>
  );
};

/**
 * カスタムレンダリング関数
 * 
 * 全てのContextプロバイダーでラップしてコンポーネントをレンダリング
 */
const customRender = (
  ui: React.ReactElement,
  options?: Omit<RenderOptions, 'wrapper'>
) => render(ui, { wrapper: AllTheProviders, ...options });

// react-testing-libraryの全てのエクスポートを再エクスポート
export * from '@testing-library/react-native';

// カスタムレンダリング関数をrenderとして上書き
export { customRender as render };
