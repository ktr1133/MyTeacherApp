# グループ管理機能 要件定義書（モバイル版）

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-08 | GitHub Copilot | 初版作成: Phase 2.B-7実装に向けたモバイル版要件定義 |
| 2025-12-08 | GitHub Copilot | Q&A反映: グループ削除機能削除、Responder実装方針明記、サブスク連携追記、テスト数修正 |

---

## 1. 概要

MyTeacher モバイルアプリにおけるグループ管理機能の要件定義書です。家族や組織でのタスク管理を可能にするグループ機能の管理画面を提供します。

### Web版との差異

| 項目 | Web版 | モバイル版 |
|------|-------|-----------|
| グループ作成 | ✅ 実装済み | ❌ 未実装（Phase 2.B-7で実装） |
| グループ削除 | ❌ 未実装 | ❌ 未実装 |
| メンバー管理 | ✅ 実装済み | ❌ 未実装（Phase 2.B-7で実装） |
| サブスク管理連携 | ✅ リンク表示 | ✅ 既存画面へ遷移（Phase 2.B-6実装済み） |
| スケジュールタスク | ✅ リンク表示 | ❌ Phase 2.B-7で実装予定 |

---

## 2. 機能要件

### 2.1 グループ情報表示・編集

#### 2.1.1 基本情報表示

```
設定画面 or ホーム画面
    ↓
「グループ管理」ボタンタップ
    ↓
GroupManageScreen表示
    ↓
グループ情報・メンバー一覧表示
```

**表示内容**:
- グループ名
- グループマスター表示（👑マーク付き）
- 作成日時
- メンバー数
- メンバー一覧
  - ユーザー名
  - メールアドレス
  - 編集権限（グループマスター、編集可、閲覧のみ）
  - テーマ設定（大人モード/子どもモード）

#### 2.1.2 グループ名編集

**権限**: グループマスターまたは編集権限を持つユーザーのみ

```
GroupManageScreen
    ↓
グループ名横の「編集」アイコンタップ
    ↓
インライン編集モードに切替
    ↓
グループ名入力
    ↓
「保存」ボタンタップ
    ↓
API呼び出し（PATCH /api/groups）
    ↓
成功時にトースト表示
```

**バリデーション**:
- グループ名: 必須、最大100文字
- 重複チェックなし

### 2.2 メンバー管理

#### 2.2.1 メンバー追加

**権限**: グループマスターまたは編集権限を持つユーザーのみ

```
GroupManageScreen
    ↓
「メンバー追加」ボタンタップ
    ↓
AddMemberModal表示
    ↓
ユーザー名入力（完全一致）
    ↓
「追加」ボタンタップ
    ↓
API呼び出し（POST /api/groups/members）
    ↓
成功時にメンバー一覧を再取得
```

**バリデーション**:
- ユーザー名: 必須
- 存在確認（API側）
- 重複追加不可（API側）

#### 2.2.2 メンバー権限変更

**権限**: グループマスターのみ

```
GroupManageScreen
    ↓
メンバー行の「権限」ボタンタップ
    ↓
権限選択ダイアログ表示
├ グループマスターに設定
├ 編集可能にする
└ 閲覧のみにする
    ↓
選択後にAPI呼び出し
├ グループマスター設定: POST /api/groups/transfer/{userId}
└ 編集権限変更: PATCH /api/groups/members/{userId}/permission
```

**注意**:
- グループマスター譲渡は確認ダイアログ表示
- 譲渡後、元マスターは編集権限付きメンバーになる

#### 2.2.3 メンバーテーマ切替

**権限**: グループマスターまたは編集権限を持つユーザー

```
GroupManageScreen
    ↓
メンバー行の「テーマ」トグルスイッチ
    ↓
切替時にAPI呼び出し（PATCH /api/groups/members/{userId}/theme）
    ↓
成功時にトースト表示
```

#### 2.2.4 メンバー削除

**権限**: グループマスターのみ

```
GroupManageScreen
    ↓
メンバー行の「削除」ボタンタップ
    ↓
確認ダイアログ表示
「{ユーザー名}をグループから削除しますか？」
    ↓
「削除」タップ
    ↓
API呼び出し（DELETE /api/groups/members/{userId}）
    ↓
成功時にメンバー一覧を再取得
```

**制約**:
- グループマスター自身は削除不可

### 2.3 グループ作成

#### 2.3.1 作成フロー

```
設定画面 or ホーム画面
    ↓
「グループ作成」ボタンタップ
    ↓
CreateGroupModal表示
    ↓
グループ名入力
    ↓
「作成」ボタンタップ
    ↓
API呼び出し（PATCH /api/groups）
    ↓
成功時にGroupManageScreenへ遷移
```

**注意**:
- グループ作成APIは更新APIと同じエンドポイント
- グループ未加入の場合、グループ名を送信することで新規作成
- 作成者は自動的にグループマスターになる

### 2.4 サブスクリプション管理連携

```
GroupManageScreen
    ↓
「サブスクリプション管理」ボタンタップ
    ↓
SubscriptionManageScreenへ遷移
```

**権限**: グループマスターまたは編集権限を持つユーザーのみ表示

**実装状況**: ✅ SubscriptionManageScreenは Phase 2.B-6で実装済み

### 2.5 スケジュールタスク管理連携

```
GroupManageScreen
    ↓
「スケジュールタスク管理」ボタンタップ
    ↓
ScheduledTaskListScreenへ遷移
```

**権限**: グループマスターまたは編集権限を持つユーザーのみ表示

**実装状況**: ❌ ScheduledTaskListScreenは Phase 2.B-7で実装予定

---

## 3. 画面設計

### 3.1 GroupManageScreen（グループ管理画面）

**レイアウト（グループ加入済み）**:
```
┌─────────────────────────────────┐
│ ← グループ管理                    │
├─────────────────────────────────┤
│                                 │
│ 📋 グループ情報                  │
│                                 │
│ グループ名: [インライン編集]      │
│ 作成日: 2025-12-08               │
│ メンバー数: 3人                  │
│                                 │
│ [サブスクリプション管理] →        │
│ [スケジュールタスク管理] →        │
│                                 │
│ 👥 メンバー一覧                  │
│                                 │
│ ┌───────────────────────────┐  │
│ │ 👑 山田太郎（マスター）       │  │
│ │ taro@example.com            │  │
│ │ テーマ: 大人 [権限] [削除]   │  │
│ └───────────────────────────┘  │
│ ┌───────────────────────────┐  │
│ │ 山田花子                    │  │
│ │ hanako@example.com          │  │
│ │ テーマ: 子ども [権限] [削除] │  │
│ └───────────────────────────┘  │
│                                 │
│        [メンバー追加]             │
│                                 │
└─────────────────────────────────┘
```

**レイアウト（グループ未加入）**:
```
┌─────────────────────────────────┐
│ ← グループ管理                    │
├─────────────────────────────────┤
│                                 │
│ グループに参加していません         │
│                                 │
│ グループを作成すると、家族や        │
│ チームでタスクを共有できます。      │
│                                 │
│        [グループ作成]             │
│                                 │
└─────────────────────────────────┘
```

### 3.2 CreateGroupModal（グループ作成モーダル）

```
┌─────────────────────────────────┐
│ グループ作成             [×]      │
├─────────────────────────────────┤
│                                 │
│ グループ名 *                     │
│ [________________]              │
│                                 │
│ [キャンセル]  [作成する]         │
│                                 │
└─────────────────────────────────┘
```

### 3.3 AddMemberModal（メンバー追加モーダル）

```
┌─────────────────────────────────┐
│ メンバー追加             [×]      │
├─────────────────────────────────┤
│                                 │
│ ユーザー名 *                     │
│ [________________]              │
│                                 │
│ ⚠️ 完全一致で入力してください      │
│                                 │
│ [キャンセル]  [追加する]         │
│                                 │
└─────────────────────────────────┘
```

### 3.4 権限選択ダイアログ

```
┌─────────────────────────────────┐
│ 権限を選択                       │
├─────────────────────────────────┤
│                                 │
│ ○ グループマスターに設定          │
│   （グループの完全な管理権限）     │
│                                 │
│ ○ 編集可能にする                 │
│   （グループ情報・メンバー編集可） │
│                                 │
│ ○ 閲覧のみにする                 │
│   （タスク作成・完了のみ可能）     │
│                                 │
│ [キャンセル]  [変更する]         │
│                                 │
└─────────────────────────────────┘
```

---

## 4. API仕様

### 4.1 グループ情報取得

**エンドポイント**: `GET /api/groups/edit`

**レスポンス**:
```json
{
  "success": true,
  "data": {
    "group": {
      "id": 1,
      "name": "山田家",
      "master_user_id": 123,
      "created_at": "2025-12-08T10:00:00Z",
      "updated_at": "2025-12-08T10:00:00Z"
    },
    "members": [
      {
        "id": 123,
        "username": "taro",
        "name": "山田太郎",
        "email": "taro@example.com",
        "group_edit_flg": true,
        "is_master": true
      },
      {
        "id": 124,
        "username": "hanako",
        "name": "山田花子",
        "email": "hanako@example.com",
        "group_edit_flg": false,
        "is_master": false
      }
    ]
  }
}
```

**実装状況**: ✅ 実装済み（`EditGroupApiAction`）
**問題点**: ⚠️ Responder未使用 - Phase 2.B-7で修正推奨

### 4.2 グループ情報更新・作成

**エンドポイント**: `PATCH /api/groups`

**リクエスト**:
```json
{
  "name": "山田家"
}
```

**レスポンス**:
```json
{
  "success": true,
  "message": "グループ情報を更新しました。",
  "data": {
    "avatar_event": "group_edited"
  }
}
```

**実装状況**: ✅ 実装済み（`UpdateGroupApiAction`）
**問題点**: ⚠️ Responder未使用 - Phase 2.B-7で修正推奨

### 4.3 メンバー追加

**エンドポイント**: `POST /api/groups/members`

**リクエスト**:
```json
{
  "username": "jiro"
}
```

**レスポンス**:
```json
{
  "success": true,
  "message": "メンバーを追加しました。"
}
```

**実装状況**: ✅ 実装済み（`AddMemberApiAction`）
**問題点**: ⚠️ Responder未使用 - Phase 2.B-7で修正推奨

### 4.4 メンバー権限変更

**エンドポイント**: `PATCH /api/groups/members/{userId}/permission`

**リクエスト**:
```json
{
  "group_edit_flg": true
}
```

**レスポンス**:
```json
{
  "success": true,
  "message": "権限を更新しました。"
}
```

**実装状況**: ✅ 実装済み（`UpdateMemberPermissionApiAction`）
**問題点**: ⚠️ Responder未使用 - Phase 2.B-7で修正推奨

### 4.5 メンバーテーマ切替

**エンドポイント**: `PATCH /api/groups/members/{userId}/theme`

**レスポンス**:
```json
{
  "success": true,
  "message": "メンバーのテーマ設定を更新しました。"
}
```

**実装状況**: ✅ 実装済み（`ToggleMemberThemeApiAction`）
**問題点**: ⚠️ Responder未使用 - Phase 2.B-7で修正推奨

### 4.6 グループマスター譲渡

**エンドポイント**: `POST /api/groups/transfer/{userId}`

**レスポンス**:
```json
{
  "success": true,
  "message": "グループマスターを譲渡しました。"
}
```

**実装状況**: ✅ 実装済み（`TransferGroupMasterApiAction`）
**問題点**: ⚠️ Responder未使用 - Phase 2.B-7で修正推奨

### 4.7 メンバー削除

**エンドポイント**: `DELETE /api/groups/members/{userId}`

**レスポンス**:
```json
{
  "success": true,
  "message": "メンバーを削除しました。"
}
```

**実装状況**: ✅ 実装済み（`RemoveMemberApiAction`）
**問題点**: ⚠️ Responder未使用 - Phase 2.B-7で修正推奨

---

## 5. データ設計

### 5.1 型定義（group.types.ts）

```typescript
/**
 * グループ基本情報
 */
export interface Group {
  id: number;
  name: string;
  master_user_id: number;
  created_at: string;
  updated_at: string;
}

/**
 * グループメンバー情報
 */
export interface GroupMember {
  id: number;
  username: string;
  name: string;
  email: string;
  group_edit_flg: boolean;
  is_master: boolean;
  theme?: 'adult' | 'child';
}

/**
 * グループ詳細情報（グループ + メンバー）
 */
export interface GroupDetail {
  group: Group;
  members: GroupMember[];
}

/**
 * グループ作成・更新リクエスト
 */
export interface GroupRequest {
  name: string;
}

/**
 * メンバー追加リクエスト
 */
export interface AddMemberRequest {
  username: string;
}

/**
 * メンバー権限変更リクエスト
 */
export interface UpdateMemberPermissionRequest {
  group_edit_flg: boolean;
}

/**
 * API共通レスポンス
 */
export interface GroupApiResponse {
  success: boolean;
  message: string;
  data?: {
    avatar_event?: string;
  };
}
```

---

## 6. Service・Hook層設計

### 6.1 GroupService（group.service.ts）

```typescript
/**
 * グループ管理サービス
 */
export const groupService = {
  /**
   * グループ情報取得
   */
  async getGroupDetail(): Promise<GroupDetail> { /* ... */ },
  
  /**
   * グループ作成・更新
   */
  async updateGroup(name: string): Promise<void> { /* ... */ },
  
  /**
   * メンバー追加
   */
  async addMember(username: string): Promise<void> { /* ... */ },
  
  /**
   * メンバー権限変更
   */
  async updateMemberPermission(userId: number, canEdit: boolean): Promise<void> { /* ... */ },
  
  /**
   * メンバーテーマ切替
   */
  async toggleMemberTheme(userId: number): Promise<void> { /* ... */ },
  
  /**
   * グループマスター譲渡
   */
  async transferMaster(userId: number): Promise<void> { /* ... */ },
  
  /**
   * メンバー削除
   */
  async removeMember(userId: number): Promise<void> { /* ... */ },
};
```

### 6.2 useGroup Hook（useGroup.ts）

```typescript
/**
 * グループ管理Hook
 */
export const useGroup = () => {
  const [groupDetail, setGroupDetail] = useState<GroupDetail | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  /**
   * グループ情報取得
   */
  const getGroupDetail = async () => { /* ... */ };
  
  /**
   * グループ作成・更新
   */
  const updateGroup = async (name: string) => { /* ... */ };
  
  /**
   * メンバー追加
   */
  const addMember = async (username: string) => { /* ... */ };
  
  /**
   * メンバー権限変更
   */
  const updateMemberPermission = async (userId: number, canEdit: boolean) => { /* ... */ };
  
  /**
   * メンバーテーマ切替
   */
  const toggleMemberTheme = async (userId: number) => { /* ... */ };
  
  /**
   * グループマスター譲渡
   */
  const transferMaster = async (userId: number) => { /* ... */ };
  
  /**
   * メンバー削除
   */
  const removeMember = async (userId: number) => { /* ... */ };
  
  return {
    groupDetail,
    isLoading,
    error,
    getGroupDetail,
    updateGroup,
    addMember,
    updateMemberPermission,
    toggleMemberTheme,
    transferMaster,
    removeMember,
  };
};
```

---

## 7. テスト要件

### 7.1 Serviceテスト（group.service.test.ts）

- ✅ グループ情報取得成功
- ✅ グループ更新成功
- ✅ メンバー追加成功
- ✅ メンバー権限変更成功
- ✅ メンバーテーマ切替成功
- ✅ グループマスター譲渡成功
- ✅ メンバー削除成功
- ✅ APIエラーハンドリング
- ✅ ネットワークエラーハンドリング

**目標**: 9テスト、全パス

### 7.2 Hookテスト（useGroup.test.ts）

- ✅ グループ情報取得（成功・失敗）
- ✅ グループ更新（成功・失敗）
- ✅ メンバー追加（成功・失敗）
- ✅ メンバー権限変更（成功・失敗）
- ✅ メンバーテーマ切替（成功・失敗）
- ✅ グループマスター譲渡（成功・失敗）
- ✅ メンバー削除（成功・失敗）
- ✅ ローディング状態管理
- ✅ エラー状態管理

**目標**: 12テスト、全パス

### 7.3 UIテスト

**GroupManageScreen.test.tsx**:
- ✅ グループ情報表示（加入済み）
- ✅ グループ未加入時の表示
- ✅ グループ名編集
- ✅ メンバー追加ダイアログ
- ✅ メンバー権限変更ダイアログ
- ✅ メンバーテーマ切替
- ✅ グループマスター譲渡確認
- ✅ メンバー削除確認
- ✅ サブスク管理画面へ遷移
- ✅ スケジュールタスク管理画面へ遷移

**目標**: 14テスト、全パス

---

## 8. 実装優先順位

### Phase 2.B-7 Week 2（グループ管理）

1. **Laravel API修正**（1日）
   - 全Action にResponder導入（7ファイル修正）
   - `GroupApiResponder` 作成（インターフェース不要）

2. **型定義作成**（0.5日）
   - `group.types.ts` 作成

3. **Service層実装**（2日）
   - `group.service.ts` 実装（7メソッド）
   - `group.service.test.ts` 作成（9テスト）

4. **Hook層実装**（2日）
   - `useGroup.ts` 実装（7メソッド）
   - `useGroup.test.ts` 作成（12テスト）

5. **UI実装**（2.5日）
   - `GroupManageScreen.tsx` 実装
   - `CreateGroupModal.tsx` 実装
   - `AddMemberModal.tsx` 実装
   - UIテスト作成（14テスト）

---

## 9. Laravel API修正タスク

### 9.1 Responder作成

**ファイル**: `app/Http/Responders/Api/Group/GroupApiResponder.php`

**注意**: Responderクラスは**インターフェース不要**（`.github/copilot-instructions.md` の規約）

```php
<?php

namespace App\Http\Responders\Api\Group;

use Illuminate\Http\JsonResponse;

/**
 * グループ管理APIレスポンス整形
 */
class GroupApiResponder
{
    public function groupDetail($group, $members): JsonResponse { /* ... */ }
    public function updated(string $avatarEvent): JsonResponse { /* ... */ }
    public function memberAdded(): JsonResponse { /* ... */ }
    public function permissionUpdated(): JsonResponse { /* ... */ }
    public function themeToggled(): JsonResponse { /* ... */ }
    public function masterTransferred(): JsonResponse { /* ... */ }
    public function memberRemoved(): JsonResponse { /* ... */ }
    public function error(string $message, int $statusCode = 400): JsonResponse { /* ... */ }
}
```

### 9.2 既存Action修正

**修正対象**（7ファイル）:
- `EditGroupApiAction.php`
- `UpdateGroupApiAction.php`
- `AddMemberApiAction.php`
- `UpdateMemberPermissionApiAction.php`
- `ToggleMemberThemeApiAction.php`
- `TransferGroupMasterApiAction.php`
- `RemoveMemberApiAction.php`

**修正内容**:
- Responderをコンストラクタ注入
- `response()->json()` を `$this->responder->xxx()` に変更

---

## 10. 参考資料

- **Web版グループ管理画面**: `/home/ktr/mtdev/resources/views/profile/group/edit.blade.php`
- **Laravel API実装**: `/home/ktr/mtdev/app/Http/Actions/Api/Group/`
- **モバイル開発規則**: `/home/ktr/mtdev/docs/mobile/mobile-rules.md`
- **プロジェクト規約**: `/home/ktr/mtdev/.github/copilot-instructions.md`
