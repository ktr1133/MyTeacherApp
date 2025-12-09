# モバイルアプリ レスポンシブ設計ガイドライン

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | 初版作成: Dimensions APIを使用したレスポンシブ設計の詳細仕様 |

---

## 1. 概要

### 1.1 目的

MyTeacherモバイルアプリにおいて、**Dimensions APIを使用したレスポンシブ対応**により、以下を実現する:

1. **デバイス間の表示差異を吸収** - iPhone SE（320px）からiPad Pro（1024px+）まで最適表示
2. **Webアプリの表示崩れを解決** - 固定値起因の問題（ヘッダー折り返し、カード見切れ等）を防止
3. **子ども向けUIの最適化** - 大きなフォント、わかりやすい配置
4. **画面回転対応** - 縦向き・横向き両対応

### 1.2 優先順位

```
1. レスポンシブ対応（最優先） > 2. 要素の装飾（Web版との一致）
```

---

## 2. ブレークポイント定義

### 2.1 デバイスカテゴリとブレークポイント

Android端末を考慮した実測値ベースの定義:

| カテゴリ | 画面幅範囲 | 対象デバイス例 | フォント | 余白 | カラム |
|---------|-----------|--------------|---------|------|-------|
| **超小型** | 〜320px | Galaxy Fold (280px), iPhone SE 1st (320px) | 0.80x | 0.75x | 1 |
| **小型** | 321px〜374px | iPhone SE 2nd/3rd (375px), Pixel 4a (393px) | 0.90x | 0.85x | 1 |
| **標準** | 375px〜413px | iPhone 12/13/14 (390px), Pixel 7 (412px) | **1.00x** | **1.00x** | 1 |
| **大型** | 414px〜767px | iPhone Pro Max (430px), Galaxy S21+ (384px) | 1.05x | 1.10x | 1 |
| **タブレット小** | 768px〜1023px | iPad mini (768px), Galaxy Tab (800px) | 1.10x | 1.20x | 1 |
| **タブレット** | 1024px〜 | iPad Pro (1024px), iPad Air (820px) | 1.15x | 1.30x | 1 |

**注**: タグバケットは全サイズで1カラム固定（視認性優先）

### 2.2 ブレークポイント取得関数

```typescript
// utils/responsive.ts
import { Dimensions } from 'react-native';

export type DeviceSize = 'xs' | 'sm' | 'md' | 'lg' | 'tablet-sm' | 'tablet';

export const getDeviceSize = (width: number): DeviceSize => {
  if (width <= 320) return 'xs';        // 超小型
  if (width <= 374) return 'sm';        // 小型
  if (width <= 413) return 'md';        // 標準
  if (width <= 767) return 'lg';        // 大型
  if (width <= 1023) return 'tablet-sm'; // タブレット小
  return 'tablet';                       // タブレット
};

export const useResponsive = () => {
  const [dimensions, setDimensions] = React.useState(Dimensions.get('window'));
  
  React.useEffect(() => {
    const subscription = Dimensions.addEventListener('change', ({ window }) => {
      setDimensions(window);
    });
    return () => subscription?.remove();
  }, []);
  
  const deviceSize = getDeviceSize(dimensions.width);
  
  return {
    width: dimensions.width,
    height: dimensions.height,
    deviceSize,
    isPortrait: dimensions.height > dimensions.width,
    isLandscape: dimensions.width > dimensions.height,
    isTablet: deviceSize === 'tablet-sm' || deviceSize === 'tablet',
  };
};
```

---

## 3. フォントサイズのスケーリング

### 3.1 大人向けテーマ（Adult Theme）

```typescript
export const getAdultFontSize = (baseSize: number, width: number): number => {
  if (width <= 320) return baseSize * 0.80;      // 超小型: 20%縮小
  if (width <= 374) return baseSize * 0.90;      // 小型: 10%縮小
  if (width <= 413) return baseSize;              // 標準: そのまま
  if (width <= 767) return baseSize * 1.05;      // 大型: 5%拡大
  if (width <= 1023) return baseSize * 1.10;     // タブレット小: 10%拡大
  return baseSize * 1.15;                         // タブレット: 15%拡大
};
```

### 3.2 子ども向けテーマ（Child Theme）

**方針**: わかりやすさ重視 → **大人向けより20%大きく**

```typescript
export const getChildFontSize = (baseSize: number, width: number): number => {
  const adultSize = getAdultFontSize(baseSize, width);
  return adultSize * 1.20; // 大人向けより20%拡大
};
```

### 3.3 テーマ別フォントサイズ取得

```typescript
export const getFontSize = (
  baseSize: number,
  width: number,
  theme: 'adult' | 'child'
): number => {
  return theme === 'child' 
    ? getChildFontSize(baseSize, width)
    : getAdultFontSize(baseSize, width);
};
```

### 3.4 実装例

```typescript
import { useResponsive, getFontSize } from '@/utils/responsive';
import { useChildTheme } from '@/hooks/useChildTheme';

const MyComponent = () => {
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const theme = isChildTheme ? 'child' : 'adult';
  
  const styles = StyleSheet.create({
    title: {
      fontSize: getFontSize(18, width, theme), // Web版 text-lg (18px) ベース
    },
    body: {
      fontSize: getFontSize(14, width, theme), // Web版 text-sm (14px) ベース
    },
  });
  
  return <Text style={styles.title}>タイトル</Text>;
};
```

### 3.5 フォントサイズ一覧表（375px基準）

| 用途 | Web版 Tailwind | Base Size | 大人 (375px) | 子ども (375px) | 大人 (1024px) | 子ども (1024px) |
|------|---------------|-----------|------------|--------------|-------------|---------------|
| ヘッダータイトル | text-2xl | 24px | 24px | 28.8px | 27.6px | 33.1px |
| サブタイトル | text-xl | 20px | 20px | 24.0px | 23.0px | 27.6px |
| カードタイトル | text-lg | 18px | 18px | 21.6px | 20.7px | 24.8px |
| 本文 | text-base | 16px | 16px | 19.2px | 18.4px | 22.1px |
| キャプション | text-sm | 14px | 14px | 16.8px | 16.1px | 19.3px |
| 小さい文字 | text-xs | 12px | 12px | 14.4px | 13.8px | 16.6px |

---

## 4. 余白（Padding、Margin）のスケーリング

### 4.1 余白スケーリング関数

```typescript
export const getSpacing = (baseSpacing: number, width: number): number => {
  // 最小余白: baseSpacingの50%（視認性確保）
  const minSpacing = baseSpacing * 0.50;
  
  let spacing: number;
  
  if (width <= 320) {
    spacing = baseSpacing * 0.75;      // 超小型: 25%縮小
  } else if (width <= 374) {
    spacing = baseSpacing * 0.85;      // 小型: 15%縮小
  } else if (width <= 413) {
    spacing = baseSpacing;              // 標準: そのまま
  } else if (width <= 767) {
    spacing = baseSpacing * 1.10;      // 大型: 10%拡大
  } else if (width <= 1023) {
    spacing = baseSpacing * 1.20;      // タブレット小: 20%拡大
  } else {
    spacing = baseSpacing * 1.30;      // タブレット: 30%拡大
  }
  
  // 最小余白を下回らないように制限
  return Math.max(spacing, minSpacing);
};
```

### 4.2 実装例

```typescript
const styles = StyleSheet.create({
  container: {
    padding: getSpacing(16, width), // Web版 p-4 (16px) ベース
  },
  card: {
    marginBottom: getSpacing(12, width), // Web版 mb-3 (12px) ベース
  },
});
```

### 4.3 余白一覧表（375px基準）

| 用途 | Web版 Tailwind | Base Size | 375px | 768px | 1024px | 最小値 |
|------|---------------|-----------|-------|-------|--------|-------|
| 画面余白 | p-4 | 16px | 16px | 19.2px | 20.8px | 8px |
| カード内余白 | p-3 | 12px | 12px | 14.4px | 15.6px | 6px |
| 要素間余白 | gap-4 | 16px | 16px | 19.2px | 20.8px | 8px |
| 小さい余白 | p-2 | 8px | 8px | 9.6px | 10.4px | 4px |

---

## 5. 角丸（Border Radius）のスケーリング

### 5.1 角丸スケーリング関数

```typescript
export const getBorderRadius = (baseRadius: number, width: number): number => {
  if (width <= 320) return baseRadius * 0.80;
  if (width <= 374) return baseRadius * 0.90;
  if (width <= 413) return baseRadius;
  if (width <= 767) return baseRadius * 1.05;
  if (width <= 1023) return baseRadius * 1.10;
  return baseRadius * 1.15;
};
```

### 5.2 角丸一覧表

| 用途 | Web版 Tailwind | Base Size | 375px | 1024px |
|------|---------------|-----------|-------|--------|
| 大きい角丸 | rounded-2xl | 16px | 16px | 18.4px |
| 標準角丸 | rounded-xl | 12px | 12px | 13.8px |
| 小さい角丸 | rounded-lg | 8px | 8px | 9.2px |
| ボタン | rounded-full | 9999px | 9999px | 9999px |

---

## 6. シャドウ（Shadow）のプラットフォーム別対応

### 6.1 シャドウ定義関数

```typescript
import { Platform, ViewStyle } from 'react-native';

export const getShadow = (elevation: number): ViewStyle => {
  if (Platform.OS === 'android') {
    return {
      elevation: elevation, // Android: elevation
    };
  }
  
  // iOS: shadow*プロパティ
  const shadowConfig = {
    2: { height: 1, opacity: 0.18, radius: 1.0 },
    4: { height: 2, opacity: 0.20, radius: 2.0 },
    6: { height: 3, opacity: 0.22, radius: 3.0 },
    8: { height: 4, opacity: 0.24, radius: 4.0 },
    12: { height: 6, opacity: 0.28, radius: 6.0 },
    16: { height: 8, opacity: 0.30, radius: 8.0 },
  };
  
  const config = shadowConfig[elevation] || shadowConfig[4];
  
  return {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: config.height },
    shadowOpacity: config.opacity,
    shadowRadius: config.radius,
  };
};
```

### 6.2 実装例

```typescript
const styles = StyleSheet.create({
  card: {
    ...getShadow(4), // elevation 4 相当
    backgroundColor: '#fff',
    borderRadius: getBorderRadius(16, width),
  },
});
```

---

## 7. Platform.select() 使用ガイド

### 7.1 使用すべき場面

1. **カレンダー選択**
   ```typescript
   import DateTimePicker from '@react-native-community/datetimepicker';
   
   // iOSとAndroidで表示が異なる
   {Platform.OS === 'ios' && <DateTimePicker mode="date" display="spinner" />}
   {Platform.OS === 'android' && <DateTimePicker mode="date" display="default" />}
   ```

2. **時刻選択**
   ```typescript
   {Platform.OS === 'ios' && <DateTimePicker mode="time" display="spinner" />}
   {Platform.OS === 'android' && <DateTimePicker mode="time" display="clock" />}
   ```

3. **セレクトボックス（Picker）**
   ```typescript
   // ❌ iOS: Pickerの挙動がおかしい場合がある
   // ✅ 代替案: モーダル + FlatListでカスタム実装
   
   {Platform.OS === 'ios' ? (
     <CustomSelectModal /> // カスタム実装
   ) : (
     <Picker /> // Android標準Picker
   )}
   ```

4. **キーボード回避**
   ```typescript
   import { KeyboardAvoidingView, Platform } from 'react-native';
   
   <KeyboardAvoidingView
     behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
   >
   ```

5. **Safe Area対応（iOS）**
   ```typescript
   import { SafeAreaView } from 'react-native-safe-area-context';
   
   // iOSのノッチ対応
   <SafeAreaView edges={['top', 'bottom']}>
   ```

### 7.2 プラットフォーム別スタイル

```typescript
const styles = StyleSheet.create({
  button: {
    ...Platform.select({
      ios: {
        paddingVertical: 12,
        borderRadius: 10,
      },
      android: {
        paddingVertical: 10,
        borderRadius: 4,
        elevation: 2,
      },
    }),
  },
});
```

---

## 8. Webアプリ表示崩れの解決策

### 8.1 ヘッダータイトル・副題の折り返し対策

**問題**: 「承認待ち一覧」「サブスクリプション管理」「グループ管理」「タスク自動作成の設定」で折り返し発生

**解決策1**: フォントサイズの動的縮小

```typescript
const getTitleFontSize = (
  title: string,
  baseSize: number,
  width: number,
  theme: 'adult' | 'child'
): number => {
  const baseFontSize = getFontSize(baseSize, width, theme);
  
  // 文字数が多い場合は縮小（10文字以上）
  if (title.length >= 10) {
    return baseFontSize * 0.85;
  }
  
  return baseFontSize;
};

const styles = StyleSheet.create({
  headerTitle: {
    fontSize: getTitleFontSize('タスク自動作成の設定', 20, width, theme),
  },
});
```

**解決策2**: 複数行表示許可 + 省略

```typescript
<Text
  style={styles.headerTitle}
  numberOfLines={2}           // 最大2行
  adjustsFontSizeToFit={true} // 自動フォントサイズ調整
  minimumFontScale={0.7}      // 最小70%まで縮小可能
>
  タスク自動作成の設定
</Text>
```

### 8.2 タグタスク一覧モーダルでのカード見切れ対策（Android）

**問題**: Androidでカードが見切れる

**解決策**: 余白とカード幅の最適化

```typescript
const getModalCardStyle = (width: number) => {
  const horizontalPadding = getSpacing(16, width);
  const cardWidth = width - horizontalPadding * 2; // 左右余白を引く
  
  return {
    container: {
      paddingHorizontal: horizontalPadding,
    },
    card: {
      width: cardWidth,
      maxWidth: '100%', // はみ出し防止
    },
  };
};
```

**Androidテスト必須デバイス**:
- Pixel 7 (412px)
- Galaxy S21 (360px)
- Galaxy Fold (280px) - 最小幅

---

## 9. グリッドレイアウト（タグバケット）

### 9.1 基本方針

- **全サイズ1カラム固定**（視認性優先）
- タブレット横向きでも1カラム（バケットの間延び防止）

### 9.2 実装

```typescript
<FlatList
  data={tagBuckets}
  renderItem={renderTagCard}
  numColumns={1} // 1カラム固定
  key="single-column" // numColumns変更時のkey
  columnWrapperStyle={undefined} // 不要
/>
```

### 9.3 将来の拡張（条件付き3カラム対応）

**条件**: タブレット横向き時にバケット間延びで視認性悪化が発生した場合

```typescript
const getNumColumns = (width: number, isLandscape: boolean) => {
  // 現在は1カラム固定
  if (width >= 1024 && isLandscape) {
    return 3; // 将来的に3カラム検討
  }
  return 1;
};
```

---

## 10. 画面回転対応

### 10.1 回転検出

```typescript
const { isPortrait, isLandscape } = useResponsive();

useEffect(() => {
  console.log('画面向き変更:', isPortrait ? '縦' : '横');
}, [isPortrait]);
```

### 10.2 回転時のレイアウト調整

```typescript
const styles = StyleSheet.create({
  container: {
    flexDirection: isLandscape ? 'row' : 'column', // 横向き時は横並び
  },
});
```

---

## 11. フォント種類

### 11.1 子ども向けフォント

**Web版使用フォント**: 
```css
/* Web: resources/css/app.css */
font-family: 'Noto Sans JP', 'Hiragino Kaku Gothic ProN', sans-serif;
```

**モバイル実装**:
```typescript
import { Platform } from 'react-native';

export const getChildFontFamily = () => {
  return Platform.select({
    ios: 'Hiragino Sans',        // iOS標準
    android: 'Noto Sans CJK JP',  // Android標準（Noto Sans JP相当）
    default: 'System',
  });
};

const styles = StyleSheet.create({
  childText: {
    fontFamily: isChildTheme ? getChildFontFamily() : 'System',
  },
});
```

### 11.2 大人向けフォント

```typescript
const styles = StyleSheet.create({
  adultText: {
    fontFamily: 'System', // システムフォント
  },
});
```

---

## 12. 実装チェックリスト

### 12.1 レスポンシブ実装

- [ ] `useResponsive()` Hookをインポート
- [ ] `getFontSize()` でフォントサイズ計算
- [ ] `getSpacing()` で余白計算
- [ ] `getBorderRadius()` で角丸計算
- [ ] `getShadow()` でシャドウ設定（Platform別）
- [ ] 画面回転リスナー実装
- [ ] Android/iOSでテスト実行

### 12.2 テーマ対応

- [ ] `useChildTheme()` Hookで子ども向けテーマ判定
- [ ] 子ども向けフォントサイズ1.2倍適用
- [ ] 子ども向けフォントファミリー設定

### 12.3 Platform別対応

- [ ] DateTimePickerでPlatform.select()使用
- [ ] PickerをiOSではカスタム実装に置き換え
- [ ] KeyboardAvoidingViewでPlatform判定
- [ ] SafeAreaViewでiOSノッチ対応

### 12.4 表示崩れ対策

- [ ] ヘッダータイトルで`adjustsFontSizeToFit`使用
- [ ] モーダルカードで見切れ対策実装
- [ ] Android実機テスト（Pixel、Galaxy）

---

## 13. テストデバイス一覧

### 13.1 必須テストデバイス

| カテゴリ | デバイス | 画面幅 | 優先度 |
|---------|---------|-------|-------|
| iOS超小型 | iPhone SE 1st | 320px | 高 |
| iOS小型 | iPhone SE 2nd/3rd | 375px | 高 |
| iOS標準 | iPhone 12/13/14 | 390px | **最高** |
| iOS大型 | iPhone 14 Pro Max | 430px | 高 |
| Android超小型 | Galaxy Fold | 280px | 中 |
| Android標準 | Pixel 7 | 412px | **最高** |
| Android大型 | Galaxy S21+ | 384px | 高 |
| タブレット | iPad mini | 768px | 高 |
| タブレット | iPad Pro | 1024px | 高 |

### 13.2 テスト観点

- [ ] 縦向き表示
- [ ] 横向き表示（タブレットのみ）
- [ ] 画面回転時のレイアウト崩れ
- [ ] テキスト折り返し・見切れ
- [ ] ボタン・カードのタップ領域
- [ ] モーダル表示
- [ ] キーボード表示時のレイアウト

---

**作成日**: 2025-12-09  
**作成者**: GitHub Copilot  
**レビュー**: 未実施  
**バージョン**: 1.0
