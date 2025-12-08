/**
 * テーマコンテキスト
 * 
 * Web版のミドルウェア（ShareThemeMiddleware）に相当
 * View::share('theme', $theme) と同じくグローバルにテーマを提供
 * 
 * Laravel API: GET /api/v1/user/current からテーマ情報を取得
 */

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { ThemeType } from '../types/user.types';
import { userService } from '../services/user.service';
import { useAuth } from './AuthContext';

/**
 * テーマコンテキストの型定義
 * 
 * Web版の Blade内での $theme 変数に相当
 */
export interface ThemeContextType {
  theme: ThemeType;
  setTheme: (theme: ThemeType) => void;
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
  const { isAuthenticated } = useAuth();
  const [theme, setTheme] = useState<ThemeType>('adult');
  const [isLoading, setIsLoading] = useState<boolean>(true);

  /**
   * Laravel API からユーザー情報を取得してテーマを設定
   * 認証済みの場合のみAPIを呼び出す
   */
  const loadTheme = async () => {
    try {
      setIsLoading(true);
      
      // 未認証の場合はAPIを呼ばない
      if (!isAuthenticated) {
        setTheme('adult'); // デフォルトテーマ
        setIsLoading(false);
        return;
      }
      
      const currentUser = await userService.getCurrentUser();
      setTheme(currentUser.theme);
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

  // 認証状態が変わったらテーマを再取得
  useEffect(() => {
    loadTheme();
  }, [isAuthenticated]);

  const contextValue: ThemeContextType = {
    theme,
    setTheme,
    isLoading,
    refreshTheme,
  };

  return (
    <ThemeContext.Provider value={contextValue}>
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
