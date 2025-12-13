/**
 * useThemedColors カスタムフック
 * 
 * テーマ（adult/child）とカラースキーマ（light/dark）を組み合わせた
 * 統合カラーパレットを提供する。
 * 
 * @example
 * const { colors, accent } = useThemedColors();
 * 
 * const styles = StyleSheet.create({
 *   container: {
 *     backgroundColor: colors.background, // ダークモード自動対応
 *   },
 *   button: {
 *     backgroundColor: accent.primary, // テーマ別アクセントカラー
 *   },
 * });
 */

import { useContext } from 'react';
import { useColorScheme } from '../contexts/ColorSchemeContext';
import { ThemeContext } from '../contexts/ThemeContext';
import { ColorPalette, ThemeColors } from '../utils/colors';

/**
 * useThemedColors の返り値型
 */
export interface ThemedColors {
  /** 現在のカラーパレット（light/dark） */
  colors: ColorPalette;
  
  /** テーマ別アクセントカラー（adult: 青系, child: オレンジ系） */
  accent: {
    primary: string;
    gradient: string[];
  };
  
  /** ダークモードかどうか */
  isDark: boolean;
  
  /** 現在のテーマ（adult/child） */
  theme: 'adult' | 'child';
}

/**
 * useThemedColors カスタムフック
 * 
 * ColorSchemeContext と ThemeContext を統合し、
 * 4つの組み合わせ（adult/child × light/dark）に対応した
 * カラーパレットを提供する。
 * 
 * @returns {ThemedColors} 統合カラーパレット
 * 
 * @throws {Error} ColorSchemeProvider または ThemeProvider 外で使用した場合
 * 
 * @example
 * // 基本的な使い方
 * const MyComponent = () => {
 *   const { colors, accent } = useThemedColors();
 *   
 *   return (
 *     <View style={{ backgroundColor: colors.background }}>
 *       <Text style={{ color: colors.text.primary }}>テキスト</Text>
 *       <Button style={{ backgroundColor: accent.primary }} />
 *     </View>
 *   );
 * };
 * 
 * @example
 * // グラデーション使用例
 * import { LinearGradient } from 'expo-linear-gradient';
 * 
 * const MyButton = () => {
 *   const { accent } = useThemedColors();
 *   
 *   return (
 *     <LinearGradient colors={accent.gradient}>
 *       <Text>ボタン</Text>
 *     </LinearGradient>
 *   );
 * };
 */
export const useThemedColors = (): ThemedColors => {
  // ColorSchemeContext からカラースキーマ（light/dark）を取得
  const { colors, isDark } = useColorScheme();
  
  // ThemeContext からテーマ（adult/child）を取得
  const themeContext = useContext(ThemeContext);
  
  if (!themeContext) {
    throw new Error('useThemedColors must be used within ThemeProvider');
  }
  
  const { theme } = themeContext;
  
  // テーマ別アクセントカラーを取得
  const accent = ThemeColors[theme];
  
  return {
    colors,
    accent,
    isDark,
    theme,
  };
};
