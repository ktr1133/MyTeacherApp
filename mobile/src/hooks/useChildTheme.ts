/**
 * 子ども向けテーマ判定Hook
 * 
 * ThemeContextから現在のテーマを取得し、子ども向けかどうかを返す
 * レスポンシブユーティリティ（getFontSize等）の第3引数で使用
 * 
 * @see /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 * @see /home/ktr/mtdev/mobile/src/contexts/ThemeContext.tsx
 */

import { useTheme } from '../contexts/ThemeContext';

/**
 * 子ども向けテーマの使用状態を取得
 * 
 * @returns 子ども向けテーマの場合true、大人向けの場合false
 * 
 * @example
 * ```tsx
 * import { useResponsive, getFontSize } from '@/utils/responsive';
 * import { useChildTheme } from '@/hooks/useChildTheme';
 * 
 * const MyScreen = () => {
 *   const { width } = useResponsive();
 *   const isChildTheme = useChildTheme();
 *   const theme = isChildTheme ? 'child' : 'adult';
 *   
 *   const styles = StyleSheet.create({
 *     title: {
 *       // 子ども向け: 基準サイズの1.2倍
 *       fontSize: getFontSize(18, width, theme),
 *     },
 *   });
 *   
 *   return <View>...</View>;
 * };
 * ```
 * 
 * @throws Error ThemeProvider外で使用した場合
 */
export const useChildTheme = (): boolean => {
  const { theme } = useTheme();
  return theme === 'child';
};
