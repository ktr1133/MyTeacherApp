/**
 * カラーパレット定義
 * 
 * WebアプリのTailwind CSS設定に基づいた統一カラーシステム。
 * ライトモード・ダークモードの両方に対応。
 */

/**
 * ライトモード・ダークモード共通のカラーパレット構造
 */
export interface ColorPalette {
  /** 背景色 */
  background: string;
  /** サーフェス色（カード、モーダル等） */
  surface: string;
  /** カード背景色 */
  card: string;
  
  /** テキスト色 */
  text: {
    /** 主要テキスト */
    primary: string;
    /** 補助テキスト */
    secondary: string;
    /** 説明文・キャプション */
    tertiary: string;
    /** 無効化テキスト */
    disabled: string;
  };
  
  /** ボーダー色 */
  border: {
    /** デフォルトボーダー */
    default: string;
    /** 半透明ボーダー */
    light: string;
  };
  
  /** ステータス色 */
  status: {
    success: string;
    warning: string;
    error: string;
    info: string;
  };
  
  /** 情報ボックス用カラー */
  info: {
    background: string;
    border: string;
    text: string;
  };
  
  /** オーバーレイ */
  overlay: string;
}

/**
 * ライトモードのカラーパレット
 * 
 * Tailwind CSS対応表:
 * - bg-white (#FFFFFF) → background
 * - bg-gray-50 (#F9FAFB) → surface
 * - bg-white (#FFFFFF) → card
 * - text-gray-900 (#111827) → text.primary
 * - text-gray-500 (#6B7280) → text.secondary
 * - text-gray-400 (#9CA3AF) → text.tertiary
 * - border-gray-200 (#E5E7EB) → border.default
 */
export const LightColors: ColorPalette = {
  background: '#FFFFFF',        // bg-white
  surface: '#F9FAFB',           // bg-gray-50
  card: '#FFFFFF',              // bg-white
  
  text: {
    primary: '#111827',         // text-gray-900
    secondary: '#6B7280',       // text-gray-500
    tertiary: '#9CA3AF',        // text-gray-400
    disabled: '#D1D5DB',        // text-gray-300
  },
  
  border: {
    default: '#E5E7EB',         // border-gray-200
    light: 'rgba(229, 231, 235, 0.5)', // border-gray-200/50
  },
  
  status: {
    success: '#10B981',         // green-500
    warning: '#F59E0B',         // amber-500
    error: '#EF4444',           // red-500
    info: '#3B82F6',            // blue-500
  },
  
  info: {
    background: '#EFF6FF',      // blue-50
    border: '#BFDBFE',          // blue-200
    text: '#1E40AF',            // blue-800
  },
  
  overlay: 'rgba(0, 0, 0, 0.5)',
};

/**
 * ダークモードのカラーパレット
 * 
 * Tailwind CSS対応表:
 * - dark:bg-gray-800 (#1F2937) → background
 * - dark:bg-gray-900 (#111827) → surface
 * - dark:bg-gray-700 (#374151) → card
 * - dark:text-white (#FFFFFF) → text.primary
 * - dark:text-gray-300 (#D1D5DB) → text.secondary
 * - dark:text-gray-400 (#9CA3AF) → text.tertiary
 * - dark:border-gray-700 (#4B5563) → border.default
 */
export const DarkColors: ColorPalette = {
  background: '#1F2937',        // dark:bg-gray-800
  surface: '#111827',           // dark:bg-gray-900
  card: '#374151',              // dark:bg-gray-700
  
  text: {
    primary: '#FFFFFF',         // dark:text-white
    secondary: '#D1D5DB',       // dark:text-gray-300
    tertiary: '#9CA3AF',        // dark:text-gray-400
    disabled: '#6B7280',        // dark:text-gray-500
  },
  
  border: {
    default: '#4B5563',         // dark:border-gray-700
    light: 'rgba(75, 85, 99, 0.5)', // dark:border-gray-700/50
  },
  
  status: {
    success: '#34D399',         // green-400 (ダークモードで明度UP)
    warning: '#FBBF24',         // amber-400 (ダークモードで明度UP)
    error: '#F87171',           // red-400 (ダークモードで明度UP)
    info: '#60A5FA',            // blue-400 (ダークモードで明度UP)
  },
  
  info: {
    background: '#1E3A8A',      // blue-900/20
    border: '#1E40AF',          // blue-800
    text: '#93C5FD',            // blue-300
  },
  
  overlay: 'rgba(0, 0, 0, 0.7)',
};

/**
 * カラースキーマ別のパレット
 */
export const Colors = {
  light: LightColors,
  dark: DarkColors,
};

/**
 * テーマ別アクセントカラー
 * 
 * - adult: 青系（落ち着いた色）
 * - child: オレンジ系（明るく親しみやすい色）
 */
export const ThemeColors = {
  adult: {
    primary: '#3B82F6',           // blue-500
    gradient: ['#3B82F6', '#2563EB'], // blue-500 → blue-600
  },
  child: {
    primary: '#F59E0B',           // amber-500
    gradient: ['#F59E0B', '#F97316'], // amber-500 → orange-500
  },
};
