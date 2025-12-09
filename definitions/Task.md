# タスク登録機能 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-09 | GitHub Copilot | モバイルアプリのAIタスク分解機能（TaskDecompositionScreen）実装完了、モバイルAPI追加 |
| 2025-12-05 | GitHub Copilot | 初版作成: タスク登録関連機能の要件定義 |
| 2025-12-05 | GitHub Copilot | 子ども向け画面（テーマ）仕様を追加 |
| 2025-12-05 | GitHub Copilot | パフォーマンス最適化実装完了（Redis接続プール、複合インデックス、キャッシュTTL延長） |
| 2025-12-05 | GitHub Copilot | 無限スクロール機能実装完了（API、フロントエンド、テスト） |

---

## 1. 概要

MyTeacher AIタスク管理プラットフォームにおけるタスク登録機能は、ユーザーが学習・業務タスクを効率的に管理するための中核機能です。通常タスクの登録、AI支援によるタスク分解、グループメンバーへのタスク割当、タスクの更新・削除など、包括的なタスク管理機能を提供します。

### 1.1 機能一覧

1. **通常タスク登録機能** - 個人タスクの新規作成
2. **AIタスク分解機能** - OpenAI APIによる大規模タスクの自動分解
3. **グループタスク割当機能** - 複数メンバーへの同時タスク割当
4. **タスク更新機能** - 既存タスクの編集・画像追加
5. **タスク削除機能** - ソフトデリートによる論理削除
6. **タスク承認機能** - グループタスクの承認フロー
7. **タスク完了機能** - タスク完了処理とトークン報酬付与
8. **子ども向け画面対応** - 年齢に応じた用語・表現の切り替え

---

## 2. 通常タスク登録機能

### 2.1 機能要件

**概要**: ユーザーが個人のタスクを新規作成する機能。

**アクセスルート**: 
- `POST /tasks` → `StoreTaskAction`

**入力項目**:

| 項目 | 型 | 必須 | 制約 | デフォルト | 説明 |
|------|-----|------|------|-----------|------|
| `title` | string | ✓ | max:255 | - | タスク名 |
| `description` | text | - | - | null | タスク詳細説明 |
| `span` | integer | ✓ | in:1,2,3 | - | 期間（1=短期、2=中期、3=長期） |
| `due_date` | string | - | - | null | 期限（形式はspanに依存） |
| `priority` | integer | - | between:1,5 | 3 | 優先度（1=最高、5=最低） |
| `tags` | array | - | each max:50 | [] | タグ配列（既存/新規） |

**due_date形式ルール**:
- `span=1` (短期): 年月日形式（例: `2025-12-20`）
- `span=2` (中期): 年月形式（例: `2025-12`）
- `span=3` (長期): 任意文字列（例: `来年の春頃`）

**処理フロー**:
```
1. バリデーション実行（StoreTaskRequest）
2. ユーザー認証確認（必須）
3. タスク作成（TaskManagementService::createTask）
   - user_id: 認証ユーザーのID
   - priority: 未指定の場合は3をデフォルト設定
4. タグ処理（TaskTagService::syncTags）
   - 既存タグ: IDで関連付け
   - 新規タグ: 作成してから関連付け
5. キャッシュクリア（ユーザータスク一覧）
6. 成功メッセージと共にリダイレクト
```

**バリデーションルール**:
```php
'title'       => 'required|string|max:255',
'description' => 'nullable|string',
'span'        => 'required|integer|in:1,2,3',
'due_date'    => 'nullable|string',
'priority'    => 'nullable|integer|between:1,5',
'tags'        => 'nullable|array',
'tags.*'      => 'string|max:50',
```

**エラーハンドリング**:
- タイトル未入力: `タスク名は必須です。`
- タイトル255文字超過: `タスク名は255文字以内で入力してください。`
- span未指定: `期間は必須です。`
- span範囲外: `期間は短期、中期、長期のいずれかを選択してください。`
- 優先度範囲外: `優先度は1〜5の範囲で指定してください。`
- 未認証: 302リダイレクト（ログインページへ）

**成功時のレスポンス**:
```php
return redirect()->route('tasks.index')
    ->with('success', 'タスクを作成しました。');
```

### 2.2 データモデル

**tasksテーブル**:
```sql
CREATE TABLE tasks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    source_proposal_id BIGINT NULL,
    assigned_by_user_id BIGINT NULL,
    approved_by_user_id BIGINT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    span INTEGER NULL,
    due_date VARCHAR(255) NULL,
    priority SMALLINT DEFAULT 3,
    group_task_id UUID NULL,
    reward INTEGER NULL,
    requires_approval BOOLEAN DEFAULT FALSE,
    requires_image BOOLEAN DEFAULT FALSE,
    approved_at TIMESTAMP NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (source_proposal_id) REFERENCES task_proposals(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (group_task_id)
);
```

**タグ関連テーブル**:
```sql
CREATE TABLE tags (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE task_tag (
    task_id BIGINT NOT NULL,
    tag_id BIGINT NOT NULL,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
```

### 2.3 実装クラス

**Action**: `App\Http\Actions\Task\StoreTaskAction`
```php
public function __invoke(StoreTaskRequest $request): RedirectResponse
{
    $data = $request->validated();
    $task = $this->taskManagementService->createTask($user, $data);
    return redirect()->route('tasks.index')->with('success', 'タスクを作成しました。');
}
```

**Service**: `App\Services\Task\TaskManagementService`
```php
public function createTask(User $user, array $data, bool $groupFlg = false): Task
{
    $data['user_id'] = $user->id;
    $data['priority'] = $data['priority'] ?? 3;
    $task = $this->repository->create($data);
    
    if (isset($data['tags'])) {
        $this->tagService->syncTags($task, $data['tags']);
    }
    
    Cache::forget("user_tasks_{$user->id}");
    return $task;
}
```

**Repository**: `App\Repositories\Task\TaskEloquentRepository`
```php
public function create(array $data): Task
{
    return Task::create($data);
}
```

---

## 3. AIタスク分解機能

### 3.1 機能要件

**概要**: OpenAI APIを使用して大規模タスクを複数の小タスクに自動分解する機能。

**アクセスルート**: 
- **Webアプリ**: `POST /tasks/propose` → `ProposeTaskAction` (提案生成)
- **Webアプリ**: `POST /tasks/adopt-proposal` → `AdoptProposalAction` (提案採用)
- **モバイルAPI**: `POST /api/tasks/propose` → `ProposeTaskApiAction` (提案生成)
- **モバイルAPI**: `POST /api/tasks/adopt` → `AdoptProposalApiAction` (提案採用)

### 3.1.1 提案生成フロー（ProposeTaskAction）

**入力項目**:

| 項目 | 型 | 必須 | 制約 | 説明 |
|------|-----|------|------|------|
| `title` | string | ✓ | max:255 | 分解対象タスク名 |
| `span` | integer | ✓ | in:1,2,3 | 期間 |
| `context` | string | - | - | 追加コンテキスト |
| `is_refinement` | boolean | - | - | 細分化モード |

**処理フロー**:
```
1. バリデーション実行
2. トークン残高確認（50,000トークン必要）
3. OpenAI API呼び出し（requestDecomposition）
   - is_refinement=true: 細分化プロンプト
   - is_refinement=false: 通常分解プロンプト
4. 提案データ保存（TaskProposal）
   - 提案ID（UUID）生成
   - OpenAIレスポンス保存
5. トークン消費処理（TokenService::consumeTokens）
6. 提案結果をJSON返却
```

**OpenAI APIレスポンス形式**:
```json
{
  "tasks": [
    {
      "title": "小タスク1",
      "description": "詳細説明1",
      "span": 1,
      "priority": 3
    },
    {
      "title": "小タスク2",
      "description": "詳細説明2",
      "span": 1,
      "priority": 2
    }
  ],
  "usage": {
    "total_tokens": 1250
  }
}
```

**トークン消費**:
- 基本消費量: 50,000トークン
- 実際のAPI使用量も記録（usage.total_tokens）
- 残高不足時: 402エラー `トークンが不足しています。`

**エラーハンドリング**:
- トークン不足: `402 Payment Required`
- OpenAI APIエラー: `500 Internal Server Error`
- タイトル未指定: `422 Unprocessable Entity`

### 3.1.2 提案採用フロー（AdoptProposalAction）

**入力項目**:

| 項目 | 型 | 必須 | 制約 | 説明 |
|------|-----|------|------|------|
| `proposal_id` | string | ✓ | exists:task_proposals | 提案ID |
| `tasks` | array | ✓ | min:1 | 採用するタスク配列 |
| `tasks.*.title` | string | ✓ | max:255 | タスク名 |
| `tasks.*.description` | string | - | - | 説明 |
| `tasks.*.span` | integer | ✓ | in:1,2,3 | 期間 |
| `tasks.*.due_date` | string | - | - | 期限 |
| `tasks.*.priority` | integer | - | between:1,5 | 優先度 |
| `tags` | array | - | - | 全タスク共通タグ |

**処理フロー**:
```
1. バリデーション実行
2. proposal_id存在確認
3. トランザクション開始
4. 各タスクをループで作成
   - source_proposal_id: 提案IDを記録
   - user_id: 認証ユーザー
   - タグ同期処理
5. トランザクションコミット
6. キャッシュクリア
7. 作成件数とともにリダイレクト
```

**成功時のレスポンス**:
```php
return redirect()->route('tasks.index')
    ->with('success', "{$count}件のタスクを作成しました。");
```

### 3.2 データモデル

**task_proposalsテーブル**:
```sql
CREATE TABLE task_proposals (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    original_title VARCHAR(255) NOT NULL,
    context TEXT NULL,
    proposal_data JSON NOT NULL,
    is_adopted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (uuid)
);
```

### 3.3 モバイルアプリ実装（TaskDecompositionScreen）

**実装日**: 2025-12-09

**ファイル**: `/home/ktr/mtdev/mobile/src/screens/tasks/TaskDecompositionScreen.tsx`

**機能概要**: モバイルアプリ用のAIタスク分解画面。3段階のフロー（入力→提案→編集・採用）で大きなタスクを複数の小タスクに分解し、一括作成できる。

**画面フロー**:
1. **入力段階（input）**: タイトル、期間（span）、期限（due_date）、コンテキストを入力
2. **提案段階（decomposition）**: AI提案を表示、タスク選択・編集可能、再提案可能
3. **改善段階（refine）**: 再提案時の改善要望入力

**主要機能**:
- ✅ タスク分解提案（ProposeTask API）
- ✅ 再提案機能（`is_refinement=true`）
- ✅ 提案タスクの選択・編集（span/due_date変更可能）
- ✅ 選択タスクの一括作成（AdoptProposal API）
- ✅ アバターイベント連携（`task_decomposition_complete`）
- ✅ 分解元タイトルを自動的にタグとして設定

**API連携**:
```typescript
// 提案API呼び出し
const response: ProposeTaskResponse = await taskService.proposeTask({
  title: title.trim(),
  span,
  due_date: dueDate.trim() || undefined,
  context: context.trim() || undefined,
  is_refinement: false,  // 再提案時はtrue
});

// 採用API呼び出し
const response = await taskService.adoptProposal({
  proposal_id: proposalId,
  tasks: selectedTasks.map(task => ({
    title: task.title,
    span: task.span,
    priority: task.priority || 3,
    due_date: task.due_date || undefined,
    tags: [originalTitle.trim()],  // 分解元をタグ化
  })),
});
```

**編集可能な項目**:
- タイトル: 提案後も編集可能
- span（期間）: 短期/中期/長期を変更可能
- due_date（期限）: spanに応じたフォーマットで編集可能
  - 短期（span=1）: YYYY-MM-DD形式
  - 中期（span=2）: YYYY-MM形式
  - 長期（span=3）: 任意文字列

**UI特徴**:
- テーマ対応（子ども/大人）
- リアルタイムバリデーション
- ローディング状態の表示
- エラーメッセージのアラート表示
- 選択済みタスクのチェックマーク表示

**テスト**: `/home/ktr/mtdev/mobile/__tests__/screens/TaskDecompositionScreen.test.tsx` - 14 tests passing

**参考レポート**: `/home/ktr/mtdev/docs/reports/2025-12-09-mobile-task-decomposition-implementation-report.md`

---

## 4. グループタスク割当機能

### 4.1 機能要件

**概要**: グループマスターまたは編集権限を持つメンバーが、他のメンバーにタスクを割り当てる機能。

**アクセスルート**: 
- `POST /tasks` (is_group_task=true) → `StoreTaskAction`

**入力項目**（通常タスク + 追加項目）:

| 項目 | 型 | 必須 | 制約 | デフォルト | 説明 |
|------|-----|------|------|-----------|------|
| `is_group_task` | boolean | ✓ | - | false | グループタスクフラグ |
| `assigned_user_id` | integer | - | exists:users | null | 割当先ユーザーID（複数指定可） |
| `reward` | integer | ✓ | min:0 | - | 報酬額（トークン） |
| `requires_approval` | boolean | - | - | false | 承認必須フラグ |
| `requires_image` | boolean | - | - | false | 画像必須フラグ |

**権限チェック**:
```php
// 権限確認条件
1. ユーザーがグループに所属している
2. グループマスター OR 編集権限あり（can_edit_group=true）
```

**処理フロー**:
```
1. バリデーション実行
2. 権限チェック（GroupService::canEditGroup）
3. プラン制限チェック（無料プラン: 月3回まで）
4. group_task_id（UUID）生成
5. 割当先ユーザーごとにタスク作成
   - user_id: 割当先ユーザーID
   - assigned_by_user_id: 作成者ID
   - group_task_id: 共通UUID
   - reward, requires_approval, requires_image設定
6. 自動承認判定（requires_approval=falseの場合）
   - TaskApprovalService::approveTask実行
   - approved_at, approved_by_user_idを設定
7. 月次カウンター更新（GroupMonthlyCounter）
8. 成功メッセージと共にリダイレクト
```

**プラン制限**:
- **無料プラン**: 月3回まで（`group_monthly_counters.monthly_count`）
- **有料プラン**: 無制限
- 上限超過時: `月次作成上限に達しました。`

**自動承認**:
```php
if (!$task->requires_approval) {
    $this->taskApprovalService->approveTask($task, $approver);
    // approved_at: now()
    // approved_by_user_id: 作成者ID
}
```

**エラーハンドリング**:
- 権限なし: `403 Forbidden - グループタスク作成権限がありません。`
- reward未指定: `報酬は必須です。`
- reward負の値: `報酬は0円以上で指定してください。`
- 存在しないユーザー: `指定されたユーザーが見つかりません。`
- 別グループのユーザー: `指定されたユーザーは同じグループに所属していません。`
- 月次上限超過: `月次作成上限に達しました。有料プランへのアップグレードをご検討ください。`

### 4.2 データモデル

**group_monthly_countersテーブル**:
```sql
CREATE TABLE group_monthly_counters (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    year_month VARCHAR(7) NOT NULL,  -- YYYY-MM形式
    monthly_count INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_month (user_id, year_month)
);
```

**グループタスクの識別**:
- `group_task_id` (UUID): 同一グループタスクは同じUUIDを共有
- `assigned_by_user_id`: タスク割当者（≠ user_id: タスク所有者）
- `reward`: トークン報酬額（完了時に付与）

---

## 5. タスク更新機能

### 5.1 機能要件

**概要**: 既存タスクの編集、画像アップロード・削除を行う機能。

**アクセスルート**: 
- `PUT /tasks/{task}` → `UpdateTaskAction`
- `POST /tasks/{task}/upload-image` → `UploadTaskImageAction`
- `DELETE /tasks/{task}/images/{image}` → `DeleteTaskImageAction`

### 5.1.1 基本フィールド更新

**入力項目**:

| 項目 | 型 | 必須 | 制約 | 説明 |
|------|-----|------|------|------|
| `title` | string | - | max:255 | タスク名 |
| `description` | text | - | - | 説明 |
| `span` | integer | - | in:1,2,3 | 期間 |
| `due_date` | string | - | - | 期限 |
| `priority` | integer | - | between:1,5 | 優先度 |
| `tags` | array | - | - | タグ配列 |

**権限チェック**:
```php
// 更新可能な条件
1. タスクの所有者（task.user_id == auth()->id()）
```

**処理フロー**:
```
1. タスク取得（存在確認）
2. 権限チェック（所有者確認）
3. バリデーション実行
4. タスク更新（TaskManagementService::updateTask）
5. タグ同期（変更がある場合）
6. キャッシュクリア
7. 成功メッセージと共にリダイレクト
```

**バリデーションルール**:
```php
'title'       => 'nullable|string|max:255',
'description' => 'nullable|string',
'span'        => 'nullable|integer|in:1,2,3',
'due_date'    => 'nullable|string',
'priority'    => 'nullable|integer|between:1,5',
'tags'        => 'nullable|array',
```

**エラーハンドリング**:
- 他人のタスク: `403 Forbidden - このタスクを更新する権限がありません。`
- 存在しないタスク: `404 Not Found`
- タイトル空: `タスク名は必須です。`
- span範囲外: `期間は1,2,3のいずれかを選択してください。`

### 5.1.2 画像アップロード

**入力項目**:

| 項目 | 型 | 必須 | 制約 | 説明 |
|------|-----|------|------|------|
| `image` | file | ✓ | image, max:5120 | 画像ファイル（JPG/PNG） |

**処理フロー**:
```
1. タスク取得・権限チェック
2. ファイルバリデーション
   - 形式: image/jpeg, image/png
   - サイズ: 最大5MB
3. S3アップロード（Storage::disk('s3')->putFile）
   - パス: task-images/{user_id}/{filename}
   - 可視性: public
4. TaskImageレコード作成
   - task_id: タスクID
   - file_path: S3パス
5. 成功メッセージ返却
```

**バリデーションルール**:
```php
'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
```

**エラーハンドリング**:
- 形式エラー: `画像形式はJPG、PNGのみです。`
- サイズ超過: `画像サイズは5MB以内にしてください。`
- 他人のタスク: `403 Forbidden`

### 5.1.3 画像削除

**処理フロー**:
```
1. タスク画像取得（TaskImage）
2. 権限チェック（タスク所有者確認）
3. S3ファイル削除（Storage::disk('s3')->delete）
4. TaskImageレコード削除
5. 成功メッセージ返却
```

### 5.2 データモデル

**task_imagesテーブル**:
```sql
CREATE TABLE task_images (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_id BIGINT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);
```

---

## 6. タスク削除機能

### 6.1 機能要件

**概要**: タスクをソフトデリート（論理削除）する機能。関連する画像もS3から削除。

**アクセスルート**: 
- `DELETE /tasks/{task}` → `DeleteTaskAction`

**処理フロー**:
```
1. タスク取得（存在確認）
2. 権限チェック（所有者確認）
3. トランザクション開始
4. 関連画像削除（TaskManagementService::deleteTask内）
   - S3ファイル削除（Storage::disk('s3')->delete）
   - TaskImageレコード削除
5. タグ関連解除（detachAllTags）
6. タスクソフトデリート（Task::delete）
   - deleted_at: now()
7. トランザクションコミット
8. キャッシュクリア
9. 成功メッセージと共にリダイレクト
```

**ソフトデリート実装**:
```php
// TaskEloquentRepository::deleteTask
$task->detachAllTags();
$task->delete();  // ← forceDelete()ではなくdelete()
```

**グループタスク削除**:
- **単一削除のみ**: 指定したタスクのみ削除（カスケードなし）
- 同じ`group_task_id`を持つ他のタスクは影響を受けない

**エラーハンドリング**:
- 他人のタスク: `403 Forbidden`
- 存在しないタスク: `404 Not Found`
- 既に削除済み: `404 Not Found`
- task_id未指定: `422 Unprocessable Entity`
- task_id不正形式: `422 Unprocessable Entity`

**成功時のレスポンス**:
```php
return redirect()->route('tasks.index')
    ->with('success', 'タスクを削除しました。');
```

### 6.2 データ保持

**ソフトデリート後のデータ**:
- `deleted_at`: 削除日時が記録される
- その他のフィールド: 保持される（監査証跡）
- 通常のクエリからは除外される（`->withTrashed()`で取得可能）

**物理削除が必要な場合**:
```php
// 管理者のみ実行可能（手動実行）
Task::onlyTrashed()->where('deleted_at', '<', now()->subMonths(6))->forceDelete();
```

---

## 7. タスク承認機能

### 7.1 機能要件

**概要**: グループタスクで`requires_approval=true`の場合、マスターまたは承認者が承認する機能。

**アクセスルート**: 
- `POST /tasks/{task}/approve` → `ApproveTaskAction`
- `POST /tasks/{task}/upload-approval-image` → 画像付き承認

**処理フロー**:
```
1. タスク取得
2. 承認権限チェック
   - グループマスター
   - OR タスク割当者（assigned_by_user_id）
3. 画像必須チェック（requires_image=trueの場合）
   - 画像が添付されているか確認
4. 承認処理（TaskApprovalService::approveTask）
   - approved_at: now()
   - approved_by_user_id: 承認者ID
5. トークン報酬付与（rewardが設定されている場合）
6. 成功メッセージ返却
```

**画像付き承認**:
```php
if ($task->requires_image && !$task->images()->exists()) {
    abort(422, '画像のアップロードが必要です。');
}
```

**トークン報酬付与**:
```php
if ($task->reward > 0) {
    $this->tokenService->grantTokens($task->user, $task->reward, 'タスク承認報酬');
}
```

**エラーハンドリング**:
- 権限なし: `403 Forbidden`
- 画像未添付: `422 Unprocessable Entity - 画像のアップロードが必要です。`
- 既に承認済み: `422 Unprocessable Entity - 既に承認されています。`

---

## 8. タスク完了機能

### 8.1 機能要件

**概要**: タスクを完了状態にする機能。グループタスクの場合はトークン報酬を付与。

**アクセスルート**: 
- `POST /tasks/{task}/complete` → `CompleteTaskAction`

**処理フロー**:
```
1. タスク取得
2. 権限チェック（所有者確認）
3. 承認チェック（requires_approval=trueの場合）
   - 承認済みでない場合はエラー
4. 完了処理
   - is_completed: true
   - completed_at: now()
5. トークン報酬付与（グループタスクの場合）
   - reward分のトークンを付与
   - トランザクション記録
6. 成功メッセージ返却
```

**エラーハンドリング**:
- 未承認: `422 Unprocessable Entity - タスクが承認されていません。`
- 既に完了済み: `422 Unprocessable Entity - 既に完了しています。`

---

## 9. 子ども向け画面（テーマ）仕様

### 9.1 概要

**目的**: 小学生など子どもユーザーが直感的に理解しやすい用語・表現に切り替えることで、タスク管理機能を親しみやすくする。

**判定方法**:
- `users.theme` カラムが `'child'` の場合に子ども向け表示
- `User::useChildTheme()` メソッドで判定
- `SetUserTheme` ミドルウェアで全ビューに `$isChildTheme` 変数を共有

**適用範囲**:
- タスク関連の全画面（一覧、詳細、作成、編集）
- サイドバー、ダッシュボード
- 通知メッセージ、メール文面

### 9.2 用語の置き換え

**タスク関連用語**:

| 大人向け表現 | 子ども向け表現 | 使用箇所 |
|------------|--------------|---------|
| タスク | やること | 画面タイトル、ボタン、説明文 |
| グループタスク | クエスト | タスクカード、一覧 |
| 承認待ち | チェック待ち | ステータス表示 |
| 完了済 | おわったよ！ | 完了タスクの表示 |
| 期限 | しめきり | タスクカード、フォーム |
| 優先度 | がんばりレベル | タスク作成・編集フォーム |
| 報酬 | ごほうび | グループタスクの報酬表示 |
| トークン | コイン | 残高表示、購入画面 |
| AI分解 | おてつだい | タスク分解機能 |
| 細分化 | もっとこまかく | 再分解機能 |

**ステータス・アクション用語**:

| 大人向け表現 | 子ども向け表現 | 使用箇所 |
|------------|--------------|---------|
| 作成する | つくる | ボタンラベル |
| 編集する | なおす | ボタンラベル |
| 削除する | けす | ボタンラベル |
| 完了する | おわった！ | 完了ボタン |
| 承認する | いいよ！ | 承認ボタン |
| 画像をアップロード | しゃしんをとる | 画像追加ボタン |

### 9.3 実装例

**Bladeテンプレート**:
```blade
{{-- タスク一覧のタイトル --}}
<h1>{{ $isChildTheme ? 'やること' : 'タスク一覧' }}</h1>

{{-- グループタスクの表示 --}}
<span>{{ $isChildTheme ? 'クエスト' : 'グループタスク' }}</span>

{{-- 報酬の表示 --}}
<span>{{ number_format($task->reward) }} {{ $isChildTheme ? 'コイン' : '円' }}</span>

{{-- 承認待ちステータス --}}
<span>{{ $isChildTheme ? 'チェック待ち' : '承認待ち' }}</span>

{{-- 完了済み表示 --}}
<span>{{ $isChildTheme ? 'おわったよ！' : '完了済' }}</span>

{{-- 期限表示 --}}
<span>{{ $isChildTheme ? 'しめきり' : '期限' }}: {{ $dueDate }}</span>
```

**通知メッセージ（Service層）**:
```php
// TaskManagementService::createTask
if ($member->useChildTheme()) {
    $message = "あたらしい「やること」ができたよ！がんばってね！";
} else {
    $message = "新しいタスクが割り当てられました。";
}

// グループタスク作成時
if ($assignedUser && $assignedUser->useChildTheme()) {
    $message = "あたらしい「クエスト」だよ！クリアすると {$reward} コインもらえるよ！";
} else {
    $message = "グループタスクが作成されました。報酬: {$reward}円";
}
```

**OpenAI APIプロンプト**:
```php
// OpenAIService::requestDecomposition
if ($user->useChildTheme()) {
    $systemPrompt = "あなたは、小学生のための「やること」をサポートする先生です。
                     むずかしいことばをつかわず、やさしくせつめいしてください。";
} else {
    $systemPrompt = "あなたは、タスク管理をサポートするAIアシスタントです。";
}
```

### 9.4 画面別の対応状況

**タスク一覧画面**:
- ✅ タスクカード内の用語切り替え
- ✅ サイドバーの「やること」表示
- ✅ ステータスバッジ（チェック待ち、おわったよ！）

**タスク作成・編集画面**:
- ✅ フォームラベル（しめきり、がんばりレベル）
- ✅ ボタンラベル（つくる、なおす、けす）
- ✅ AI分解ボタン（おてつだい）

**グループタスク画面**:
- ✅ クエスト表示
- ✅ ごほうび表示（コイン）
- ✅ チェック待ちステータス

**通知・メール**:
- ✅ タスク割当通知
- ✅ 承認完了通知
- ✅ 報酬付与通知

### 9.5 機能制限

**子ども向け画面で非表示にする機能**:

| 機能 | 理由 | 実装方法 |
|------|------|---------|
| トークン統計 | 複雑すぎる | `@if(!$isChildTheme)` で非表示 |
| プラン管理 | 保護者が管理すべき | サイドバーから除外 |
| 請求履歴 | 金銭情報の保護 | アクセス制限 |
| グループ設定 | 管理機能のため | 親のみアクセス可能 |
| AI設定詳細 | 技術的すぎる | 簡易表示のみ |

**実装例**:
```blade
{{-- サイドバー: 統計情報を非表示 --}}
@if(!$isChildTheme)
    <a href="{{ route('reports.index') }}">
        <span>トークン統計</span>
    </a>
@endif

{{-- プラン管理を非表示 --}}
@if(!$isChildTheme)
    <a href="{{ route('subscription.plans') }}">
        <span>プラン管理</span>
    </a>
@endif
```

### 9.6 バリデーションメッセージ

**子ども向けエラーメッセージ**:

| 大人向けメッセージ | 子ども向けメッセージ |
|------------------|---------------------|
| タスク名は必須です。 | 「やること」のなまえをかいてね！ |
| タスク名は255文字以内で入力してください。 | なまえがながすぎるよ！もっとみじかくしてね！ |
| 期間は必須です。 | いつまでにやるか、えらんでね！ |
| 優先度は1〜5の範囲で指定してください。 | がんばりレベルは1から5のなかからえらんでね！ |
| 画像のアップロードが必要です。 | しゃしんをわすれているよ！とってアップしてね！ |
| トークンが不足しています。 | コインがたりないよ！おうちのひとにたのんでみよう！ |

**実装方法**:
```php
// StoreTaskRequest::messages()
public function messages(): array
{
    $isChildTheme = Auth::user()?->useChildTheme() ?? false;
    
    if ($isChildTheme) {
        return [
            'title.required' => '「やること」のなまえをかいてね！',
            'title.max' => 'なまえがながすぎるよ！もっとみじかくしてね！',
            'span.required' => 'いつまでにやるか、えらんでね！',
            'priority.between' => 'がんばりレベルは1から5のなかからえらんでね！',
        ];
    }
    
    return [
        'title.required' => 'タスク名は必須です。',
        'title.max' => 'タスク名は255文字以内で入力してください。',
        'span.required' => '期間は必須です。',
        'priority.between' => '優先度は1〜5の範囲で指定してください。',
    ];
}
```

### 9.7 成功メッセージ

**リダイレクト時のフラッシュメッセージ**:

| 大人向けメッセージ | 子ども向けメッセージ |
|------------------|---------------------|
| タスクを作成しました。 | あたらしい「やること」をつくったよ！ |
| タスクを更新しました。 | 「やること」をなおしたよ！ |
| タスクを削除しました。 | 「やること」をけしたよ！ |
| タスクを完了しました。 | やったね！おわったよ！ |
| {count}件のタスクを作成しました。 | {count}このやることをつくったよ！ |
| タスクを承認しました。 | よくできたね！いいよ！ |
| {reward}トークンを獲得しました。 | {reward}コインをゲットしたよ！ |

**実装例**:
```php
// StoreTaskAction::__invoke
$message = $user->useChildTheme()
    ? 'あたらしい「やること」をつくったよ！'
    : 'タスクを作成しました。';

return redirect()->route('tasks.index')->with('success', $message);

// CompleteTaskAction::__invoke
$message = $user->useChildTheme()
    ? 'やったね！おわったよ！'
    : 'タスクを完了しました。';

return redirect()->route('tasks.show', $task)->with('success', $message);

// グループタスク報酬付与時
$message = $user->useChildTheme()
    ? "{$reward}コインをゲットしたよ！すごい！"
    : "{$reward}トークンを獲得しました。";
```

### 9.8 アバターコメント

**子ども向けアバターコメント**:

| イベント | 大人向けコメント | 子ども向けコメント |
|---------|----------------|-------------------|
| task_created | タスクを作成しました。頑張りましょう！ | あたらしいこと、がんばろうね！ |
| task_completed | 素晴らしいです！タスクを完了しましたね。 | やったね！すごいよ！ |
| task_decomposition | AIがタスクを分解しました。確認してください。 | こまかくしてみたよ！これならできるかな？ |
| group_task_assigned | グループタスクが割り当てられました。 | あたらしいクエストだよ！クリアできるかな？ |
| task_approved | タスクが承認されました。報酬を獲得です！ | よくできました！ごほうびだよ！ |

**実装箇所**: `GenerateAvatarImagesJob::getCommentTemplates()`

```php
if ($user->useChildTheme()) {
    $templates['task_created'] = [
        'あたらしいこと、がんばろうね！',
        'やることがふえたね！いっしょにがんばろう！',
        'できるかな？がんばって！',
    ];
    $templates['task_completed'] = [
        'やったね！すごいよ！',
        'おわったね！えらいえらい！',
        'よくできました！すばらしい！',
    ];
}
```

### 9.9 実装ガイドライン

**新規機能実装時のチェックリスト**:

- [ ] `$isChildTheme` 変数を受け取る
- [ ] 用語を条件分岐で切り替える
- [ ] エラーメッセージを子ども向けに用意
- [ ] 成功メッセージを子ども向けに用意
- [ ] 複雑な機能は子ども向けで非表示にする
- [ ] 通知・メール文面を分岐させる
- [ ] アバターコメントを分岐させる

**コーディング規約**:
```php
// ✅ OK: 三項演算子で簡潔に
$label = $isChildTheme ? 'やること' : 'タスク';

// ✅ OK: メソッドで分岐
$message = $user->useChildTheme()
    ? 'あたらしい「やること」をつくったよ！'
    : 'タスクを作成しました。';

// ✅ OK: Bladeで分岐
<h1>{{ $isChildTheme ? 'やること' : 'タスク一覧' }}</h1>

// ❌ NG: ハードコード（切り替えができない）
$label = 'タスク'; // 子ども向けに対応していない
```

**テスト実装**:
```php
// 子ども向け表示のテスト
test('子ども向けテーマで「やること」と表示される', function () {
    $user = User::factory()->create(['theme' => 'child']);
    
    $response = $this->actingAs($user)->get(route('tasks.index'));
    
    $response->assertSee('やること');
    $response->assertDontSee('タスク一覧');
});

// 大人向け表示のテスト
test('大人向けテーマで「タスク一覧」と表示される', function () {
    $user = User::factory()->create(['theme' => 'adult']);
    
    $response = $this->actingAs($user)->get(route('tasks.index'));
    
    $response->assertSee('タスク一覧');
    $response->assertDontSee('やること');
});
```

### 9.10 注意事項

**データベース保存値**:
- ⚠️ **データベースには大人向け用語で保存** - 表示時のみ切り替える
- 例: `tasks.priority` は 1-5 の数値で保存（表示時に「がんばりレベル」に変換）

**API レスポンス**:
- ⚠️ **APIレスポンスは大人向け用語** - フロントエンドで切り替え
- 理由: 外部システム連携、管理画面での一貫性確保

**検索・フィルタ**:
- ⚠️ **検索は大人向け用語でも動作させる** - 子どもが「タスク」と入力しても検索可能
- 実装: 検索クエリは正規化してから実行

**パフォーマンス**:
- ⚠️ **`useChildTheme()` の呼び出し最小化** - ビューで共有変数 `$isChildTheme` を活用
- 理由: 毎回メソッド呼び出しするとN+1問題のリスク

---

## 10. 非機能要件

### 10.1 パフォーマンス

**N+1問題対策**:
```php
// タスク一覧取得時は必ずEager Loadingを使用
Task::with(['user', 'tags', 'images', 'assignedBy', 'approvedBy'])
    ->where('user_id', $userId)
    ->get();
```

**キャッシュ戦略**（実装済み - 2025-12-05最適化）:
```php
// TaskListService.php にて実装済み
// タスク一覧をRedisキャッシュ（15分TTL）
Cache::tags(['dashboard', "user:{$userId}"])->remember(
    "dashboard:user:{$userId}:incomplete-tasks",
    now()->addMinutes(15),
    fn() => $this->fetchTasksFromDatabase($userId, $filters)
);

// タスク作成・更新・削除時にタグベースでキャッシュクリア
Cache::tags(['dashboard', "user:{$userId}", 'tasks'])->flush();
```

**データベース最適化**（実装済み - 2025-12-05）:
```sql
-- タスク一覧画面用の複合インデックス（最頻出クエリ）
CREATE INDEX idx_tasks_user_incomplete_due 
ON tasks(user_id, is_completed, due_date, priority);

-- ソフトデリート対応
CREATE INDEX idx_tasks_user_deleted 
ON tasks(user_id, deleted_at);

-- グループタスク検索用
CREATE INDEX idx_tasks_group_user 
ON tasks(group_task_id, user_id);

-- 完了タスク検索用（実績画面等で使用）
CREATE INDEX idx_tasks_user_completed 
ON tasks(user_id, is_completed, completed_at);
```

**クエリ最適化**（実装済み - 2025-12-05）:
```php
// TaskEloquentRepository.php - 必要なカラムのみ取得
Task::query()
    ->select([
        'id', 'user_id', 'title', 'description',
        'due_date', 'span', 'priority', 'is_completed',
        'completed_at', 'group_task_id', 'reward',
        'requires_approval', 'requires_image', 'approved_at',
        'assigned_by_user_id', 'approved_by_user_id',
        'source_proposal_id', 'created_at', 'updated_at', 'deleted_at'
    ])
    ->with(['tags', 'images'])
    ->where('user_id', $userId)
    ->get();
```

**パフォーマンス最適化の効果**（推定値）:
- 複合インデックス追加: 30-50% 高速化
- キャッシュTTL延長（5→15分）: 10-20% DB負荷軽減
- Select句最適化: 5-15% データ転送量削減

**今後の改善検討事項**:
- [ ] ページネーション導入（100件超のタスクに対応）
- [ ] 無限スクロール実装（UX改善）
- [ ] Redis接続プール最適化

### 10.2 セキュリティ

**認証・認可**:
- 全てのタスク操作は認証必須（`auth` middleware）
- 所有者確認: `task.user_id == auth()->id()`
- グループ権限確認: `GroupService::canEditGroup()`

**SQLインジェクション対策**:
- Eloquent ORMを使用（prepared statement自動適用）
- 生SQL使用時は必ずバインディング使用

**XSS対策**:
- Blade自動エスケープ（`{{ $variable }}`）
- ユーザー入力は常にバリデーション

**CSRF対策**:
- 全てのPOST/PUT/DELETEリクエストに`@csrf`トークン必須

### 10.3 トランザクション管理

**複数テーブル更新時は必ずトランザクション**:
```php
DB::transaction(function () use ($data) {
    $task = Task::create($data);
    $task->tags()->attach($tagIds);
    $this->tokenService->consumeTokens($user, 50000);
});
```

**トークン操作は常にトランザクション内**:
```php
DB::transaction(function () use ($user, $amount) {
    $balance = TokenBalance::lockForUpdate()->where('user_id', $user->id)->first();
    $balance->balance -= $amount;
    $balance->save();
    
    TokenTransaction::create([
        'user_id' => $user->id,
        'amount' => -$amount,
        'type' => 'consume',
    ]);
});
```

### 10.4 エラーログ

**重要な操作は必ずログ記録**:
```php
Log::info('タスク作成', [
    'user_id' => $user->id,
    'task_id' => $task->id,
    'is_group_task' => $isGroupTask,
]);

Log::error('タスク作成エラー', [
    'user_id' => $user->id,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

---

## 11. テスト要件

### 10.1 テストカバレッジ

**実装済みテスト**: 93 tests, 348 assertions（2025-12-05時点）

**テストファイル**:
1. `StoreTaskTest.php` - 19 tests（通常タスク登録）
2. `TaskDecompositionTest.php` - 20 tests（AI分解）
3. `GroupTaskTest.php` - 16 tests（グループタスク）
4. `DeleteTaskTest.php` - 12 tests（削除）
5. `UpdateTaskTest.php` - 22 tests（更新）
6. `StoreTaskSimpleTest.php` - 5 tests（簡易版）

**テストカテゴリ**:
- ✅ 正常系: 基本機能、全項目指定、エッジケース
- ✅ 異常系: バリデーションエラー、権限エラー
- ✅ 権限チェック: 未認証、他人のタスク、グループ権限

### 10.2 テスト実行方法

**基本実行**:
```bash
cd /home/ktr/mtdev
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Task/
```

**環境変数の説明**:
- `CACHE_STORE=array`: Redis接続回避（インメモリキャッシュ）
- `DB_HOST=localhost`: ホスト側からPostgreSQL接続
- `DB_PORT=5432`: PostgreSQLポート

**カバレッジレポート**:
```bash
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Task/ --coverage
```

### 10.3 テストデータ注意事項

**span値の明示的指定**:
```php
// ❌ NG: TaskFactoryはspan=1-30のランダム値を生成
$task = Task::factory()->create();

// ✅ OK: 明示的にspan=1を指定
$task = Task::factory()->create(['span' => 1]);
```

**due_date形式**:
```php
// span=1: 年月日のみ
'due_date' => '2025-12-20'

// span=2: 年月
'due_date' => '2025-12'

// span=3: 任意文字列
'due_date' => '来年の春頃'
```

**Storage fake使用**:
```php
Storage::fake('s3');
// ... 画像操作 ...
Storage::disk('s3')->assertExists($filePath);
```

---

## 11. 今後の拡張予定

### 11.1 実装予定機能

**高優先度**:
- [ ] タスクテンプレート機能（定型タスクの保存・再利用）
- [ ] サブタスク機能（親子関係のタスク管理）
- [ ] タスク依存関係（先行タスク完了後に開始）
- [ ] タスク繰り返し設定（日次・週次・月次）

**中優先度**:
- [ ] タスクコメント機能（メンバー間のコミュニケーション）
- [ ] タスク履歴表示（変更履歴の追跡）
- [ ] タスク検索・フィルタ強化（複数条件、保存済み検索）
- [ ] タスク統計ダッシュボード（完了率、平均所要時間）

**低優先度**:
- [ ] タスクエクスポート（CSV, PDF）
- [ ] タスクインポート（CSV, Excel）
- [ ] 外部カレンダー連携（Google Calendar, Outlook）
- [ ] Slack/Discord通知連携

### 11.2 パフォーマンス改善

**実装済み最適化**（2025-12-05）:
- ✅ 複合インデックス追加（4種類のインデックス）
- ✅ Redisキャッシュ導入（15分TTL、タグベース無効化）
- ✅ Redis接続プール最適化（永続的接続、タイムアウト設定）
- ✅ Select句最適化（必要なカラムのみ取得）
- ✅ Eager Loading（N+1問題回避）
- ✅ 無限スクロール実装（ページネーションAPI、1ページ50件）

**今後の検討事項**:
- [ ] タスク一覧の完全ページネーション対応（現在の通常画面は全件取得）
- [ ] Redis接続プール詳細チューニング（maxclients, timeout）
- [ ] 非同期処理の拡大（画像処理、通知送信）

**パフォーマンス目標**:
- タスク一覧表示: 200ms以内（1000件のタスク）
- タスク作成: 500ms以内（AI分解なし）
- AI分解: 5秒以内（OpenAI API依存）
- 画像アップロード: 2秒以内（5MBファイル）
- 無限スクロール読み込み: 300ms以内（50件単位）

### 11.3 無限スクロール機能（実装済み - 2025-12-05）

**概要**: スクロール操作だけで自動的に次のタスクを読み込む機能

**実装内容**:
```
バックエンド:
- TaskListServiceInterface::getTasksForUserPaginated() - ページネーション用メソッド
- GetTasksPaginatedApiAction - APIエンドポイント
- ルート: GET /api/tasks/paginated, GET /api/v1/tasks/paginated

フロントエンド:
- resources/js/infinite-scroll.js - InfiniteScrollManagerクラス
- 設定可能パラメータ: perPage（デフォルト50件）, threshold（デフォルト200px）

レスポンス形式:
{
  "success": true,
  "data": {
    "tasks": [...],
    "pagination": {
      "current_page": 1,
      "next_page": 2,
      "has_more": true,
      "per_page": 50
    }
  }
}
```

**使用方法**:
```javascript
// 初期化
const scrollManager = new InfiniteScrollManager({
    apiEndpoint: '/api/tasks/paginated',
    container: document.getElementById('task-list'),
    loadingElement: document.getElementById('loading-indicator'),
    perPage: 50,
    threshold: 200
});

// フィルター設定
scrollManager.setFilters({
    priority: 1,
    search: 'テスト'
});
```

**キャッシュ戦略**:
- ページごとにキャッシュ（キーパターン: `dashboard:user:{$userId}:tasks:page:{$page}:perpage:{$perPage}`）
- TTL: 15分
- フィルター適用時はキャッシュバイパス
- タスク更新時はタグベースで一括無効化

**テスト**:
- テストファイル: `tests/Feature/Task/InfiniteScrollTest.php`
- テスト数: 9 tests, 116 assertions
- カバレッジ: ページネーション、フィルタリング、エラーハンドリング、データ構造

---

## 12. 参考資料

**関連ドキュメント**:
- `docs/reports/2025-12-05-task-feature-test-completion-report.md` - テスト実装完了レポート
- `definitions/AvatarDefinition.md` - 教師アバター機能要件定義
- `definitions/ScheduleGroupTask.md` - スケジュール実行機能要件定義
- `definitions/TESTING.md` - テスト実行ガイド

**主要ファイル**:
- Action: `app/Http/Actions/Task/`
- Service: `app/Services/Task/`
- Repository: `app/Repositories/Task/`
- Model: `app/Models/Task.php`
- Migration: `database/migrations/2025_10_27_135127_tasks.php`
- Test: `tests/Feature/Task/`

**外部API**:
- OpenAI API: https://platform.openai.com/docs/api-reference
- Replicate API: https://replicate.com/docs (アバター生成用)

---

## 付録A: バリデーションルール一覧

### 通常タスク

| フィールド | ルール | エラーメッセージ |
|-----------|--------|----------------|
| title | required, string, max:255 | タスク名は必須です。／タスク名は255文字以内で入力してください。 |
| description | nullable, string | - |
| span | required, integer, in:1,2,3 | 期間は必須です。／期間は短期、中期、長期のいずれかを選択してください。 |
| due_date | nullable, string | - |
| priority | nullable, integer, between:1,5 | 優先度は1〜5の範囲で指定してください。 |
| tags | nullable, array | タグは配列形式で指定してください。 |
| tags.* | string, max:50 | タグは文字列で指定してください。／タグは50文字以内で入力してください。 |

### グループタスク（追加項目）

| フィールド | ルール | エラーメッセージ |
|-----------|--------|----------------|
| assigned_user_id | nullable, integer, exists:users,id | 指定されたユーザーが見つかりません。 |
| reward | required, integer, min:0 | 報酬は必須です。／報酬は0円以上で指定してください。 |
| requires_approval | nullable, boolean | - |
| requires_image | nullable, boolean | - |

### 画像アップロード

| フィールド | ルール | エラーメッセージ |
|-----------|--------|----------------|
| image | required, image, mimes:jpeg,png,jpg, max:5120 | 画像は必須です。／画像形式はJPG、PNGのみです。／画像サイズは5MB以内にしてください。 |

---

## 付録B: HTTPステータスコード一覧

| ステータス | 説明 | 使用ケース |
|-----------|------|-----------|
| 200 OK | 成功 | タスク一覧表示、詳細表示 |
| 201 Created | 作成成功 | （API使用時のみ） |
| 302 Found | リダイレクト | 作成・更新・削除後の遷移 |
| 401 Unauthorized | 未認証 | ログインしていない |
| 403 Forbidden | 権限なし | 他人のタスク操作、グループ権限不足 |
| 404 Not Found | 存在しない | タスクが見つからない、削除済み |
| 422 Unprocessable Entity | バリデーションエラー | 入力値不正 |
| 500 Internal Server Error | サーバーエラー | 予期しないエラー、OpenAI APIエラー |

---

**以上**
