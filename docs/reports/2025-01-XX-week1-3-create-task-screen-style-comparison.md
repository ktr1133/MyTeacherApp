# Week 1-3: タスク作成画面 スタイル比較レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-01-XX | GitHub Copilot | 初版作成: Web版とモバイル版のスタイル差異分析 |

---

## 概要

**Week 1-3「タスク作成画面」**のWeb版スタイル統一作業として、以下を完了しました：

- ✅ **Step 1: Web版スタイル抽出**完了
- ✅ **Step 2: モバイル版との詳細比較**完了
- ⏳ **Step 3: スタイル統一実装**準備中

**参照ファイル**:
- **Web版（通常タスク）**: `resources/views/dashboard/modal-dashboard-task.blade.php`
- **Web版（グループタスク）**: `resources/views/dashboard/modal-group-task.blade.php`
- **モバイル版**: `mobile/src/screens/tasks/CreateTaskScreen.tsx` (1211行)

---

## Web版スタイル抽出結果

### 1. 通常タスク作成モーダル（modal-dashboard-task.blade.php）

#### 1-1. モーダル基本構造

```blade
<!-- モーダルオーバーレイ -->
class="modal fixed inset-0 z-50 flex items-center justify-center p-4 
       modal-overlay bg-gray-900/75 backdrop-blur-sm hidden opacity-0 
       transition-opacity duration-300"

<!-- モーダルコンテンツ -->
class="modal-content bg-white dark:bg-gray-900 w-full max-w-3xl max-h-[90vh] 
       flex flex-col overflow-hidden transform transition-all duration-300 
       translate-y-4 scale-95 shadow-2xl rounded-2xl"
```

**特徴**:
- **backdrop-blur-sm**: オーバーレイにぼかし効果
- **shadow-2xl**: 深いシャドウ
- **rounded-2xl**: 大きめの角丸（16px）
- **transition-all duration-300**: スムーズなアニメーション

#### 1-2. ヘッダー（テーマ別グラデーション）

```blade
<!-- デフォルトテーマ -->
class="px-6 py-4 border-b border-[#59B9C6]/20 
       bg-gradient-to-r from-[#59B9C6]/10 to-blue-50"

<!-- アイコン背景 -->
class="w-10 h-10 rounded-xl 
       bg-gradient-to-br from-[#59B9C6] to-blue-600 
       flex items-center justify-center shadow-lg"

<!-- タイトル -->
class="text-lg font-bold 
       bg-gradient-to-r from-[#59B9C6] to-blue-600 
       bg-clip-text text-transparent"
```

**特徴**:
- **bg-gradient-to-r**: 横方向グラデーション（ヘッダー背景）
- **bg-gradient-to-br**: 斜めグラデーション（アイコン背景）
- **bg-clip-text text-transparent**: テキストにグラデーション適用
- **shadow-lg**: アイコン背景に影

#### 1-3. フォーム要素

##### タイトル・説明入力
```blade
class="w-full px-4 py-2.5 border 
       border-[#59B9C6]/30 dark:border-[#59B9C6]/40 
       rounded-lg bg-white dark:bg-gray-800 
       focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent 
       transition text-sm placeholder-gray-400"
```

**特徴**:
- **focus:ring-2**: フォーカス時に外枠表示（2px幅）
- **focus:border-transparent**: フォーカス時にボーダー透明化
- **transition**: スムーズな状態遷移

##### スパン選択（短期・中期・長期）
```blade
class="w-full px-4 py-2.5 border 
       border-[#59B9C6]/30 dark:border-[#59B9C6]/40 
       rounded-lg bg-white dark:bg-gray-800 
       focus:ring-2 focus:ring-[#59B9C6] focus:border-transparent 
       transition text-sm"
```

**モバイル版との違い**: Web版は`<select>`タグ、モバイル版はセグメントボタン

##### タグ選択（チップ形式）
```blade
<!-- 未選択状態 -->
class="task-tag-chip inline-flex items-center px-3 py-1.5 rounded-lg 
       cursor-pointer transition 
       bg-gray-100 text-gray-700 hover:bg-gray-200"

<!-- 選択状態（JavaScript動的追加） -->
class="bg-[#59B9C6] text-white"
```

**特徴**:
- **rounded-lg**: 中程度の角丸（8px）
- **hover:bg-gray-200**: ホバー時に背景色変更
- **transition**: スムーズな状態遷移

#### 1-4. ボタン

##### プライマリボタン（AIで分解、受け入れる）
```blade
class="inline-flex justify-center items-center px-5 py-2 
       border border-transparent text-sm font-semibold rounded-lg 
       text-white 
       bg-gradient-to-r from-[#59B9C6] to-blue-600 
       hover:from-[#4AA0AB] hover:to-blue-700 
       shadow-lg hover:shadow-xl 
       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#59B9C6] 
       transition 
       disabled:opacity-50 disabled:cursor-not-allowed"
```

**特徴**:
- **bg-gradient-to-r**: プライマリカラーからブルーへのグラデーション
- **shadow-lg**: 深い影（ボタンを浮かせる）
- **hover:shadow-xl**: ホバー時にさらに深い影
- **hover:from-[#4AA0AB]**: ホバー時にグラデーション開始色を暗く
- **focus:ring-2 focus:ring-offset-2**: フォーカス時にリング表示

##### セカンダリボタン（登録）
```blade
class="inline-flex justify-center items-center px-5 py-2 
       border-2 border-[#59B9C6] 
       text-sm font-semibold rounded-lg 
       text-[#59B9C6] bg-white dark:bg-gray-800 
       hover:bg-[#59B9C6]/10 dark:hover:bg-[#59B9C6]/20 
       transition 
       disabled:opacity-50 disabled:cursor-not-allowed"
```

**特徴**:
- **border-2**: 太いボーダー（2px）
- **hover:bg-[#59B9C6]/10**: ホバー時に半透明背景

##### 再提案ボタン（オレンジグラデーション）
```blade
class="inline-flex justify-center items-center px-5 py-2 
       border border-transparent text-sm font-semibold rounded-lg 
       text-white 
       bg-gradient-to-r from-yellow-500 to-orange-500 
       hover:from-yellow-600 hover:to-orange-600 
       shadow-lg hover:shadow-xl 
       transition"
```

**特徴**:
- **from-yellow-500 to-orange-500**: イエローからオレンジへのグラデーション

#### 1-5. ローディングオーバーレイ
```blade
class="absolute inset-0 
       bg-white/95 dark:bg-gray-900/95 
       backdrop-blur-sm 
       items-center justify-center z-10 rounded-2xl hidden"
```

**特徴**:
- **backdrop-blur-sm**: ぼかし効果
- **bg-white/95**: 半透明白背景（95%不透明度）

#### 1-6. AI提案レビュー画面
```blade
<!-- タグ表示カード -->
class="bg-gradient-to-br from-[#59B9C6]/10 to-blue-50 
       dark:from-[#59B9C6]/20 dark:to-blue-900/20 
       p-4 rounded-lg mb-4 border border-[#59B9C6]/20"

<!-- 提案タスク数 -->
class="text-[#59B9C6]"

<!-- タグバッジ -->
class="inline-block px-2 py-1 bg-[#59B9C6] text-white rounded text-xs ml-2"
```

**特徴**:
- **bg-gradient-to-br**: 斜めグラデーション背景
- **rounded-lg**: 中程度の角丸

---

### 2. グループタスク作成モーダル（modal-group-task.blade.php）

#### 2-1. ヘッダー（パープルテーマ）

```blade
<!-- ヘッダー背景 -->
class="px-6 py-4 border-b border-purple-200/50 dark:border-purple-700/50 
       flex justify-between items-center shrink-0 
       bg-gradient-to-r from-purple-50 to-pink-50 
       dark:from-purple-900/20 dark:to-pink-900/20"

<!-- アイコン背景 -->
class="w-10 h-10 rounded-xl 
       bg-gradient-to-br from-purple-600 to-pink-600 
       flex items-center justify-center shadow-lg"

<!-- タイトル -->
class="text-lg font-bold 
       bg-gradient-to-r from-purple-600 to-pink-600 
       bg-clip-text text-transparent"
```

**特徴**:
- **purple → pink グラデーション**: グループタスク専用カラー

#### 2-2. チェックボックスカード（画像必須、承認必須）

```blade
<!-- 画像必須（パープルテーマ） -->
class="bg-gradient-to-br from-purple-50 to-pink-50 
       dark:from-purple-900/20 dark:to-pink-900/20 
       p-4 rounded-xl border border-purple-200/50 dark:border-purple-700/50"

<!-- 承認必須（アンバーテーマ） -->
class="bg-gradient-to-br from-amber-50 to-orange-50 
       dark:from-amber-900/20 dark:to-orange-900/20 
       p-4 rounded-xl border border-amber-200/50 dark:border-amber-700/50"
```

**特徴**:
- **rounded-xl**: 大きめの角丸（12px）
- **機能別カラーリング**: 画像=パープル、承認=アンバー

#### 2-3. タスク作成方法選択（新規 / テンプレート）

```blade
class="group-task-chip inline-flex items-center justify-center 
       px-4 py-2.5 rounded-lg cursor-pointer transition border-2"

<!-- 選択状態はJavaScript動的追加 -->
```

#### 2-4. テンプレートプレビュー

```blade
class="bg-gradient-to-br from-purple-50 to-pink-50 
       dark:from-purple-900/20 dark:to-pink-900/20 
       p-4 rounded-lg border border-purple-200/50 dark:border-purple-700/50"
```

#### 2-5. フッターボタン

```blade
<!-- キャンセルボタン -->
class="inline-flex justify-center items-center px-5 py-2 
       border-2 border-purple-300 dark:border-purple-600 
       text-sm font-semibold rounded-lg 
       text-purple-700 dark:text-purple-300 
       bg-white dark:bg-gray-800 
       hover:bg-purple-50 dark:hover:bg-purple-900/30 
       transition"

<!-- 登録ボタン -->
class="inline-flex justify-center items-center px-5 py-2 
       border border-transparent text-sm font-semibold rounded-lg 
       text-white 
       bg-gradient-to-r from-purple-600 to-pink-600 
       hover:from-purple-700 hover:to-pink-700 
       shadow-lg hover:shadow-xl 
       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 
       transition"
```

---

## モバイル版現状スタイル

### 基本構造

```tsx
container: {
  flex: 1,
  backgroundColor: '#F9FAFB', // Web版との違い: Web版は#FFFFFF
}

header: {
  flexDirection: 'row',
  justifyContent: 'space-between',
  alignItems: 'center',
  paddingHorizontal: getSpacing(16, width),
  paddingVertical: getSpacing(16, width),
  backgroundColor: '#FFFFFF',
  borderBottomWidth: 1,
  borderBottomColor: '#E5E7EB', // Web版との違い: グラデーションなし
}
```

### フォーム要素

```tsx
// タイトル・説明入力
input: {
  backgroundColor: '#FFFFFF',
  borderWidth: 1,
  borderColor: '#D1D5DB', // Web版との違い: #59B9C6/30なし
  borderRadius: getBorderRadius(8, width),
  paddingHorizontal: getSpacing(12, width),
  paddingVertical: getSpacing(10, width),
  fontSize: getFontSize(16, width, theme),
  color: '#111827',
  // Web版との違い: focus:ring-2なし（実装不可）
}

// スパン選択（セグメントボタン）
segmentButton: {
  flex: 1,
  paddingVertical: getSpacing(10, width),
  paddingHorizontal: getSpacing(12, width),
  borderRadius: getBorderRadius(8, width),
  backgroundColor: '#F3F4F6', // Web版との違い: グラデーションなし
  alignItems: 'center',
  borderWidth: 1,
  borderColor: '#E5E7EB',
}

segmentButtonActive: {
  backgroundColor: '#4F46E5', // Web版との違い: #59B9C6なし
  borderColor: '#4F46E5',
}
```

### ボタン

```tsx
// AIタスク分解ボタン
decomposeButton: {
  backgroundColor: '#FFFFFF',
  borderWidth: 2,
  borderColor: '#4F46E5', // Web版との違い: グラデーションなし
  borderRadius: getBorderRadius(8, width),
  paddingVertical: getSpacing(14, width),
  alignItems: 'center',
  marginTop: getSpacing(8, width),
  // Web版との違い: shadow-lgなし
}

// 作成ボタン
createButton: {
  backgroundColor: '#4F46E5', // Web版との違い: グラデーションなし
  borderRadius: getBorderRadius(8, width),
  paddingVertical: getSpacing(14, width),
  alignItems: 'center',
  marginTop: getSpacing(12, width),
  marginBottom: getSpacing(40, width),
  // Web版との違い: shadow-lgなし
}
```

### タグ

```tsx
tagChip: {
  flexDirection: 'row',
  alignItems: 'center',
  paddingHorizontal: getSpacing(12, width),
  paddingVertical: getSpacing(8, width),
  borderRadius: getBorderRadius(16, width),
  backgroundColor: '#F3F4F6', // Web版との違い: #gray-100なし
  borderWidth: 1,
  borderColor: '#E5E7EB',
}

tagChipSelected: {
  backgroundColor: '#4F46E5', // Web版との違い: #59B9C6なし
  borderColor: '#4F46E5',
}
```

---

## 差異分析・統一が必要な項目リスト

### 優先度A（高）: 視覚的に大きな差異、ユーザー体験への影響大

| # | 項目 | Web版スタイル | モバイル版現状 | 実装方針 |
|---|------|--------------|--------------|---------|
| **A-1** | **ヘッダータイトルグラデーション** | `bg-gradient-to-r from-[#59B9C6] to-blue-600 bg-clip-text text-transparent` | `color: '#111827'`（単色） | **MaskedView + LinearGradient**で実装（TaskDetailScreen.tsxと同じパターン） |
| **A-2** | **AIボタングラデーション** | `bg-gradient-to-r from-[#59B9C6] to-blue-600` + `shadow-lg` | `backgroundColor: '#FFFFFF'` + `borderColor: '#4F46E5'` | **LinearGradient**で背景実装、`getShadow()`でシャドウ追加 |
| **A-3** | **作成ボタングラデーション** | `bg-gradient-to-r from-[#59B9C6] to-blue-600` + `shadow-lg hover:shadow-xl` | `backgroundColor: '#4F46E5'`（単色） | **LinearGradient**で背景実装、`getShadow()`でシャドウ追加 |
| **A-4** | **ヘッダーアイコン背景グラデーション** | `bg-gradient-to-br from-[#59B9C6] to-blue-600 shadow-lg` | なし（アイコンのみ） | ヘッダーに**LinearGradient**アイコン背景追加 |
| **A-5** | **タグ選択時カラー変更** | `bg-[#59B9C6] text-white` | `backgroundColor: '#4F46E5'`（インディゴ） | `#59B9C6`（プライマリカラー）に統一 |
| **A-6** | **グループタスクヘッダー（パープル）** | `bg-gradient-to-br from-purple-600 to-pink-600` | 未実装（通常タスクと同じ） | グループタスク判定時に**パープルグラデーション**適用 |
| **A-7** | **承認/画像必須カード背景** | `bg-gradient-to-br from-amber-50 to-orange-50` (承認) <br> `from-purple-50 to-pink-50` (画像) | `backgroundColor: '#F9FAFB'`（単色） | 機能別グラデーション背景追加 |

### 優先度B（中）: デザイン一貫性の向上

| # | 項目 | Web版スタイル | モバイル版現状 | 実装方針 |
|---|------|--------------|--------------|---------|
| **B-1** | **フォーム要素ボーダーカラー** | `border-[#59B9C6]/30` | `borderColor: '#D1D5DB'`（グレー） | プライマリカラー半透明に変更 |
| **B-2** | **スパンセグメント選択色** | `<select>`タグ（UI異なる） | `backgroundColor: '#4F46E5'`（インディゴ） | `#59B9C6`に統一（セグメントボタンは維持） |
| **B-3** | **角丸サイズ統一** | `rounded-2xl` (16px) モーダル<br>`rounded-xl` (12px) カード<br>`rounded-lg` (8px) フォーム | すべて`getBorderRadius(8, width)`（8px） | モーダル相当なし、カード系を`12px`に拡大検討 |
| **B-4** | **テンプレートプレビュー背景** | `bg-gradient-to-br from-purple-50 to-pink-50` | `backgroundColor: '#F0F9FF'`（ブルー） | パープルグラデーション適用 |
| **B-5** | **タグ検索バーボーダー** | `border-[#59B9C6]/30` | `borderColor: '#D1D5DB'` | プライマリカラー半透明に変更 |

### 優先度C（低）: 細部の調整

| # | 項目 | Web版スタイル | モバイル版現状 | 実装方針 |
|---|------|--------------|--------------|---------|
| **C-1** | **ボタンホバー効果** | `hover:from-[#4AA0AB] hover:to-blue-700`<br>`hover:shadow-xl` | 実装不可（React Native） | スキップ（モバイルはタップアニメーション優先） |
| **C-2** | **フォーカスリング** | `focus:ring-2 focus:ring-[#59B9C6]` | 実装不可（React Native） | スキップ（モバイルは不要） |
| **C-3** | **backdrop-blur** | `backdrop-blur-sm`（オーバーレイ） | 実装不可（React Native） | スキップ（モバイルは半透明背景のみ） |
| **C-4** | **transition duration** | `transition-all duration-300` | 実装済み（Animated API） | 現状維持 |

---

## 実装推奨順序（優先度A項目）

### Phase 1: ヘッダー・タイトル（A-1, A-4, A-6）

1. **ヘッダータイトルグラデーション**（A-1）
   - TaskDetailScreen.tsxの`MaskedView + LinearGradient`パターンを流用
   - 通常タスク: `#59B9C6 → blue-600`
   - グループタスク: `purple-600 → pink-600`

2. **ヘッダーアイコン背景**（A-4）
   - 左側にLinearGradient背景の円形アイコン追加
   - SVGアイコン（+マーク）を白色で表示

3. **グループタスクヘッダー判定**（A-6）
   - `isGroupTask`フラグでヘッダーカラー切り替え

### Phase 2: ボタングラデーション（A-2, A-3）

4. **AIタスク分解ボタン**（A-2）
   - `LinearGradient` 背景: `#59B9C6 → blue-600`
   - `getShadow(4)` でシャドウ追加
   - ボタンテキスト: 白色 + 絵文字🤖

5. **作成ボタン**（A-3）
   - `LinearGradient` 背景: `#59B9C6 → blue-600`
   - `getShadow(4)` でシャドウ追加

### Phase 3: カラー統一（A-5, A-7）

6. **タグ選択時カラー**（A-5）
   - `tagChipSelected`: `backgroundColor: '#59B9C6'`に変更

7. **承認/画像必須カード背景**（A-7）
   - 承認必須: `LinearGradient from-amber-50 to-orange-50`
   - 画像必須: `LinearGradient from-purple-50 to-pink-50`

---

## 実装時の注意事項

### 1. グラデーション実装パターン

**TaskDetailScreen.tsxの成功パターン**を踏襲:

```tsx
// タイトルグラデーション
<MaskedView
  maskElement={
    <Text style={styles.headerTitle}>
      {theme === 'child' ? 'やることをつくる' : 'タスク作成'}
    </Text>
  }
>
  <LinearGradient
    colors={['#59B9C6', '#3b82f6']} // Web版と同じカラー
    start={{ x: 0, y: 0 }}
    end={{ x: 1, y: 0 }}
    style={{ flex: 1 }}
  >
    <Text style={[styles.headerTitle, { opacity: 0 }]}>
      {theme === 'child' ? 'やることをつくる' : 'タスク作成'}
    </Text>
  </LinearGradient>
</MaskedView>

// ボタングラデーション
<LinearGradient
  colors={['#59B9C6', '#3b82f6']}
  start={{ x: 0, y: 0 }}
  end={{ x: 1, y: 0 }}
  style={styles.createButton}
>
  <Pressable onPress={handleCreate}>
    <Text style={styles.createButtonText}>作成する</Text>
  </Pressable>
</LinearGradient>
```

### 2. シャドウ実装

```tsx
import { getShadow } from '../../utils/responsive';

const styles = StyleSheet.create({
  createButton: {
    ...getShadow(4), // Web版のshadow-lgに相当
    // その他のスタイル
  },
});
```

### 3. グループタスク判定

```tsx
// ヘッダーカラー切り替え
const headerGradientColors = isGroupTask
  ? ['#9333ea', '#ec4899'] // purple-600 → pink-600
  : ['#59B9C6', '#3b82f6']; // プライマリ → blue-600
```

### 4. テスト実行

```bash
cd /home/ktr/mtdev/mobile
npm test -- --testPathPattern=CreateTaskScreen
```

**期待結果**: 既存テスト成功率99.4%維持

---

## 次のステップ

### Step 3: スタイル統一実装（推奨）

1. **優先度A全7項目**を実装
   - Phase 1: ヘッダー・タイトル（A-1, A-4, A-6）
   - Phase 2: ボタングラデーション（A-2, A-3）
   - Phase 3: カラー統一（A-5, A-7）

2. **実装手順**:
   ```bash
   # 1. バックアップ
   cp mobile/src/screens/tasks/CreateTaskScreen.tsx \
      mobile/src/screens/tasks/CreateTaskScreen.tsx.backup
   
   # 2. スタイル統一実装
   # - ヘッダータイトルグラデーション
   # - AIボタン・作成ボタングラデーション
   # - タグ選択時カラー変更
   # - グループタスクヘッダー
   # - 承認/画像必須カード背景
   
   # 3. テスト実行
   cd mobile
   npm test -- --testPathPattern=CreateTaskScreen
   
   # 4. 実機確認（iOS/Android）
   npm run ios
   npm run android
   ```

3. **成功基準**:
   - テスト成功率99.4%維持
   - Web版と視覚的に統一（グラデーション、カラー、シャドウ）
   - 実機で動作確認（iOS/Android）

### Step 4: テスト・動作確認

1. ユニットテスト実行
2. 実機テスト（iOS/Android）
3. Week 1-3完了レポート作成

---

## 参考資料

- **計画書**: `docs/plans/phase2-b8-web-style-alignment-plan.md`
- **TaskDetailScreen.tsx完了レポート**: `docs/reports/2025-01-XX-week1-2-task-detail-screen-completion-report.md`
- **Web版スタイルガイド**: Tailwind CSS 3（`resources/views/dashboard/modal-*.blade.php`）
