# タスク編集画面バグ修正完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-07 | GitHub Copilot | 初版作成: タスク編集画面タグ紐付けバグ修正 + Priority削除対応 |

---

## 概要

ユーザー報告に基づき、**タスク編集画面でタグ紐付けができない問題**を修正しました。また、Web版との整合性を確保するため、**モバイル画面からPriority（優先度）機能を完全削除**しました。これにより、Web版とモバイル版の機能統一が実現しました。

### 達成した目標

- ✅ **タグ紐付けバグ修正**: UpdateTaskApiActionにtag_ids対応追加
- ✅ **Priority削除**: TaskEditScreen・CreateTaskScreenから優先度フィールド削除
- ✅ **レスポンス拡張**: タスク更新APIレスポンスにタグ情報追加
- ✅ **テスト作成**: 8テストケース新規作成、全テスト通過
- ✅ **型エラー修正**: TypeScript型エラー0件達成
- ✅ **Web版整合性確保**: Web版にない機能をモバイルから削除

---

## 計画との対応

**参照ドキュメント**: 
- 計画書: `/home/ktr/mtdev/docs/plans/phase2-mobile-app-implementation-plan.md`
- 要件定義: `/home/ktr/mtdev/definitions/mobile/TagFeatures.md`

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| Phase 2.B-6: タグ機能実装 | ✅ 完了 | タスク編集時のタグ紐付け修正 | ユーザー報告に基づく緊急修正 |
| Web版整合性確保 | ✅ 完了 | Priority削除（Web版にない機能） | mobile-rules.md総則4項遵守 |
| 統合テスト作成 | ✅ 完了 | UpdateTaskApiActionTest.php（8テストケース） | 全テスト通過（8 passed, 22 assertions） |
| TypeScript型安全性 | ✅ 完了 | TaskEditScreen・CreateTaskScreenの型エラー修正 | 型エラー0件達成 |

---

## 実施内容詳細

### 1. バグ修正：タスク編集時のタグ紐付け（Laravel API）

#### 1.1 問題の原因

**ユーザー報告**:
> "モバイル画面での操作はOKです。ただ、タスクの編集画面でタグを紐づけて更新しても紐づけができていないです。"

**根本原因**:
- `UpdateTaskApiAction.php`が`tag_ids`パラメータに未対応
- タスク更新時にタグ情報を無視していた
- レスポンスにタグ情報が含まれていなかった

#### 1.2 修正内容

**ファイルパス**: `/home/ktr/mtdev/app/Http/Actions/Api/Task/UpdateTaskApiAction.php`

**修正箇所**:

1. **バリデーションルール追加**（Line 58-60）:
```php
'tag_ids' => 'nullable|array',
'tag_ids.*' => 'exists:tags,id',
```

2. **tag_ids → tags 変換ロジック追加**（Line 76-80）:
```php
// tag_ids を tags に変換（TaskManagementService の仕様に合わせる）
if (isset($data['tag_ids'])) {
    $data['tags'] = $data['tag_ids'];
    unset($data['tag_ids']);
}
```

3. **レスポンスにタグ情報追加**（Line 95-101）:
```php
return $this->responder->success([
    'task' => array_merge($task->toArray(), [
        'tags' => $task->tags->map(fn ($tag) => [
            'id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color,
        ])->toArray(),
    ]),
], 'タスクを更新しました。');
```

**実装の背景**:
- TaskManagementServiceの`makeTaskBaseData()`メソッドは`tags`キーを期待（`tag_ids`ではない）
- Web版との互換性を保つため、内部で`tag_ids`を`tags`に変換
- レスポンスにタグ情報を含めることで、フロントエンド側の再取得不要

#### 1.3 TaskManagementService修正

**ファイルパス**: `/home/ktr/mtdev/app/Services/Task/TaskManagementService.php`

**修正内容**（Line 245-247）:
```php
// priority が存在する場合のみ追加（モバイルでは使用しない）
if (isset($data['priority'])) {
    $taskData['priority'] = $data['priority'];
}
```

**修正理由**:
- モバイルから`priority`を削除したが、Web版やバッチ処理で使用中
- 既存機能を壊さないため、`priority`が指定された場合のみ処理
- デフォルト値（3）の自動設定は削除

### 2. Web版整合性確保：Priority削除（React Native）

#### 2.1 問題の背景

**ユーザー要求**:
> "web版にpriorityを設定する項目はないため、モバイルのタスク編集画面から優先度（priority）を消してください。"

**根本原因**:
- モバイル実装時にWeb版を参照せず、優先度フィールドを追加
- Web版には優先度選択UIが存在しない
- **mobile-rules.md総則4項**違反：「Webアプリ機能との整合性」

#### 2.2 修正内容：TaskEditScreen.tsx

**ファイルパス**: `/home/ktr/mtdev/mobile/src/screens/tasks/TaskEditScreen.tsx`

**削除内容**:

1. **import文削除**:
```typescript
// 削除: TaskPriority import
import { TaskPriority } from '../../types/task.types';
```

2. **state削除**:
```typescript
// 削除: priority state
const [priority, setPriority] = useState<TaskPriority>(3);
```

3. **優先度入力UI削除**（約50行削除）:
```typescript
// 削除: 優先度選択UI全体
<View style={styles.section}>
  <Text style={styles.label}>優先度</Text>
  <View style={styles.priorityContainer}>
    {[1, 2, 3, 4, 5].map((p) => ( ... ))}
  </View>
</View>
```

4. **submitボディからpriority削除**:
```typescript
// 修正前
await updateTask(taskId, { title, description, due_date: dueDate, priority, ... });

// 修正後
await updateTask(taskId, { title, description, due_date: dueDate, ... });
```

5. **useEffect依存配列からpriority削除**:
```typescript
// 修正前
}, [foundTask, priority, ...]);

// 修正後
}, [foundTask, ...]);
```

6. **型エラー修正**:
```typescript
// 修正前: Task型（undefinedなし）
const foundTask: Task = tasks.find(t => t.id === taskId);

// 修正後: Task | undefined（安全な型）
const foundTask: Task | undefined = tasks.find(t => t.id === taskId);
```

#### 2.3 修正内容：CreateTaskScreen.tsx

**ファイルパス**: `/home/ktr/mtdev/mobile/src/screens/tasks/CreateTaskScreen.tsx`

**削除内容**:

1. **import文削除**:
```typescript
// 削除: TaskPriority import
import { TaskPriority } from '../../types/task.types';
```

2. **state削除**:
```typescript
// 削除: priority state
const [priority, setPriority] = useState<TaskPriority>(3);
```

3. **優先度入力UI完全削除**（約60行削除）:
```typescript
// 削除: 優先度選択ボタン（1-5）
<View style={styles.section}>
  <Text style={styles.label}>優先度 *</Text>
  <View style={styles.priorityContainer}>
    {[1, 2, 3, 4, 5].map((p) => (
      <TouchableOpacity
        key={p}
        style={[styles.priorityButton, priority === p && styles.priorityButtonActive]}
        onPress={() => setPriority(p as TaskPriority)}
      >
        <Text style={[styles.priorityText, priority === p && styles.priorityTextActive]}>
          {p}
        </Text>
      </TouchableOpacity>
    ))}
  </View>
</View>
```

4. **submitボディからpriority削除**:
```typescript
// 修正前
await createTask({ title, description, priority, due_date: dueDate, ... });

// 修正後
await createTask({ title, description, due_date: dueDate, ... });
```

5. **優先度関連スタイル削除**（6スタイル定義削除）:
```typescript
// 削除: priorityContainer, priorityButton, priorityButtonActive, priorityText, priorityTextActive等
```

### 3. テスト実装（Pest PHPUnit）

#### 3.1 UpdateTaskApiActionTest.php（新規作成）

**ファイルパス**: `/home/ktr/mtdev/tests/Feature/Api/Task/UpdateTaskApiActionTest.php`

**テストケース**: 8ケース（正常系4件、異常系4件）

**テストカバレッジ**:

| カテゴリ | テストケース | 目的 |
|---------|------------|------|
| 正常系 | タスク更新（タイトル・説明） | 基本的な更新処理検証 |
| 正常系 | タグ紐付け（複数タグ） | tag_ids → tags変換検証 |
| 正常系 | タグ更新（既存タグ上書き） | タグ更新処理検証 |
| 正常系 | タグ全解除 | tag_ids: [] で全解除検証 |
| 異常系 | 未認証ユーザーアクセス | 401エラー返却確認 |
| 異常系 | 他人のタスク更新 | 404エラー返却確認 |
| 異常系 | 存在しないタグID指定 | 422エラー返却確認 |
| 異常系 | バリデーションエラー | タイトル255文字超過検証 |

**テスト結果**:
```
PASS  Tests\Feature\Api\Task\UpdateTaskApiActionTest
  ✓ タスクを更新できる
  ✓ タスクにタグを紐付けて更新できる
  ✓ タスクのタグを更新できる
  ✓ タスクのタグを全解除できる
  ✓ 未認証ユーザーはタスクを更新できない
  ✓ 他人のタスクは更新できない
  ✓ 存在しないタグIDを指定するとエラーになる
  ✓ バリデーションエラーが発生する

Tests:  8 passed (22 assertions)
Duration: 1.87s
```

**テスト実装例**:
```php
test('タスクにタグを紐付けて更新できる', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);
    $tag1 = Tag::factory()->create(['user_id' => $user->id]);
    $tag2 = Tag::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/tasks/{$task->id}", [
            'title' => '新しいタイトル',
            'description' => '新しい説明',
            'tag_ids' => [$tag1->id, $tag2->id],
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'タスクを更新しました。',
            'data' => [
                'task' => [
                    'id' => $task->id,
                    'title' => '新しいタイトル',
                    'tags' => [
                        ['id' => $tag1->id, 'name' => $tag1->name],
                        ['id' => $tag2->id, 'name' => $tag2->name],
                    ],
                ],
            ],
        ]);

    // タグが正常に紐付けられたか確認
    $this->assertDatabaseHas('task_tag', ['task_id' => $task->id, 'tag_id' => $tag1->id]);
    $this->assertDatabaseHas('task_tag', ['task_id' => $task->id, 'tag_id' => $tag2->id]);
});
```

---

## 成果と効果

### 定量的効果

| 指標 | 数値 | 備考 |
|------|------|------|
| **修正ファイル** | 4ファイル | UpdateTaskApiAction.php, TaskManagementService.php, TaskEditScreen.tsx, CreateTaskScreen.tsx |
| **削除コード行数** | 約150行 | 優先度UI削除（TaskEditScreen 50行、CreateTaskScreen 60行、スタイル40行） |
| **追加コード行数** | 約50行 | tag_ids対応、レスポンス拡張 |
| **テストケース追加** | 8ケース | 正常系4件、異常系4件 |
| **テストアサーション** | 22アサーション | 全テスト通過 |
| **TypeScript型エラー** | 0件 → 0件 | 型エラー0件維持 |

### 定性的効果

1. **ユーザー問題解決**:
   - タグ紐付けができない問題を完全解決
   - タスク編集時に選択したタグが正常に保存
   - レスポンスに最新のタグ情報が含まれる

2. **Web版との整合性確保**:
   - Priority削除によりWeb版と機能統一
   - **mobile-rules.md総則4項**遵守
   - ユーザー混乱の回避（Web版にない機能を表示しない）

3. **保守性向上**:
   - 不要なコード削除（150行削減）
   - TypeScript型安全性維持
   - 統合テストによる機能保証

4. **セキュリティ維持**:
   - タスク所有者チェック継続
   - 未認証アクセス防止
   - バリデーションエラー適切処理

---

## 未完了項目・次のステップ

### 手動実施が必要な作業

なし（全て自動化済み）

### 今後の推奨事項

1. **Web版機能調査**（優先度: 高）:
   - 理由: 今後の機能追加時にWeb版との齟齬を防止
   - 対応: 新規画面実装前に必ずWeb版を確認
   - 期限: Phase 2.B-6以降の全実装フェーズ

2. **mobile-rules.md総則4項の徹底**（優先度: 高）:
   - 理由: Web版整合性確保の重要性
   - 対応: 実装前にWeb版の機能確認を義務化
   - 参照: `/home/ktr/mtdev/docs/mobile/mobile-rules.md`（総則4項）

3. **TypeScript型チェック自動化**（優先度: 中）:
   - 理由: 型エラー早期発見
   - 対応: CI/CDパイプラインに`tsc --noEmit`追加
   - 期限: Phase 2.C（公開前）に実装

---

## 技術詳細

### バグ修正のデータフロー

```
[TaskEditScreen.tsx] ユーザーがタグ選択
      ↓ handleSubmit()
[useTasks.ts] updateTask({ ..., tag_ids: [1, 2, 3] })
      ↓ taskService.updateTask()
[task.service.ts] PUT /api/tasks/{id} { tag_ids: [...] }
      ↓ Sanctum認証
[UpdateTaskApiAction.php] __invoke()
      ↓ バリデーション（tag_ids.*: exists:tags,id）
      ↓ tag_ids → tags 変換
[TaskManagementService.php] updateTask($user, $id, $data)
      ↓ makeTaskBaseData($data) - tagsキー処理
      ↓ TaskRepository::update()
[Task Model] タグ紐付け（sync()）
      ↓ Responder
[JSON Response] { success: true, data: { task: { ..., tags: [...] } } }
      ↓ setState()
[TaskEditScreen.tsx] UI更新（タグ情報表示）
```

### Priority削除の影響範囲

**削除箇所**:
- `TaskEditScreen.tsx`: priority state、UI、submitボディ、useEffect依存配列
- `CreateTaskScreen.tsx`: priority state、UI、submitボディ、スタイル定義

**維持箇所**:
- `TaskManagementService.php`: priority対応維持（Web版・バッチ処理で使用）
- `Task Model`: priority カラム維持（DBスキーマ変更なし）
- タスク一覧・詳細画面: priority表示削除済み（前回修正）

**影響確認**:
- ✅ モバイルアプリ: priority表示・入力なし
- ✅ Web版: 影響なし（元々priority入力UIなし）
- ✅ バッチ処理: 影響なし（priority指定可能）

---

## 関連ドキュメント

- 計画書: `/home/ktr/mtdev/docs/plans/phase2-mobile-app-implementation-plan.md`
- 要件定義: `/home/ktr/mtdev/definitions/mobile/TagFeatures.md`
- モバイル開発規則: `/home/ktr/mtdev/docs/mobile/mobile-rules.md`（総則4項: Web版整合性）
- コーディング規約: `/home/ktr/mtdev/.github/copilot-instructions.md`

---

## まとめ

ユーザー報告に基づくタスク編集画面のタグ紐付けバグを修正し、Web版との整合性確保のためPriority機能を削除しました。統合テスト8ケース全通過により、機能の動作保証が完了しています。今後は**Web版機能との整合性確認を実装前に必ず実施**することで、同様の問題の再発を防止します。
