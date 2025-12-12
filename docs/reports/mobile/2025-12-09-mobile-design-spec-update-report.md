# モバイル画面デザイン要件定義書 修正完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | 初版作成: Web CSS変換表に注力する方針への修正完了 |

---

## 1. 概要

### 1.1 修正の背景

モバイルアプリの画面デザイン要件定義書において、当初「Webアプリのレスポンシブデザイン（375px幅）と同等の画面構成」という方針を、タブレット対応等の新機能追加を含む形で誤解していました。

ユーザーからの明確化により、以下の認識に修正しました:

**正しい認識**:
- ❌ タブレット対応（2カラムグリッド）は**不要**（操作性次第で将来検討）
- ✅ **既存要素は変えない** - JSX/TSXの構造は変更しない
- ✅ **見た目をWebアプリと同じに** - Web CSSの値をStyleSheetに適用
- ✅ **アニメーションをWebアプリと同じように** - Web hoverをReact Native Animatedに変換
- ✅ **CSS参照対象**: app.css、dashboard.css、JS動的生成HTMLを含むすべてのスタイル

### 1.2 修正の目的

- タブレット対応等の新機能追加の記述を削除
- Web CSS → React Native StyleSheet変換表に注力する内容に修正
- Dimensions APIによる動的サイズ計算を禁止事項として明記
- 画面デザイン方針をCSS値の固定設定に限定

---

## 2. 修正内容

### 2.1 修正対象ファイル

| ファイル | 修正内容 | 行数変化 |
|---------|---------|---------|
| `/home/ktr/mtdev/definitions/mobile/ScreenDesignTemplate.md` | Header.tsx参照削除、タブレット対応削除、スマホ専用を明記 | -4行 |
| `/home/ktr/mtdev/definitions/mobile/TaskListScreen.md` | タブレット対応削除、Dimensions API削除、CSS変換表に注力 | -15行 |
| `/home/ktr/mtdev/docs/mobile/mobile-rules.md` | Step 6をCSS参照対象に変更、Dimensions API禁止を明記 | +25行 |

### 2.2 修正内容の詳細

#### 2.2.1 ScreenDesignTemplate.md

**修正前**:
```markdown
| 1 | タイトル | 「{タイトル}」 | ... | 15 | Header.tsx | - |
```

**修正後**:
```markdown
| 1 | タイトル | 「{タイトル}」 | ... | 15 | {Component}.tsx | - |
```

**理由**: Header.tsxが存在しないため、汎用的な表記に変更。

---

**修正前**:
```markdown
| `sm:` | 640px〜 | タブレット対応（Phase 3以降） |
| `md:` | 768px〜 | タブレット対応（Phase 3以降） |
```

**修正後**:
```markdown
| `sm:` | 640px〜 | 対象外 |
| `md:` | 768px〜 | 対象外 |

**注意**: スマートフォン（375px幅）のみ対応。`sm:`以上のブレークポイントクラスは無視し、基本クラス（ブレークポイント接頭辞なし）のみを実装する。
```

**理由**: タブレット対応は不要。基本クラスのみに注力。

---

#### 2.2.2 TaskListScreen.md

**修正前**:
```markdown
## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | 全面改訂: Web版Blade解析に基づくBentoグリッドUI要件追加（mobile-rules.md準拠） |
```

**修正後**:
```markdown
## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | CSS変換表に注力: タブレット対応削除、Web CSS→Mobile StyleSheetマッピングを主軸に |
| 2025-12-09 | GitHub Copilot | 全面改訂: Web版Blade解析に基づくBentoグリッドUI要件追加（mobile-rules.md準拠） |
```

**理由**: 最新の修正内容を更新履歴に追記。

---

**修正前**:
```markdown
- **Phase 2.B-5 Step 3**: UI改善（今回実装）
  - ✅ Web版dashboard.cssベースのデザイン適用
  - ✅ タブレット対応（2カラムグリッド）
  - ✅ タップアニメーション
```

**修正後**:
```markdown
- **Phase 2.B-5 Step 3**: UI改善（今回実装）
  - ✅ Web版dashboard.cssベースのデザイン適用
  - ✅ タップアニメーション（Web hoverアニメをタップに変換）
```

**理由**: タブレット対応は不要。Web CSSベースのデザイン適用とアニメーション変換のみ。

---

**修正前**:
```markdown
**実装状況**:
- ✅ **タグ別バケット表示**: 実装済み（Phase 2.B-5 Step 1）
- ✅ **スマホ対応**: 1カラム表示
- ✅ **タブレット対応**: 2カラムグリッド（今回追加）
- ✅ **検索機能**: フロントエンド側フィルタリング
- ✅ **画面遷移**: タグカードタップ→タグ内タスク一覧
```

**修正後**:
```markdown
**実装状況**:
- ✅ **タグ別バケット表示**: 実装済み（Phase 2.B-5 Step 1）
- ✅ **スマホ対応**: 1カラム表示
- ✅ **検索機能**: フロントエンド側フィルタリング
- ✅ **画面遷移**: タグカードタップ→タグ内タスク一覧
```

**理由**: タブレット対応は不要。

---

**修正前**:
```typescript
#### 15.5.2 実装例

import { Dimensions, PixelRatio } from 'react-native';

const { width } = Dimensions.get('window');

const scale = (size: number) => {
  const baseWidth = 375; // iPhone 12基準
  return (width / baseWidth) * size;
};

const styles = StyleSheet.create({
  headerTitle: {
    fontSize: width < 375 ? 16 : 18,
  },
  container: {
    paddingHorizontal: width < 375 ? 12 : 16,
  },
  tagCard: {
    padding: width < 375 ? 12 : 16,
  },
});
```

**修正後**:
```typescript
#### 15.5.2 実装方針

**原則**: スマートフォン（375px幅）を基準とし、StyleSheetに固定値を設定する。動的なサイズ計算は行わない。

**❌ 非推奨**: `Dimensions` APIを使った動的なサイズ計算

**✅ 推奨**: Web版CSSの値を固定値としてStyleSheetに設定

// Web版: text-lg (18px), p-4 (16px), rounded-2xl (16px)
const styles = StyleSheet.create({
  headerTitle: {
    fontSize: 18, // text-lg
    fontWeight: 'bold',
  },
  container: {
    paddingHorizontal: 16, // p-4
  },
  tagCard: {
    padding: 16, // p-4
    borderRadius: 16, // rounded-2xl
  },
});
```

**理由**: Dimensions APIによる動的計算は禁止。Web CSSの値を固定値として設定。

---

#### 2.2.3 mobile-rules.md

**修正前（Step 6）**:
```typescript
**Step 6: レスポンシブ対応の実装**

import { Dimensions } from 'react-native';

const { width } = Dimensions.get('window');
const isSmallDevice = width < 375; // 小型デバイス対応

const styles = StyleSheet.create({
  container: {
    padding: isSmallDevice ? 12 : 16, // 小型デバイスは余白を縮小
  },
  title: {
    fontSize: isSmallDevice ? 20 : 24, // 小型デバイスは文字を縮小
  },
});
```

**修正後（Step 6）**:
```markdown
**Step 6: Web CSSの参照対象**

**原則**: Webアプリのタスク一覧画面で使用されている**すべてのCSS・JSファイルを参照対象とする**。

**参照対象ファイル**:
- `resources/css/app.css` - 全画面共通のベーススタイル
- `resources/css/dashboard.css` - ダッシュボード専用スタイル（グラデーション、アニメーション等）
- `resources/views/**/*.blade.php` - Blade内の`<style>`タグ、インラインスタイル
- `resources/js/**/*.js` - JavaScript動的生成のHTML/スタイル

**抽出すべきスタイル要素**:
- 色: `background-color`, `color`, `gradient` 等
- サイズ: `font-size`, `padding`, `margin`, `width`, `height` 等
- 角丸: `border-radius`
- シャドウ: `box-shadow`, `shadow-*` クラス
- アニメーション: `transition`, `transform`, `hover:`, `active:` 等

**CSS変換表の作成**:

| Web CSS | 値 | React Native StyleSheet | 値 | 実装箇所 |
|---------|---|------------------------|---|---------|
| `text-lg` | 18px | `fontSize` | 18 | styles.title |
| `p-4` | 16px | `padding` | 16 | styles.card |
| `rounded-2xl` | 16px | `borderRadius` | 16 | styles.card |
| `bg-gradient-to-r from-[#59B9C6] to-purple-600` | - | `LinearGradient colors` | `['#59B9C6', '#9333EA']` | <LinearGradient> |
| `hover:translate-y-[-3px]` | -3px | `Animated.spring(translateY)` | -3 | onPressIn |

**実装方針**:
- ✅ **固定値を使用**: Web CSSの値をそのままReact Nativeに変換
- ❌ **動的計算は禁止**: `Dimensions` APIによる画面幅計算は行わない
- ✅ **既存コンポーネントのみ**: JSX/TSXの構造は変更しない
- ✅ **StyleSheet値のみ修正**: `styles` オブジェクト内の値を更新

// ❌ NG: Dimensions APIで動的計算
const { width } = Dimensions.get('window');
const fontSize = width < 375 ? 16 : 18;

// ✅ OK: Web CSSの値を固定値として設定
const styles = StyleSheet.create({
  title: {
    fontSize: 18, // Web版 text-lg
    fontWeight: 'bold', // Web版 font-bold
  },
});
```

**理由**: Dimensions APIによる動的計算を禁止し、Web CSSの値を固定値として設定する方針に変更。CSS参照対象を明確化。

---

**修正前（チェックリスト）**:
```markdown
**チェックリスト**:
- [ ] Bladeファイルを1行目から最終行まで読解した
- [ ] Tailwind CSSクラスを抽出し、React Native StyleSheetに変換した
- [ ] UI要素を構造化リスト（表形式）にまとめた
- [ ] Webアプリの全UI要素をモバイル版に実装した
- [ ] レスポンシブ対応（小型デバイス）を実装した
- [ ] Webアプリとの差分を明記した
```

**修正後（チェックリスト）**:
```markdown
**チェックリスト**:
- [ ] Bladeファイルを1行目から最終行まで読解した
- [ ] app.css、dashboard.css等のすべてのCSSファイルを参照した
- [ ] JavaScript動的生成のHTML/スタイルも確認した
- [ ] Tailwind CSSクラスを抽出し、React Native StyleSheetに変換した
- [ ] Web CSS → React Native 変換表（表形式）を作成した
- [ ] UI要素を構造化リスト（表形式）にまとめた
- [ ] Webアプリの全UI要素をモバイル版に実装した
- [ ] StyleSheetの値のみを修正し、JSX/TSX構造は変更していない
- [ ] Dimensions APIによる動的計算を使用していない
- [ ] Webアプリとの差分を明記した
```

**理由**: CSS参照範囲の拡大、変換表作成、JSX/TSX構造変更の禁止、Dimensions API禁止を明記。

---

## 3. 修正による影響範囲

### 3.1 既存実装への影響

**影響あり**:
- `/home/ktr/mtdev/mobile/src/components/tasks/BucketCard.tsx` - Animated API追加は**保留**（ユーザー確認待ち）
- `/home/ktr/mtdev/mobile/src/screens/tasks/TaskListScreen.tsx` - Dimensions API、numColumns削除が必要

**影響なし**:
- `/home/ktr/mtdev/definitions/mobile/NotificationListScreen.md` - 通知画面は今回の方針変更の影響を受けない
- `/home/ktr/mtdev/definitions/mobile/NotificationDetailScreen.md` - 同上
- `/home/ktr/mtdev/definitions/mobile/SubscriptionManagementScreen.md` - 同上

### 3.2 今後の実装方針

**実装時の手順**:

1. **Web版Bladeファイルの全文読解** - 1行目から最終行まで
2. **すべてのCSSファイルを参照** - app.css、dashboard.css、JS動的生成HTML
3. **Web CSS → React Native 変換表を作成** - 表形式で整理
4. **既存のStyleSheetの値のみを修正** - JSX/TSX構造は変更しない
5. **Dimensions APIは使用しない** - 固定値のみ

**禁止事項**:
- ❌ タブレット対応等の新機能追加
- ❌ Dimensions APIによる動的サイズ計算
- ❌ JSX/TSXの構造変更（コンポーネント追加、props変更等）

---

## 4. 今後の対応事項

### 4.1 TaskListScreen.tsxの修正（必須）

**削除すべきコード**:

```typescript
// ❌ 削除: Dimensions API
import { Dimensions } from 'react-native';

// ❌ 削除: 動的カラム計算
const [numColumns, setNumColumns] = useState(1);
useEffect(() => {
  const { width } = Dimensions.get('window');
  setNumColumns(width >= 768 ? 2 : 1);
}, []);

// ❌ 削除: numColumns、columnWrapperStyle
<FlatList numColumns={numColumns} columnWrapperStyle={...} />
```

**修正後のコード**:

```typescript
// ✅ 1カラム固定
<FlatList numColumns={1} />
```

### 4.2 BucketCard.tsxの確認（ユーザー判断待ち）

**確認事項**:
- Animated.spring タップスケールアニメーションは**CSS変換**か**新機能**か？
- Web版のhoverアニメーション（`hover:translate-y-[-3px]`）をタップに変換したものか？

**判断基準**:
- ✅ **CSS変換**: Web版にhoverアニメーションが存在し、それをタップに変換した場合 → 保持
- ❌ **新機能**: Web版にアニメーションが存在しない場合 → 削除

---

## 5. 修正完了チェックリスト

- [x] ScreenDesignTemplate.md修正完了
- [x] TaskListScreen.md修正完了
- [x] mobile-rules.md修正完了
- [ ] TaskListScreen.tsx修正（Dimensions API削除） - **次ステップ**
- [ ] BucketCard.tsx確認（Animated API保持/削除判断） - **ユーザー確認待ち**
- [x] 他の画面要件定義書（NotificationListScreen.md等）の影響確認 - **影響なし**

---

## 6. 添付資料

### 6.1 修正ファイル一覧

```bash
# 修正済みファイル
/home/ktr/mtdev/definitions/mobile/ScreenDesignTemplate.md
/home/ktr/mtdev/definitions/mobile/TaskListScreen.md
/home/ktr/mtdev/docs/mobile/mobile-rules.md

# 次ステップ修正対象
/home/ktr/mtdev/mobile/src/screens/tasks/TaskListScreen.tsx
/home/ktr/mtdev/mobile/src/components/tasks/BucketCard.tsx（確認後）
```

### 6.2 Git差分

```bash
# 修正内容確認
git diff definitions/mobile/ScreenDesignTemplate.md
git diff definitions/mobile/TaskListScreen.md
git diff docs/mobile/mobile-rules.md
```

---

**作成日**: 2025-12-09  
**作成者**: GitHub Copilot  
**レビュー**: 未実施  
**次のアクション**: TaskListScreen.tsx修正、BucketCard.tsx確認（ユーザー判断待ち）
