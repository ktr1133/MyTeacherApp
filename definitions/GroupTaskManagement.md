# グループタスク管理機能 要件定義書（Webアプリ）

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-14 | GitHub Copilot | グループタスク作成上限エラーUI仕様追加: Web版モーダル、モバイル版モーダル |
| 2025-12-10 | GitHub Copilot | 初版作成: グループタスク編集・削除機能（Web版） |

---

## 1. 概要

### 1.1 目的

ログインユーザーが作成したグループタスクのうち、未完了または完了済未承認のタスクを編集・削除できる管理画面を提供します。

### 1.2 対象ユーザー

グループ編集権限を有するユーザーのみアクセス可能:
- `users.group_edit_flg = true` のユーザー
- または `groups.master_user_id = users.id` のユーザー（グループ管理者）

### 1.3 機能一覧

1. **グループタスク一覧表示** - 編集・削除可能なグループタスクの表示
2. **グループタスク編集** - タスク情報の一括更新（同じgroup_task_id全体）
3. **グループタスク削除** - 論理削除（同じgroup_task_id全体）

---

## 2. データ仕様

### 2.1 対象タスクの抽出条件

以下の3条件を**すべて**満たすタスクが対象:

```sql
SELECT * FROM tasks
WHERE group_task_id IS NOT NULL
  AND assigned_by_user_id = {ログインユーザーのID}
  AND approved_at IS NULL
  AND deleted_at IS NULL;
```

| 条件 | 説明 |
|------|------|
| `group_task_id IS NOT NULL` | グループタスクである |
| `assigned_by_user_id = {user_id}` | ログインユーザーが作成した |
| `approved_at IS NULL` | 未承認（未完了または完了済未承認） |
| `deleted_at IS NULL` | 削除されていない（ソフトデリート考慮） |

### 2.2 グループタスクの構造

グループタスクは`group_task_id`（UUID）で複数のタスクがグループ化されています:

```
group_task_id: "550e8400-e29b-41d4-a716-446655440000"
├── Task 1 (user_id: 10, assigned_by_user_id: 1)
├── Task 2 (user_id: 11, assigned_by_user_id: 1)
└── Task 3 (user_id: 12, assigned_by_user_id: 1)
```

**編集・削除の単位**: `group_task_id`単位（グループ全体を一括操作）

---

## 3. 画面仕様

### 3.1 画面配置

**タスクリスト画面（dashboard）からアクセス**

#### ボタン配置（レスポンシブ対応）

**ヘッダー1段目の場合（幅1024px以上）**:
```
[タスク登録] [グループタスク管理] [グループタスク登録] [通知] [ユーザー]
```
- 配置位置: グループタスク登録ボタンと通知ボタンの間

**ヘッダー2段目の場合（幅1023px以下）**:
```
1段目: [メニュー] [タイトル] [タスク登録] [グループタスク登録] [通知]
2段目: [検索欄] [グループタスク管理アイコン]
```
- 配置位置: 検索欄の右隣にアイコンのみ表示

### 3.2 グループタスク管理画面の要素

#### 3.2.1 画面タイトル
- **タイトル**: 「グループタスク管理」
- **副題**: 「作成したグループタスクの編集・削除」

#### 3.2.2 一覧表示（テーブル形式）

| カラム | 内容 | 幅 | ソート |
|--------|------|-----|--------|
| タイトル | タスク名 | 30% | ✓ |
| 説明 | タスク説明（省略表示） | 25% | - |
| 報酬 | トークン数 | 10% | ✓ |
| 期限 | due_date | 15% | ✓ |
| 割当人数 | 同じgroup_task_idのタスク数 | 10% | ✓ |
| 操作 | 編集・削除ボタン | 10% | - |

**グループ化表示**: 同じ`group_task_id`のタスクは1行にまとめて表示

#### 3.2.3 フィルタリング・検索機能

- タイトル検索（部分一致）
- 期限絞り込み（期限切れ、今週、今月、すべて）
- 報酬範囲フィルタ（スライダー）

---

## 4. 編集機能

### 4.1 アクセスルート

```
GET  /group-tasks/{group_task_id}/edit → ShowGroupTaskEditFormAction
PUT  /group-tasks/{group_task_id}      → UpdateGroupTaskAction
```

### 4.2 編集可能項目

| 項目 | 型 | 必須 | 制約 | 説明 |
|------|-----|------|------|------|
| `title` | string | ✓ | max:255 | タスクタイトル |
| `description` | text | - | - | タスク説明 |
| `span` | integer | ✓ | in:1,2,3 | 期間（短期・中期・長期） |
| `due_date` | string | - | - | 期限（spanに応じた形式） |
| `priority` | integer | - | between:1,5 | 優先度 |
| `reward` | integer | - | min:0 | 報酬トークン数 |
| `tags` | array | - | each max:50 | タグ配列 |
| `requires_approval` | boolean | - | - | 承認要否 |
| `requires_image` | boolean | - | - | 画像必須フラグ |

**編集対象外**:
- 割り当てメンバー（`user_id`）の追加・削除は不可
- `group_task_id`（変更不可）
- `assigned_by_user_id`（変更不可）

### 4.3 処理フロー

```
1. バリデーション実行（UpdateGroupTaskRequest）
2. 権限チェック:
   - ログインユーザーのgroup_edit_flg = true または
   - ログインユーザーのid = groups.master_user_id
3. 対象タスク取得（group_task_id一致 & approved_at IS NULL）
4. トランザクション開始
   - 同じgroup_task_idのタスク全件を更新
   - タグ更新（TaskTagService::syncTags）
   - キャッシュクリア
5. トランザクションコミット
6. 成功メッセージと共にリダイレクト
```

### 4.4 バリデーションルール

```php
'title'             => 'required|string|max:255',
'description'       => 'nullable|string',
'span'              => 'required|integer|in:1,2,3',
'due_date'          => 'nullable|string',
'priority'          => 'nullable|integer|between:1,5',
'reward'            => 'nullable|integer|min:0',
'tags'              => 'nullable|array',
'tags.*'            => 'string|max:50',
'requires_approval' => 'nullable|boolean',
'requires_image'    => 'nullable|boolean',
```

### 4.5 エラーハンドリング

| エラー | HTTPコード | メッセージ |
|--------|-----------|-----------|
| 権限なし | 403 | この操作を実行する権限がありません。 |
| グループタスク不存在 | 404 | 指定されたグループタスクが見つかりません。 |
| 承認済みタスク編集 | 422 | 承認済みのタスクは編集できません。 |
| バリデーションエラー | 422 | （項目ごとのエラーメッセージ） |

---

## 5. 削除機能

### 5.1 アクセスルート

```
DELETE /group-tasks/{group_task_id} → DestroyGroupTaskAction
```

### 5.2 削除仕様

**削除方式**: 論理削除（ソフトデリート）
- `deleted_at`カラムに削除日時を記録
- データベースから物理削除はしない
- `SoftDeletes` trait使用

### 5.3 削除対象

同じ`group_task_id`を持つ**すべてのタスク**を一括削除:
```sql
UPDATE tasks
SET deleted_at = NOW()
WHERE group_task_id = {指定されたUUID}
  AND assigned_by_user_id = {ログインユーザーのID}
  AND approved_at IS NULL;
```

### 5.4 処理フロー

```
1. 権限チェック:
   - ログインユーザーのgroup_edit_flg = true または
   - ログインユーザーのid = groups.master_user_id
2. 対象タスク取得（group_task_id一致 & approved_at IS NULL）
3. トランザクション開始
   - 同じgroup_task_idのタスク全件を論理削除
   - 関連データ処理:
     * task_tag（中間テーブル）は自動削除（ON DELETE CASCADE）
     * task_images（画像）は削除しない（復元可能性考慮）
   - キャッシュクリア
4. トランザクションコミット
5. 成功メッセージと共にリダイレクト
```

### 5.5 確認ダイアログ

削除実行前に確認モーダルを表示:

```
タイトル: グループタスクの削除
メッセージ: 「{タスク名}」と関連する全メンバーのタスク（{割当人数}件）を削除します。
          この操作は取り消せません。本当に削除しますか?

ボタン: [キャンセル] [削除する]
```

### 5.6 エラーハンドリング

| エラー | HTTPコード | メッセージ |
|--------|-----------|-----------|
| 権限なし | 403 | この操作を実行する権限がありません。 |
| グループタスク不存在 | 404 | 指定されたグループタスクが見つかりません。 |
| 承認済みタスク削除 | 422 | 承認済みのタスクは削除できません。 |

---

## 6. デザイン仕様

### 6.1 参照ドキュメント

- `/home/ktr/mtdev/docs/plans/phase2-b8-web-style-alignment-plan.md`
  - Tailwind CSS使用、カラーパレット、グラデーション
- `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md`
  - レスポンシブ対応、ブレークポイント

### 6.2 カラーパレット（Tailwind CSS）

| 用途 | クラス | HEX |
|------|-------|-----|
| プライマリ | `bg-blue-600` | `#2563EB` |
| プライマリホバー | `hover:bg-blue-700` | `#1D4ED8` |
| グループタスク | `bg-purple-600` | `#9333EA` |
| グループタスクホバー | `hover:bg-purple-700` | `#7E22CE` |
| 削除 | `bg-red-600` | `#DC2626` |
| 削除ホバー | `hover:bg-red-700` | `#B91C1C` |

### 6.3 レスポンシブ対応

**ブレークポイント**:
- `xs`: 〜320px（超小型）
- `sm`: 321px〜640px（小型）
- `md`: 641px〜768px（標準）
- `lg`: 769px〜1024px（大型）
- `xl`: 1025px〜（タブレット/デスクトップ）

**レイアウト調整**:
- `lg`以下: テーブルをカード形式に切り替え
- `md`以下: アイコンのみボタン表示
- `sm`以下: 1カラム表示

---

## 7. 実装クラス

### 7.1 ルート定義

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/group-tasks', ListGroupTasksAction::class)
        ->name('group-tasks.index');
    Route::get('/group-tasks/{group_task_id}/edit', ShowGroupTaskEditFormAction::class)
        ->name('group-tasks.edit');
    Route::put('/group-tasks/{group_task_id}', UpdateGroupTaskAction::class)
        ->name('group-tasks.update');
    Route::delete('/group-tasks/{group_task_id}', DestroyGroupTaskAction::class)
        ->name('group-tasks.destroy');
});
```

### 7.2 Action層

**一覧表示**: `App\Http\Actions\GroupTask\ListGroupTasksAction`
```php
public function __invoke(Request $request): View
{
    $user = $request->user();
    
    // 権限チェック
    if (!$user->canEditGroup()) {
        abort(403, 'この操作を実行する権限がありません。');
    }
    
    // グループタスク取得（group_task_id単位でグループ化）
    $groupTasks = $this->groupTaskService->getEditableGroupTasks($user);
    
    return $this->responder->index($groupTasks);
}
```

**編集フォーム表示**: `App\Http\Actions\GroupTask\ShowGroupTaskEditFormAction`
```php
public function __invoke(Request $request, string $groupTaskId): View
{
    $user = $request->user();
    
    if (!$user->canEditGroup()) {
        abort(403);
    }
    
    $groupTask = $this->groupTaskService->findEditableGroupTask($user, $groupTaskId);
    
    if (!$groupTask) {
        abort(404, '指定されたグループタスクが見つかりません。');
    }
    
    return $this->responder->edit($groupTask);
}
```

**更新**: `App\Http\Actions\GroupTask\UpdateGroupTaskAction`
```php
public function __invoke(UpdateGroupTaskRequest $request, string $groupTaskId): RedirectResponse
{
    $user = $request->user();
    
    if (!$user->canEditGroup()) {
        abort(403);
    }
    
    try {
        $this->groupTaskService->updateGroupTask($user, $groupTaskId, $request->validated());
        return redirect()->route('group-tasks.index')
            ->with('success', 'グループタスクを更新しました。');
    } catch (\Exception $e) {
        Log::error('グループタスク更新エラー', ['error' => $e->getMessage()]);
        return redirect()->back()
            ->withErrors(['error' => 'グループタスクの更新に失敗しました。'])
            ->withInput();
    }
}
```

**削除**: `App\Http\Actions\GroupTask\DestroyGroupTaskAction`
```php
public function __invoke(Request $request, string $groupTaskId): RedirectResponse
{
    $user = $request->user();
    
    if (!$user->canEditGroup()) {
        abort(403);
    }
    
    try {
        $deletedCount = $this->groupTaskService->deleteGroupTask($user, $groupTaskId);
        return redirect()->route('group-tasks.index')
            ->with('success', "{$deletedCount}件のタスクを削除しました。");
    } catch (\Exception $e) {
        Log::error('グループタスク削除エラー', ['error' => $e->getMessage()]);
        return redirect()->back()
            ->withErrors(['error' => 'グループタスクの削除に失敗しました。']);
    }
}
```

### 7.3 Service層

**GroupTaskManagementService** (新規作成)

```php
interface GroupTaskManagementServiceInterface
{
    /**
     * 編集可能なグループタスク一覧を取得（group_task_id単位）
     */
    public function getEditableGroupTasks(User $user): Collection;
    
    /**
     * 編集可能な特定グループタスクを取得
     */
    public function findEditableGroupTask(User $user, string $groupTaskId): ?array;
    
    /**
     * グループタスクを更新（同じgroup_task_id全体）
     */
    public function updateGroupTask(User $user, string $groupTaskId, array $data): int;
    
    /**
     * グループタスクを削除（同じgroup_task_id全体）
     */
    public function deleteGroupTask(User $user, string $groupTaskId): int;
}
```

### 7.4 Repository層

**GroupTaskRepository** (新規作成)

```php
interface GroupTaskRepositoryInterface
{
    /**
     * ユーザーが作成した編集可能なグループタスクを取得
     */
    public function findEditableByUser(int $userId): Collection;
    
    /**
     * 特定のgroup_task_idのタスクを取得
     */
    public function findByGroupTaskId(string $groupTaskId, int $assignedByUserId): Collection;
    
    /**
     * グループタスクを一括更新
     */
    public function updateByGroupTaskId(string $groupTaskId, int $assignedByUserId, array $data): int;
    
    /**
     * グループタスクを一括削除（論理削除）
     */
    public function softDeleteByGroupTaskId(string $groupTaskId, int $assignedByUserId): int;
}
```

### 7.5 Responder層

**GroupTaskResponder** (新規作成)

```php
class GroupTaskResponder
{
    public function index(Collection $groupTasks): View
    {
        return view('group-tasks.index', compact('groupTasks'));
    }
    
    public function edit(array $groupTask): View
    {
        return view('group-tasks.edit', compact('groupTask'));
    }
}
```

---

## 8. ビューファイル

### 8.1 一覧画面

**ファイルパス**: `resources/views/group-tasks/index.blade.php`

**主要コンポーネント**:
- ヘッダー（タイトル、検索フォーム、フィルタ）
- テーブル/カード切り替え（レスポンシブ）
- ページネーション
- 削除確認モーダル

### 8.2 編集画面

**ファイルパス**: `resources/views/group-tasks/edit.blade.php`

**主要コンポーネント**:
- タスク情報フォーム（タイトル、説明、期限等）
- タグ入力（既存タグ選択 + 新規タグ作成）
- 割当メンバー表示（読み取り専用）
- 保存・キャンセルボタン

---

## 9. 権限制御

### 9.1 User Modelへのメソッド追加

```php
// app/Models/User.php
public function canEditGroup(): bool
{
    // group_edit_flgがtrueの場合
    if ($this->group_edit_flg) {
        return true;
    }
    
    // グループの管理者（master_user_id）の場合
    if ($this->group && $this->group->master_user_id === $this->id) {
        return true;
    }
    
    return false;
}
```

### 9.2 Bladeテンプレートでの制御

```blade
@if(Auth::user()->canEditGroup())
    <button id="open-group-task-manage-btn" class="...">
        グループタスク管理
    </button>
@endif
```

---

## 10. テスト仕様

### 10.1 単体テスト

**GroupTaskManagementServiceTest**:
- ✅ 編集可能なグループタスクを取得できる
- ✅ 承認済みタスクは除外される
- ✅ 他ユーザーが作成したタスクは除外される
- ✅ グループタスクを一括更新できる
- ✅ グループタスクを一括削除できる（論理削除）

**GroupTaskRepositoryTest**:
- ✅ group_task_id単位でタスクを取得できる
- ✅ 一括更新が正しく実行される
- ✅ 論理削除が正しく実行される

### 10.2 統合テスト

**Feature/GroupTask/ListGroupTasksTest**:
- ✅ 権限のあるユーザーは一覧画面にアクセスできる
- ✅ 権限のないユーザーは403エラーになる
- ✅ 編集可能なグループタスクのみ表示される

**Feature/GroupTask/UpdateGroupTaskTest**:
- ✅ 権限のあるユーザーはグループタスクを更新できる
- ✅ 同じgroup_task_idのタスク全体が更新される
- ✅ バリデーションエラーが正しく処理される

**Feature/GroupTask/DestroyGroupTaskTest**:
- ✅ 権限のあるユーザーはグループタスクを削除できる
- ✅ 同じgroup_task_idのタスク全体が削除される
- ✅ 論理削除が正しく実行される

---

## 11. セキュリティ考慮事項

### 11.1 権限チェック

- すべてのアクションで`canEditGroup()`による権限検証
- `assigned_by_user_id`の一致確認（他ユーザーのタスク編集防止）
- `approved_at IS NULL`の確認（承認済みタスク編集防止）

### 11.2 CSRF保護

- すべてのフォームに`@csrf`トークン含める
- Laravel標準のCSRF保護機能使用

### 11.3 SQLインジェクション対策

- Eloquent ORMによるプリペアドステートメント使用
- 生SQLは使用しない

---

## 12. パフォーマンス最適化

### 12.1 N+1問題対策

```php
$tasks = Task::with(['user', 'tags', 'assignedBy'])
    ->whereNotNull('group_task_id')
    ->where('assigned_by_user_id', $userId)
    ->whereNull('approved_at')
    ->get();
```

### 12.2 キャッシュ戦略

- ユーザーごとのグループタスク一覧をキャッシュ（TTL: 15分）
- 更新・削除時にキャッシュクリア
- キャッシュキー: `user_group_tasks_{user_id}`

### 12.3 インデックス

既存のインデックスを活用:
```sql
INDEX (group_task_id)
INDEX (assigned_by_user_id)
INDEX (approved_at)
INDEX (deleted_at)
```

---

## 13. グループタスク作成上限エラーUI仕様

### 13.1 概要

サブスク未加入ユーザーが規定回数以上のグループタスクを作成しようとした際の、エラー表示とサブスク管理画面への誘導UI仕様。

### 13.2 エラー判定条件

**バックエンド**: `GroupTaskLimitService::canCreateGroupTask()` が `false` を返す場合
- サブスクリプション未加入（`subscription_active = false`）
- 月次作成数が上限に達している（`group_task_count_current_month >= free_group_task_limit`）

### 13.3 Web版仕様

#### 13.3.1 エラーレスポンス形式

**エンドポイント**: `POST /tasks`  
**HTTPステータス**: 422 Unprocessable Entity  
**レスポンス**:
```json
{
  "message": "今月のグループタスク作成数が上限（X件）に達しました。プレミアムプランにアップグレードすると無制限でグループタスクを作成できます。",
  "usage": {
    "current": 5,
    "limit": 5,
    "remaining": 0,
    "is_unlimited": false,
    "has_subscription": false,
    "reset_at": "2025-01-01T00:00:00Z"
  },
  "upgrade_required": true
}
```

#### 13.3.2 モーダル表示仕様

**コンポーネント**: `/home/ktr/mtdev/resources/views/components/group-task-limit-modal.blade.php`  
**制御スクリプト**: `GroupTaskLimitModal` (Vanilla JS)

**モーダル構成**:
1. **ヘッダー**: 紫→ピンクのグラデーション、警告アイコン
2. **エラーメッセージ**: バックエンドから受け取ったメッセージを表示
3. **サブスク特典カード**:
   - グループタスクを無制限に作成
   - 月次レポート自動生成
   - 全機能が使い放題
   - 月額 ¥500〜
4. **アクションボタン**:
   - 「閉じる」: モーダルを閉じる
   - 「サブスク管理画面へ」: `/subscriptions` に遷移

**トリガー**: `group-task.js` の `fetch` エラーハンドリング
```javascript
if (errorData.upgrade_required && window.GroupTaskLimitModal) {
  closeModal(groupModal, groupModalContent);
  resetForm();
  window.GroupTaskLimitModal.show(errorData.message);
}
```

### 13.4 モバイル版仕様

#### 13.4.1 エラーレスポンス形式

Web版と同じ形式（`POST /api/tasks`）

#### 13.4.2 モーダル表示仕様

**コンポーネント**: `/home/ktr/mtdev/mobile/src/components/common/GroupTaskLimitModal.tsx`

**モーダル構成**:
1. **ヘッダー**: 紫→ピンクのグラデーション、警告絵文字（⚠️）
2. **エラーメッセージ**: バックエンドから受け取ったメッセージを表示（テーマ対応不要）
3. **サブスク特典カード**: Web版と同じ内容（子どもテーマ対応）
4. **アクションボタン**:
   - 「閉じる」（子: 「とじる」）: モーダルを閉じる
   - 「サブスク管理画面へ」（子: 「サブスク画面へ」）: `SubscriptionManage` 画面に遷移

**エラーハンドリングフロー**:
1. `task.service.ts`: 422エラー + `upgrade_required` フラグを検出してエラーオブジェクトに付与
   ```typescript
   if (error.response?.status === 422 && error.response.data.upgrade_required) {
     const limitError = new Error(error.response.data.message);
     (limitError as any).upgrade_required = true;
     throw limitError;
   }
   ```

2. `useTasks.ts`: `upgrade_required` フラグ付きエラーは呼び出し元に伝播
   ```typescript
   if ((err as any).upgrade_required) {
     throw err;
   }
   ```

3. `CreateTaskScreen.tsx`: エラーをキャッチしてモーダル表示
   ```typescript
   try {
     const newTask = await createTask(taskData);
   } catch (err: any) {
     if (err.upgrade_required) {
       setLimitErrorMessage(err.message);
       setShowLimitModal(true);
     }
   }
   ```

**レスポンシブ対応**: `useResponsive` + テーマ別フォント・余白調整

### 13.5 その他のエラー表示

**Web版**:
- グループタスク上限エラー以外のエラーは従来通り `alert()` で表示

**モバイル版**:
- グループタスク上限エラー以外のエラーは `useTasks` の `error` state にセットされ、`Alert.alert()` で表示（既存実装）
- バリデーションエラー、ネットワークエラー等は通常のエラーメッセージを表示

### 13.6 実装ファイル一覧

| ファイル | 説明 |
|---------|------|
| **Web版** | |
| `/home/ktr/mtdev/resources/views/components/group-task-limit-modal.blade.php` | モーダルコンポーネント（Blade + Vanilla JS） |
| `/home/ktr/mtdev/resources/js/dashboard/group-task.js` | エラーハンドリング + モーダル表示トリガー |
| `/home/ktr/mtdev/resources/views/dashboard.blade.php` | モーダルをinclude |
| `/home/ktr/mtdev/app/Http/Actions/Task/StoreTaskAction.php` | 既存実装（変更なし） |
| **モバイル版** | |
| `/home/ktr/mtdev/mobile/src/components/common/GroupTaskLimitModal.tsx` | モーダルコンポーネント（React Native） |
| `/home/ktr/mtdev/mobile/src/services/task.service.ts` | エラーレスポンス判定 + `upgrade_required` フラグ付与 |
| `/home/ktr/mtdev/mobile/src/hooks/useTasks.ts` | エラー伝播処理 |
| `/home/ktr/mtdev/mobile/src/screens/tasks/CreateTaskScreen.tsx` | エラーキャッチ + モーダル表示 |
| `/home/ktr/mtdev/app/Http/Actions/Api/Task/StoreTaskApiAction.php` | 既存実装（変更なし） |

---

## 14. エラーメッセージ一覧

| コード | メッセージ | 対処方法 |
|--------|-----------|---------|
| 403 | この操作を実行する権限がありません。 | グループ編集権限が必要 |
| 404 | 指定されたグループタスクが見つかりません。 | URLを確認するか、一覧から選択 |
| 422 | 承認済みのタスクは編集できません。 | 承認前のタスクのみ編集可能 |
| 422 | 承認済みのタスクは削除できません。 | 承認前のタスクのみ削除可能 |
| 500 | グループタスクの更新に失敗しました。 | 管理者に連絡 |
| 500 | グループタスクの削除に失敗しました。 | 管理者に連絡 |

---

## 14. 今後の拡張案

### 14.1 将来的な機能追加

- [ ] 割り当てメンバーの追加・削除機能
- [ ] グループタスクの複製機能
- [ ] グループタスクのテンプレート化
- [ ] 一括承認・一括削除機能
- [ ] グループタスクの進捗可視化（ダッシュボード）

### 14.2 パフォーマンス改善

- [ ] Elasticsearchによる全文検索
- [ ] Redis Pub/Subによるリアルタイム通知
- [ ] バックグラウンドジョブによる一括処理

---

## 15. 関連ドキュメント

| ドキュメント | パス |
|------------|------|
| タスク要件定義書 | `/home/ktr/mtdev/definitions/Task.md` |
| レスポンシブガイドライン | `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md` |
| Web版スタイル統一計画 | `/home/ktr/mtdev/docs/plans/phase2-b8-web-style-alignment-plan.md` |
| プロジェクト規約 | `/home/ktr/mtdev/.github/copilot-instructions.md` |
