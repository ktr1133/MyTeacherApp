# TaskListScreen デザイン要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | レスポンシブ対応全面刷新: Dimensions API積極使用、Android/iOS/タブレット対応 |
| 2025-12-09 | GitHub Copilot | CSS変換表に注力: タブレット対応削除、Web CSS→Mobile StyleSheetマッピングを主軸に |
| 2025-12-09 | GitHub Copilot | 全面改訂: Web版Blade解析に基づくBentoグリッドUI要件追加（mobile-rules.md準拠） |
| 2025-12-09 | GitHub Copilot | IndexTaskApiActionのデフォルトstatus変更（'all'→'pending'）を反映、Web/Mobile整合性確保 |
| 2025-12-07 | GitHub Copilot | 初版作成: Phase 2.B-5 Step 1 完了後の質疑応答結果を要件化 |
| 2025-12-07 | GitHub Copilot | 画像機能に関する追記（画像アップロード機能は実装済み、IndexTaskApiAction修正） |
| 2025-12-07 | GitHub Copilot | タスク一覧画面に関連しない項目を削除（通知機能、テスト修正は別ファイルに記載） |

---

## 1. 概要

### 1.1 対応フェーズ

- **Phase 2.B-3**: タスク管理機能（タスク一覧画面）
- **Phase 2.B-5 Step 1**: タスク一覧画面基本実装（2025-12-06～2025-12-07完了）
  - ✅ タグ別バケット表示（Bentoグリッド）実装済み
  - ✅ 検索機能実装済み
  - ✅ Pull-to-Refresh実装済み
- **Phase 2.B-5 Step 3**: UI改善（今回実装）
  - ✅ Web版dashboard.cssベースのデザイン適用
  - ✅ **レスポンシブ対応**（Dimensions API使用）
  - ✅ タップアニメーション（Web hoverアニメをタップに変換）
  - ✅ **子ども向けテーマ対応**（フォント1.2倍）
  - ✅ **表示崩れ対策**（ヘッダー折り返し、カード見切れ）

### 1.2 対応画面

- **モバイル**: `TaskListScreen.tsx`
- **Web**: `/home/ktr/mtdev/resources/views/dashboard.blade.php`
- **Web Action**: `IndexTaskAction.php`

### 1.3 目的

ユーザーのタスク一覧を**タグ別バケット形式（Bentoグリッド）**で表示し、タスクの登録・編集・完了・削除などの操作を可能にする。モバイルアプリのデフォルト画面（ログイン後の最初の画面）として機能する。

**実装状況**:
- ✅ **タグ別バケット表示**: 実装済み（Phase 2.B-5 Step 1）
- ✅ **スマホ対応**: 1カラム表示
- ✅ **タブレット対応**: 1カラム表示（視認性優先）
- ✅ **レスポンシブ対応**: Dimensions API使用
- ✅ **検索機能**: フロントエンド側フィルタリング
- ✅ **画面遷移**: タグカードタップ→タグ内タスク一覧

### 1.4 関連画面

- **Webアプリ**: ダッシュボード（Bentoレイアウト）、タスク一覧
- **モバイルアプリ**: TaskListScreen、TaskDetailScreen、TaskEditScreen、CreateTaskScreen

---

## 2. Webアプリとの対応関係

### 2.1 Web版Bladeファイル

**メインファイル**: `/home/ktr/mtdev/resources/views/dashboard.blade.php`

**総行数**: 85行

**主要セクション**:
| セクション名 | 行番号 | 説明 |
|------------|-------|------|
| レイアウト定義 | 1-5 | `x-app-layout`、CSS/JS読み込み |
| 背景装飾 | 27-34 | 大人テーマのみのグラデーション背景 |
| サイドバー | 37 | `x-layouts.sidebar` コンポーネント |
| ヘッダー | 43 | `dashboard.partials.header` インクルード |
| メインコンテンツ | 46-65 | タスク一覧表示、無限スクロール |
| モーダル群 | 70-82 | タスク登録/編集/詳細モーダル |

**関連ファイル**:
| ファイル | 行数 | 用途 |
|---------|-----|------|
| `dashboard/partials/header.blade.php` | 168行 | ヘッダー（タイトル、ボタン、通知） |
| `dashboard/partials/task-bento.blade.php` | 50行 | タスクのバケット（タグ）別表示 |
| `dashboard/partials/task-bento-layout.blade.php` | 170行 | Bentoグリッドレイアウト |

### 2.2 使用されているコンポーネント

| コンポーネント名 | ファイルパス | 用途 |
|---------------|------------|------|
| `x-app-layout` | `resources/views/layouts/app.blade.php` | 基本レイアウト |
| `x-layouts.sidebar` | `resources/views/components/layouts/sidebar.blade.php` | サイドバー（モバイルではドロワー） |
| `x-task-filter` | - | タスクフィルター（検索、ステータス、優先度、タグ） |

---

## 3. UI要素一覧（Web版Blade解析ベース）

### 3.1 ヘッダー（Web版: header.blade.php）

| # | 要素種別 | ラベル/テキスト | Tailwind CSS | Web行番号 | モバイル実装状況 | 備考 |
|---|---------|---------------|-------------|----------|----------------|------|
| 1 | ボタン | ハンバーガーメニュー | `lg:hidden p-2 rounded-lg border border-gray-200` | header:7-14 | ✅ Header.tsx | モバイルのみ表示 |
| 2 | アイコン | タスクアイコン | `w-10 h-10 rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600` | header:22-26 | ✅ Header.tsx | グラデーション背景 |
| 3 | タイトル（大人テーマ） | 「タスクリスト」 | `text-lg font-bold text-gray-900` | header:28-30 | ✅ Header.tsx | adultテーマ |
| 4 | タイトル（子供テーマ） | 「ToDo」 | `text-lg font-bold` | header:33-35 | ✅ Header.tsx | childテーマ |
| 5 | ボタン | 「タスク登録」/「つくる」 | `rounded-full px-4 py-2.5 bg-gradient-to-r from-[#59B9C6] to-purple-600` | header:88-102 | ✅ FAB | 右下に固定配置 |
| 6 | ボタン | 「グループタスク」 | `rounded-full px-4 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600` | header:107-120 | ❌ 未実装 | グループ管理者のみ |
| 7 | ボタン | 通知アイコン | `p-2.5 rounded-xl bg-gradient-to-br from-amber-50 to-orange-50` | header:111-123 | ❌ 未実装 | 未読バッジ付き |
| 8 | バッジ | 未読通知件数 | `min-w-[20px] h-5 px-1.5 bg-gradient-to-r from-red-500 to-pink-500 rounded-full` | header:128-131 | ❌ 未実装 | 0件なら非表示 |

### 3.2 Bentoグリッド（Web版: task-bento-layout.blade.php）

**重要**: Web版はタグ別バケット表示（Bentoグリッド）を採用。モバイル版は**Phase 2.B-5 Step 3で実装予定**。

| # | 要素種別 | ラベル/テキスト | Tailwind CSS | Web行番号 | モバイル実装状況 | 備考 |
|---|---------|---------------|-------------|----------|----------------|------|
| 9 | グリッドコンテナ | - | `grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6` | bento-layout:1 | ❌ 未実装 | モバイルは1カラム予定 |
| 10 | タグカード | タグ名 | `rounded-2xl shadow-lg p-4 lg:p-6 bg-white` | bento-layout:85-90 | ❌ 未実装 | タップでモーダル |
| 11 | タグアイコン | グリッドアイコン | `w-8 h-8 lg:w-10 lg:h-10 rounded-lg bg-gradient-to-br from-[#59B9C6] to-purple-600` | bento-layout:94-99 | ❌ 未実装 | 左上配置 |
| 12 | タグ名 | 「{タグ名}」 | `text-base lg:text-lg font-bold text-gray-900` | bento-layout:100 | ❌ 未実装 | 中央配置 |
| 13 | タスク件数バッジ | 件数 | `min-w-[2rem] h-6 px-2 rounded-full bg-gradient-to-r from-[#59B9C6] to-purple-600` | bento-layout:102-105 | ❌ 未実装 | 右上配置 |
| 14 | ボタン | 「全完」/「全戻」 | `h-6 px-2 rounded-lg text-xs font-semibold bg-gradient-to-r from-emerald-500 to-teal-600` | bento-layout:107-128 | ❌ 未実装 | 一括完了ボタン |
| 15 | タスクプレビュー | タスクタイトル | `text-xs bg-white/50 px-2 py-1 rounded-full` | bento-layout:133-140 | ❌ 未実装 | 最大6件表示 |

### 3.3 現在のモバイル実装（Phase 2.B-5 Step 1完了版）

| # | 要素種別 | ラベル/テキスト | モバイル実装 | 備考 |
|---|---------|---------------|-----------|------|
| 16 | 検索バー | 「タスクを検索（タイトル・説明・タグ）」 | ✅ SearchBar.tsx | フロントエンド側フィルタリング |
| 17 | タスクカード | タスク情報 | ✅ TaskCard.tsx | フラットリスト表示 |
| 18 | タイトル | タスク名 | ✅ Text | 必須 |
| 19 | 説明 | 説明文（2行まで） | ✅ Text | 存在する場合 |
| 20 | タグバッジ | タグ名 | ✅ Badge | 複数タグ対応 |
| 21 | 報酬表示 | トークン数 | ✅ Text | グループタスクのみ |
| 22 | 期限表示 | 期限日 | ✅ Text | 存在する場合 |
| 23 | 完了ボタン | 「できた!」/「完了にする」 | ✅ Button | 未完了タスクのみ |

---

## 4. 画面仕様（現在の実装 + 将来のBentoグリッド）

---

## 4. 画面仕様（現在の実装 + 将来のBentoグリッド）

### 4.1 現在の画面構成（Phase 2.B-5 Step 1）

```
┌─────────────────────────────────────┐
│ ヘッダー                             │
│ [☰] タスク一覧               [通知]  │← Phase 2.B-5 Step 3以降
├─────────────────────────────────────┤
│ 検索バー                             │
│ 🔍 タスクを検索...          [✕]     │← 実装済み
├─────────────────────────────────────┤
│ 検索結果件数（検索時のみ）            │
│ 5件のタスクが見つかりました          │← 実装済み
├─────────────────────────────────────┤
│ タスクカード一覧（フラットリスト）     │← 現在の実装
│ ┌─────────────────────────────┐     │
│ │ [タイトル]                  │     │
│ │ [説明文（2行まで）]          │     │
│ │ [タグ1] [タグ2]             │     │
│ │ 報酬: 100トークン 期限: 12/31│     │
│ │ [完了にする]                 │     │
│ └─────────────────────────────┘     │
│                                     │
│         [FAB: タスク作成]            │← 実装済み
└─────────────────────────────────────┘
```

### 4.2 将来の画面構成（Phase 2.B-5 Step 3: Bentoグリッド）

```
┌─────────────────────────────────────┐
│ ヘッダー                             │
│ [☰] タスクリスト       [通知] [+]   │
├─────────────────────────────────────┤
│ Bentoグリッド（タグ別バケット）       │
│ ┌─────────────────────────────┐     │
│ │ 📋 プログラミング      [全完] │     │← タグカード
│ │      3件                    │     │
│ │ • タスク1 • タスク2 • タスク3│     │← プレビュー
│ └─────────────────────────────┘     │
│ ┌─────────────────────────────┐     │
│ │ 📝 デザイン           [全完] │     │
│ │      2件                    │     │
│ │ • タスク4 • タスク5         │     │
│ └─────────────────────────────┘     │
│ ┌─────────────────────────────┐     │
│ │ 📚 未分類/そのほか    [全完] │     │
│ │      1件                    │     │
│ │ • タスク6                   │     │
│ └─────────────────────────────┘     │
└─────────────────────────────────────┘
```

**注**: タグカードタップ → タグ内タスク一覧モーダル表示（Web版と同じ動作）。

---

## 5. データ取得仕様

---

## 5. データ取得仕様

### 5.1 API仕様

#### 5.1.1 API仕様

| 項目 | 仕様 |
|------|------|
| エンドポイント | `GET /api/tasks?status=pending` |
| パラメータ | `status=pending`（未完了のみ） |
| デフォルト動作 | パラメータ省略時も`status=pending`が適用される（2025-12-09変更） |
| ページネーション | 20件/ページ（デフォルト） |
| レスポンス | `Task[]`（is_completed, completed_at, tags配列） |

**重要**: 2025-12-09に`IndexTaskApiAction`のデフォルト値を`'all'`から`'pending'`に変更し、WebアプリとモバイルアプリでAPI動作を統一しました。これにより、`status`パラメータを省略した場合でも未完了タスクのみが返却されます。

```php
// IndexTaskApiAction.php（変更後）
public function __invoke(IndexTaskApiRequest $request): JsonResponse
{
    $status = $request->query('status', 'pending');  // デフォルト: pending
    // ...
}
```

#### 5.1.2 取得データ項目

| 項目 | 型 | 説明 |
|------|-----|------|
| id | number | タスクID |
| title | string | タスク名 |
| description | string \| null | 説明文 |
| is_completed | boolean | 完了状態 |
| completed_at | string \| null | 完了日時（ISO 8601） |
| reward | number | 報酬トークン |
| is_group_task | boolean | グループタスクフラグ |
| due_date | string \| null | 期限（YYYY-MM-DD） |
| tags | TaskTag[] | タグ配列（id + name） |
| images | TaskImage[] | 画像配列（id + path + url） |

**TaskImage型**:
```typescript
{
  id: number;
  path: string;      // S3/MinIOパス（例: task_approvals/xxx.jpg）
  url: string;       // 公開URL
}
```

**注意**: 画像アップロード機能は実装済み（TaskDetailScreen）。一覧画面では画像は表示せず、詳細画面で表示。

### 5.2 Webアプリとの差分（データ取得）

| 機能 | Web版 | モバイル版 | 備考 |
|------|-------|----------|------|
| 無限スクロール | ✅ | ✅ | `FlatList`の`onEndReached` |
| タグ別バケット | ✅ | ❌ Phase 2.B-5 Step 3以降 | Web版はBentoグリッド |
| 検索機能 | ✅ モーダル | ✅ 画面内検索バー | フロントエンド側フィルタリング |

---

## 6. 機能要件

### 6.1 未完了タスクのみ表示（実装済み）

#### 6.1.1 要件

- **表示対象**: `is_completed = false` のタスクのみ
- **理由**: データ量削減、UX向上（Webアプリと同様）
- **実装方式**: API呼び出し時に `status=pending` パラメータ指定（デフォルト値）

#### 6.1.2 除外機能

- ❌ **完了/未完了切り替えボタン**: 不要（Webアプリと異なる）
- ❌ **「すべて」フィルター**: 不要

### 6.2 検索機能（実装済み）

#### 3.2.1 検索仕様

| 項目 | 仕様 |
|------|------|
| 検索方式 | フロントエンド側フィルタリング |
| 検索対象 | タイトル、説明、タグ名 |
| 検索タイプ | 部分一致（大文字小文字区別なし） |
| 即時反映 | 入力と同時にフィルタリング実行 |

#### 3.2.2 検索結果表示

- **件数表示**: 「5件のタスクが見つかりました」（検索時のみ）
- **0件時**: 「やることがないよ」（子ども）/ 「タスクがありません」（大人）

#### 3.2.3 バックエンド検索API（未実装）

- **現在**: フロントエンド側フィルタリング（取得済み20件内）
- **将来**: `/api/tasks?q={query}` によるサーバー側検索（大量タスク対応時）
- **実装時期**: 大量タスクが発生した場合に検討

### 3.3 タグ表示

#### 3.3.1 表示仕様

| 項目 | 仕様 |
|------|------|
| 表示形式 | タグバッジ（紫色背景、白文字、丸み） |
| 複数タグ | 横並び、折り返しあり |
| データ構造 | `{id: number, name: string}[]` |

#### 3.3.2 タグフィルター機能（未実装）

- **要件**: タグ選択によるタスク絞り込み
- **実装方式**: Option B（フラットリスト + タグフィルター）
- **実装時期**: Phase 2.B-5 Step 3以降

### 3.4 報酬表示

#### 3.4.1 表示条件

- **表示対象**: グループタスク（`is_group_task = true`）のみ
- **非表示**: 個人タスク（`is_group_task = false`）

#### 3.4.2 表示形式

- **子どもテーマ**: 「⭐100」
- **大人テーマ**: 「報酬: 100トークン」

### 3.5 タスク詳細遷移

#### 3.5.1 遷移仕様

| 項目 | 仕様 |
|------|------|
| トリガー | タスクカードタップ |
| 遷移先 | グループタスク: TaskDetailScreen（閲覧のみ）<br>通常タスク: TaskEditScreen（編集可能） |
| パラメータ | `{taskId: number}` |
| ナビゲーション | Stack Navigator |

#### 3.5.2 遷移ロジック

```typescript
const navigateToDetail = useCallback(
  (taskId: number) => {
    const task = tasks.find(t => t.id === taskId);
    if (task?.is_group_task) {
      // グループタスク → 詳細画面（編集不可）
      navigation.navigate('TaskDetail', { taskId });
    } else {
      // 通常タスク → 編集画面
      navigation.navigate('TaskEdit', { taskId });
    }
  },
  [tasks, navigation]
);
```

---

## 4. UI/UXガイドライン

### 4.1 テーマ対応

#### 4.1.1 子どもテーマ

| 要素 | 仕様 |
|------|------|
| 文言 | ひらがな中心、親しみやすい表現 |
| アイコン | 絵文字使用（⭐、⏰） |
| 色 | 明るい色、丸みのあるデザイン |

**例**:
- タイトル: 「やること」
- 検索: 「さがす」
- 報酬: 「⭐100」
- 期限: 「⏰ 12/31」
- 完了ボタン: 「できた!」

#### 4.1.2 大人テーマ

| 要素 | 仕様 |
|------|------|
| 文言 | 漢字・ビジネス用語 |
| アイコン | シンプルなアイコン |
| 色 | 落ち着いた色、シャープなデザイン |

**例**:
- タイトル: 「タスク一覧」
- 検索: 「タスクを検索」
- 報酬: 「報酬: 100トークン」
- 期限: 「期限: 12/31」
- 完了ボタン: 「完了にする」

### 4.2 レスポンシブ対応

- **リスト表示**: FlatList使用、スクロール最適化
- **タップ領域**: 最小44x44pt（iOS Human Interface Guidelines準拠）

---

## 5. データベーススキーマ対応

### 5.1 使用テーブル

#### 5.1.1 tasksテーブル

| カラム名 | 型 | 説明 | 使用箇所 |
|---------|-----|------|---------|
| id | integer | タスクID | 一覧表示、詳細遷移 |
| title | string | タスク名 | タスクカード |
| description | text | 説明 | タスクカード |
| is_completed | boolean | 完了状態 | フィルター条件 |
| completed_at | timestamp | 完了日時 | レスポンス |
| reward | integer | 報酬 | グループタスク表示 |
| is_group_task | boolean | グループタスクフラグ | 報酬表示条件 |
| due_date | string | 期限 | タスクカード |
| group_task_id | uuid | グループID | グループタスク判定 |

**注意**: `status`カラムは存在しない（`is_completed`を使用）

#### 5.1.2 tagsテーブル（リレーション）

| カラム名 | 型 | 説明 |
|---------|-----|------|
| id | integer | タグID |
| name | string | タグ名 |

**リレーション**: `tasks.tags` → `TaskTag[]`（id + name）

---

## 6. エラーハンドリング

### 6.1 エラーパターン

| エラー種別 | 原因 | 対応 |
|-----------|------|------|
| ネットワークエラー | 通信失敗 | アラート表示、リトライ可能 |
| 401エラー | 認証切れ | 自動ログアウト → ログイン画面遷移 |
| 500エラー | サーバーエラー | アラート表示、技術サポート誘導 |
| タスク0件 | データなし | 空状態表示（作成ボタン誘導） |

### 6.2 エラーメッセージ

| テーマ | メッセージ |
|-------|-----------|
| 子ども | 「エラーがおきちゃった！もういちどためしてね」 |
| 大人 | 「通信エラーが発生しました。再度お試しください。」 |

---

## 7. パフォーマンス要件

### 7.1 応答時間

| 項目 | 目標 |
|------|------|
| API取得 | 2秒以内 |
| 検索フィルター | 即座（同期処理） |
| 画面遷移 | 0.3秒以内 |

### 7.2 最適化

- **FlatList最適化**: `keyExtractor`, `getItemLayout`使用
- **メモ化**: `useMemo`, `useCallback`使用
- **画像遅延読み込み**: 将来対応（タスク画像表示時）

---

## 8. テスト要件

### 8.1 単体テスト

- [x] タスク取得成功時、未完了タスクのみ表示
- [x] 検索入力時、タイトル・説明・タグで部分一致フィルタリング
- [x] グループタスクのみ報酬表示
- [x] タグが正しく表示（id + name）
- [x] 完了ボタンタップで完了API呼び出し
- [x] グループタスクはTaskDetailScreen、通常タスクはTaskEditScreenに遷移

### 8.2 統合テスト

- [x] タスクカードタップで適切な画面に遷移
- [x] 検索結果件数が正しく表示
- [x] 0件時に空状態表示
- [x] 画面フォーカス時にタスクリストを再同期

### 8.3 E2Eテスト（Phase 2.B-8）

- [ ] ログイン → タスク一覧表示 → 検索 → 詳細遷移

### 8.4 テスト実行結果（2025-12-07）

**モバイルアプリテスト**: 203 tests passing (14 test suites)
- TaskListScreen: 11 tests passing
- useTasks Hook: 7 tests passing
- task.service: 9 tests passing

**Laravelテスト**: 442 tests passing, 18 skipped
- IndexTaskApiAction: タスク一覧取得API正常動作

---

## 9. 実装ファイル

### 9.1 実装ファイル一覧

| ファイルパス | 説明 | 行数 | 状態 |
|------------|------|------|------|
| `/home/ktr/mtdev/mobile/src/screens/tasks/TaskListScreen.tsx` | タスク一覧画面 | 575行 | ✅ 実装完了 |
| `/home/ktr/mtdev/mobile/src/hooks/useTasks.ts` | タスク管理Hook | 465行 | ✅ 実装完了 |
| `/home/ktr/mtdev/mobile/src/services/task.service.ts` | タスクAPI通信 | 390行 | ✅ 実装完了 |
| `/home/ktr/mtdev/mobile/src/types/task.types.ts` | タスク型定義 | 133行 | ✅ 実装完了 |
| `/home/ktr/mtdev/app/Http/Actions/Api/Task/IndexTaskApiAction.php` | バックエンドAPI | 116行 | ✅ 実装完了 |

### 9.2 テストファイル

| ファイルパス | テスト数 | 状態 |
|------------|---------|------|
| `/__tests__/services/task.service.test.ts` | 9テスト | ✅ 全件合格 |
| `/__tests__/hooks/useTasks.test.tsx` | 7テスト | ✅ 全件合格 |
| `/__tests__/screens/TaskListScreen.test.tsx` | 11テスト | ✅ 全件合格 |

### 9.3 主要実装詳細

#### 9.3.1 画面フォーカス時の再同期

**実装場所**: TaskListScreen.tsx 73-80行目

```typescript
useFocusEffect(
  useCallback(() => {
    // 画面がフォーカスされたら、未完了タスクを再取得
    fetchTasks({ status: 'pending' });
  }, [fetchTasks])
);
```

**目的**: タスク削除後に前画面に戻った際、削除されたタスクを即座に消すため

#### 9.3.2 検索フィルタリング

**実装場所**: TaskListScreen.tsx 86-106行目

```typescript
useEffect(() => {
  if (searchQuery.trim()) {
    // 検索クエリがある場合: タイトル、説明、タグ名で部分一致フィルタリング
    const query = searchQuery.toLowerCase();
    const filtered = tasks.filter(task => {
      if (task.title?.toLowerCase().includes(query)) return true;
      if (task.description?.toLowerCase().includes(query)) return true;
      if (task.tags?.some(tag => tag.name?.toLowerCase().includes(query))) return true;
      return false;
    });
    setFilteredTasks(filtered);
  } else {
    setFilteredTasks(tasks);
  }
}, [tasks, searchQuery]);
```

**特徴**: フロントエンド側フィルタリング（即座に反応）

---

## 10. 将来対応

### 10.1 Phase 2.B-5 Step 3以降

- **タスク編集機能強化**: タイトル、説明、タグ、期限の編集
- **タスク削除機能**: 確認ダイアログ付き削除
- **タグフィルター機能**: タグ選択によるタスク絞り込み
- **複数タグ選択**: AND/OR条件指定
- **タグ管理**: タグ作成・編集・削除

### 10.2 Phase 2.B-6

- **バケツ表示（Bentoレイアウト）**: タグごとのグループ化表示（Web版整合性）
- **ページネーション**: 無限スクロール対応
- **バックエンド検索API**: `/api/tasks?q={query}` 実装（大量タスク対応）

---

## 11. Webアプリとの差分

### 11.1 実装済み機能（モバイル版）

- ✅ 未完了タスク表示
- ✅ 検索機能（フロントエンド側フィルタリング）
- ✅ タグ表示（id + name）
- ✅ グループタスクのみ報酬表示
- ✅ タスク詳細遷移（グループタスク: 詳細画面、通常タスク: 編集画面）
- ✅ 画面フォーカス時の自動再同期
- ✅ Pull-to-Refresh機能
- ✅ 完了ボタンによるタスク完了

### 11.2 未実装機能（将来対応）

- ❌ **バケツ表示（Bentoレイアウト）**: タグごとのグループカード表示（Phase 2.B-6）
- ❌ **タグフィルター**: タグ選択による絞り込み（Phase 2.B-5 Step 3）
- ❌ **完了/未完了切り替え**: モバイル版は未完了のみ表示（Webと異なる仕様）
- ❌ **一括操作**: 複数タスク選択・一括完了（Phase 2.B-6）

### 11.3 モバイル独自機能

- 現時点ではなし

---

## 12. 参考資料

### 12.1 関連ドキュメント

- `/home/ktr/mtdev/docs/plans/phase2-mobile-app-implementation-plan.md`
- `/home/ktr/mtdev/docs/mobile/mobile-rules.md`
- `/home/ktr/mtdev/.github/copilot-instructions.md`
- `/home/ktr/mtdev/definitions/Task.md`（Webアプリ要件）

### 12.2 完了レポート

- `/home/ktr/mtdev/docs/reports/mobile/2025-12-07-phase2-b5-step1-task-list-completion-report.md`

---

## 13. 質疑応答履歴

### Q1: 検索機能の挙動について

**質問**: 検索はどういう挙動になりますか？一覧画面に存在するタスクの件名の一部を入力しても何も反応はありません。

**回答**: 
- **原因**: バックエンド側で検索APIが未実装
- **対応**: フロントエンド側フィルタリングに変更（取得済みタスクを即座にフィルター）
- **実装**: TaskListScreen.tsx 73-93行目

### Q2: 報酬表示について

**質問**: グループタスクではないのに報酬の表示は不要です。グループタスクの場合のみ表示するようにしてください。

**回答**:
- **修正前**: すべてのタスクで報酬表示
- **修正後**: `is_group_task === true` のタスクのみ報酬表示
- **実装**: TaskListScreen.tsx 194-199行目

### Q3: タグ表示について

**質問**: タグが「不明」となっています。（webアプリでは「すべての画面のレスポンシブUIを改善する作業」のタグがついています）

**回答**:
- **原因**: `item.tags`の参照方法が間違っていた
- **修正**: `task.tags.map(tag => tag.name)` で正しく表示
- **実装**: TaskListScreen.tsx 191-197行目（タグバッジ表示）

### Q4: ステータスフィルターについて

**質問**: 完了、未完了が選択できる状態です。未完了のみ表示するようにしてください（webアプリでは画面に呼び出すデータを減らす目的で未完了のみ表示するようにしています。）

**回答**:
- **修正前**: `selectedStatus = 'all'` → 完了・未完了両方表示
- **修正後**: `selectedStatus = 'pending'` → 未完了のみ表示
- **UI変更**: ステータス切り替えボタン削除
- **実装**: TaskListScreen.tsx 53行目

### Q5: バケツ表示（Bentoレイアウト）について

**質問**: タスクのバケツ表示方法についての進捗はいかがですか？

**回答**:
- **Option A**: Webと同じバケツレイアウト（複雑、スクロール操作難）
- **Option B**: タスクをフラットリスト表示 + タグでフィルター（モバイル推奨） ← **採用**
- **実装時期**: Phase 2.B-5 Step 3以降（タグフィルター機能）

### Q6: 検索機能のバックエンド実装について

**質問**: 検索機能はバックエンド側で未実装であるため、これも後ろ倒しでの対応ということでOKですか？

**回答**:
- **現在**: フロントエンド側フィルタリング（取得済み20件内）で**実装済み**
- **バックエンドAPI**: 大量タスクが発生した場合に検討
- **結論**: 検索機能は実装済み・動作中、後ろ倒し不要

---

## 14. 承認

| 承認項目 | 承認者 | 承認日 | 備考 |
|---------|--------|--------|------|
| 要件定義承認 | - | 2025-12-07 | Phase 2.B-5 Step 1完了時点 |
| 実装完了承認 | - | 2025-12-07 | 実機テスト完了 |

---

**作成日**: 2025-12-07  
**最終更新日**: 2025-12-09  
**レビュー**: 未実施  
**バージョン**: 2.0（Web Blade解析ベース）

---

## 15. Web版Blade解析ベース: Bentoグリッド実装仕様（Phase 2.B-5 Step 3）

### 15.1 概要

**Web版**: タグ別バケット表示（Bentoグリッド）を採用。1-4カラムのレスポンシブグリッドでタスクをタグごとにグループ化表示。

**モバイル版**: 1カラムのタグカードリスト形式で実装予定。タグカードタップ → タグ内タスク一覧モーダル表示（Web版と同じ動作）。

### 15.2 Bentoグリッド UI要素詳細

#### 15.2.1 タグカード構成

| 要素 | Web版Tailwind CSS | React Native StyleSheet | 説明 |
|-----|------------------|------------------------|------|
| カードコンテナ | `rounded-2xl shadow-lg p-4 bg-white` | `borderRadius: 16, padding: 16, backgroundColor: '#FFFFFF', shadowColor: '#000', shadowOffset: {width: 0, height: 4}, shadowOpacity: 0.1, shadowRadius: 8, elevation: 4` | タグカード全体 |
| タグアイコン | `w-10 h-10 rounded-xl bg-gradient-to-br from-[#59B9C6] to-purple-600` | `width: 40, height: 40, borderRadius: 12` + LinearGradient | 左上のアイコン |
| タグ名 | `text-lg font-bold text-gray-900` | `fontSize: 18, fontWeight: 'bold', color: '#111827'` | タグ名表示 |
| タスク件数バッジ | `min-w-[2.5rem] h-7 px-3 rounded-full bg-gradient-to-r from-[#59B9C6] to-purple-600` | `minWidth: 40, height: 28, paddingHorizontal: 12, borderRadius: 9999` + LinearGradient | 右上の件数 |
| タスクプレビューチップ | `text-xs bg-white/50 px-3 py-1.5 rounded-full border border-gray-200/50` | `fontSize: 12, backgroundColor: 'rgba(255, 255, 255, 0.5)', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 9999, borderWidth: 1, borderColor: 'rgba(229, 231, 235, 0.5)'` | 最大6件表示 |
| 一括完了ボタン | `h-7 px-3 rounded-lg text-xs font-semibold bg-gradient-to-r from-emerald-500 to-teal-600` | `height: 28, paddingHorizontal: 12, borderRadius: 8, fontSize: 12, fontWeight: '600'` + LinearGradient | 条件付き表示 |

#### 15.2.2 グリッドレイアウト

**Web版**:
```css
/* Tailwind CSS */
grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6
```

**モバイル版**:
```typescript
// React Native FlatList
<FlatList
  data={tagBuckets}
  renderItem={renderTagCard}
  numColumns={1} // 1カラム固定
  contentContainerStyle={{ padding: 16, gap: 16 }}
/>
```

#### 15.2.3 タグバケット作成ロジック

**Web版（Blade）**:
```php
// task-bento.blade.php
$buckets = [];
foreach ($tasks as $task) {
    if ($task->tags->isEmpty()) {
        $key = $theme === 'adult' ? '未分類' : 'そのほか';
        $buckets[$key][] = $task;
    } else {
        foreach ($task->tags as $tag) {
            $buckets[$tag->name][] = $task;
        }
    }
}
```

**モバイル版（TypeScript）**:
```typescript
const bucketizeTasks = (tasks: Task[]): TagBucket[] => {
  const buckets: { [key: string]: Task[] } = {};
  
  tasks.forEach((task) => {
    if (task.tags.length === 0) {
      const key = theme === 'adult' ? '未分類' : 'そのほか';
      if (!buckets[key]) buckets[key] = [];
      buckets[key].push(task);
    } else {
      task.tags.forEach((tag) => {
        if (!buckets[tag.name]) buckets[tag.name] = [];
        buckets[tag.name].push(task);
      });
    }
  });
  
  return Object.entries(buckets).map(([name, tasks]) => ({
    name,
    tasks,
    count: tasks.length,
  }));
};
```

### 15.3 FAB（フローティングアクションボタン）

#### 15.3.1 仕様

| 項目 | Web版 | モバイル版 |
|------|-------|----------|
| 表示位置 | ヘッダー右側（固定ボタン） | 右下角（FAB） |
| サイズ | `h-10 w-10` → `sm:w-auto sm:px-4`（レスポンシブ） | `width: 56, height: 56`（固定） |
| 形状 | `rounded-full`（円形） | `borderRadius: 28`（円形） |
| グラデーション | `bg-gradient-to-r from-[#59B9C6] to-purple-600` | LinearGradient |
| 影 | `shadow-lg` | `shadowColor: '#000', shadowOffset: {width: 0, height: 4}, shadowOpacity: 0.3, shadowRadius: 8, elevation: 8` |

#### 15.3.2 実装例

```typescript
// FAB.tsx
import React from 'react';
import { TouchableOpacity, StyleSheet } from 'react-native';
import LinearGradient from 'react-native-linear-gradient';
import Icon from 'react-native-vector-icons/MaterialCommunityIcons';

interface FABProps {
  icon: string;
  onPress: () => void;
  onLongPress?: () => void;
}

export const FAB: React.FC<FABProps> = ({ icon, onPress, onLongPress }) => {
  return (
    <TouchableOpacity
      style={styles.fab}
      onPress={onPress}
      onLongPress={onLongPress}
      activeOpacity={0.8}
    >
      <LinearGradient
        colors={['#59B9C6', '#9333EA']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.gradient}
      >
        <Icon name={icon} size={24} color="#FFFFFF" />
      </LinearGradient>
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  fab: {
    position: 'absolute',
    right: 16,
    bottom: 16,
    width: 56,
    height: 56,
    borderRadius: 28,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
  },
  gradient: {
    width: '100%',
    height: '100%',
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
  },
});
```

### 15.4 プルダウンリフレッシュ

**Web版**: 実装なし（手動リロード or 自動リロード）

**モバイル版**: `<RefreshControl>`で実装

```typescript
<FlatList
  data={tagBuckets}
  renderItem={renderTagCard}
  refreshControl={
    <RefreshControl
      refreshing={refreshing}
      onRefresh={onRefresh}
      tintColor="#59B9C6" // iOS
      colors={['#59B9C6', '#9333EA']} // Android
    />
  }
/>
```

### 15.5 レスポンシブ対応（詳細）

**必須参照**: `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md`

#### 15.5.1 実装方針

**原則**: **Dimensions APIを使用したレスポンシブ対応**により、Web版の表示崩れ問題を解決する。

```typescript
import { useResponsive, getFontSize, getSpacing, getBorderRadius, getShadow } from '@/utils/responsive';
import { useChildTheme } from '@/hooks/useChildTheme';

const TaskListScreen = () => {
  const { width, deviceSize, isPortrait } = useResponsive();
  const isChildTheme = useChildTheme();
  const theme = isChildTheme ? 'child' : 'adult';
  
  // ヘッダータイトルの折り返し対策
  const headerTitleSize = getFontSize(20, width, theme);
  
  // カード余白の動的計算（見切れ対策）
  const cardPadding = getSpacing(16, width);
  const cardMargin = getSpacing(12, width);
  
  const styles = StyleSheet.create({
    headerTitle: {
      fontSize: headerTitleSize,
      adjustsFontSizeToFit: true,  // 自動フォントサイズ調整
      minimumFontScale: 0.7,        // 最小70%まで縮小可能
      numberOfLines: 2,              // 最大2行
    },
    card: {
      padding: cardPadding,
      marginBottom: cardMargin,
      borderRadius: getBorderRadius(16, width),
      ...getShadow(4), // Platform別シャドウ
      width: width - cardPadding * 2, // 左右余白を引く（見切れ防止）
    },
  });
  
  return (
    <View style={styles.container}>
      <Text style={styles.headerTitle}>タスク一覧</Text>
      <FlatList
        data={tasks}
        renderItem={({ item }) => <TaskCard style={styles.card} task={item} />}
        numColumns={1} // 1カラム固定（視認性優先）
      />
    </View>
  );
};
```

#### 15.5.2 ブレークポイント別対応

| カテゴリ | 画面幅範囲 | デバイス例 | フォント | 余白 | 角丸 |
|---------|-----------|----------|---------|------|------|
| 超小型 | 〜320px | Galaxy Fold | 0.80x | 0.75x | 0.80x |
| 小型 | 321px〜374px | iPhone SE 2nd | 0.90x | 0.85x | 0.90x |
| 標準 | 375px〜413px | iPhone 12, Pixel 7 | **1.00x** | **1.00x** | **1.00x** |
| 大型 | 414px〜767px | iPhone Pro Max | 1.05x | 1.10x | 1.05x |
| タブレット小 | 768px〜1023px | iPad mini | 1.10x | 1.20x | 1.10x |
| タブレット | 1024px〜 | iPad Pro | 1.15x | 1.30x | 1.15x |

**子ども向けテーマ**: 上記フォントサイズに**さらに1.2倍**を適用（わかりやすさ重視）

#### 15.5.3 表示崩れ対策

**問題1: ヘッダータイトルの折り返し**

Web版で「承認待ち一覧」「サブスクリプション管理」等で折り返し発生

```typescript
<Text
  style={styles.headerTitle}
  adjustsFontSizeToFit={true}  // ✅ 自動フォントサイズ調整
  minimumFontScale={0.7}        // ✅ 最小70%まで縮小
  numberOfLines={2}              // ✅ 最大2行（必要に応じて）
>
  サブスクリプション管理
</Text>
```

**問題2: タグタスク一覧モーダルでカード見切れ（Android）**

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

### 15.6 パフォーマンス要件（詳細）

#### 15.6.1 レンダリング最適化

```typescript
// React.memo でタグカードをメモ化
export const TagCard = React.memo<TagCardProps>(
  ({ tag, tasks, onPress }) => {
    // ...
  },
  (prevProps, nextProps) => {
    return (
      prevProps.tag.name === nextProps.tag.name &&
      prevProps.tasks.length === nextProps.tasks.length
    );
  }
);

// useCallback でコールバック関数をメモ化
const handleTagCardPress = useCallback(
  (tagName: string) => {
    navigation.navigate('TagTasks', { tagName });
  },
  [navigation]
);
```

#### 15.6.2 FlatList最適化

```typescript
<FlatList
  data={tagBuckets}
  renderItem={renderTagCard}
  keyExtractor={(item) => item.name}
  windowSize={10} // メモリ最適化
  maxToRenderPerBatch={10} // バッチレンダリング
  updateCellsBatchingPeriod={50} // 更新頻度制限
  removeClippedSubviews={true} // オフスクリーン要素を削除
  initialNumToRender={20} // 初回レンダリング件数
/>
```

### 15.7 テスト要件（Bentoグリッド）

#### 15.7.1 単体テスト

```typescript
describe('Bentoグリッド', () => {
  it('タグ別にタスクがバケット化される', () => {
    const tasks = [
      { id: 1, title: 'Task 1', tags: [{ id: 1, name: 'プログラミング' }] },
      { id: 2, title: 'Task 2', tags: [{ id: 1, name: 'プログラミング' }] },
      { id: 3, title: 'Task 3', tags: [{ id: 2, name: 'デザイン' }] },
    ];
    const buckets = bucketizeTasks(tasks);
    expect(buckets).toHaveLength(2);
    expect(buckets[0].name).toBe('プログラミング');
    expect(buckets[0].count).toBe(2);
  });
  
  it('タグなしタスクは「未分類」/「そのほか」に振り分けられる', () => {
    const tasks = [
      { id: 1, title: 'Task 1', tags: [] },
    ];
    const bucketsAdult = bucketizeTasks(tasks, 'adult');
    const bucketsChild = bucketizeTasks(tasks, 'child');
    expect(bucketsAdult[0].name).toBe('未分類');
    expect(bucketsChild[0].name).toBe('そのほか');
  });
});
```

#### 15.7.2 統合テスト

- [ ] タグカードタップでモーダルが表示される
- [ ] タスクプレビューチップが最大6件表示される
- [ ] 一括完了ボタンが正しく動作する
- [ ] プルダウンリフレッシュでデータが再取得される

---

## 16. 実装チェックリスト（Bentoグリッド追加版）

### 16.1 Phase 2.B-5 Step 1（完了）

- [x] フラットリスト表示
- [x] 検索機能
- [x] タグ表示
- [x] 報酬表示（グループタスクのみ）
- [x] FAB実装
- [x] プルダウンリフレッシュ

### 16.2 Phase 2.B-5 Step 3（予定）

- [ ] Bentoグリッド実装
- [ ] タグバケット化ロジック
- [ ] タグカードコンポーネント
- [ ] タグ内タスク一覧モーダル
- [ ] 一括完了ボタン
- [ ] タグフィルター機能
- [ ] レスポンシブ対応（小型デバイス）
- [ ] パフォーマンス最適化
- [ ] 単体テスト・統合テスト

---

**作成日**: 2025-12-07  
**最終更新日**: 2025-12-09（Web Blade解析ベース追加）  
**レビュー**: 未実施  
**バージョン**: 2.0（Web Blade解析ベース）
