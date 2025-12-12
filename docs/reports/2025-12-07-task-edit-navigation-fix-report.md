# タスク編集画面遷移不具合修正レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-07 | GitHub Copilot | 初版作成: タスク編集画面遷移エラーの修正完了報告 |

## 概要

モバイルアプリでタスクを選択して編集画面に遷移しようとすると、「タスクが見つかりません」エラーが発生する不具合が発生しました。この不具合は**アバター実装（Context API導入）後に発生**したもので、直接的な原因はアバター実装ではなく、`useTasks`フックの`fetchTasks()`メソッドの非同期処理に起因していました。

## 不具合の詳細

### 発生状況

- **発生タイミング**: タスク一覧画面からタスクをタップして編集画面に遷移する際
- **エラーメッセージ**: 「タスクが見つかりません」（Alert表示）
- **影響範囲**: TaskEditScreen（通常タスク編集）とTaskDetailScreen（グループタスク詳細）

### 再現手順

1. タスク一覧画面を表示（20件のタスクが正常に表示される）
2. 任意のタスク（通常タスク）をタップ
3. TaskEditScreenに遷移
4. エラーアラート「タスクが見つかりません」が表示される

## 原因分析

### ログ解析結果

```
LOG  [useTasks] fetchTasks success, tasks count: 20
LOG  [TaskEditScreen] after fetchTasks, tasks count: 0  ← 問題発生箇所
LOG  [TaskEditScreen] foundTask: null
ERROR [TaskEditScreen] Task not found in tasks array
```

### 根本原因

`useTasks`フックの`fetchTasks()`メソッドは以下の処理を行っていました：

```typescript
// 修正前
const fetchTasks = async (filters?: TaskFilters) => {
  const response = await taskService.getTasks(filters);
  setTasks(response.tasks);  // State更新（非同期）
  // ← 返り値なし
};
```

TaskEditScreenでは以下のように呼び出していました：

```typescript
// TaskEditScreenの問題コード
await fetchTasks();  // State更新を待つが...
const foundTask = tasks.find(t => t.id === taskId);  // tasksはまだ空配列
```

**問題点**:
1. `fetchTasks()`は`setTasks()`を呼ぶが、React Stateの更新は非同期
2. `await fetchTasks()`は完了するが、`tasks`変数はまだ更新されていない
3. 次の行の`tasks.find()`は古い（空の）`tasks`配列を参照する

### なぜアバター実装後に発生したか

- アバター実装前: TaskEditScreenがマウントされる前にTaskListScreenで`fetchTasks()`が完了し、`tasks`配列が既に存在していた（タイミングの問題で偶然動作）
- アバター実装後: AvatarProviderやContext APIの追加により、コンポーネントのマウント順序やレンダリングタイミングが微妙に変化
- 結果: TaskEditScreenが先にマウントされ、`tasks`配列が空の状態で`loadTask()`が実行される

## 修正内容

### 1. `fetchTasks()`の返り値を追加（useTasks.ts）

**修正前**:
```typescript
const fetchTasks = useCallback(
  async (filters?: TaskFilters) => {
    const response = await taskService.getTasks(filters);
    setTasks(response.tasks);
    setPagination(response.pagination);
  },
  [handleError]
);
```

**修正後**:
```typescript
const fetchTasks = useCallback(
  async (filters?: TaskFilters): Promise<Task[]> => {
    const response = await taskService.getTasks(filters);
    setTasks(response.tasks);
    setPagination(response.pagination);
    return response.tasks;  // ← 取得したタスク配列を返す
  },
  [handleError]
);
```

### 2. TaskEditScreenで返り値を使用

**修正前**:
```typescript
if (tasks.length === 0) {
  await fetchTasks();  // State更新を待つだけ
}
const foundTask = tasks.find((t) => t.id === taskId);  // 古いtasksを参照
```

**修正後**:
```typescript
let foundTask = tasks.find((t) => t.id === taskId);

if (!foundTask) {
  const fetchedTasks = await fetchTasks();  // 返り値を取得
  foundTask = fetchedTasks.find((t) => t.id === taskId);  // 返り値から直接検索
}
```

### 3. TaskDetailScreenに同様の修正を適用

TaskDetailScreen（グループタスク詳細画面）も同じパターンで修正しました。

## 成果と効果

### 定量的効果

- **修正ファイル数**: 3ファイル
  - `mobile/src/hooks/useTasks.ts`: `fetchTasks()`返り値追加
  - `mobile/src/screens/tasks/TaskEditScreen.tsx`: 返り値利用
  - `mobile/src/screens/tasks/TaskDetailScreen.tsx`: 返り値利用
- **テスト結果**: 229 passed, 1 skipped（全て成功）
- **デバッグログ追加**: 15箇所（トレース用ログ）

### 定性的効果

- ✅ タスク編集画面への遷移が正常に動作
- ✅ グループタスク詳細画面への遷移も修正
- ✅ 非同期処理の競合状態を根本的に解決
- ✅ 将来的な同様のバグを予防（パターン確立）

## 修正検証

### 実行ログ（修正後）

```
LOG  [TaskListScreen] Task item pressed: 2647 デザインモックアップの作成
LOG  [TaskListScreen] navigateToDetail called, taskId: 2647
LOG  [TaskListScreen] tasks count: 20
LOG  [TaskListScreen] found task: id=2647, is_group_task=false
LOG  [TaskListScreen] Navigating to TaskEdit
LOG  [TaskEditScreen] loadTask - taskId: 2647
LOG  [TaskEditScreen] Task not found in current tasks, fetching...
LOG  [useTasks] fetchTasks started
LOG  [useTasks] fetchTasks success, tasks count: 20
LOG  [TaskEditScreen] fetchedTasks count: 20
LOG  [TaskEditScreen] foundTask from fetchedTasks: id=2647
✅ タスク編集画面が正常に表示される
```

## 教訓と今後の対策

### 学んだこと

1. **React Stateの非同期性**: `setState()`の後、即座に変数は更新されない
2. **タイミング依存のバグ**: 偶然動作していたコードは、環境変化で壊れやすい
3. **Context API導入の副作用**: レンダリング順序やタイミングが変わる可能性
4. **デバッグログの重要性**: 段階的なログで問題箇所を特定できた

### 推奨事項

1. **非同期データ取得のパターン**:
   - データ取得関数は常に返り値を提供する
   - 呼び出し側は返り値を直接使用し、State変数に依存しない
   
2. **テストカバレッジ強化**:
   - 画面遷移フローのE2Eテスト追加を検討
   - TaskEditScreen, TaskDetailScreenの統合テスト作成
   
3. **コードレビュー基準**:
   - 非同期処理は必ず返り値のパターンで実装
   - State依存の処理は競合状態を考慮

## 関連ドキュメント

- アバター実装完了レポート: `docs/reports/2025-12-07-avatar-implementation-completion-report.md`（別途作成）
- プロジェクト構造: `.github/copilot-instructions.md`
- タスク管理仕様: `definitions/Task.md`

## まとめ

本不具合は、React Stateの非同期更新特性と、非同期データ取得関数の設計不足が原因でした。`fetchTasks()`に返り値を追加し、呼び出し側で直接使用することで根本的に解決しました。

この修正により、タスク編集・詳細画面への遷移が安定し、同様の非同期競合バグを予防するパターンが確立されました。
