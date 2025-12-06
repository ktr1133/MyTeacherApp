/**
 * テーマコンテキスト
 * 
 * Web版のミドルウェア（ShareThemeMiddleware）に相当
 * View::share('theme', $theme) と同じくグローバルにテーマを提供
 * 
 * @note 暫定実装: ProfileServiceが /api/v1/profile/edit を使用
 * @todo Laravel側で /api/v1/user/current API作成後に切り替え
 */

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { ThemeType } from '../types/user.types';
import { profileService } from '../services/profile.service';

/**
 * テーマコンテキストの型定義
 * 
 * Web版の Blade内での $theme 変数に相当
 */
interface ThemeContextType {
  theme: ThemeType;
  isLoading: boolean;
  refreshTheme: () => Promise<void>;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

/**
 * テーマプロバイダー
 * 
 * App.tsx でラップすることで全コンポーネントでテーマを利用可能にする
 * Web版のミドルウェアで View::share() するのと同じ役割
 * 
 * @example
 * ```tsx
 * <ThemeProvider>
 *   <App />
 * </ThemeProvider>
 * ```
 */
export const ThemeProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [theme, setTheme] = useState<ThemeType>('adult');
  const [isLoading, setIsLoading] = useState<boolean>(true);

  /**
   * Laravel API からプロフィール情報を取得してテーマを設定
   */
  const loadTheme = async () => {
    try {
      setIsLoading(true);
      const profile = await profileService.getProfile();
      setTheme(profile.theme);
    } catch (error: any) {
      console.warn('Failed to load theme from API, using default', error);
      // エラー時は大人向けテーマをデフォルト
      setTheme('adult');
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * テーマを再取得（プロフィール更新後に呼び出す）
   */
  const refreshTheme = async () => {
    await loadTheme();
  };

  // 初回マウント時にテーマを取得
  useEffect(() => {
    loadTheme();
  }, []);

  return (
    <ThemeContext.Provider value={{ theme, isLoading, refreshTheme }}>
      {children}
    </ThemeContext.Provider>
  );
};

/**
 * テーマフック
 * 
 * Web版の Blade内で $theme を参照するのと同じ
 * 
 * @example
 * ```tsx
 * const { theme } = useTheme();
 * 
 * // Web版: {{ $theme === 'child' ? 'やることリスト' : 'タスク一覧' }}
 * // React Native版:
 * <Text>{theme === 'child' ? 'やることリスト' : 'タスク一覧'}</Text>
 * ```
 * 
 * @throws Error ThemeProvider外で使用した場合
 */
export const useTheme = (): ThemeContextType => {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme must be used within ThemeProvider');
  }
  return context;
};
