# モバイルアプリ ダークモード対応 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-13 | GitHub Copilot | 初版作成: iOS/AndroidのOSレベルダークモード自動追従 + 手動切り替え対応 |

---

## 1. 概要

### 1.1 目的

MyTeacherモバイルアプリにおいて、**iOS/AndroidのOSレベルのダークモード設定に即座に追従**し、ユーザー体験を向上させる。

### 1.2 対応範囲

1. **OSレベルの自動追従** - デバイスのダークモード設定変更を即座に反映
2. **手動切り替え機能** - Android OSの一部機種でOSレベル設定が反映されない場合の対策
3. **テーマとの組み合わせ** - 既存の `adult` / `child` テーマとダークモードの4パターン対応
4. **Webアプリとの整合性** - Web版で使用している Tailwind CSS の `dark:` クラスに対応する色設定

### 1.3 優先順位

```
1. OSレベル自動追従 > 2. 手動切り替え > 3. UI装飾の統一
```

---

## 2. Webアプリとの対応関係

### 2.1 Web版のダークモード実装

**設定ファイル**: `/home/ktr/mtdev/tailwind.config.js`

```javascript
darkMode: 'class', // クラスベースのダークモード切り替え
```

**使用されているダークモードクラス例**:

| 要素 | ライトモード | ダークモード | 用途 |
|------|-------------|-------------|------|
| テキスト | `text-gray-900` | `dark:text-white` | 見出し・本文 |
| 背景 | `bg-white` | `dark:bg-gray-800` | カード・コンテナ |
| ボーダー | `border-gray-200` | `dark:border-gray-700` | 区切り線 |
| ホバー | `hover:bg-gray-50` | `dark:hover:bg-gray-700` | インタラクティブ要素 |
| 補助テキスト | `text-gray-500` | `dark:text-gray-400` | 説明文 |
| ナビゲーション | `text-gray-500` | `dark:text-gray-400` | リンク（非アクティブ） |
| アクティブ | `text-gray-900` | `dark:text-gray-100` | リンク（アクティブ） |

### 2.2 カラーパレット定義

**参照**: Web版の実装から抽出した色定義

---

## 3. カラースキーマ設計

### 3.1 カラーパレット（Light/Dark）

React Native用のカラー定義:

```typescript
// utils/colors.ts

export const Colors = {
  light: {
    // 基本色
    background: '#FFFFFF',        // bg-white
    surface: '#F9FAFB',           // bg-gray-50
    card: '#FFFFFF',              // bg-white
    
    // テキスト
    text: {
      primary: '#111827',         // text-gray-900
      secondary: '#6B7280',       // text-gray-500
      tertiary: '#9CA3AF',        // text-gray-400
      inverse: '#FFFFFF',         // text-white
    },
    
    // ボーダー・区切り線
    border: {
      default: '#E5E7EB',         // border-gray-200
      light: 'rgba(229, 231, 235, 0.5)', // border-gray-200/50
    },
    
    // インタラクティブ要素
    interactive: {
      hover: '#F3F4F6',           // hover:bg-gray-100
      pressed: '#E5E7EB',         // active:bg-gray-200
    },
    
    // アクセントカラー（既存のグラデーション等）
    accent: {
      primary: '#3B82F6',         // blue-500
      secondary: '#8B5CF6',       // purple-500
    },
  },
  
  dark: {
    // 基本色
    background: '#1F2937',        // bg-gray-800
    surface: '#111827',           // bg-gray-900
    card: '#374151',              // bg-gray-700
    
    // テキスト
    text: {
      primary: '#FFFFFF',         // dark:text-white
      secondary: '#D1D5DB',       // dark:text-gray-300
      tertiary: '#9CA3AF',        // dark:text-gray-400
      inverse: '#111827',         // dark:text-gray-900
    },
    
    // ボーダー・区切り線
    border: {
      default: '#4B5563',         // dark:border-gray-700
      light: 'rgba(75, 85, 99, 0.5)', // dark:border-gray-700/50
    },
    
    // インタラクティブ要素
    interactive: {
      hover: '#4B5563',           // dark:hover:bg-gray-700
      pressed: '#374151',         // dark:active:bg-gray-600
    },
    
    // アクセントカラー（ダークモードでは若干明るく）
    accent: {
      primary: '#60A5FA',         // blue-400
      secondary: '#A78BFA',       // purple-400
    },
  },
};
```

### 3.2 テーマ別カラー拡張（Adult/Child）

**方針**: `adult` / `child` テーマはライト/ダークモード共通でアクセントカラーのみ変更

```typescript
export const ThemeColors = {
  adult: {
    // 大人向けテーマのアクセントカラー（既存の実装を踏襲）
    primary: '#3B82F6',           // blue-500
    gradient: ['#3B82F6', '#8B5CF6'], // blue → purple
  },
  
  child: {
    // 子ども向けテーマのアクセントカラー（明るく親しみやすい色）
    primary: '#F59E0B',           // amber-500
    gradient: ['#F59E0B', '#F97316'], // amber → orange
  },
};
```

### 3.3 色の組み合わせパターン

| ユーザー向けテーマ | カラースキーマ | 背景色 | テキスト色 | アクセント色 |
|------------------|--------------|--------|-----------|-------------|
| Adult | Light | `#FFFFFF` | `#111827` | `#3B82F6` |
| Adult | Dark | `#1F2937` | `#FFFFFF` | `#60A5FA` |
| Child | Light | `#FFFFFF` | `#111827` | `#F59E0B` |
| Child | Dark | `#1F2937` | `#FFFFFF` | `#FBBF24` |

---

## 4. 技術設計

### 4.1 ColorSchemeContext（新規作成）

**ファイル**: `/home/ktr/mtdev/mobile/src/contexts/ColorSchemeContext.tsx`

**責務**:
- React Nativeの `useColorScheme()` フックでOSの設定を監視
- ユーザーによる手動切り替えをサポート
- `'light'` / `'dark'` / `'auto'` の3モード管理

**インターフェース**:

```typescript
export type ColorSchemeMode = 'light' | 'dark' | 'auto';

export interface ColorSchemeContextType {
  /** 現在のカラースキーマ（light/dark） */
  colorScheme: 'light' | 'dark';
  
  /** ユーザー設定モード（auto/light/dark） */
  mode: ColorSchemeMode;
  
  /** カラースキーマを手動で設定 */
  setMode: (mode: ColorSchemeMode) => void;
  
  /** 現在のカラーパレット */
  colors: typeof Colors.light | typeof Colors.dark;
  
  /** ダークモードかどうか */
  isDark: boolean;
}
```

**実装例**:

```typescript
import React, { createContext, useContext, useState, useEffect } from 'react';
import { useColorScheme, Appearance } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Colors } from '../utils/colors';

const STORAGE_KEY = '@MyTeacher:colorSchemeMode';

export const ColorSchemeProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const systemColorScheme = useColorScheme(); // OSの設定を取得
  const [mode, setModeState] = useState<ColorSchemeMode>('auto');
  
  // 実際に適用するカラースキーマ
  const colorScheme: 'light' | 'dark' = 
    mode === 'auto' 
      ? (systemColorScheme ?? 'light') 
      : mode;
  
  // 初期化: AsyncStorageから設定を読み込み
  useEffect(() => {
    const loadMode = async () => {
      const saved = await AsyncStorage.getItem(STORAGE_KEY);
      if (saved) setModeState(saved as ColorSchemeMode);
    };
    loadMode();
  }, []);
  
  // OSの設定変更を監視（auto モード時のみ反映）
  useEffect(() => {
    const subscription = Appearance.addChangeListener(({ colorScheme: newScheme }) => {
      if (mode === 'auto') {
        // OSの設定が変更されたら即座に反映（再レンダリング）
        console.log('[ColorScheme] OS設定が変更されました:', newScheme);
      }
    });
    
    return () => subscription.remove();
  }, [mode]);
  
  // 手動切り替え
  const setMode = async (newMode: ColorSchemeMode) => {
    setModeState(newMode);
    await AsyncStorage.setItem(STORAGE_KEY, newMode);
  };
  
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
```

### 4.2 既存ThemeContextとの統合

**現在の構造**:

```tsx
// App.tsx（現行）
<ThemeProvider> {/* adult/child */}
  <App />
</ThemeProvider>
```

**新しい構造**:

```tsx
// App.tsx（ダークモード対応後）
<ColorSchemeProvider> {/* light/dark/auto */}
  <ThemeProvider> {/* adult/child */}
    <App />
  </ThemeProvider>
</ColorSchemeProvider>
```

**注意**: 
- `ColorSchemeProvider` を外側に配置（グローバル設定）
- `ThemeProvider` は認証状態に依存するためそのまま維持

### 4.3 カスタムフック

#### 4.3.1 useColorScheme（新規）

```typescript
// hooks/useColorScheme.ts
export const useColorScheme = () => {
  const context = useContext(ColorSchemeContext);
  if (!context) {
    throw new Error('useColorScheme must be used within ColorSchemeProvider');
  }
  return context;
};
```

#### 4.3.2 useThemedColors（新規）

**用途**: テーマ（adult/child）とカラースキーマ（light/dark）を組み合わせた色を取得

```typescript
// hooks/useThemedColors.ts
export const useThemedColors = () => {
  const { colorScheme, colors } = useColorScheme();
  const { theme } = useTheme(); // 既存のThemeContext
  
  return {
    ...colors,
    // テーマ別アクセントカラー
    accent: ThemeColors[theme],
  };
};
```

### 4.4 既存コンポーネントの修正方針

**原則**: ハードコードされた色を `colors` オブジェクトから取得するように変更

**修正前**:
```tsx
const styles = StyleSheet.create({
  container: {
    backgroundColor: '#FFFFFF',
    borderColor: '#E5E7EB',
  },
  text: {
    color: '#111827',
  },
});
```

**修正後**:
```tsx
const { colors } = useThemedColors();

const styles = StyleSheet.create({
  container: {
    backgroundColor: colors.background,
    borderColor: colors.border.default,
  },
  text: {
    color: colors.text.primary,
  },
});
```

---

## 5. 手動切り替え機能（Android対策）

### 5.1 設定画面への追加

**対象画面**: `SettingsScreen.tsx` または `ProfileScreen.tsx`

**UI要素**:

| # | 要素種別 | ラベル | 選択肢 | デフォルト値 |
|---|---------|-------|--------|------------|
| 1 | セクション見出し | 「表示設定」 | - | - |
| 2 | セグメントコントロール | 「カラーテーマ」 | `自動` / `ライト` / `ダーク` | `自動` |

**実装例**:

```tsx
import { useColorScheme } from '../contexts/ColorSchemeContext';

const SettingsScreen = () => {
  const { mode, setMode } = useColorScheme();
  
  return (
    <View>
      <Text>表示設定</Text>
      
      <SegmentedControl
        values={['自動', 'ライト', 'ダーク']}
        selectedIndex={mode === 'auto' ? 0 : mode === 'light' ? 1 : 2}
        onChange={(event) => {
          const index = event.nativeEvent.selectedSegmentIndex;
          const modes: ColorSchemeMode[] = ['auto', 'light', 'dark'];
          setMode(modes[index]);
        }}
      />
      
      <Text style={{ color: colors.text.secondary }}>
        「自動」はデバイスの設定に従います
      </Text>
    </View>
  );
};
```

### 5.2 AsyncStorageによる永続化

**キー**: `@MyTeacher:colorSchemeMode`

**保存値**: `'auto'` / `'light'` / `'dark'`

**読み込みタイミング**: `ColorSchemeProvider` の初期化時

---

## 6. 対応が必要なコンポーネント一覧

### 6.1 優先度: 高（即座に視認できる部分）

| コンポーネント | ファイルパス | 対応内容 | 備考 |
|--------------|------------|---------|------|
| TaskCard | `src/components/tasks/TaskCard.tsx` | 背景、ボーダー、テキスト色 | 最も使用頻度が高い |
| DeadlineBadge | `src/components/tasks/DeadlineBadge.tsx` | バッジ背景、テキスト色 | 現在編集中 |
| TaskListScreen | `src/screens/tasks/TaskListScreen.tsx` | 背景、カード、検索バー | メイン画面 |
| Navigation | `src/navigation/AppNavigator.tsx` | タブバー、ヘッダー | 常に表示 |
| Button | `src/components/common/Button.tsx` | ボタン背景、テキスト色 | 全画面で使用 |

### 6.2 優先度: 中（頻繁に使用）

| コンポーネント | ファイルパス | 対応内容 |
|--------------|------------|---------|
| Modal | `src/components/common/Modal.tsx` | モーダル背景、オーバーレイ |
| Input | `src/components/common/Input.tsx` | 入力欄背景、ボーダー、テキスト色 |
| Card | `src/components/common/Card.tsx` | カード背景、シャドウ |
| ProfileScreen | `src/screens/profile/ProfileScreen.tsx` | 背景、カード、テキスト色 |

### 6.3 優先度: 低（詳細画面）

| コンポーネント | ファイルパス | 対応内容 |
|--------------|------------|---------|
| TaskDetailScreen | `src/screens/tasks/TaskDetailScreen.tsx` | 詳細表示 |
| NotificationListScreen | `src/screens/notifications/NotificationListScreen.tsx` | 通知一覧 |
| PerformanceChart | `src/components/charts/PerformanceChart.tsx` | グラフ配色 |

---

## 7. レスポンシブ設計との統合

### 7.1 既存のuseResponsiveとの組み合わせ

**参照**: `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md`

```typescript
import { useResponsive } from '../utils/responsive';
import { useThemedColors } from '../hooks/useThemedColors';

const MyComponent = () => {
  const { width } = useResponsive();
  const { colors, accent } = useThemedColors();
  const theme = isChildTheme ? 'child' : 'adult';
  
  const styles = StyleSheet.create({
    container: {
      backgroundColor: colors.background, // ダークモード対応
    },
    text: {
      fontSize: getFontSize(18, width, theme), // レスポンシブ対応
      color: colors.text.primary, // ダークモード対応
    },
  });
  
  return <Text style={styles.text}>サンプル</Text>;
};
```

---

## 8. テストケース

### 8.1 単体テスト

| # | テストケース | 期待結果 |
|---|------------|---------|
| 1 | OSがライトモードの場合 | `colorScheme === 'light'` |
| 2 | OSがダークモードの場合 | `colorScheme === 'dark'` |
| 3 | 手動で「ライト」に設定 | `mode === 'light'`, `colorScheme === 'light'` |
| 4 | 手動で「ダーク」に設定 | `mode === 'dark'`, `colorScheme === 'dark'` |
| 5 | 手動で「自動」に設定 | `mode === 'auto'`, OSの設定に従う |
| 6 | アプリ再起動後の設定保持 | AsyncStorageから読み込み、前回の設定が維持される |

### 8.2 結合テスト

| # | テストケース | 期待結果 |
|---|------------|---------|
| 1 | OS設定をライト→ダークに変更 | アプリが即座にダークモードに切り替わる |
| 2 | OS設定をダーク→ライトに変更 | アプリが即座にライトモードに切り替わる |
| 3 | 手動で「ライト」設定後、OS変更 | アプリは「ライト」のまま（OS設定を無視） |
| 4 | Adult テーマ + ダークモード | 青系のアクセントカラー、暗い背景 |
| 5 | Child テーマ + ダークモード | オレンジ系のアクセントカラー、暗い背景 |

### 8.3 実機テスト（必須）

**対象デバイス**:

| OS | デバイス | 画面サイズ | テスト内容 |
|----|---------|-----------|-----------|
| iOS | iPhone SE (3rd) | 375px | OS設定変更の即座反映 |
| iOS | iPad Air | 820px | タブレットレイアウト + ダークモード |
| Android | Pixel 7 | 412px | OS設定変更の即座反映 |
| Android | Galaxy Fold | 280px | 超小型デバイス + ダークモード |

**確認項目**:
- [ ] OS設定変更後、1秒以内にアプリが切り替わる
- [ ] 設定画面で手動切り替えが正しく動作
- [ ] テキスト、背景、ボーダーの色が適切
- [ ] アクセントカラー（グラデーション）が視認可能
- [ ] ステータスバーの色も追従する（`StatusBar.setBarStyle()`）

---

## 9. 実装手順

### Phase 1: 基盤実装（1日目）

1. **カラーパレット定義**
   - [ ] `src/utils/colors.ts` 作成
   - [ ] ライト/ダークの色定義

2. **ColorSchemeContext作成**
   - [ ] `src/contexts/ColorSchemeContext.tsx` 作成
   - [ ] `useColorScheme` カスタムフック作成
   - [ ] `App.tsx` に `ColorSchemeProvider` を追加

3. **useThemedColors作成**
   - [ ] `src/hooks/useThemedColors.ts` 作成
   - [ ] テーマ別アクセントカラーの統合

### Phase 2: コンポーネント対応（2-3日目）

4. **優先度: 高 のコンポーネント修正**
   - [ ] `TaskCard.tsx`
   - [ ] `DeadlineBadge.tsx`
   - [ ] `TaskListScreen.tsx`
   - [ ] `AppNavigator.tsx`
   - [ ] `Button.tsx`

5. **優先度: 中 のコンポーネント修正**
   - [ ] `Modal.tsx`
   - [ ] `Input.tsx`
   - [ ] `Card.tsx`
   - [ ] `ProfileScreen.tsx`

### Phase 3: 手動切り替え機能（4日目）

6. **設定画面への追加**
   - [ ] `SettingsScreen.tsx` にカラーテーマ切り替えUIを追加
   - [ ] AsyncStorageによる永続化実装

### Phase 4: テスト・調整（5日目）

7. **テスト実施**
   - [ ] 単体テスト（Jest）
   - [ ] 結合テスト（実機）
   - [ ] iOS/Android両OSで動作確認

8. **微調整**
   - [ ] 色の視認性確認
   - [ ] アニメーション・遷移の滑らかさ確認
   - [ ] ステータスバーの色調整

---

## 10. 注意事項・制約

### 10.1 OSレベル対応の注意点

1. **Android 9以前のOS**
   - ダークモードAPIが存在しない可能性あり
   - フォールバック: 手動切り替えのみ提供

2. **Expo Go でのテスト**
   - Expo Go アプリのダークモード設定に影響される場合あり
   - 本番ビルド（EAS Build）で最終確認必須

3. **ステータスバーの色**
   - `expo-status-bar` を使用して自動調整
   - iOS: `dark-content` / `light-content` 切り替え
   - Android: `backgroundColor` も変更必要

### 10.2 既存テーマとの競合

**問題**: `ThemeContext` の `theme` プロパティ名と混同しやすい

**対策**:
- `ThemeContext` は `userTheme` に改名（将来的に検討）
- または `ColorSchemeContext` の `colorScheme` を明示的に使用

### 10.3 パフォーマンス最適化

**問題**: 全コンポーネントが `useThemedColors()` を呼ぶと再レンダリングコストが高い

**対策**:
- `React.memo()` で不要な再レンダリングを防止
- `useMemo()` でスタイルオブジェクトをキャッシュ

---

## 11. 今後の拡張性

### 11.1 カスタムテーマ（将来的な対応）

- ユーザーがアクセントカラーを選択可能
- プリセットテーマ（ブルー、グリーン、パープル等）

### 11.2 アニメーション効果

- ライト↔ダーク切り替え時にフェードアニメーション
- `react-native-reanimated` を使用

### 11.3 Web版との完全同期

- Webアプリのダークモード設定をAPI経由で取得
- モバイルとWebで統一された体験

---

## 12. 関連ドキュメント

| ドキュメント名 | パス | 用途 |
|--------------|------|------|
| モバイル開発規則 | `/home/ktr/mtdev/docs/mobile/mobile-rules.md` | 全体の開発方針 |
| レスポンシブ設計ガイドライン | `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md` | 画面サイズ対応 |
| プロジェクト開発規則 | `/home/ktr/mtdev/.github/copilot-instructions.md` | コーディング規約 |
| Tailwind設定 | `/home/ktr/mtdev/tailwind.config.js` | Web版のダークモード設定 |

---

## 付録A: Tailwind CSS → React Native 色変換表

### A.1 背景色

| Tailwind CSS | Hex値 | React Native変数 | 用途 |
|-------------|-------|------------------|------|
| `bg-white` | `#FFFFFF` | `colors.background` (light) | ライトモード背景 |
| `bg-gray-50` | `#F9FAFB` | `colors.surface` (light) | ライトモードサーフェス |
| `bg-gray-800` | `#1F2937` | `colors.background` (dark) | ダークモード背景 |
| `bg-gray-900` | `#111827` | `colors.surface` (dark) | ダークモードサーフェス |
| `bg-gray-700` | `#374151` | `colors.card` (dark) | ダークモードカード |

### A.2 テキスト色

| Tailwind CSS | Hex値 | React Native変数 | 用途 |
|-------------|-------|------------------|------|
| `text-gray-900` | `#111827` | `colors.text.primary` (light) | 主要テキスト |
| `text-gray-500` | `#6B7280` | `colors.text.secondary` (light) | 補助テキスト |
| `text-gray-400` | `#9CA3AF` | `colors.text.tertiary` (light) | 説明文 |
| `dark:text-white` | `#FFFFFF` | `colors.text.primary` (dark) | ダークモード主要テキスト |
| `dark:text-gray-300` | `#D1D5DB` | `colors.text.secondary` (dark) | ダークモード補助テキスト |
| `dark:text-gray-400` | `#9CA3AF` | `colors.text.tertiary` (dark) | ダークモード説明文 |

### A.3 ボーダー色

| Tailwind CSS | Hex値 | React Native変数 | 用途 |
|-------------|-------|------------------|------|
| `border-gray-200` | `#E5E7EB` | `colors.border.default` (light) | ライトモードボーダー |
| `dark:border-gray-700` | `#4B5563` | `colors.border.default` (dark) | ダークモードボーダー |
| `border-gray-200/50` | `rgba(229,231,235,0.5)` | `colors.border.light` (light) | 半透明ボーダー |
| `dark:border-gray-700/50` | `rgba(75,85,99,0.5)` | `colors.border.light` (dark) | ダークモード半透明ボーダー |

---

## 付録B: 実装チェックリスト

### B.1 コード実装

- [ ] `src/utils/colors.ts` 作成（カラーパレット定義）
- [ ] `src/contexts/ColorSchemeContext.tsx` 作成
- [ ] `src/hooks/useColorScheme.ts` 作成
- [ ] `src/hooks/useThemedColors.ts` 作成
- [ ] `App.tsx` に `ColorSchemeProvider` 追加
- [ ] 全コンポーネントのハードコード色を変数化

### B.2 機能実装

- [ ] OSレベルダークモード自動追従
- [ ] 手動切り替え機能（設定画面）
- [ ] AsyncStorageによる設定永続化
- [ ] ステータスバーの色自動調整

### B.3 テスト

- [ ] 単体テスト（Context、Hook）
- [ ] iOS実機テスト（OS設定変更）
- [ ] Android実機テスト（OS設定変更）
- [ ] タブレット実機テスト
- [ ] 手動切り替え動作確認

### B.4 ドキュメント

- [ ] 実装完了レポート作成（`docs/reports/mobile/`）
- [ ] 本要件定義書の更新履歴を追記

---

**以上**
