/**
 * レスポンシブデザインユーティリティ
 * 
 * Dimensions APIを使用してデバイスサイズに応じた動的スケーリングを提供
 * 
 * @see /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 */

import { useState, useEffect } from 'react';
import { Dimensions, Platform, ScaledSize } from 'react-native';

/**
 * デバイスサイズカテゴリ
 * 
 * - xs: 超小型 (〜320px) - Galaxy Fold, iPhone SE 1st
 * - sm: 小型 (321px〜374px) - iPhone SE 2nd/3rd, Pixel 4a
 * - md: 標準 (375px〜413px) - iPhone 12/13/14, Pixel 7
 * - lg: 大型 (414px〜767px) - iPhone Pro Max
 * - tablet-sm: タブレット小 (768px〜1023px) - iPad mini
 * - tablet: タブレット (1024px〜) - iPad Pro
 */
export type DeviceSize = 'xs' | 'sm' | 'md' | 'lg' | 'tablet-sm' | 'tablet';

/**
 * テーマタイプ
 * 
 * - adult: 大人向け（標準フォントサイズ）
 * - child: 子ども向け（フォントサイズ1.2倍）
 */
export type ThemeType = 'adult' | 'child';

/**
 * useResponsive Hookの返り値
 */
export interface ResponsiveResult {
  /** 現在の画面幅 */
  width: number;
  /** 現在の画面高さ */
  height: number;
  /** デバイスサイズカテゴリ */
  deviceSize: DeviceSize;
  /** 縦向きかどうか */
  isPortrait: boolean;
  /** 横向きかどうか */
  isLandscape: boolean;
}

/**
 * デバイスサイズカテゴリを判定
 * 
 * @param width - 画面幅（px）
 * @returns デバイスサイズカテゴリ
 * 
 * @example
 * const size = getDeviceSize(375); // 'sm'
 */
export const getDeviceSize = (width: number): DeviceSize => {
  if (width <= 320) return 'xs';
  if (width <= 374) return 'sm';
  if (width <= 413) return 'md';
  if (width <= 767) return 'lg';
  if (width <= 1023) return 'tablet-sm';
  return 'tablet';
};

/**
 * レスポンシブデザイン用カスタムHook
 * 
 * 画面サイズと向きを監視し、変更時に自動再レンダリング
 * 
 * @returns 画面情報オブジェクト
 * 
 * @example
 * const MyScreen = () => {
 *   const { width, deviceSize, isPortrait } = useResponsive();
 *   
 *   return (
 *     <View>
 *       <Text>Width: {width}px</Text>
 *       <Text>Device: {deviceSize}</Text>
 *       <Text>Portrait: {isPortrait ? 'Yes' : 'No'}</Text>
 *     </View>
 *   );
 * };
 */
export const useResponsive = (): ResponsiveResult => {
  const [dimensions, setDimensions] = useState<ScaledSize>(
    Dimensions.get('window')
  );

  useEffect(() => {
    const subscription = Dimensions.addEventListener(
      'change',
      ({ window }) => {
        setDimensions(window);
      }
    );

    // クリーンアップ: メモリリーク防止
    return () => subscription?.remove();
  }, []);

  const { width, height } = dimensions;
  const deviceSize = getDeviceSize(width);
  const isPortrait = height > width;
  const isLandscape = width > height;

  return {
    width,
    height,
    deviceSize,
    isPortrait,
    isLandscape,
  };
};

/**
 * デバイスサイズ別のスケール係数マップ
 */
const FONT_SCALE_MAP: Record<DeviceSize, number> = {
  xs: 0.8,
  sm: 0.9,
  md: 1.0,
  lg: 1.05,
  'tablet-sm': 1.1,
  tablet: 1.15,
};

/**
 * 大人向けテーマのフォントサイズを計算
 * 
 * @param baseSize - 基準フォントサイズ（px）
 * @param width - 画面幅（px）
 * @returns スケーリング後のフォントサイズ
 * 
 * @example
 * const fontSize = getAdultFontSize(18, 375); // 18 * 0.9 = 16.2
 */
export const getAdultFontSize = (baseSize: number, width: number): number => {
  const deviceSize = getDeviceSize(width);
  const scale = FONT_SCALE_MAP[deviceSize];
  return baseSize * scale;
};

/**
 * 子ども向けテーマのフォントサイズを計算
 * 
 * 大人向けより20%拡大して視認性を向上
 * 
 * @param baseSize - 基準フォントサイズ（px）
 * @param width - 画面幅（px）
 * @returns スケーリング後のフォントサイズ
 * 
 * @example
 * const fontSize = getChildFontSize(18, 375); // (18 * 0.9) * 1.2 = 19.44
 */
export const getChildFontSize = (baseSize: number, width: number): number => {
  const adultSize = getAdultFontSize(baseSize, width);
  return adultSize * 1.2; // 20%拡大
};

/**
 * テーマに応じたフォントサイズを計算
 * 
 * @param baseSize - 基準フォントサイズ（px）
 * @param width - 画面幅（px）
 * @param theme - テーマタイプ（デフォルト: 'adult'）
 * @returns スケーリング後のフォントサイズ
 * 
 * @example
 * const { width } = useResponsive();
 * const theme = isChildTheme ? 'child' : 'adult';
 * 
 * const styles = StyleSheet.create({
 *   title: {
 *     fontSize: getFontSize(18, width, theme),
 *   },
 * });
 */
export const getFontSize = (
  baseSize: number,
  width: number,
  theme: ThemeType = 'adult'
): number => {
  return theme === 'child'
    ? getChildFontSize(baseSize, width)
    : getAdultFontSize(baseSize, width);
};

/**
 * デバイスサイズ別の余白スケール係数マップ
 */
const SPACING_SCALE_MAP: Record<DeviceSize, number> = {
  xs: 0.75,
  sm: 0.85,
  md: 1.0,
  lg: 1.1,
  'tablet-sm': 1.2,
  tablet: 1.3,
};

/**
 * 余白サイズを計算（50%最小値保証）
 * 
 * 極小デバイスでも読みやすさを維持するため、
 * スケーリング後も基準値の50%を下回らない
 * 
 * @param baseSpacing - 基準余白サイズ（px）
 * @param width - 画面幅（px）
 * @returns スケーリング後の余白サイズ
 * 
 * @example
 * const padding = getSpacing(16, 280); // max(16 * 0.75, 16 * 0.5) = 12
 */
export const getSpacing = (baseSpacing: number, width: number): number => {
  const minSpacing = baseSpacing * 0.5; // 50%最小値
  const deviceSize = getDeviceSize(width);
  const scale = SPACING_SCALE_MAP[deviceSize];
  const spacing = baseSpacing * scale;

  return Math.max(spacing, minSpacing);
};

/**
 * デバイスサイズ別の角丸スケール係数マップ
 */
const BORDER_RADIUS_SCALE_MAP: Record<DeviceSize, number> = {
  xs: 0.8,
  sm: 0.9,
  md: 1.0,
  lg: 1.05,
  'tablet-sm': 1.1,
  tablet: 1.15,
};

/**
 * 角丸サイズを計算
 * 
 * @param baseRadius - 基準角丸サイズ（px）
 * @param width - 画面幅（px）
 * @returns スケーリング後の角丸サイズ
 * 
 * @example
 * const borderRadius = getBorderRadius(16, 375); // 16 * 0.9 = 14.4
 */
export const getBorderRadius = (
  baseRadius: number,
  width: number
): number => {
  const deviceSize = getDeviceSize(width);
  const scale = BORDER_RADIUS_SCALE_MAP[deviceSize];
  return baseRadius * scale;
};

/**
 * シャドウスタイルオブジェクト（Platform別）
 */
export interface ShadowStyle {
  // Android
  elevation?: number;
  // iOS
  shadowColor?: string;
  shadowOffset?: { width: number; height: number };
  shadowOpacity?: number;
  shadowRadius?: number;
}

/**
 * Platform別のシャドウスタイルを生成
 * 
 * - Android: elevation プロパティ
 * - iOS: shadowColor, shadowOffset, shadowOpacity, shadowRadius
 * 
 * @param elevation - シャドウの強度（1〜24）
 * @returns Platform別シャドウスタイル
 * 
 * @example
 * const styles = StyleSheet.create({
 *   card: {
 *     padding: 16,
 *     borderRadius: 8,
 *     ...getShadow(4), // Platform別シャドウ適用
 *   },
 * });
 */
export const getShadow = (elevation: number): ShadowStyle => {
  if (Platform.OS === 'android') {
    return {
      elevation,
    };
  }

  // iOS: elevationから4つのshadowプロパティを計算
  const shadowIntensity = elevation / 8;

  return {
    shadowColor: '#000',
    shadowOffset: {
      width: 0,
      height: elevation / 2,
    },
    shadowOpacity: 0.1 + shadowIntensity * 0.15,
    shadowRadius: elevation,
  };
};

/**
 * ヘッダータイトル用のテキストプロパティ
 * 
 * Web版で発生する折り返し問題への対策
 * 
 * @example
 * <Text
 *   style={styles.headerTitle}
 *   {...getHeaderTitleProps()}
 * >
 *   サブスクリプション管理
 * </Text>
 */
export const getHeaderTitleProps = () => ({
  numberOfLines: 2,
  adjustsFontSizeToFit: true,
  minimumFontScale: 0.7,
});

/**
 * モーダルカード用のスタイル計算（Android見切れ対策）
 * 
 * @param width - 画面幅（px）
 * @param horizontalPadding - 横方向余白（デフォルト: 16px）
 * @returns モーダルカードスタイル
 * 
 * @example
 * const { width } = useResponsive();
 * const modalCardStyle = getModalCardStyle(width);
 * 
 * <View style={modalCardStyle.container}>
 *   <View style={modalCardStyle.card}>...</View>
 * </View>
 */
export const getModalCardStyle = (
  width: number,
  horizontalPadding: number = 16
) => {
  const padding = getSpacing(horizontalPadding, width);
  const cardWidth = width - padding * 2;

  return {
    container: {
      paddingHorizontal: padding,
    },
    card: {
      width: cardWidth,
      maxWidth: '100%' as const,
    },
  };
};
