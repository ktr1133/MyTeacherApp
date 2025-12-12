# モバイルアプリ不具合修正完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-08 | GitHub Copilot | 初版作成: モバイルアプリの4件の不具合修正完了 |

## 概要

MyTeacherモバイルアプリケーション（React Native + Expo）において発生していた以下の不具合を修正しました：

- ✅ **不具合1**: グループ管理画面でグループ名が表示されない
- ✅ **不具合2**: スケジュールタスク編集画面で更新エラー発生
- ✅ **不具合3**: スケジュールタスク編集画面でタグ表示時にクラッシュ
- ✅ **不具合4**: タスク完了時・実績画面でアバター画像が表示されない

すべての不具合を修正し、関連する統合テストも成功しました。

---

## 不具合1: グループ名が表示されない

### 問題の詳細

**発生箇所**: `GroupManagementScreen.tsx` (グループ管理画面)

**症状**:
- グループ管理画面で「マイグループ」とハードコードされた文字列が表示される
- 実際のグループ名（例: 「テストグループ」）が取得・表示されない

**エラーログ**: なし（機能不足）

### 原因

1. **バックエンドAPI** (`EditProfileApiAction`):
   - プロフィールAPIが`group`リレーションをloadしていなかった
   - レスポンスに`group_id`と`group_edit_flg`のみを返し、グループ名を含めていなかった

2. **モバイル型定義** (`user.types.ts`):
   - `User`インターフェースに`group`プロパティが定義されていなかった

3. **画面実装** (`GroupManagementScreen.tsx`):
   - グループ名を「マイグループ」とハードコード
   - `user.group.name`へのアクセスができなかった

### 解決策

#### バックエンド修正

**EditProfileApiAction.php**:
```php
public function __invoke(Request $request): JsonResponse
{
    $user = $request->user();
    
    // group リレーションをload
    $user->load('group');

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $user->id,
            'username' => $user->username,
            // ... 他のフィールド
            'group_id' => $user->group_id,
            'group_edit_flg' => (bool) $user->group_edit_flg,
            'group' => $user->group ? [
                'id' => $user->group->id,
                'name' => $user->group->name,
            ] : null,
            // ...
        ],
    ], 200);
}
```

#### モバイル修正

**user.types.ts**:
```typescript
export interface User {
  id: number;
  username: string;
  // ... 他のフィールド
  group_id: number | null;
  group_edit_flg: boolean;
  group?: {
    id: number;
    name: string;
  } | null;
  // ...
}
```

**GroupManagementScreen.tsx**:
```typescript
const groupName = user?.group?.name || 'マイグループ';
```

### 成果

- グループに所属するユーザーは実際のグループ名を表示
- グループ未所属の場合は「マイグループ」をフォールバック
- バックエンド: 13テスト成功 (ProfileApiTest)
- モバイル: 15テスト成功 (GroupManagementScreen + navigation)

**コミット**: `ae49465` - "fix(mobile): Display actual group name in GroupManagementScreen"

---

## 不具合2: スケジュールタスク更新エラー

### 問題の詳細

**発生箇所**: `ScheduledTaskEditScreen.tsx` (スケジュールタスク編集画面)

**症状**:
- 更新ボタン押下時にエラーが発生
- APIは200 OKを返すがモバイル側でエラー判定

**エラーログ**:
```
LOG  [API] Response status: 200
LOG  [API] Response data: {"message":"スケジュールタスクを更新しました。","data":{...}}
ERROR [ScheduledTaskService] updateScheduledTask failed: success=false or no data
ERROR [ScheduledTaskService] updateScheduledTask error: [Error: API_ERROR]
```

### 原因

**APIレスポンス形式の不統一**:
- **期待形式**: `{ success: true, message, data }`
- **実際のレスポンス**: `{ message, data }` (`success`フィールドなし)

`ScheduledTaskApiResponder`が他のAPIと異なり、`success`フィールドを返していなかった。

### 解決策

**ScheduledTaskApiResponder.php** - 全メソッドに`success`フィールドを追加:

```php
// Before
public function update($scheduledTask): JsonResponse
{
    return response()->json([
        'message' => 'スケジュールタスクを更新しました。',
        'data' => ['scheduled_task' => $scheduledTask],
    ], 200);
}

// After
public function update($scheduledTask): JsonResponse
{
    return response()->json([
        'success' => true,
        'message' => 'スケジュールタスクを更新しました。',
        'data' => ['scheduled_task' => $scheduledTask],
    ], 200);
}
```

修正対象メソッド（10個）:
1. `index()` - 一覧取得
2. `create()` - 作成情報取得
3. `store()` - 新規作成
4. `edit()` - 編集情報取得
5. `update()` - 更新
6. `delete()` - 削除
7. `pause()` - 一時停止
8. `resume()` - 再開
9. `history()` - 実行履歴
10. `error()` - エラー (`success: false`)

### 成果

- スケジュールタスクの更新が正常に動作
- 全CRUD操作で`success`フィールドを返却
- 他のAPIレスポンスと形式を統一
- バックエンド: 14テスト成功 (ScheduledTaskApiTest)

**コミット**: `0ff3bde` - "fix(api): Add success field to ScheduledTask API responses"

---

## 不具合3: タグ表示時にクラッシュ

### 問題の詳細

**発生箇所**: `ScheduledTaskEditScreen.tsx` (スケジュールタスク編集画面)

**症状**:
- 編集画面に遷移した瞬間にクラッシュ
- タグを表示しようとした際にエラー

**エラーログ**:
```
ERROR [Error: Objects are not valid as a React child 
(found: object with keys {id, scheduled_task_id, tag_name, created_at, updated_at}). 
If you meant to render a collection of children, use an array instead.]
```

### 原因

**データ形式の不一致**:

1. **バックエンド**:
   - `ScheduledGroupTask`モデルの`tags`リレーションが`ScheduledTaskTag`オブジェクトの配列を返す
   ```php
   [
     { id: 1, scheduled_task_id: 1, tag_name: '家事', created_at: ..., updated_at: ... },
     { id: 2, scheduled_task_id: 1, tag_name: 'ゴミ', created_at: ..., updated_at: ... }
   ]
   ```

2. **モバイル**:
   - 文字列配列を期待: `['家事', 'ゴミ']`
   - `.join(', ')`でオブジェクトを結合しようとしてエラー

### 解決策

#### バックエンド修正

**ScheduledGroupTask.php** - アクセサを追加:

```php
class ScheduledGroupTask extends Model
{
    protected $appends = ['tag_names'];
    
    /**
     * タグ名の配列を取得（Accessor）
     */
    public function getTagNamesAttribute(): array
    {
        // リレーションロード済みの場合
        if ($this->relationLoaded('tags') && $this->tags !== null) {
            return $this->tags->pluck('tag_name')->toArray();
        }
        
        // IDが未設定の場合（新規作成時など）
        if (!$this->id) {
            return [];
        }
        
        // ロードされていない場合はDBから取得
        return DB::table('scheduled_task_tags')
            ->where('scheduled_task_id', $this->id)
            ->pluck('tag_name')
            ->toArray();
    }
}
```

これにより、APIレスポンスに自動的に`tag_names: ['家事', 'ゴミ']`が追加される。

#### モバイル修正

**ScheduledTaskEditScreen.tsx**:
```typescript
// Before
setTagsInput(task.tags ? task.tags.join(', ') : '');

// After
const tags = task.tag_names || task.tags || [];
setTagsInput(Array.isArray(tags) ? tags.join(', ') : '');
```

**ScheduledTaskListScreen.tsx**:
```typescript
const tags = item.tag_names || item.tags || [];
{tags.slice(0, 3).map((tag, index) => (
  <View key={index}><Text>{tag}</Text></View>
))}
```

**scheduled-task.types.ts**:
```typescript
export interface ScheduledTask {
  tags: string[];        // UI用
  tag_names?: string[];  // APIから返される実際のフィールド
}
```

### 成果

- スケジュールタスク編集画面でタグが正しく表示
- タグ一覧画面でもタグが正しく表示
- バックエンド: 14テスト成功 (ScheduledTaskApiTest)
- モバイル: 10テスト成功 (ScheduledTaskEditScreen)

**コミット**: `9d3e498` - "fix(scheduled-task): Convert tags relation to tag_names array in API responses"

---

## 不具合4: アバター画像が表示されない

### 問題の詳細

**発生箇所**:
1. タスク完了時のアバター表示
2. 実績画面（PerformanceScreen）のアバター表示

**症状**:
- **タスク完了時**: コメントは表示されるが画像が表示されない
- **実績画面**: コメントも画像も表示されない

### 原因

#### 原因1: APIレスポンス形式の不一致

**バックエンド** (`TeacherAvatarApiResponder`):
```php
return response()->json([
    'success' => true,
    'data' => [
        'comment' => $comment,
        'image_url' => $imageUrl,  // ← snake_case
        'animation' => $animation,
    ],
], 200);
```

**モバイル** (`avatar.service.ts`):
```typescript
// ❌ 変換なしで直接返していた
const response = await api.get<AvatarCommentResponse>(`/avatar/comment/${eventType}`);
return response.data; // image_url が imageUrl にならない
```

型定義は`imageUrl` (camelCase)を期待しているが、APIは`image_url` (snake_case)を返していた。

#### 原因2: AvatarWidgetがレンダリングされていない

- `AvatarContext`がイベントを正しくディスパッチ ✅
- `AvatarWidget`コンポーネントが存在 ✅
- `App.tsx`が`AvatarProvider`でラップ ✅
- **しかし`AvatarWidget`コンポーネント自体がDOMツリーに配置されていない** ❌

`App.tsx`は`AvatarProvider`でコンテキストを提供していたが、実際に`AvatarWidget`をレンダリングしていなかった。

### 解決策

#### 1. avatar.service.ts - レスポンス変換

```typescript
const getCommentForEvent = async (
  eventType: AvatarEventType
): Promise<AvatarCommentResponse> => {
  const response = await api.get<{
    success: boolean;
    data: {
      comment: string;
      image_url: string;  // ← snake_case
      animation: string;
    };
  }>(`/avatar/comment/${eventType}`);
  
  // snake_case → camelCase 変換
  const result: AvatarCommentResponse = {
    comment: response.data.data.comment,
    imageUrl: response.data.data.image_url,  // ← 変換
    animation: response.data.data.animation as any,
  };
  
  return result;
};
```

#### 2. AppNavigator.tsx - AvatarWidget配置

```tsx
export default function AppNavigator() {
  const { isVisible, currentData, hideAvatar } = useAvatarContext();
  
  return (
    <>
      <NavigationContainer key="authenticated">
        <Stack.Navigator>
          {/* ... 全画面 ... */}
        </Stack.Navigator>
      </NavigationContainer>
      
      {/* アバターウィジェット（全画面共通オーバーレイ） */}
      <AvatarWidget
        visible={isVisible}
        data={currentData}
        onClose={hideAvatar}
      />
    </>
  );
}
```

`NavigationContainer`の外側に配置することで、すべての認証済み画面でアバターを表示できるようにした。

### 成果

**Before**:
- タスク完了: 🗨️ コメントのみ、🖼️ 画像なし
- 実績画面: ❌ 何も表示されない

**After**:
- タスク完了: 🗨️ コメント + 🖼️ 画像 ✅
- 実績画面: 🗨️ コメント + 🖼️ 画像 ✅
- 全画面: アバターが正しく表示される ✅

モバイル: 13テスト成功 (AvatarWidget.test.tsx)

**コミット**: `adbdde3` - "fix(mobile): Display avatar image and comment in all screens"

---

## 全体的な成果

### 修正ファイル数

| カテゴリ | ファイル数 | 主な変更 |
|---------|-----------|---------|
| バックエンド (PHP) | 4ファイル | API Responder + Service + Model修正 |
| モバイル (TypeScript) | 7ファイル | 画面 + Service + 型定義修正 |
| **合計** | **11ファイル** | **4件の不具合修正** |

### テスト結果

| テストスイート | テスト数 | 結果 |
|--------------|---------|------|
| ProfileApiTest | 13 | ✅ 全て成功 |
| ScheduledTaskApiTest | 14 | ✅ 全て成功 |
| GroupManagementScreen | 15 | ✅ 全て成功 |
| ScheduledTaskEditScreen | 10 | ✅ 全て成功 |
| AvatarWidget | 13 | ✅ 全て成功 |
| **合計** | **65テスト** | **✅ 全て成功** |

### コミット履歴

1. `ae49465` - グループ名表示修正
2. `0ff3bde` - スケジュールタスクAPI修正
3. `9d3e498` - タグ表示修正
4. `adbdde3` - アバター表示修正

---

## 今後の推奨事項

### 1. APIレスポンス形式の統一

**現状の問題**:
- 一部のAPIが`snake_case`、一部が`camelCase`を返す
- モバイル側で都度変換が必要

**推奨対策**:
- バックエンドで統一的にcamelCase変換ミドルウェアを実装
- または、OpenAPI仕様書でレスポンス形式を明確化

### 2. 型安全性の強化

**推奨対策**:
- バックエンドのAPIレスポンスをTypeScript型定義に自動変換
- OpenAPI Generator等のツールを活用
- フロントエンド・バックエンド間の型不一致を防止

### 3. コンポーネントレンダリングの可視化

**推奨対策**:
- React Native Debuggerでコンポーネントツリーを定期的に確認
- レンダリングされていないコンポーネントを早期発見
- E2Eテストでビジュアル確認を追加

### 4. エラーハンドリングの改善

**推奨対策**:
- APIレスポンス形式が期待と異なる場合の明確なエラーメッセージ
- デバッグログに実際のレスポンス構造を出力
- Sentryなどのエラー追跡ツールの導入

---

## 結論

本修正作業により、MyTeacherモバイルアプリケーションの主要な不具合4件をすべて解決しました。

- ✅ グループ名が正しく表示される
- ✅ スケジュールタスクの更新が正常に動作
- ✅ タグが正しく表示される
- ✅ アバター画像とコメントが全画面で表示される

すべての関連テスト（65テスト）が成功し、機能の安定性が確認されました。今後は上記の推奨事項を実装することで、同様の不具合の再発を防止できます。
