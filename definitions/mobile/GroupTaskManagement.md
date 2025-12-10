# グループタスク管理機能 要件定義書（モバイルアプリ）

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-10 | GitHub Copilot | 初版作成: グループタスク編集・削除機能（モバイル版） |

---

## 1. 概要

### 1.1 目的

モバイルアプリにおいて、ログインユーザーが作成したグループタスクのうち、未完了または完了済未承認のタスクを編集・削除できる管理画面を提供します。

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

### 2.2 グループタスクの構造

グループタスクは`group_task_id`（UUID）で複数のタスクがグループ化されています。

**編集・削除の単位**: `group_task_id`単位（グループ全体を一括操作）

---

## 3. 画面仕様

### 3.1 ナビゲーション

**ハンバーガーメニューからアクセス**

```tsx
// DrawerNavigator.tsx
<Drawer.Screen
  name="GroupTaskManagement"
  component={GroupTaskManagementScreen}
  options={{
    title: 'グループタスク管理',
    drawerIcon: ({ color, size }) => (
      <Ionicons name="people-outline" size={size} color={color} />
    ),
  }}
/>
```

**メニュー表示条件**:
```tsx
// DrawerContent.tsx
{user?.group_edit_flg || user?.group?.master_user_id === user?.id ? (
  <DrawerItem
    label="グループタスク管理"
    onPress={() => navigation.navigate('GroupTaskManagement')}
    icon={({ color, size }) => (
      <Ionicons name="people-outline" size={size} color={color} />
    )}
  />
) : null}
```

### 3.2 グループタスク管理画面（GroupTaskManagementScreen）

#### 3.2.1 画面構成

```
┌─────────────────────────────────┐
│ ← グループタスク管理        🔔 │ ← ヘッダー
├─────────────────────────────────┤
│ 🔍 検索欄                       │ ← 検索バー
├─────────────────────────────────┤
│ [フィルタ: すべて ▼]           │ ← フィルタボタン
├─────────────────────────────────┤
│ ┌─────────────────────────────┐ │
│ │ 📋 数学の宿題              │ │
│ │ 報酬: 1000トークン         │ │
│ │ 期限: 2025-12-20           │ │
│ │ 割当: 5人                  │ │
│ │ [編集] [削除]              │ │
│ └─────────────────────────────┘ │
│ ┌─────────────────────────────┐ │
│ │ 📋 英語の課題              │ │
│ │ ...                        │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

#### 3.2.2 カード表示形式

各グループタスクはカード形式で表示:

```tsx
<View style={styles.card}>
  <View style={styles.cardHeader}>
    <Ionicons name="people" size={24} color="#9333EA" />
    <Text style={styles.title}>{task.title}</Text>
  </View>
  
  <Text style={styles.description} numberOfLines={2}>
    {task.description}
  </Text>
  
  <View style={styles.infoRow}>
    <View style={styles.infoItem}>
      <Ionicons name="gift-outline" size={16} />
      <Text>{task.reward}トークン</Text>
    </View>
    <View style={styles.infoItem}>
      <Ionicons name="calendar-outline" size={16} />
      <Text>{formatDate(task.due_date)}</Text>
    </View>
    <View style={styles.infoItem}>
      <Ionicons name="people-outline" size={16} />
      <Text>{task.assigned_count}人</Text>
    </View>
  </View>
  
  <View style={styles.actions}>
    <TouchableOpacity style={styles.editButton}>
      <Text>編集</Text>
    </TouchableOpacity>
    <TouchableOpacity style={styles.deleteButton}>
      <Text>削除</Text>
    </TouchableOpacity>
  </View>
</View>
```

---

## 4. レスポンシブ対応

### 4.1 参照ドキュメント

- `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md`
- `/home/ktr/mtdev/docs/mobile/mobile-rules.md`

### 4.2 デバイスサイズ別調整

```typescript
// responsive.ts使用
const { width, deviceSize } = useResponsive();

const styles = createStyles(width);

function createStyles(width: number) {
  const fontSize = getAdultFontSize(16, width);
  const spacing = getSpacing(16, width);
  
  return StyleSheet.create({
    card: {
      padding: spacing,
      marginBottom: getSpacing(12, width),
      backgroundColor: '#FFFFFF',
      borderRadius: 12,
      shadowColor: '#000',
      shadowOffset: { width: 0, height: 2 },
      shadowOpacity: 0.1,
      shadowRadius: 4,
      elevation: 2,
    },
    title: {
      fontSize: fontSize * 1.125, // 18px相当
      fontWeight: '600',
      color: '#1F2937',
    },
    description: {
      fontSize: fontSize * 0.875, // 14px相当
      color: '#6B7280',
      marginTop: getSpacing(8, width),
    },
    // ...
  });
}
```

### 4.3 ブレークポイント別レイアウト

| デバイス | カラム | パディング | フォント |
|---------|-------|----------|---------|
| 超小型（〜320px） | 1 | 0.75x | 0.80x |
| 小型（321-374px） | 1 | 0.85x | 0.90x |
| 標準（375-413px） | 1 | 1.00x | 1.00x |
| 大型（414-767px） | 1 | 1.10x | 1.05x |
| タブレット（768px〜） | 2 | 1.30x | 1.15x |

---

## 5. 編集機能

### 5.1 画面遷移

```
GroupTaskManagementScreen
  ↓ [編集]ボタン押下
GroupTaskEditScreen
  ↓ [保存]ボタン押下
API: PUT /api/group-tasks/{group_task_id}
  ↓ 成功
GroupTaskManagementScreen（更新された一覧）
```

### 5.2 編集画面（GroupTaskEditScreen）

**編集可能項目**:
- タスクタイトル
- タスク説明
- 期間（短期・中期・長期）
- 期限
- 優先度
- 報酬トークン数
- タグ
- 承認要否フラグ
- 画像必須フラグ

**編集対象外**:
- 割り当てメンバー（表示のみ）

### 5.3 API仕様

**エンドポイント**: `PUT /api/group-tasks/{group_task_id}`

**リクエストボディ**:
```json
{
  "title": "更新されたタスク名",
  "description": "更新された説明",
  "span": 1,
  "due_date": "2025-12-31",
  "priority": 3,
  "reward": 1000,
  "tags": ["数学", "宿題"],
  "requires_approval": true,
  "requires_image": false
}
```

**レスポンス**:
```json
{
  "success": true,
  "message": "グループタスクを更新しました",
  "data": {
    "updated_count": 5
  }
}
```

### 5.4 バリデーション

```typescript
const schema = z.object({
  title: z.string().min(1).max(255),
  description: z.string().optional(),
  span: z.enum(['1', '2', '3']),
  due_date: z.string().optional(),
  priority: z.number().min(1).max(5).optional(),
  reward: z.number().min(0).optional(),
  tags: z.array(z.string().max(50)).optional(),
  requires_approval: z.boolean().optional(),
  requires_image: z.boolean().optional(),
});
```

---

## 6. 削除機能

### 6.1 削除フロー

```
1. [削除]ボタン押下
2. 確認アラート表示
   "「{タスク名}」と関連する全メンバーのタスク（{割当人数}件）を削除します。
    この操作は取り消せません。本当に削除しますか?"
3. [削除する]ボタン押下
4. API: DELETE /api/group-tasks/{group_task_id}
5. 成功メッセージ表示
6. 一覧画面に戻る（削除されたタスクは非表示）
```

### 6.2 確認アラート

```tsx
Alert.alert(
  'グループタスクの削除',
  `「${task.title}」と関連する全メンバーのタスク（${task.assigned_count}件）を削除します。\nこの操作は取り消せません。本当に削除しますか?`,
  [
    {
      text: 'キャンセル',
      style: 'cancel',
    },
    {
      text: '削除する',
      style: 'destructive',
      onPress: () => handleDelete(task.group_task_id),
    },
  ]
);
```

### 6.3 API仕様

**エンドポイント**: `DELETE /api/group-tasks/{group_task_id}`

**レスポンス**:
```json
{
  "success": true,
  "message": "5件のタスクを削除しました",
  "data": {
    "deleted_count": 5
  }
}
```

---

## 7. 実装ファイル構成

### 7.1 画面コンポーネント

```
mobile/src/screens/group-tasks/
├── GroupTaskManagementScreen.tsx  # 一覧画面
├── GroupTaskEditScreen.tsx        # 編集画面
└── components/
    ├── GroupTaskCard.tsx          # タスクカード
    ├── GroupTaskFilters.tsx       # フィルタコンポーネント
    └── DeleteConfirmModal.tsx     # 削除確認モーダル
```

### 7.2 Service層

```typescript
// mobile/src/services/groupTask.service.ts
export interface GroupTaskService {
  /**
   * 編集可能なグループタスク一覧を取得
   */
  getEditableGroupTasks(filters?: GroupTaskFilters): Promise<GroupTask[]>;
  
  /**
   * 特定のグループタスクを取得
   */
  getGroupTaskById(groupTaskId: string): Promise<GroupTask>;
  
  /**
   * グループタスクを更新
   */
  updateGroupTask(groupTaskId: string, data: UpdateGroupTaskData): Promise<UpdateResult>;
  
  /**
   * グループタスクを削除
   */
  deleteGroupTask(groupTaskId: string): Promise<DeleteResult>;
}
```

### 7.3 Hook層

```typescript
// mobile/src/hooks/useGroupTasks.ts
export function useGroupTasks() {
  const [tasks, setTasks] = useState<GroupTask[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  
  const fetchTasks = async (filters?: GroupTaskFilters) => {
    // 一覧取得処理
  };
  
  const updateTask = async (groupTaskId: string, data: UpdateGroupTaskData) => {
    // 更新処理
  };
  
  const deleteTask = async (groupTaskId: string) => {
    // 削除処理
  };
  
  return { tasks, loading, error, fetchTasks, updateTask, deleteTask };
}
```

### 7.4 型定義

```typescript
// mobile/src/types/groupTask.types.ts
export interface GroupTask {
  group_task_id: string;
  title: string;
  description: string | null;
  span: number;
  due_date: string | null;
  priority: number;
  reward: number | null;
  requires_approval: boolean;
  requires_image: boolean;
  assigned_count: number; // 割当人数
  tags: Tag[];
  created_at: string;
  updated_at: string;
}

export interface UpdateGroupTaskData {
  title: string;
  description?: string;
  span: number;
  due_date?: string;
  priority?: number;
  reward?: number;
  tags?: string[];
  requires_approval?: boolean;
  requires_image?: boolean;
}

export interface GroupTaskFilters {
  search?: string;
  due_date?: 'overdue' | 'this_week' | 'this_month' | 'all';
  reward_min?: number;
  reward_max?: number;
}
```

---

## 8. バックエンドAPI（Laravel）

### 8.1 ルート定義

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/group-tasks', ListGroupTasksApiAction::class);
    Route::get('/group-tasks/{group_task_id}', ShowGroupTaskApiAction::class);
    Route::put('/group-tasks/{group_task_id}', UpdateGroupTaskApiAction::class);
    Route::delete('/group-tasks/{group_task_id}', DestroyGroupTaskApiAction::class);
});
```

### 8.2 Action層

**一覧取得**: `App\Http\Actions\Api\GroupTask\ListGroupTasksApiAction`
```php
public function __invoke(Request $request): JsonResponse
{
    $user = $request->user();
    
    if (!$user->canEditGroup()) {
        return response()->json(['error' => '権限がありません'], 403);
    }
    
    $filters = $request->only(['search', 'due_date', 'reward_min', 'reward_max']);
    $groupTasks = $this->groupTaskService->getEditableGroupTasks($user, $filters);
    
    return response()->json([
        'success' => true,
        'data' => $groupTasks,
    ]);
}
```

**更新**: `App\Http\Actions\Api\GroupTask\UpdateGroupTaskApiAction`
```php
public function __invoke(UpdateGroupTaskRequest $request, string $groupTaskId): JsonResponse
{
    $user = $request->user();
    
    if (!$user->canEditGroup()) {
        return response()->json(['error' => '権限がありません'], 403);
    }
    
    try {
        $updatedCount = $this->groupTaskService->updateGroupTask($user, $groupTaskId, $request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'グループタスクを更新しました',
            'data' => ['updated_count' => $updatedCount],
        ]);
    } catch (\Exception $e) {
        Log::error('グループタスク更新エラー', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'グループタスクの更新に失敗しました'], 500);
    }
}
```

**削除**: `App\Http\Actions\Api\GroupTask\DestroyGroupTaskApiAction`
```php
public function __invoke(Request $request, string $groupTaskId): JsonResponse
{
    $user = $request->user();
    
    if (!$user->canEditGroup()) {
        return response()->json(['error' => '権限がありません'], 403);
    }
    
    try {
        $deletedCount = $this->groupTaskService->deleteGroupTask($user, $groupTaskId);
        
        return response()->json([
            'success' => true,
            'message' => "{$deletedCount}件のタスクを削除しました",
            'data' => ['deleted_count' => $deletedCount],
        ]);
    } catch (\Exception $e) {
        Log::error('グループタスク削除エラー', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'グループタスクの削除に失敗しました'], 500);
    }
}
```

---

## 9. テスト仕様

### 9.1 フロントエンドテスト

**GroupTaskManagementScreen.test.tsx**:
```typescript
describe('GroupTaskManagementScreen', () => {
  it('✅ 権限のあるユーザーは一覧画面にアクセスできる', async () => {
    // テスト内容
  });
  
  it('✅ グループタスクが正しく表示される', async () => {
    // テスト内容
  });
  
  it('✅ 検索フィルタが正しく動作する', async () => {
    // テスト内容
  });
  
  it('✅ 編集ボタン押下で編集画面に遷移する', async () => {
    // テスト内容
  });
  
  it('✅ 削除ボタン押下で確認アラートが表示される', async () => {
    // テスト内容
  });
});
```

**GroupTaskEditScreen.test.tsx**:
```typescript
describe('GroupTaskEditScreen', () => {
  it('✅ タスク情報が正しく表示される', async () => {
    // テスト内容
  });
  
  it('✅ バリデーションエラーが正しく表示される', async () => {
    // テスト内容
  });
  
  it('✅ 保存ボタン押下でAPIが呼ばれる', async () => {
    // テスト内容
  });
});
```

### 9.2 バックエンドテスト

**Feature/Api/GroupTask/ListGroupTasksApiTest**:
- ✅ 権限のあるユーザーは一覧を取得できる
- ✅ 権限のないユーザーは403エラーになる
- ✅ フィルタが正しく適用される

**Feature/Api/GroupTask/UpdateGroupTaskApiTest**:
- ✅ グループタスクを正しく更新できる
- ✅ バリデーションエラーが正しく返される
- ✅ 権限のないユーザーは403エラーになる

**Feature/Api/GroupTask/DestroyGroupTaskApiTest**:
- ✅ グループタスクを正しく削除できる
- ✅ 論理削除が正しく実行される
- ✅ 権限のないユーザーは403エラーになる

---

## 10. セキュリティ考慮事項

### 10.1 認証・認可

- Sanctum tokenによる認証
- `canEditGroup()`メソッドによる権限チェック
- `assigned_by_user_id`の一致確認

### 10.2 データ保護

- HTTPS通信（本番環境）
- トークンのSecure Storage保存
- APIレスポンスの最小化（必要なデータのみ）

---

## 11. パフォーマンス最適化

### 11.1 キャッシュ戦略

- React Queryによるキャッシュ管理
- 一覧データの自動再取得（staleTime: 5分）
- Optimistic Update（楽観的更新）

### 11.2 遅延読み込み

- FlatListによる仮想スクロール
- 画像の遅延読み込み
- ページネーション（無限スクロール）

---

## 12. エラーハンドリング

### 12.1 ネットワークエラー

```typescript
try {
  await groupTaskService.updateGroupTask(id, data);
} catch (error) {
  if (error.code === 'NETWORK_ERROR') {
    Alert.alert('ネットワークエラー', 'インターネット接続を確認してください');
  } else if (error.code === 'TIMEOUT') {
    Alert.alert('タイムアウト', 'サーバーへの接続がタイムアウトしました');
  } else {
    Alert.alert('エラー', 'グループタスクの更新に失敗しました');
  }
}
```

### 12.2 権限エラー

```typescript
if (response.status === 403) {
  Alert.alert('権限エラー', 'この操作を実行する権限がありません');
  navigation.navigate('TaskList');
}
```

---

## 13. 関連ドキュメント

| ドキュメント | パス |
|------------|------|
| モバイル開発規則 | `/home/ktr/mtdev/docs/mobile/mobile-rules.md` |
| レスポンシブガイドライン | `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md` |
| タスク要件定義書 | `/home/ktr/mtdev/definitions/Task.md` |
| プロジェクト規約 | `/home/ktr/mtdev/.github/copilot-instructions.md` |
