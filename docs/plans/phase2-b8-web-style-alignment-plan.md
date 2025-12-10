# Phase 2.B-8: Web版スタイル統一計画書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | 初版作成: レスポンシブ対応完了後のWeb版デザイン統一計画 |
| 2025-12-09 | GitHub Copilot | レスポンシブ対応完了を反映: 前提条件更新、実装状況の明確化 |

---

## 1. 概要

### 1.1 目的

モバイルアプリの見た目をWeb版（Tailwind CSS + resources/css/*.css）に統一し、ブランドの一貫性とユーザー体験の連続性を実現する。

### 1.2 前提条件

- ✅ **Phase 2.B-8（レスポンシブ対応）完了**（2025-12-09）:
  - **実装完了**: responsive.ts（9,014行）、useChildTheme.ts（1,283行）
  - **全32画面対応完了**: createStyles(width)パターン統一実装
  - **テスト完了**: 335テストケース、99.7%成功率
  - **完了レポート**: `docs/reports/mobile/2025-12-09-responsive-implementation-completion-report.md`
- 🎯 **次のステップ**: Web版スタイル統一（カラー、グラデーション、ボタン、アニメーション等）

### 1.3 優先順位の明確化

```
優先度1: レスポンシブ対応（構造調整） > 優先度2: Web版スタイル統一（装飾）
```

**理由**: 
- 表示崩れはユーザー体験を大きく損なう（即修正必要）
- 装飾の差異は機能性に影響しない（段階的実施可能）

---

## 2. 実装方針

### 2.1 基本原則（mobile-rules.md 総則4項準拠）

**原則**: **レスポンシブ対応を最優先**とし、Dimensions APIを積極的に使用してデバイス間の表示差異を吸収する。

**優先順位**:
```
優先度1: レスポンシブ対応（構造調整） > 優先度2: Web版スタイル統一（装飾）
```

**理由**: 
- 表示崩れはユーザー体験を大きく損なう（即修正必要）
- 装飾の差異は機能性に影響しない（段階的実施可能）
- **レスポンシブ対応は全32画面で完了済み**（2025-12-09）

### 2.2 実装手順（mobile-rules.md 総則4項 Step 6準拠）

各画面ごとに以下の順序で実施:

```
Step 1: レスポンシブ対応（✅完了） → Step 2: Web版スタイル適用（🎯実施中） → Step 3: 動作確認 → 次の画面へ
```

**Step 2詳細（Web版スタイル適用）**:
1. Bladeファイルを読み、Tailwind CSSクラスを抽出
2. responsive.ts の関数（getFontSize, getSpacing等）で動的計算
3. カラーパレット統一（colors.ts参照）
4. グラデーション効果適用（LinearGradient使用）
5. Platform別対応（iOS/Android両対応）
6. Android/iOS実機テスト

### 2.3 関連ドキュメント

| ドキュメント | 用途 | ステータス |
|------------|------|----------|
| `/home/ktr/mtdev/docs/mobile/mobile-rules.md` | モバイルアプリ開発規則（1416行、レスポンシブ・デザイン方針含む） | ✅ 参照必須 |
| `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md` | レスポンシブ対応の詳細技術仕様（605行、Dimensions API使用） | ✅ 実装完了 |
| `/home/ktr/mtdev/.github/copilot-instructions.md` | プロジェクト全体規約（不具合対応方針、コード修正規則等） | ✅ 遵守必須 |
| `/home/ktr/mtdev/docs/reports/mobile/2025-12-09-responsive-implementation-completion-report.md` | レスポンシブ実装完了レポート（全32画面、335テスト） | ✅ 作成済み |
| `/home/ktr/mtdev/docs/plans/phase2-mobile-app-implementation-plan.md` | Phase 2全体計画書 | ✅ 更新済み |

---

## 3. Web版スタイル統一の実施内容

### 3.1 カラーパレット統一

**Web版Tailwind CSS → React Native対応表**

#### プライマリカラー

| 用途 | Web版 Tailwind | HEXコード | React Native |
|------|---------------|----------|--------------|
| プライマリ | `bg-blue-600` | `#2563EB` | `backgroundColor: '#2563EB'` |
| プライマリホバー | `hover:bg-blue-700` | `#1D4ED8` | Pressable: `opacity: 0.7` |
| プライマリライト | `bg-blue-50` | `#EFF6FF` | `backgroundColor: '#EFF6FF'` |

#### セカンダリカラー

| 用途 | Web版 Tailwind | HEXコード | React Native |
|------|---------------|----------|--------------|
| 成功 | `bg-green-600` | `#10B981` | `backgroundColor: '#10B981'` |
| 警告 | `bg-yellow-500` | `#F59E0B` | `backgroundColor: '#F59E0B'` |
| エラー | `bg-red-600` | `#EF4444` | `backgroundColor: '#EF4444'` |
| 情報 | `bg-purple-600` | `#9333EA` | `backgroundColor: '#9333EA'` |

#### グレースケール

| 用途 | Web版 Tailwind | HEXコード | React Native |
|------|---------------|----------|--------------|
| 背景（最も明るい） | `bg-gray-50` | `#F9FAFB` | `backgroundColor: '#F9FAFB'` |
| 背景（カード） | `bg-gray-100` | `#F3F4F6` | `backgroundColor: '#F3F4F6'` |
| ボーダー | `border-gray-300` | `#D1D5DB` | `borderColor: '#D1D5DB'` |
| テキスト（セカンダリ） | `text-gray-600` | `#4B5563` | `color: '#4B5563'` |
| テキスト（プライマリ） | `text-gray-900` | `#111827` | `color: '#111827'` |

#### 子ども向けテーマカラー

| 用途 | Web版 | HEXコード | React Native |
|------|------|----------|--------------|
| プライマリ | `bg-yellow-400` | `#FBBF24` | `backgroundColor: '#FBBF24'` |
| セカンダリ | `bg-orange-400` | `#FB923C` | `backgroundColor: '#FB923C'` |
| 背景 | `bg-yellow-50` | `#FFFBEB` | `backgroundColor: '#FFFBEB'` |

**実装方法**:

```typescript
// カラーは直接StyleSheetに記述（constants/colors.tsは作成しない）
import { StyleSheet } from 'react-native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '@/utils/responsive';
import { useChildTheme } from '@/hooks/useChildTheme';

const MyComponent = () => {
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  
  const styles = StyleSheet.create({
    button: {
      backgroundColor: '#2563EB', // Web版 bg-blue-600
      borderRadius: getBorderRadius(8, width),
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(24, width),
    },
    buttonText: {
      color: '#FFFFFF',
      fontSize: getFontSize(16, width, isChildTheme ? 'child' : 'adult'),
    },
  });
  
  return (
    <Pressable
      style={({ pressed }) => [styles.button, pressed && { opacity: 0.7 }]}
    >
      <Text style={styles.buttonText}>ボタン</Text>
    </Pressable>
  );
};
    hover: '#1D4ED8',
    light: '#EFF6FF',
  },
  secondary: {
    success: '#10B981',
    warning: '#F59E0B',
    danger: '#EF4444',
    info: '#9333EA',
  },
  gray: {
    50: '#F9FAFB',
    100: '#F3F4F6',
    300: '#D1D5DB',
    600: '#4B5563',
    900: '#111827',
  },
  child: {
    primary: '#FBBF24',
    secondary: '#FB923C',
    background: '#FFFBEB',
  },
};

// 使用例
import { colors } from '@/constants/colors';

const styles = StyleSheet.create({
  button: {
    backgroundColor: colors.primary.main,
  },
  buttonText: {
    color: '#FFFFFF',
  },
```

**注意**: `constants/colors.ts` は作成せず、各コンポーネントで直接HEXコードを記述する。これにより、Web版Bladeファイルとの対応関係が明確になり、保守性が向上する。

### 3.2 グラデーション効果（バケットカード）

**Web版**: `bg-gradient-to-br from-blue-50 to-purple-50`

**モバイル実装**（ResponsiveDesignGuideline.md 準拠）:

```typescript
import { LinearGradient } from 'expo-linear-gradient';
import { useResponsive, getBorderRadius, getSpacing, getShadow } from '@/utils/responsive';

const MyComponent = () => {
  const { width } = useResponsive();
  
  // バケットカード背景グラデーション
  return (
    <LinearGradient
      colors={['#EFF6FF', '#FAF5FF']} // blue-50 → purple-50
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={styles.cardGradient}
    >
      {/* カード内容 */}
    </LinearGradient>
  );
};

const createStyles = (width: number) => StyleSheet.create({
  cardGradient: {
    borderRadius: getBorderRadius(16, width), // Web版 rounded-2xl
    padding: getSpacing(16, width),           // Web版 p-4
    ...getShadow(4),                          // Web版 shadow-md
  },
});
```

**適用対象**:
- BucketCard.tsx: タグバケット背景
- TaskCard.tsx: タスクカード背景（優先度別グラデーション）
- AvatarWidget.tsx: アバター背景（子ども向けテーマ）

### 3.3 シャドウ効果の統一

**実装状況**: ✅ **完了** - responsive.ts の `getShadow()` で既に対応済み（2025-12-09）

```typescript
// Web版: shadow-md (Tailwind CSS)
// モバイル: getShadow(4) で自動変換

const styles = StyleSheet.create({
  card: {
    ...getShadow(4), // Android: elevation 4, iOS: shadowColor等
  },
  modal: {
    ...getShadow(8), // 強めのシャドウ
  },
});
```

**シャドウレベル対応表**:

| Web版 Tailwind | elevation | 用途 |
|---------------|-----------|------|
| `shadow-sm` | 2 | 軽いカード |
| `shadow` (デフォルト) | 4 | 通常カード |
| `shadow-md` | 6 | 重要カード |
| `shadow-lg` | 8 | モーダル |
| `shadow-xl` | 12 | フローティングボタン |
| `shadow-2xl` | 16 | ダイアログ |

### 3.4 ボタンスタイル統一

**Web版**: hover効果 + transition-colors

**モバイル実装**: Pressableでopacity調整（mobile-rules.md 準拠）

```typescript
import { Pressable, Text, StyleSheet } from 'react-native';
import { useResponsive, getFontSize, getSpacing, getBorderRadius } from '@/utils/responsive';
import { useChildTheme } from '@/hooks/useChildTheme';

const PrimaryButton = ({ title, onPress }) => {
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const theme = isChildTheme ? 'child' : 'adult';
  
  const styles = StyleSheet.create({
    button: {
      backgroundColor: '#2563EB', // Web版 bg-blue-600
      paddingVertical: getSpacing(12, width),
      paddingHorizontal: getSpacing(24, width),
      borderRadius: getBorderRadius(8, width),
      alignItems: 'center',
    },
    buttonText: {
      color: '#FFFFFF',
      fontSize: getFontSize(16, width, theme),
      fontWeight: '600',
    },
  });
  
  return (
    <Pressable
      onPress={onPress}
      style={({ pressed }) => [
        styles.button,
        pressed && { opacity: 0.7 }, // Web版のhover:bg-blue-700を再現
      ]}
    >
      <Text style={styles.buttonText}>{title}</Text>
    </Pressable>
  );
};
```

**ボタンバリエーション**:

| 種類 | Web版クラス | モバイル実装 |
|------|-----------|------------|
| プライマリ | `bg-blue-600 hover:bg-blue-700` | `backgroundColor: colors.primary.main` + pressed opacity |
| セカンダリ | `bg-white border border-gray-300` | `backgroundColor: '#FFFFFF', borderColor: colors.gray[300]` |
| 危険 | `bg-red-600 hover:bg-red-700` | `backgroundColor: colors.secondary.danger` + pressed opacity |
| テキストのみ | `text-blue-600 hover:underline` | `color: colors.primary.main` + pressed opacity |

### 3.5 フォント統一（子ども向けテーマ）

**実装状況**: ✅ **完了** - responsive.ts の `getFontSize()` で既に対応済み（1.2倍拡大、2025-12-09）

**追加実装**: フォントファミリーの統一

```typescript
import { Platform } from 'react-native';

// mobile/src/utils/responsive.ts に追加
export const getChildFontFamily = (): string => {
  return Platform.select({
    ios: 'Hiragino Sans',        // iOS標準（Web版: Hiragino Kaku Gothic ProN相当）
    android: 'Noto Sans CJK JP',  // Android標準（Web版: Noto Sans JP相当）
    default: 'System',
  }) || 'System';
};

// 使用例
const styles = StyleSheet.create({
  childText: {
    fontFamily: isChildTheme ? getChildFontFamily() : 'System',
    fontSize: getFontSize(16, width, 'child'), // 1.2倍拡大
  },
});
```

### 3.6 アニメーション統一

**Web版**: transition-colors、hover効果、transform

**モバイル実装**: Animated API

#### タップアニメーション（カード）

**実装状況**: ✅ **完了** - BucketCard.tsx で既に実装済み（2025-12-09）

```typescript
import { Animated, TouchableOpacity } from 'react-native';
import { useRef } from 'react';

const AnimatedCard = ({ children, onPress }) => {
  const scaleAnim = useRef(new Animated.Value(1)).current;

  const handlePressIn = () => {
    Animated.spring(scaleAnim, {
      toValue: 0.97, // Web版のtransform: translateY(2px)を再現
      useNativeDriver: true,
    }).start();
  };

  const handlePressOut = () => {
    Animated.spring(scaleAnim, {
      toValue: 1,
      friction: 3,
      useNativeDriver: true,
    }).start();
  };

  return (
    <Animated.View style={{ transform: [{ scale: scaleAnim }] }}>
      <TouchableOpacity
        onPressIn={handlePressIn}
        onPressOut={handlePressOut}
        onPress={onPress}
        activeOpacity={1}
      >
        {children}
      </TouchableOpacity>
    </Animated.View>
  );
};
```

**適用対象**:
- BucketCard.tsx: ✅ **実装済み**（2025-12-09）
- TaskCard.tsx: 🎯 次フェーズで実装
- NotificationCard.tsx: 🎯 次フェーズで実装
- AvatarCard.tsx: 🎯 次フェーズで実装

#### ローディングアニメーション

```typescript
import { ActivityIndicator } from 'react-native';
import { colors } from '@/constants/colors';

// Web版のスピナーを再現
<ActivityIndicator size="large" color={colors.primary.main} />
```

---

## 4. 実装スケジュール（3週間）

**前提**: レスポンシブ対応完了（全32画面、2025-12-09）により、Web版スタイル統一のみに集中可能

**原則**: mobile-rules.md 総則4項に基づき、各画面でBladeファイル読解→Tailwind CSS抽出→React Native変換→実機テストを実施

### Week 1: コアタスク機能のスタイル統一（優先度：最高） - 10画面

| # | 画面 | 参照Bladeファイル | Web版スタイル適用内容 | 工数 |
|---|------|-----------------|-------------------|------|
| 1 | タスク一覧画面（BucketCard） | `resources/views/tasks/index.blade.php` | カラー、グラデーション、シャドウ統一 | 0.5日 |
| 2 | タスク詳細画面 | `resources/views/tasks/show.blade.php` | カラー、ボタン統一 | 0.5日 |
| 3 | タスク作成画面 | `resources/views/tasks/create.blade.php` | フォーム要素デザイン統一 | 0.5日 |
| 4 | タスク編集画面 | `resources/views/tasks/edit.blade.php` | DatePicker Platform対応 | 0.5日 |
| 5 | タスク承認待ち一覧 | `resources/views/task_approvals/index.blade.php` | カラー、シャドウ、ボタン統一 | 0.5日 |
| 6 | タスク承認詳細 | `resources/views/task_approvals/show.blade.php` | カラー、ボタン統一 | 0.5日 |
| 7 | グループタスク作成 | `resources/views/tasks/create.blade.php` | ユーザー選択UIデザイン統一 | 0.5日 |
| 8 | タグ管理 | `resources/views/tags/index.blade.php` | カラー統一 | 0.5日 |
| 9 | タグ編集 | `resources/views/tags/edit.blade.php` | フォーム要素デザイン統一 | 0.5日 |
| 10 | タグバケット詳細 | `resources/views/tasks/index.blade.php` | カード・モーダルデザイン統一 | 0.5日 |

### Week 2: 管理・設定画面のスタイル統一（優先度：高） - 11画面

| # | 画面 | 参照Bladeファイル | Web版スタイル適用内容 | 工数 |
|---|------|-----------------|-------------------|------|
| 11 | グループ管理 | `resources/views/groups/index.blade.php` | メンバーカードデザイン統一 | 0.5日 |
| 12 | グループ詳細 | `resources/views/groups/show.blade.php` | カラー、ボタン統一 | 0.5日 |
| 13 | グループ作成 | `resources/views/groups/create.blade.php` | フォーム要素デザイン統一 | 0.5日 |
| 14 | グループ編集 | `resources/views/groups/edit.blade.php` | フォーム要素デザイン統一 | 0.5日 |
| 15 | タスク自動作成設定 | `resources/views/scheduled_tasks/index.blade.php` | フォーム要素デザイン統一 | 1日 |
| 16 | タスク自動作成編集 | `resources/views/scheduled_tasks/edit.blade.php` | フォーム要素デザイン統一 | 1日 |
| 17 | 通知一覧 | `resources/views/notifications/index.blade.php` | アバターサイズ最適化 | 0.5日 |
| 18 | 通知詳細 | `resources/views/notifications/show.blade.php` | ボタン配置最適化 | 0.5日 |
| 19 | アバター管理 | `resources/views/avatars/index.blade.php` | グリッド表示デザイン | 0.5日 |
| 20 | アバター作成 | `resources/views/avatars/create.blade.php` | フォーム要素デザイン統一 | 0.5日 |
| 21 | アバター編集 | `resources/views/avatars/edit.blade.php` | フォーム要素デザイン統一 | 0.5日 |

### Week 3: 課金・レポート画面のスタイル統一 + テスト（優先度：中） - 11画面 + テスト

| # | 画面 | 参照Bladeファイル | Web版スタイル適用内容 | 工数 |
|---|------|-----------------|-------------------|------|
| 22 | サブスクリプション管理 | `resources/views/subscription/manage.blade.php` | グラデーション、カラー統一 | 1日 |
| 23 | プラン選択 | `resources/views/subscription/plans.blade.php` | プランカードデザイン統一 | 0.5日 |
| 24 | 決済履歴 | `resources/views/subscription/history.blade.php` | テーブルデザイン統一 | 0.5日 |
| 25 | トークン購入 | `resources/views/tokens/purchase.blade.php` | ボタン、カードデザイン統一 | 0.5日 |
| 26 | トークン履歴 | `resources/views/tokens/history.blade.php` | テーブルデザイン統一 | 0.5日 |
| 27 | パフォーマンスレポート | `resources/views/reports/performance.blade.php` | 統計カードデザイン統一 | 1日 |
| 28 | 月次レポート | `resources/views/reports/monthly.blade.php` | グラフ・カードデザイン統一 | 1日 |
| 29 | プロフィール | `resources/views/profile/edit.blade.php` | フォーム要素デザイン統一 | 0.5日 |
| 30 | プロフィール設定 | `resources/views/profile/settings.blade.php` | スイッチ・ボタンデザイン統一 | 0.5日 |
| 31 | ログイン画面 | `resources/views/auth/login.blade.php` | カラー、ボタン統一 | 0.5日 |
| 32 | 登録画面 | `resources/views/auth/register.blade.php` | フォーム要素デザイン統一 | 0.5日 |
| - | **全画面デバイステスト** | - | 7デバイス × 縦横向き確認 | 1.5日 |
| - | **スクリーンショット比較** | - | Web版と目視確認 | 0.5日 |
| - | **ドキュメント作成** | - | 完了レポート作成 | 0.5日 |

**合計工数**: 18日（3週間）

**工数内訳**:
- Web版スタイル適用: 15日（全32画面 × 平均0.47日/画面）
- テスト・検証: 2.5日
- ドキュメント: 0.5日

**工数削減理由**:
- ✅ レスポンシブ対応が完了済み（全32画面、responsive.ts 9,014行）
- ✅ ユーティリティ関数が実装済み（getFontSize, getSpacing, getBorderRadius, getShadow）
- ✅ 実装パターンが確立済み（createStyles(width)パターン）
- ✅ テストフレームワーク整備済み（335テストケース、99.7%成功率）
- → Web版Bladeファイル読解 + Tailwind CSS変換のみに集中可能

---

## 5. 画面別実装詳細

### 5.1 承認待ち一覧画面

**ファイル**: `mobile/src/screens/approvals/ApprovalListScreen.tsx`

**Web版参照**: `/home/ktr/mtdev/resources/views/task_approvals/index.blade.php`

#### レスポンシブ対応

**実装状況**: ✅ **完了**（2025-12-09）

```typescript
import { useResponsive, getFontSize, getSpacing, getHeaderTitleProps } from '@/utils/responsive';
import { useChildTheme } from '@/hooks/useChildTheme';

const ApprovalListScreen = () => {
  const { width } = useResponsive();
  const isChildTheme = useChildTheme();
  const themeType = isChildTheme ? 'child' : 'adult';

  return (
    <View>
      <Text style={styles.headerTitle} {...getHeaderTitleProps()}>
        承認待ち一覧
      </Text>
    </View>
  );
};

const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  headerTitle: {
    fontSize: getFontSize(20, width, theme),
  },
  container: {
    padding: getSpacing(16, width),
  },
});
```

#### Web版スタイル適用

```typescript
import { colors } from '@/constants/colors';
import { getShadow, getBorderRadius } from '@/utils/responsive';

const createStyles = (width: number, theme: 'adult' | 'child') => StyleSheet.create({
  approvalCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: getBorderRadius(12, width),
    padding: getSpacing(16, width),
    marginBottom: getSpacing(12, width),
    ...getShadow(4), // Web版: shadow-md
  },
  approveButton: {
    backgroundColor: colors.secondary.success, // Web版: bg-green-600
    borderRadius: getBorderRadius(8, width),
    paddingVertical: getSpacing(8, width),
    paddingHorizontal: getSpacing(16, width),
  },
  rejectButton: {
    backgroundColor: colors.secondary.danger, // Web版: bg-red-600
    borderRadius: getBorderRadius(8, width),
    paddingVertical: getSpacing(8, width),
    paddingHorizontal: getSpacing(16, width),
  },
});
```

### 5.2 サブスクリプション管理画面

**ファイル**: `mobile/src/screens/subscription/SubscriptionManagementScreen.tsx`

**Web版参照**: `/home/ktr/mtdev/resources/views/subscription/manage.blade.php`

#### グラデーション背景（プランカード）

```typescript
import { LinearGradient } from 'expo-linear-gradient';

// Free プラン: グレーグラデーション
<LinearGradient
  colors={['#F9FAFB', '#F3F4F6']} // gray-50 → gray-100
  start={{ x: 0, y: 0 }}
  end={{ x: 0, y: 1 }}
  style={styles.planCard}
>
  <Text style={styles.planName}>Free</Text>
</LinearGradient>

// Premium プラン: ブルーグラデーション
<LinearGradient
  colors={['#EFF6FF', '#DBEAFE']} // blue-50 → blue-100
  start={{ x: 0, y: 0 }}
  end={{ x: 0, y: 1 }}
  style={styles.planCard}
>
  <Text style={styles.planName}>Premium</Text>
</LinearGradient>

// Enterprise プラン: パープルグラデーション
<LinearGradient
  colors={['#FAF5FF', '#F3E8FF']} // purple-50 → purple-100
  start={{ x: 0, y: 0 }}
  end={{ x: 0, y: 1 }}
  style={styles.planCard}
>
  <Text style={styles.planName}>Enterprise</Text>
</LinearGradient>
```

### 5.3 タスク自動作成の設定画面

**ファイル**: `mobile/src/screens/scheduled-tasks/ScheduledTaskSettingsScreen.tsx`

**Web版参照**: `/home/ktr/mtdev/resources/views/scheduled_tasks/index.blade.php`

#### 長いタイトル対策（特別対応）

```typescript
// ヘッダータイトルのフォントサイズを強制的に縮小
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

<Text
  style={[
    styles.headerTitle,
    { fontSize: getTitleFontSize('タスク自動作成の設定', 20, width, themeType) }
  ]}
  numberOfLines={2}
  adjustsFontSizeToFit={true}
  minimumFontScale={0.7}
>
  タスク自動作成の設定
</Text>
```

---

## 6. テスト計画

### 6.1 デバイス別テスト

| デバイス | 画面幅 | 縦向き | 横向き | 優先度 |
|---------|-------|-------|-------|-------|
| iPhone SE 1st | 320px | ✅ | ✅ | 高 |
| iPhone 12/13/14 | 390px | ✅ | ✅ | **最高** |
| iPhone 14 Pro Max | 430px | ✅ | ✅ | 高 |
| Pixel 7 | 412px | ✅ | ✅ | **最高** |
| Galaxy Fold | 280px | ✅ | - | 中 |
| iPad mini | 768px | ✅ | ✅ | 高 |
| iPad Pro | 1024px | ✅ | ✅ | 高 |

### 6.2 確認項目

#### レスポンシブ対応

- [ ] 全デバイスでテキスト折り返しなし
- [ ] カード・画像が見切れない
- [ ] 余白が適切（最小値保証）
- [ ] 画面回転時にレイアウト崩れなし
- [ ] 子ども向けテーマでフォント1.2倍適用

#### Web版スタイル統一

- [ ] カラーパレットが統一されている
- [ ] グラデーション効果が適用されている
- [ ] シャドウが適切に表示される（iOS/Android）
- [ ] ボタンのPressed効果が動作する
- [ ] アニメーションがスムーズに動作する
- [ ] フォントがWeb版と同等（子ども向けテーマ）

### 6.3 スクリーンショット比較

**手順**:
1. Web版（375px幅）でスクリーンショット撮影
2. モバイル版（iPhone 12, 390px）でスクリーンショット撮影
3. 並べて比較し、差異を確認
4. 必要に応じて微調整

---

## 7. 成果物

### 7.1 実装ファイル

- [ ] `/mobile/src/constants/colors.ts` - カラーパレット定義（新規）
- [ ] 全画面のレスポンシブ対応完了（12画面）
- [ ] 全画面のWeb版スタイル適用完了（12画面）

### 7.2 ドキュメント

- [ ] 完了レポート作成（`docs/reports/mobile/2025-12-XX-web-style-alignment-completion-report.md`）
- [ ] 計画書の更新履歴に完了日記載

---

## 8. リスク・制約事項

### 8.1 技術的制約

| 項目 | Web版 | モバイル制約 | 対処方法 |
|------|------|-----------|---------|
| グラデーション | CSS gradient | LinearGradient必須 | expo-linear-gradient使用 |
| hover効果 | :hover擬似クラス | タッチのみ | Pressableのpressed状態で代替 |
| transition | CSS transition | Animated API | useRef + Animated.spring() |
| カスタムフォント | Webフォント | システムフォントのみ | Platform.select()で近似 |

### 8.2 デザイン差異の許容範囲

以下はモバイル特有の制約により、Web版と完全一致させない:

- ✅ **許容**: ホバー効果 → Pressed効果（タップ時のみ）
- ✅ **許容**: トランジション速度の微調整（体感速度の違い）
- ✅ **許容**: フォントの微妙な見た目の違い（システムフォント使用）
- ❌ **不許容**: カラー、余白、角丸、シャドウの明らかな差異

---

## 9. 完了条件

### 9.1 実装完了条件

- [x] **全32画面でレスポンシブ対応完了**（2025-12-09）
  - [x] responsive.ts実装（9,014行）
  - [x] useChildTheme.ts実装（1,283行）
  - [x] createStyles(width)パターン統一
  - [x] 335テストケース成功（99.7%）
  - [x] レスポンシブ実装完了レポート作成

- [ ] **全32画面でWeb版スタイル適用完了**（🎯 実施中）
  - [ ] 各画面でBladeファイル読解完了（mobile-rules.md 総則4項準拠）
  - [ ] Tailwind CSSクラス抽出完了（grep_searchによる機械的検出）
  - [ ] React Native StyleSheet変換完了（responsive.ts関数使用）
  - [ ] カラーパレット統一（HEXコード直接記述）
  - [ ] グラデーション効果適用（LinearGradient）
  - [ ] ボタンスタイル統一（Pressable + opacity）
  - [ ] アニメーション統一（Animated API）
  - [ ] Platform別対応完了（iOS/Android両対応）

### 9.2 テスト完了条件

- [ ] **デバイス別動作確認完了**（7デバイス × 縦横向き）
  - [ ] iPhone SE 1st (320px) - 縦向き
  - [ ] iPhone 12/13/14 (390px) - 縦向き・横向き
  - [ ] iPhone 14 Pro Max (430px) - 縦向き・横向き
  - [ ] Pixel 7 (412px) - 縦向き・横向き
  - [ ] Galaxy Fold (280px) - 縦向き
  - [ ] iPad mini (768px) - 縦向き・横向き
  - [ ] iPad Pro (1024px) - 縦向き・横向き

- [ ] **スクリーンショット比較完了**
  - [ ] Web版（375px幅）とモバイル版（iPhone 12）で並べて比較
  - [ ] カラー、余白、角丸、シャドウの差異なし確認
  - [ ] フォントサイズ・ファミリーの統一性確認
  - [ ] 子ども向けテーマの1.2倍フォント適用確認

### 9.3 ドキュメント完了条件（copilot-instructions.md レポート作成規則準拠）

- [ ] **完了レポート作成**（`docs/reports/mobile/2025-12-XX-web-style-alignment-completion-report.md`）
  - [ ] 更新履歴セクション（冒頭配置）
  - [ ] 概要セクション（達成した目標、主要な成果）
  - [ ] 計画との対応関係（phase2-b8-web-style-alignment-plan.mdとの対応表）
  - [ ] 実施内容詳細（全32画面の作業内容、使用ツール、成果物）
  - [ ] 成果と効果（定量的・定性的効果）
  - [ ] 未完了項目・次のステップ（残作業、今後の推奨事項）

- [ ] **計画書の更新履歴に完了日記載**
  - [ ] phase2-b8-web-style-alignment-plan.md の更新履歴に完了日追加
  - [ ] phase2-mobile-app-implementation-plan.md の更新

### 9.4 品質保証条件

- [ ] **静的解析ツールによる検証**（copilot-instructions.md コード修正規則準拠）
  - [ ] Intelephense: 警告・エラーなし
  - [ ] 未使用変数・インポートの削除
  - [ ] 未定義メソッド・プロパティの修正
  - [ ] 型不一致の解消
  - [ ] 名前空間エラーの修正

- [ ] **コードレビュー完了**
  - [ ] mobile-rules.md 総則4項チェックリスト全項目確認
  - [ ] ResponsiveDesignGuideline.md 実装チェックリスト全項目確認
  - [ ] copilot-instructions.md 禁止事項の違反なし確認

---

**作成日**: 2025-12-09  
**最終更新**: 2025-12-09  
**作成者**: GitHub Copilot  
**関連Phase**: Phase 2.B-8  
**前提Phase**: Phase 2.B-8（レスポンシブ対応）完了（2025-12-09）  
**参照レポート**: `docs/reports/mobile/2025-12-09-responsive-implementation-completion-report.md`
