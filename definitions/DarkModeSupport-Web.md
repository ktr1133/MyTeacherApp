# Webアプリ ダークモード完全対応 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-13 | GitHub Copilot | 初版作成: Laravel Blade版ダークモード完全対応計画 |
| 2025-12-13 | GitHub Copilot | Section 3.9追加: トークン購入画面CSS修正（package-list.blade.phpの3箇所） |

---

## 1. 概要

### 1.1 目的

MyTeacherのWebアプリケーション（Laravel Blade）において、**既存のダークモード実装を完全にする**ことで、すべての画面で一貫したユーザー体験を提供する。

### 1.2 現状分析

**Tailwind設定**: `darkMode: 'class'` で実装済み

**対応状況**:
- ✅ **一部対応済み**: メインダッシュボード、タスクカード、ナビゲーション等
- ❌ **未対応**: エラーページ（5ファイル）、管理画面の一部、モーダル、ポータルサイト

**詳細**: サブエージェント調査レポート参照

### 1.3 対応範囲

**対象ファイル数**: 149ファイル（全Bladeファイル）
**未対応箇所**: 約50箇所
**作業時間見積もり**: 約4時間

---

## 2. カラーパレット標準化

### 2.1 統一カラーパレット定義

既存の実装から抽出した標準パターン:

#### 2.1.1 背景色

| 用途 | ライトモード | ダークモード | Tailwindクラス |
|------|------------|------------|---------------|
| ページ背景 | `#F9FAFB` (gray-50) | `#111827` (gray-900) | `bg-gray-50 dark:bg-gray-900` |
| セクション背景 | `#FFFFFF` (white) | `#1F2937` (gray-800) | `bg-white dark:bg-gray-800` |
| カード背景 | `#FFFFFF` (white) | `#1F2937` (gray-800) | `bg-white dark:bg-gray-800` |
| サーフェス（軽い強調） | `#F3F4F6` (gray-100) | `#374151` (gray-700) | `bg-gray-100 dark:bg-gray-700` |
| モーダルオーバーレイ | `rgba(0,0,0,0.5)` | `rgba(0,0,0,0.7)` | `bg-black/50 dark:bg-black/70` |

#### 2.1.2 テキスト色

| 用途 | ライトモード | ダークモード | Tailwindクラス |
|------|------------|------------|---------------|
| 見出し（最強調） | `#111827` (gray-900) | `#FFFFFF` (white) | `text-gray-900 dark:text-white` |
| 本文（強調） | `#1F2937` (gray-800) | `#E5E7EB` (gray-200) | `text-gray-800 dark:text-gray-200` |
| 本文（標準） | `#374151` (gray-700) | `#D1D5DB` (gray-300) | `text-gray-700 dark:text-gray-300` |
| サブテキスト | `#4B5563` (gray-600) | `#9CA3AF` (gray-400) | `text-gray-600 dark:text-gray-400` |
| プレースホルダー | `#6B7280` (gray-500) | `#6B7280` (gray-500) | `text-gray-500 dark:text-gray-500` |

#### 2.1.3 ボーダー・区切り線

| 用途 | ライトモード | ダークモード | Tailwindクラス |
|------|------------|------------|---------------|
| 標準ボーダー | `#E5E7EB` (gray-200) | `#4B5563` (gray-700) | `border-gray-200 dark:border-gray-700` |
| インプット枠 | `#D1D5DB` (gray-300) | `#4B5563` (gray-600) | `border-gray-300 dark:border-gray-600` |
| 半透明ボーダー | `rgba(229,231,235,0.5)` | `rgba(75,85,99,0.5)` | `border-gray-200/50 dark:border-gray-700/50` |

#### 2.1.4 インタラクティブ要素（Hover/Focus）

| 用途 | ライトモード | ダークモード | Tailwindクラス |
|------|------------|------------|---------------|
| Hover背景 | `#F9FAFB` (gray-50) | `#374151` (gray-700) | `hover:bg-gray-50 dark:hover:bg-gray-700` |
| 軽いHover | `#F3F4F6` (gray-100) | `#4B5563` (gray-600) | `hover:bg-gray-100 dark:hover:bg-gray-600` |
| Hoverテキスト | `#374151` (gray-700) | `#D1D5DB` (gray-300) | `hover:text-gray-700 dark:hover:text-gray-300` |

#### 2.1.5 アクセントカラー（変更不要）

以下は**ライト/ダーク共通**で使用（視認性確保のため変更不要）:

| アクセント | カラー | 用途 |
|-----------|-------|------|
| プライマリ | `#59B9C6` (ブランドカラー) | CTAボタン、リンク |
| セカンダリ | `#3B82F6` (blue-500) | 情報表示 |
| 成功 | `#10B981` (green-500) | 完了状態 |
| 警告 | `#F59E0B` (amber-500) | 注意事項 |
| エラー | `#EF4444` (red-500) | エラー表示 |

### 2.2 CSS変数定義（オプション）

将来的なメンテナンス性向上のため、CSS変数化を検討:

```css
/* resources/css/app.css */
@layer base {
  :root {
    /* Light mode (default) */
    --color-background: #F9FAFB;
    --color-surface: #FFFFFF;
    --color-text-primary: #111827;
    --color-text-secondary: #374151;
    --color-border: #E5E7EB;
  }
  
  .dark {
    /* Dark mode */
    --color-background: #111827;
    --color-surface: #1F2937;
    --color-text-primary: #FFFFFF;
    --color-text-secondary: #D1D5DB;
    --color-border: #4B5563;
  }
}
```

**注意**: 現時点では **Tailwindクラスのみを使用** し、CSS変数化は将来的な拡張として検討。

---

## 3. 優先度別対応計画

### Phase 1: 最優先（ユーザー体験への直接影響）

#### 3.1 エラーページ（5ファイル）

**対象ファイル**:
1. `/home/ktr/mtdev/resources/views/errors/404.blade.php`
2. `/home/ktr/mtdev/resources/views/errors/403.blade.php`
3. `/home/ktr/mtdev/resources/views/errors/419.blade.php`
4. `/home/ktr/mtdev/resources/views/errors/500.blade.php`
5. `/home/ktr/mtdev/resources/views/errors/503.blade.php`

**共通パターン**:
```blade
<!-- 修正前 -->
<div class="bg-gray-50">
    <h2 class="text-gray-900">エラーコード</h2>
    <p class="text-gray-600">説明文</p>
    <p class="text-gray-700">サブテキスト</p>
</div>

<!-- 修正後 -->
<div class="bg-gray-50 dark:bg-gray-900">
    <h2 class="text-gray-900 dark:text-white">エラーコード</h2>
    <p class="text-gray-600 dark:text-gray-400">説明文</p>
    <p class="text-gray-700 dark:text-gray-300">サブテキスト</p>
</div>
```

**作業時間**: 30分

#### 3.2 管理画面ログインページ

**対象ファイル**: `/home/ktr/mtdev/resources/views/admin/auth/login.blade.php`

**修正箇所**:
| 行番号 | 現状 | 修正後 |
|-------|------|--------|
| 22 | `bg-white shadow-lg` | `bg-white dark:bg-gray-800 shadow-lg` |
| 36 | `bg-white rounded-lg shadow-2xl` | `bg-white dark:bg-gray-800 rounded-lg shadow-2xl` |
| 67, 86 | `text-gray-700` | `text-gray-700 dark:text-gray-300` |
| 110 | `text-gray-900` | `text-gray-900 dark:text-white` |

**作業時間**: 15分

#### 3.2a ハンバーガーメニューアイコン（重要）

**対象ファイル**: `/home/ktr/mtdev/resources/views/layouts/navigation.blade.php`

**問題**: ダークモード時にハンバーガーメニューアイコンの色が暗すぎて視認困難

**修正箇所**:
| 行番号 | 現状 | 修正後 |
|-------|------|--------|
| 49 | `text-gray-400 dark:text-gray-500` | `text-gray-500 dark:text-gray-400` |
| 49 | `hover:text-gray-500 dark:hover:text-gray-400` | `hover:text-gray-700 dark:hover:text-gray-300` |

**完全な修正例**:
```blade
<!-- 修正前 -->
<button class="... text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 ...">

<!-- 修正後 -->
<button class="... text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 ...">
```

**作業時間**: 5分

#### 3.3 管理画面ダッシュボード

**対象ファイル**: `/home/ktr/mtdev/resources/views/admin/dashboard.blade.php`

**修正箇所**:
| 行番号 | 現状 | 修正後 |
|-------|------|--------|
| 51, 80, 105 | `bg-white border border-gray-200` | `bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700` |
| 28, 53, 82, 107, 136 | `text-gray-900` | `text-gray-900 dark:text-white` |

**作業時間**: 20分

---

### Phase 2: 重要（頻繁に使用される画面）

#### 3.4 モーダルダイアログ

**対象ファイル**: `/home/ktr/mtdev/resources/views/dashboard/modal-group-task-detail.blade.php`

**修正箇所**:
| 行番号 | 現状 | 修正後 |
|-------|------|--------|
| 9, 25 | `text-gray-800` | `text-gray-800 dark:text-white` |
| 28 | `text-gray-700` | `text-gray-700 dark:text-gray-300` |
| 157 | `border-t bg-white` | `border-t dark:border-gray-700 bg-white dark:bg-gray-800` |

**作業時間**: 15分

#### 3.5 ポータルトップページ

**対象ファイル**: `/home/ktr/mtdev/resources/views/portal/index.blade.php`

**修正箇所**:
| 行番号 | 現状 | 修正後 |
|-------|------|--------|
| 151 | `bg-gray-100 text-gray-800` (動的生成) | `bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300` |
| 200 | `bg-gray-100 text-gray-800` | `bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300` |
| 268 | `bg-white text-[#59B9C6]` | `bg-white dark:bg-gray-800 text-[#59B9C6]` |

**作業時間**: 20分

---

### Phase 3: 補完（UX改善）

#### 3.6 ウェルカムページ

**対象ファイル**: `/home/ktr/mtdev/resources/views/welcome.blade.php`

**修正箇所**:
| 行番号 | 現状 | 修正後 |
|-------|------|--------|
| 533 | `bg-white text-[#59B9C6] hover:bg-gray-50` | `bg-white dark:bg-gray-800 text-[#59B9C6] hover:bg-gray-50 dark:hover:bg-gray-700` |

**作業時間**: 10分

#### 3.7 プロフィール設定

**対象ファイル**: `/home/ktr/mtdev/resources/views/profile/edit.blade.php`

**修正箇所**:
| 行番号 | 現状 | 修正後 |
|-------|------|--------|
| 67 | `bg-white p-4 rounded-lg` | `bg-white dark:bg-gray-800 p-4 rounded-lg` |

**作業時間**: 5分

#### 3.8 その他ポータルページ

**対象ファイル**:
- `/home/ktr/mtdev/resources/views/portal/features/pricing.blade.php`
- `/home/ktr/mtdev/resources/views/portal/guide/*.blade.php`

**作業時間**: 30分

#### 3.9 トークン購入画面のCSS修正

**対象ファイル**: `/home/ktr/mtdev/resources/css/tokens/purchase.css`

**問題**: パッケージカードのテキスト色がダークモード時に適切に上書きされていない（black/dark grayのままで視認困難）

**修正箇所**:

| 行番号 | クラス名 | 現状 | 修正後 |
|-------|---------|------|--------|
| 227 | `.token-label` | `color: #6b7280;` | `color: #6b7280;`<br>`dark .token-label { color: #d1d5db; }` |
| 241 | `.price-label` | `color: #9ca3af;` | `color: #9ca3af;`<br>`.dark .price-label { color: #d1d5db; }` |
| 245 | `.package-description` | `color: #6b7280;` | `color: #6b7280;`<br>`.dark .package-description { color: #d1d5db; }` |

**完全な修正例**:

```css
/* Line 227 */
.token-label {
    font-size: 1rem;
    color: #6b7280;
}

.dark .token-label {
    color: #d1d5db;  /* gray-300 */
}

/* Line 241 */
.price-label {
    font-size: 0.875rem;
    color: #9ca3af;
}

.dark .price-label {
    color: #d1d5db;  /* gray-300 */
}

/* Line 245 */
.package-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
    text-align: center;
}

.dark .package-description {
    color: #d1d5db;  /* gray-300 */
}
```

**既に対応済みの箇所（変更不要）**:
- `.token-gradient-bg` - Line 13でダークモード対応済み
- `.token-header-blur` - Line 34でダークモード対応済み
- `.tab-button` - Line 73でダークモード対応済み
- `.balance-card` - Line 127でダークモード対応済み
- `.package-card` - Line 183でダークモード対応済み（背景・ボーダー）
- `.pending-request-card` - Line 287でダークモード対応済み
- `.approval-notice` - Line 345でダークモード対応済み

**作業時間**: 10分

---

## 4. 実装ガイドライン

### 4.1 基本原則

1. **既存のパターンを尊重**: プロジェクト内で既に使用されている `dark:` クラスの組み合わせを踏襲
2. **最小限の変更**: 既存のレイアウトやスタイルを変更せず、`dark:` クラスの追加のみ
3. **アクセントカラーは変更不要**: `text-[#59B9C6]`, `bg-purple-600` 等は視認性が確保されているため、ダークモード用クラス不要

### 4.2 クラス追加パターン

| 現状クラス | 追加する `dark:` クラス | 完全な記述 |
|-----------|----------------------|-----------|
| `bg-white` | `dark:bg-gray-800` | `bg-white dark:bg-gray-800` |
| `bg-gray-50` | `dark:bg-gray-900` | `bg-gray-50 dark:bg-gray-900` |
| `bg-gray-100` | `dark:bg-gray-700` | `bg-gray-100 dark:bg-gray-700` |
| `text-gray-900` | `dark:text-white` | `text-gray-900 dark:text-white` |
| `text-gray-800` | `dark:text-gray-200` | `text-gray-800 dark:text-gray-200` |
| `text-gray-700` | `dark:text-gray-300` | `text-gray-700 dark:text-gray-300` |
| `text-gray-600` | `dark:text-gray-400` | `text-gray-600 dark:text-gray-400` |
| `border-gray-200` | `dark:border-gray-700` | `border-gray-200 dark:border-gray-700` |
| `border-gray-300` | `dark:border-gray-600` | `border-gray-300 dark:border-gray-600` |
| `hover:bg-gray-50` | `dark:hover:bg-gray-700` | `hover:bg-gray-50 dark:hover:bg-gray-700` |
| `hover:bg-gray-100` | `dark:hover:bg-gray-600` | `hover:bg-gray-100 dark:hover:bg-gray-600` |

### 4.3 動的クラス生成への対応

Blade変数で動的にクラスを生成している箇所:

**例**: `/home/ktr/mtdev/resources/views/portal/index.blade.php` Line 151

```blade
<!-- 修正前 -->
@php
    $statusConfig = [
        'scheduled' => ['label' => '予定', 'class' => 'bg-blue-100 text-blue-800'],
        'in_progress' => ['label' => '作業中', 'class' => 'bg-yellow-100 text-yellow-800'],
        'completed' => ['label' => '完了', 'class' => 'bg-green-100 text-green-800'],
    ];
    $status = $statusConfig[$maintenance->status] ?? ['label' => '不明', 'class' => 'bg-gray-100 text-gray-800'];
@endphp

<!-- 修正後 -->
@php
    $statusConfig = [
        'scheduled' => ['label' => '予定', 'class' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'],
        'in_progress' => ['label' => '作業中', 'class' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'],
        'completed' => ['label' => '完了', 'class' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'],
    ];
    $status = $statusConfig[$maintenance->status] ?? ['label' => '不明', 'class' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'];
@endphp
```

### 4.4 注意事項

1. **グラデーション**: `bg-gradient-to-r from-blue-600 to-purple-600` は基本的に変更不要（視認性確保済み）
2. **シャドウ**: `shadow-lg` は自動的にダークモードで調整されるため、変更不要
3. **トランジション**: `transition` や `duration-200` は変更不要
4. **特定のブランドカラー**: `text-[#59B9C6]`, `bg-[#9DD9C0]` 等は変更不要

---

## 5. テスト・検証

### 5.1 ダークモード切り替え方法

**方法1: ブラウザ開発者ツール（Chrome/Edge）**

```javascript
// ダークモード有効化
localStorage.setItem('theme', 'dark');
document.documentElement.classList.add('dark');

// ライトモード有効化
localStorage.setItem('theme', 'light');
document.documentElement.classList.remove('dark');
```

**方法2: Webアプリ内の切り替えUI**

現在のWebアプリには切り替えUIが実装されている場合、それを使用。

### 5.2 検証項目

| # | 検証内容 | 期待結果 |
|---|---------|---------|
| 1 | エラーページ（404, 500等） | 背景が暗くなり、テキストが白く表示される |
| 2 | 管理画面ログイン | フォーム背景が暗くなり、入力欄が視認可能 |
| 3 | 管理画面ダッシュボード | カードが暗い背景、テキストが白く表示 |
| 4 | モーダルダイアログ | モーダル背景が暗くなり、テキストが視認可能 |
| 5 | ポータルトップ | CTAボタンが暗い背景で視認可能 |
| 6 | テキストのコントラスト | すべてのテキストがWCAG AA基準を満たす |
| 7 | ホバー効果 | ライト/ダーク両方で適切に動作 |
| 8 | フォーカス状態 | 入力欄のフォーカスが視認可能 |

### 5.3 コントラスト比チェック

**推奨ツール**: 
- Chrome DevTools の Lighthouse（アクセシビリティ監査）
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)

**最低基準**: WCAG AA（コントラスト比 4.5:1 以上）

---

## 6. 実装スケジュール

| Phase | 作業内容 | 所要時間 | 担当者 | 期限 |
|-------|---------|---------|-------|------|
| Phase 1 | エラーページ（5ファイル） | 30分 | GitHub Copilot | 即座 |
| Phase 1 | 管理画面ログイン | 15分 | GitHub Copilot | 即座 |
| Phase 1 | ハンバーガーメニュー | 5分 | GitHub Copilot | 即座 |
| Phase 1 | 管理画面ダッシュボード | 20分 | GitHub Copilot | 即座 |
| Phase 2 | モーダルダイアログ | 15分 | GitHub Copilot | 当日中 |
| Phase 2 | ポータルトップ | 20分 | GitHub Copilot | 当日中 |
| Phase 3 | ウェルカムページ | 10分 | GitHub Copilot | 当日中 |
| Phase 3 | プロフィール設定 | 5分 | GitHub Copilot | 当日中 |
| Phase 3 | その他ポータル | 30分 | GitHub Copilot | 当日中 |
| Phase 3 | トークン購入CSS | 10分 | GitHub Copilot | 当日中 |
| 検証 | 全画面動作確認 | 30分 | GitHub Copilot | 当日中 |
| **合計** | | **約3時間20分** | | |

---

## 7. モバイルアプリへの展開

**Web版完了後**に、以下の手順でモバイル版に展開:

1. **カラーパレット最終化**: Web版の実装を基にReact Native用のカラーパレット(`colors.ts`)を作成
2. **モバイル要件定義更新**: `/home/ktr/mtdev/definitions/mobile/DarkModeSupport.md` の「付録A」を更新
3. **実装開始**: Phase 1（ColorSchemeContext作成）から順次実装

---

## 8. 完了後の成果物

### 8.1 ドキュメント

- [x] 本要件定義書（`definitions/DarkModeSupport-Web.md`）
- [ ] 実装完了レポート（`docs/reports/darkmode-web-completion-report.md`）

### 8.2 コード変更

- [ ] Bladeファイル約15ファイルの `dark:` クラス追加
- [ ] 動的クラス生成箇所の修正（3箇所）

### 8.3 テストエビデンス

- [ ] ライト/ダーク両モードのスクリーンショット（主要画面）
- [ ] コントラスト比チェック結果

---

## 9. 関連ドキュメント

| ドキュメント名 | パス | 用途 |
|--------------|------|------|
| プロジェクト開発規則 | `/home/ktr/mtdev/.github/copilot-instructions.md` | コーディング規約 |
| Tailwind設定 | `/home/ktr/mtdev/tailwind.config.js` | ダークモード設定 |
| モバイル版要件定義 | `/home/ktr/mtdev/definitions/mobile/DarkModeSupport.md` | モバイル版実装計画 |
| 調査レポート | （サブエージェント生成） | 未対応箇所詳細 |

---

## 付録A: チェックリスト

### Phase 1実装チェックリスト

- [x] `errors/404.blade.php` 修正完了
- [x] `errors/403.blade.php` 修正完了
- [x] `errors/419.blade.php` 修正完了
- [x] `errors/500.blade.php` 修正完了
- [x] `errors/503.blade.php` 修正完了
- [x] `admin/auth/login.blade.php` 修正完了
- [x] `layouts/navigation.blade.php` ハンバーガーメニュー修正完了
- [x] `admin/dashboard.blade.php` 修正完了
- [ ] Phase 1動作確認完了

### Phase 2実装チェックリスト

- [x] `dashboard/modal-group-task-detail.blade.php` 修正完了
- [x] `portal/index.blade.php` 修正完了
- [ ] Phase 2動作確認完了

### Phase 3実装チェックリスト

- [x] `welcome.blade.php` 修正完了
- [ ] `profile/edit.blade.php` 修正完了（要件定義の該当箇所が現在のコードに存在せず）
- [ ] その他ポータルページ修正完了
- [x] `resources/css/tokens/purchase.css` 修正完了（3箇所）
- [ ] トークン購入画面のパッケージカード視認性確認
- [ ] Phase 3動作確認完了

### 最終検証チェックリスト

- [ ] 全エラーページでダークモード動作確認
- [ ] 管理画面でダークモード動作確認
- [ ] ユーザー画面でダークモード動作確認
- [ ] コントラスト比WCAG AA基準クリア
- [ ] ホバー・フォーカス状態動作確認
- [ ] 実装完了レポート作成
- [ ] モバイル版要件定義更新

---

**以上**
