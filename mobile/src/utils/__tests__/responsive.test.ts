/**
 * responsive.ts テスト
 * 
 * レスポンシブデザインユーティリティのテスト
 * 
 * @see /home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md
 */

import { renderHook, act } from '@testing-library/react-native';
import { Dimensions, Platform } from 'react-native';
import {
  getDeviceSize,
  getFontSize,
  getSpacing,
  getBorderRadius,
  getShadow,
  useResponsive,
  type ThemeType,
} from '../responsive';

describe('responsive.ts - レスポンシブデザインユーティリティ', () => {
  /**
   * テスト1: getDeviceSize() - デバイスサイズカテゴリ判定
   * 
   * 要件:
   * - 画面幅に応じて正しいDeviceSizeを返す
   * - ブレークポイント定義に従う（ResponsiveDesignGuideline.md）
   */
  describe('getDeviceSize()', () => {
    it('超小型デバイス (〜320px) を正しく判定する', () => {
      expect(getDeviceSize(280)).toBe('xs'); // Galaxy Fold
      expect(getDeviceSize(320)).toBe('xs'); // iPhone SE 1st
    });

    it('小型デバイス (321px〜374px) を正しく判定する', () => {
      expect(getDeviceSize(321)).toBe('sm');
      expect(getDeviceSize(360)).toBe('sm'); // 一般的なAndroid
      expect(getDeviceSize(374)).toBe('sm');
    });

    it('標準デバイス (375px〜413px) を正しく判定する', () => {
      expect(getDeviceSize(375)).toBe('md'); // iPhone SE 2nd/3rd
      expect(getDeviceSize(390)).toBe('md'); // iPhone 12/13/14
      expect(getDeviceSize(412)).toBe('md'); // Pixel 7
      expect(getDeviceSize(413)).toBe('md');
    });

    it('大型デバイス (414px〜767px) を正しく判定する', () => {
      expect(getDeviceSize(414)).toBe('lg');
      expect(getDeviceSize(430)).toBe('lg'); // iPhone Pro Max
      expect(getDeviceSize(767)).toBe('lg');
    });

    it('タブレット小 (768px〜1023px) を正しく判定する', () => {
      expect(getDeviceSize(768)).toBe('tablet-sm'); // iPad mini
      expect(getDeviceSize(800)).toBe('tablet-sm'); // Galaxy Tab
      expect(getDeviceSize(1023)).toBe('tablet-sm');
    });

    it('タブレット (1024px〜) を正しく判定する', () => {
      expect(getDeviceSize(1024)).toBe('tablet'); // iPad Pro
      expect(getDeviceSize(1366)).toBe('tablet'); // iPad Pro 12.9
    });

    it('境界値を正しく判定する', () => {
      expect(getDeviceSize(320)).toBe('xs');
      expect(getDeviceSize(321)).toBe('sm');
      expect(getDeviceSize(374)).toBe('sm');
      expect(getDeviceSize(375)).toBe('md');
      expect(getDeviceSize(413)).toBe('md');
      expect(getDeviceSize(414)).toBe('lg');
      expect(getDeviceSize(767)).toBe('lg');
      expect(getDeviceSize(768)).toBe('tablet-sm');
      expect(getDeviceSize(1023)).toBe('tablet-sm');
      expect(getDeviceSize(1024)).toBe('tablet');
    });
  });

  /**
   * テスト2: getFontSize() - フォントサイズスケーリング
   * 
   * 要件:
   * - デバイスサイズに応じてフォントサイズをスケール
   * - 大人向けテーマ: 0.80x〜1.15x
   * - 子ども向けテーマ: 大人向け × 1.20
   */
  describe('getFontSize()', () => {
    const baseSize = 16;

    describe('大人向けテーマ (adult)', () => {
      const theme: ThemeType = 'adult';

      it('超小型デバイス (xs) で0.80倍にスケールする', () => {
        const expected = baseSize * 0.80; // 12.8
        expect(getFontSize(baseSize, 320, theme)).toBe(expected);
      });

      it('小型デバイス (sm) で0.90倍にスケールする', () => {
        const expected = baseSize * 0.90; // 14.4
        expect(getFontSize(baseSize, 360, theme)).toBe(expected);
      });

      it('標準デバイス (md) でそのまま返す', () => {
        expect(getFontSize(baseSize, 375, theme)).toBe(baseSize); // 16
        expect(getFontSize(baseSize, 390, theme)).toBe(baseSize);
      });

      it('大型デバイス (lg) で1.05倍にスケールする', () => {
        const expected = baseSize * 1.05; // 16.8
        expect(getFontSize(baseSize, 430, theme)).toBe(expected);
      });

      it('タブレット小 (tablet-sm) で1.10倍にスケールする', () => {
        const expected = baseSize * 1.10; // 17.6
        expect(getFontSize(baseSize, 768, theme)).toBe(expected);
      });

      it('タブレット (tablet) で1.15倍にスケールする', () => {
        const expected = baseSize * 1.15; // 18.4
        expect(getFontSize(baseSize, 1024, theme)).toBe(expected);
      });
    });

    describe('子ども向けテーマ (child)', () => {
      const theme: ThemeType = 'child';

      it('標準デバイスで大人向けの1.20倍になる', () => {
        const adultSize = baseSize; // 16
        const expected = adultSize * 1.20; // 19.2
        expect(getFontSize(baseSize, 375, theme)).toBe(expected);
      });

      it('超小型デバイスで大人向けの1.20倍になる', () => {
        const adultSize = baseSize * 0.80; // 12.8
        const expected = adultSize * 1.20; // 15.36
        expect(getFontSize(baseSize, 320, theme)).toBe(expected);
      });

      it('タブレットで大人向けの1.20倍になる', () => {
        const adultSize = baseSize * 1.15; // 18.4
        const expected = adultSize * 1.20; // 22.08
        expect(getFontSize(baseSize, 1024, theme)).toBe(expected);
      });
    });

    describe('実例: ヘッダータイトル (24px)', () => {
      const titleSize = 24;

      it('iPhone 12 (390px, adult) で24pxになる', () => {
        expect(getFontSize(titleSize, 390, 'adult')).toBe(24);
      });

      it('iPhone 12 (390px, child) で28.8pxになる', () => {
        expect(getFontSize(titleSize, 390, 'child')).toBe(24 * 1.20);
      });

      it('iPad Pro (1024px, adult) で27.6pxになる', () => {
        expect(getFontSize(titleSize, 1024, 'adult')).toBe(24 * 1.15);
      });

      it('iPad Pro (1024px, child) で33.12pxになる', () => {
        expect(getFontSize(titleSize, 1024, 'child')).toBe(24 * 1.15 * 1.20);
      });
    });
  });

  /**
   * テスト3: getSpacing() - 余白スケーリング
   * 
   * 要件:
   * - デバイスサイズに応じて余白をスケール
   * - 最小余白: baseSpacingの50%（視認性確保）
   * - スケール: 0.75x〜1.30x
   */
  describe('getSpacing()', () => {
    const baseSpacing = 16;

    it('超小型デバイス (xs) で0.75倍にスケールする', () => {
      const expected = baseSpacing * 0.75; // 12
      expect(getSpacing(baseSpacing, 320)).toBe(expected);
    });

    it('小型デバイス (sm) で0.85倍にスケールする', () => {
      const expected = baseSpacing * 0.85; // 13.6
      expect(getSpacing(baseSpacing, 360)).toBe(expected);
    });

    it('標準デバイス (md) でそのまま返す', () => {
      expect(getSpacing(baseSpacing, 375)).toBe(baseSpacing); // 16
    });

    it('大型デバイス (lg) で1.10倍にスケールする', () => {
      const expected = baseSpacing * 1.10; // 17.6
      expect(getSpacing(baseSpacing, 430)).toBe(expected);
    });

    it('タブレット小 (tablet-sm) で1.20倍にスケールする', () => {
      const expected = baseSpacing * 1.20; // 19.2
      expect(getSpacing(baseSpacing, 768)).toBe(expected);
    });

    it('タブレット (tablet) で1.30倍にスケールする', () => {
      const expected = baseSpacing * 1.30; // 20.8
      expect(getSpacing(baseSpacing, 1024)).toBe(expected);
    });

    it('最小余白（50%）を下回らない', () => {
      const minSpacing = baseSpacing * 0.50; // 8
      const xsSpacing = getSpacing(baseSpacing, 320); // 12
      expect(xsSpacing).toBeGreaterThanOrEqual(minSpacing);
    });

    it('極端に小さいデバイスでも最小余白を保証', () => {
      const minSpacing = baseSpacing * 0.50; // 8
      const tinySpacing = getSpacing(baseSpacing, 200); // 超小型デバイス（想定外）
      expect(tinySpacing).toBeGreaterThanOrEqual(minSpacing);
    });
  });

  /**
   * テスト4: getBorderRadius() - 角丸スケーリング
   * 
   * 要件:
   * - デバイスサイズに応じて角丸をスケール
   * - スケール: 0.80x〜1.15x
   */
  describe('getBorderRadius()', () => {
    const baseRadius = 8;

    it('超小型デバイス (xs) で0.80倍にスケールする', () => {
      const expected = baseRadius * 0.80; // 6.4
      expect(getBorderRadius(baseRadius, 320)).toBe(expected);
    });

    it('小型デバイス (sm) で0.90倍にスケールする', () => {
      const expected = baseRadius * 0.90; // 7.2
      expect(getBorderRadius(baseRadius, 360)).toBe(expected);
    });

    it('標準デバイス (md) でそのまま返す', () => {
      expect(getBorderRadius(baseRadius, 375)).toBe(baseRadius); // 8
    });

    it('大型デバイス (lg) で1.05倍にスケールする', () => {
      const expected = baseRadius * 1.05; // 8.4
      expect(getBorderRadius(baseRadius, 430)).toBe(expected);
    });

    it('タブレット小 (tablet-sm) で1.10倍にスケールする', () => {
      const expected = baseRadius * 1.10; // 8.8
      expect(getBorderRadius(baseRadius, 768)).toBe(expected);
    });

    it('タブレット (tablet) で1.15倍にスケールする', () => {
      const expected = baseRadius * 1.15; // 9.2
      expect(getBorderRadius(baseRadius, 1024)).toBe(expected);
    });

    it('大きな角丸値でも正しくスケールする (rounded-2xl: 16px)', () => {
      const largeRadius = 16;
      expect(getBorderRadius(largeRadius, 375)).toBe(16);
      expect(getBorderRadius(largeRadius, 1024)).toBe(16 * 1.15); // 18.4
    });
  });

  /**
   * テスト5: getShadow() - プラットフォーム別シャドウ
   * 
   * 要件:
   * - iOS: shadowColor, shadowOffset, shadowOpacity, shadowRadius
   * - Android: elevation
   */
  describe('getShadow()', () => {
    const originalPlatform = Platform.OS;

    afterEach(() => {
      // Platform.OSを元に戻す
      Object.defineProperty(Platform, 'OS', {
        value: originalPlatform,
        writable: true,
      });
    });

    describe('iOS', () => {
      beforeEach(() => {
        Object.defineProperty(Platform, 'OS', {
          value: 'ios',
          writable: true,
        });
      });

      it('elevation 2 でiOSシャドウを返す', () => {
        const shadow = getShadow(2);
        expect(shadow).toHaveProperty('shadowColor', '#000');
        expect(shadow).toHaveProperty('shadowOffset');
        expect(shadow.shadowOffset).toHaveProperty('width', 0);
        expect(shadow.shadowOffset?.height).toBeGreaterThan(0);
        expect(shadow).toHaveProperty('shadowOpacity');
        expect(shadow).toHaveProperty('shadowRadius');
        expect(shadow).not.toHaveProperty('elevation');
      });

      it('elevation 4 でiOSシャドウを返す', () => {
        const shadow = getShadow(4);
        expect(shadow).toHaveProperty('shadowColor', '#000');
        expect(shadow.shadowOffset?.height).toBeGreaterThan(0);
      });

      it('elevation 8 でiOSシャドウを返す', () => {
        const shadow = getShadow(8);
        expect(shadow).toHaveProperty('shadowColor', '#000');
        expect(shadow.shadowOffset?.height).toBeGreaterThan(0);
      });
    });

    describe('Android', () => {
      beforeEach(() => {
        Object.defineProperty(Platform, 'OS', {
          value: 'android',
          writable: true,
        });
      });

      it('elevation 2 でAndroidシャドウを返す', () => {
        const shadow = getShadow(2);
        expect(shadow).toHaveProperty('elevation', 2);
        expect(shadow).not.toHaveProperty('shadowColor');
        expect(shadow).not.toHaveProperty('shadowOffset');
        expect(shadow).not.toHaveProperty('shadowOpacity');
        expect(shadow).not.toHaveProperty('shadowRadius');
      });

      it('elevation 4 でAndroidシャドウを返す', () => {
        const shadow = getShadow(4);
        expect(shadow).toHaveProperty('elevation', 4);
      });

      it('elevation 8 でAndroidシャドウを返す', () => {
        const shadow = getShadow(8);
        expect(shadow).toHaveProperty('elevation', 8);
      });
    });

    it('未定義のelevation値でもエラーにならない', () => {
      Object.defineProperty(Platform, 'OS', {
        value: 'ios',
        writable: true,
      });
      const shadow = getShadow(99);
      expect(shadow).toHaveProperty('shadowColor', '#000');
    });
  });

  /**
   * テスト6: useResponsive() - カスタムフック
   * 
   * 要件:
   * - Dimensions.get('window') から画面サイズ取得
   * - 画面回転時に自動更新
   * - width, height, deviceSize, isPortrait, isLandscape を返す
   */
  describe('useResponsive()', () => {
    it('初期状態で画面サイズを取得する', () => {
      // Dimensions.get をモック
      const mockDimensions = {
        width: 390,
        height: 844,
        scale: 3,
        fontScale: 1,
      };
      jest.spyOn(Dimensions, 'get').mockReturnValue(mockDimensions);

      const { result } = renderHook(() => useResponsive());

      expect(result.current.width).toBe(390);
      expect(result.current.height).toBe(844);
      expect(result.current.deviceSize).toBe('md'); // 390px = 標準デバイス
      expect(result.current.isPortrait).toBe(true); // height > width
      expect(result.current.isLandscape).toBe(false);
    });

    it('横向きを正しく判定する', () => {
      const mockDimensions = {
        width: 844,
        height: 390,
        scale: 3,
        fontScale: 1,
      };
      jest.spyOn(Dimensions, 'get').mockReturnValue(mockDimensions);

      const { result } = renderHook(() => useResponsive());

      expect(result.current.isPortrait).toBe(false);
      expect(result.current.isLandscape).toBe(true); // width > height
    });

    it('タブレットを正しく判定する', () => {
      const mockDimensions = {
        width: 1024,
        height: 768,
        scale: 2,
        fontScale: 1,
      };
      jest.spyOn(Dimensions, 'get').mockReturnValue(mockDimensions);

      const { result } = renderHook(() => useResponsive());

      expect(result.current.deviceSize).toBe('tablet');
    });

    it('画面回転時にサイズを更新する', () => {
      // 初期状態: 縦向き
      const mockDimensionsPortrait = {
        width: 390,
        height: 844,
        scale: 3,
        fontScale: 1,
      };
      jest.spyOn(Dimensions, 'get').mockReturnValue(mockDimensionsPortrait);
      
      // addEventListener をモック化
      const mockRemoveListener = jest.fn();
      const addEventListenerSpy = jest.spyOn(Dimensions, 'addEventListener').mockReturnValue({
        remove: mockRemoveListener,
      } as any);

      const { result, rerender, unmount } = renderHook(() => useResponsive());

      expect(result.current.width).toBe(390);
      expect(result.current.isPortrait).toBe(true);

      // 画面回転: 横向き
      const mockDimensionsLandscape = {
        width: 844,
        height: 390,
        scale: 3,
        fontScale: 1,
      };
      jest.spyOn(Dimensions, 'get').mockReturnValue(mockDimensionsLandscape);

      // addEventListenerに登録されたコールバックを呼び出す
      if (addEventListenerSpy.mock.calls.length > 0) {
        const callback = addEventListenerSpy.mock.calls[0][1];
        act(() => {
          callback({ window: mockDimensionsLandscape });
        });
      }

      // 状態が更新されたことを確認
      expect(result.current.width).toBe(844);
      expect(result.current.isPortrait).toBe(false);
      expect(result.current.isLandscape).toBe(true);
      
      // クリーンアップ検証
      unmount();
      expect(mockRemoveListener).toHaveBeenCalled();
      
      // モックをリセット
      addEventListenerSpy.mockRestore();
    });
  });

  /**
   * テスト7: 統合テスト - 実際のスタイル生成
   * 
   * 要件:
   * - ResponsiveDesignGuideline.md の実装例を再現
   */
  describe('統合テスト: スタイル生成', () => {
    it('iPhone 12 (390px, adult) でスタイルを生成', () => {
      const width = 390;
      const theme: ThemeType = 'adult';

      const styles = {
        title: {
          fontSize: getFontSize(24, width, theme), // 24px
          marginBottom: getSpacing(8, width), // 8px
        },
        card: {
          padding: getSpacing(16, width), // 16px
          borderRadius: getBorderRadius(12, width),
          ...getShadow(4),
        },
      };

      expect(styles.title.fontSize).toBe(24);
      expect(styles.title.marginBottom).toBe(8);
      expect(styles.card.padding).toBe(16);
      expect(styles.card.borderRadius).toBe(12);
    });

    it('iPhone 12 (390px, child) でスタイルを生成', () => {
      const width = 390;
      const theme: ThemeType = 'child';

      const styles = {
        title: {
          fontSize: getFontSize(24, width, theme), // 24 * 1.20 = 28.8px
        },
        body: {
          fontSize: getFontSize(14, width, theme), // 14 * 1.20 = 16.8px
        },
      };

      expect(styles.title.fontSize).toBe(24 * 1.20);
      expect(styles.body.fontSize).toBe(14 * 1.20);
    });

    it('iPad Pro (1024px, adult) でスタイルを生成', () => {
      const width = 1024;
      const theme: ThemeType = 'adult';

      const styles = {
        title: {
          fontSize: getFontSize(24, width, theme), // 24 * 1.15 = 27.6px
          marginBottom: getSpacing(8, width), // 8 * 1.30 = 10.4px
        },
        card: {
          padding: getSpacing(16, width), // 16 * 1.30 = 20.8px
          borderRadius: getBorderRadius(12, width), // 12 * 1.15 = 13.8px
        },
      };

      expect(styles.title.fontSize).toBe(24 * 1.15);
      expect(styles.title.marginBottom).toBe(8 * 1.30);
      expect(styles.card.padding).toBe(16 * 1.30);
      expect(styles.card.borderRadius).toBe(12 * 1.15);
    });

    it('Galaxy Fold (280px, adult) でスタイルを生成', () => {
      const width = 280;
      const theme: ThemeType = 'adult';

      const styles = {
        title: {
          fontSize: getFontSize(24, width, theme), // 24 * 0.80 = 19.2px
          marginBottom: getSpacing(8, width), // 8 * 0.75 = 6px
        },
        card: {
          padding: getSpacing(16, width), // 16 * 0.75 = 12px (最小8px以上)
          borderRadius: getBorderRadius(12, width), // 12 * 0.80 = 9.6px
        },
      };

      expect(styles.title.fontSize).toBe(24 * 0.80);
      expect(styles.title.marginBottom).toBe(8 * 0.75);
      expect(styles.card.padding).toBe(16 * 0.75);
      expect(styles.card.borderRadius).toBe(12 * 0.80);
    });
  });
});
