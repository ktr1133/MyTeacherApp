/**
 * ColorSchemeContext - カラースキーマ管理
 * 
 * OSレベルのダークモード検知と手動切り替えをサポート。
 * - 'auto': OSの設定に追従（iOS/Android両対応）
 * - 'light': ライトモードに固定
 * - 'dark': ダークモードに固定
 */

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { useColorScheme as useRNColorScheme, Appearance } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Colors, ColorPalette } from '../utils/colors';

/** 設定保存キー */
const STORAGE_KEY = '@MyTeacher:colorSchemeMode';

/** カラースキーマモード */
export type ColorSchemeMode = 'light' | 'dark' | 'auto';

/** ColorSchemeContext型定義 */
export interface ColorSchemeContextType {
  /** 現在のカラースキーマ（light/dark） */
  colorScheme: 'light' | 'dark';
  
  /** ユーザー設定モード（auto/light/dark） */
  mode: ColorSchemeMode;
  
  /** カラースキーマを手動で設定 */
  setMode: (mode: ColorSchemeMode) => void;
  
  /** 現在のカラーパレット */
  colors: ColorPalette;
  
  /** ダークモードかどうか */
  isDark: boolean;
}

/** ColorSchemeContext */
const ColorSchemeContext = createContext<ColorSchemeContextType | undefined>(undefined);

/**
 * ColorSchemeProvider
 * 
 * アプリ全体のカラースキーマを管理するプロバイダー。
 * App.tsx で ThemeProvider の外側にラップする。
 */
export const ColorSchemeProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  // OSの設定を取得（iOS/Android両対応）
  const systemColorScheme = useRNColorScheme();
  
  // ユーザー設定モード（デフォルトは'auto'）
  const [mode, setModeState] = useState<ColorSchemeMode>('auto');
  
  // OSのカラースキーマを状態として保持（Appearance変更の確実な検知）
  const [detectedSystemScheme, setDetectedSystemScheme] = useState<'light' | 'dark'>(() => {
    // 初期値を確実に設定
    const initial = systemColorScheme ?? 'light';
    console.log('[ColorScheme] 初期システムカラースキーマ:', initial);
    return initial;
  });
  
  // 実際に適用するカラースキーマ
  const colorScheme: 'light' | 'dark' = 
    mode === 'auto' 
      ? detectedSystemScheme // OSの設定に従う
      : mode; // 手動設定値を使用
  
  // デバッグ用：カラースキーマ決定のログ
  useEffect(() => {
    console.log('[ColorScheme] カラースキーマ決定:', {
      mode,
      detectedSystemScheme,
      systemColorScheme,
      finalColorScheme: colorScheme
    });
  }, [mode, detectedSystemScheme, systemColorScheme, colorScheme]);
  
  /**
   * 初期化: AsyncStorageから前回の設定を読み込み
   */
  useEffect(() => {
    const loadMode = async () => {
      try {
        const saved = await AsyncStorage.getItem(STORAGE_KEY);
        if (saved && ['auto', 'light', 'dark'].includes(saved)) {
          setModeState(saved as ColorSchemeMode);
          console.log('[ColorScheme] 設定読み込み:', saved);
        }
      } catch (error) {
        console.error('[ColorScheme] 設定読み込みエラー:', error);
      }
    };
    
    loadMode();
  }, []);
  
  /**
   * useRNColorScheme()の変更を監視して状態に反映
   * （autoモード時のOS設定変更を確実に検知）
   */
  useEffect(() => {
    if (systemColorScheme && mode === 'auto') {
      setDetectedSystemScheme(systemColorScheme);
      console.log('[ColorScheme] useRNColorScheme更新 (auto mode):', systemColorScheme);
    }
  }, [systemColorScheme, mode]);
  
  /**
   * OSの設定変更を監視（バックアップ: Appearance API直接監視）
   */
  useEffect(() => {
    console.log('[ColorScheme] Appearance listener setup, mode:', mode);
    
    const subscription = Appearance.addChangeListener(({ colorScheme: newScheme }) => {
      console.log('[ColorScheme] Appearance変更検知 - mode:', mode, 'newScheme:', newScheme);
      if (newScheme && mode === 'auto') {
        setDetectedSystemScheme(newScheme);
        console.log('[ColorScheme] detectedSystemScheme更新:', newScheme);
      }
    });
    
    return () => {
      console.log('[ColorScheme] Appearance listener cleanup');
      subscription.remove();
    };
  }, [mode]);
  
  /**
   * 手動切り替え
   * 
   * 設定画面から呼び出される。AsyncStorageに永続化。
   */
  const setMode = async (newMode: ColorSchemeMode) => {
    try {
      setModeState(newMode);
      await AsyncStorage.setItem(STORAGE_KEY, newMode);
      console.log('[ColorScheme] 設定保存:', newMode);
    } catch (error) {
      console.error('[ColorScheme] 設定保存エラー:', error);
    }
  };
  
  // コンテキスト値
  const contextValue: ColorSchemeContextType = {
    colorScheme,
    mode,
    setMode,
    colors: Colors[colorScheme],
    isDark: colorScheme === 'dark',
  };
  
  return (
    <ColorSchemeContext.Provider value={contextValue}>
      {children}
    </ColorSchemeContext.Provider>
  );
};

/**
 * useColorScheme カスタムフック
 * 
 * コンポーネントでカラースキーマ情報を取得する。
 * 
 * @example
 * const { colorScheme, colors, isDark, setMode } = useColorScheme();
 * 
 * @throws {Error} ColorSchemeProvider 外で使用した場合
 */
export const useColorScheme = (): ColorSchemeContextType => {
  const context = useContext(ColorSchemeContext);
  
  if (!context) {
    throw new Error('useColorScheme must be used within ColorSchemeProvider');
  }
  
  return context;
};
