# 保護者招待トークン経由での親子紐付け機能 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-17 | GitHub Copilot | Phase 8完了: 統合テスト実装（67テスト全パス、346アサーション、100.42秒）、完了レポート作成 |
| 2025-01-22 | GitHub Copilot | Phase 7完了: モバイルUI実装（SearchChildrenModal、NotificationDetailScreen承認・拒否UI、GroupManagementScreen検索ボタン）、レスポンシブデザイン・テーマ対応、COPPA法遵守フロー実装 |
| 2025-12-17 | GitHub Copilot | Phase 6完了: Mobile API実装（SearchUnlinkedChildrenApiAction、SendChildLinkRequestApiAction、ApproveParentLinkApiAction、RejectParentLinkApiAction）、openapi.yaml更新 |
| 2025-12-17 | GitHub Copilot | Phase 5完了: 承認・拒否処理（Web）実装、ApproveParentLinkAction/RejectParentLinkAction作成、通知詳細画面UI修正 |
| 2025-12-17 | GitHub Copilot | Phase 4完了: 通知システム統合（通知タイプ追加、カテゴリ検出拡張、デフォルト値確認） |
| 2025-12-17 | GitHub Copilot | Phase 3完了: 未紐付け子検索機能（Web）実装、SendPushNotificationJob統合 |
| 2025-12-17 | GitHub Copilot | Phase 2完了: グループ自動作成機能実装、OpenAPI定義更新 |
| 2025-12-17 | GitHub Copilot | 初版作成: Phase 5-2拡張（招待トークン機能） |

---

## 1. 概要

### 1.1 背景

**Phase 5-2（13歳未満新規登録時の保護者メール同意）** の実装により、子アカウントが先に作成され、保護者がメール経由で同意する仕様が完成した。しかし、以下の課題が判明：

**問題点**:
```
子アカウント作成 → 保護者同意 → 子ログイン可能
                        ↓
                保護者が別途アカウント登録
                        ↓
                ❌ parent_user_idが未設定
                ❌ group_idが未設定
                ❌ Phase 5-1の管理機能（タスク承認等）が使えない
```

**Phase 5-1（既存）との差異**:
- Phase 5-1: 保護者が直接子アカウントを作成 → `parent_user_id`, `group_id`, `groups.master_user_id`すべて設定済み
- Phase 5-2: 子が先に作成 → 紐付け手段なし

### 1.2 目的

**案1（招待トークン方式）**を実装し、以下を実現する:

1. **確実な親子紐付け**: 保護者が専用招待リンクから登録 → 自動で`parent_user_id`設定
2. **グループ自動作成**: 招待リンク経由の保護者登録時に家族グループ作成、子アカウントを自動参加
3. **未紐付けアカウント救済**: 招待リンク失効後の手動紐付け機能（フォールバック）

### 1.3 対象ユーザー

- **子アカウント**: 13歳未満、Phase 5-2で作成済み、`parent_user_id = NULL`
- **保護者**: 招待リンクまたは未紐付け検索機能で紐付けを希望

### 1.4 Phase 5-2拡張の位置づけ

```
Phase 5-2 (基本)
├── 子アカウント作成（13歳未満）
├── 保護者同意トークン（7日間）
├── 保護者同意完了 → 子ログイン可能
└── ❌ 保護者アカウント登録方法なし

Phase 5-2拡張 (今回実装)
├── 保護者招待トークン生成（30日間）
├── 同意完了画面に招待リンク表示
├── 招待リンク経由の保護者登録 → 自動グループ作成・親子紐付け
└── 未紐付けアカウント検索・承認フロー（フォールバック）
```

### 1.5 実装フェーズ状況

| Phase | 概要 | プラットフォーム | ステータス | 完了日 |
|-------|------|-----------------|-----------|--------|
| **Phase 1** | 招待トークン機能（Web） | Web | ✅ 完了 | 2025-12-17 |
| **Phase 2** | グループ自動作成機能 | Web | ✅ 完了 | 2025-12-17 |
| **Phase 3** | 未紐付け子検索機能（Web） | Web | ✅ 完了 | 2025-12-17 |
| **Phase 4** | 通知システム統合 | Web | ✅ 完了 | 2025-12-17 |
| **Phase 5** | 承認・拒否処理（Web） | Web | ✅ 完了 | 2025-12-17 |
| **Phase 6** | Mobile API実装 | Mobile API | ✅ 完了 | 2025-12-17 |
| **Phase 7** | Mobile UI実装 | Mobile App | ✅ 完了 | 2025-01-22 |
| **Phase 8** | テスト実装 | Web + Mobile | ✅ 完了 | 2025-12-17 |
| **Phase 9** | ドキュメント整備 | - | ⏳ 予定 | 2025-02-15 |

**Phase 7完了内容**:
- ✅ SearchChildrenModal: 親のメールアドレスで未紐付け子検索 + リクエスト送信（430行新規作成）
- ✅ NotificationDetailScreen: 承認・拒否ボタンUI（LinearGradient、COPPA警告表示、+161行）
- ✅ GroupManagementScreen: 「未紐付け子検索」ボタン追加（+68行）
- ✅ group.service.ts: searchUnlinkedChildren(), sendLinkRequest()追加（+53行）
- ✅ notification.service.ts: approveParentLink(), rejectParentLink()追加（+80行）
- ✅ レスポンシブデザイン: iPhone SE 〜 iPad Pro対応（useResponsive, getFontSize, getSpacing, getBorderRadius）
- ✅ テーマ対応: adult/childテーマ（hiragana、20%大きめフォント）
- ✅ COPPA法遵守: 拒否時のアカウント削除 + 自動ログアウトフロー（AsyncStorage.removeItem → logout() → ログイン画面遷移）

**詳細レポート**: [docs/reports/2025-01-22-phase7-mobile-ui-completion-report.md](../docs/reports/2025-01-22-phase7-mobile-ui-completion-report.md)

---
- ✅ GroupManagementScreen: 「未紐付け子検索」ボタン追加
- ✅ レスポンシブデザイン: iPhone SE 〜 iPad Pro対応
- ✅ テーマ対応: adult/childテーマ（hiragana、20%大きめフォント）
- ✅ COPPA法遵守: 拒否時のアカウント削除 + 自動ログアウトフロー

**詳細レポート**: [docs/reports/2025-01-22-phase7-mobile-ui-completion-report.md](../docs/reports/2025-01-22-phase7-mobile-ui-completion-report.md)

---

## 2. 機能要件

### 2.1 保護者招待トークン機能（Web + Mobile）

#### 2.1.1 トークン生成タイミング

**トリガー**: 13歳未満ユーザーの新規登録時

**実装箇所**:
- `RegisterAction::store()` (Web)
- `RegisterApiAction::__invoke()` (Mobile API)

**生成仕様**:
```php
// 保護者招待トークン生成
$invitationToken = Str::random(64); // 64文字ランダム文字列
$invitationExpiresAt = now()->addDays(30); // 有効期限30日

$user->update([
    'parent_invitation_token' => $invitationToken,
    'parent_invitation_expires_at' => $invitationExpiresAt,
]);
```

**関連カラム**:
- `users.parent_invitation_token` (string, 64文字, unique, nullable)
- `users.parent_invitation_expires_at` (timestamp, nullable)

#### 2.1.2 招待リンクURL形式

**Web版**:
```
https://myteacher.example/register?parent_invite={64文字トークン}
```

**Mobile版（ディープリンク）**:
```
myteacher://register?parent_invite={64文字トークン}
```

#### 2.1.3 保護者同意完了画面の表示内容

**ファイル**: `resources/views/legal/parent-consent-complete.blade.php`

**表示項目**:
1. ✅ 同意完了メッセージ
2. 📧 子アカウント情報
   - ユーザー名: `{{ session('child_user')->username }}`
   - メールアドレス: `{{ session('child_user')->email }}`
3. 🔐 ログイン可能通知
4. 🔗 **保護者招待リンク**（コピー機能付き）
   ```html
   <input type="text" id="invitation-link" readonly 
          value="{{ url(route('register', ['parent_invite' => $invitationToken])) }}" />
   <button onclick="copyInvitationLink()">コピー</button>
   ```
5. 📱 モバイルアプリダウンロード案内
6. 🎯 ログイン画面へのリンク

#### 2.1.4 招待リンク経由の登録処理（Web）

**ルート**: `POST /register?parent_invite={token}`

**Action**: `RegisterAction::store()`

**処理フロー**:
```php
// 1. 招待トークン取得
$parentInviteToken = $request->query('parent_invite');

// 2. 子アカウント検索
$childUser = User::where('parent_invitation_token', $parentInviteToken)
    ->where('is_minor', true)
    ->first();

// 3. トークン検証
if (!$childUser || $childUser->isParentInvitationExpired()) {
    return redirect()->route('register')
        ->withErrors(['parent_invite' => '招待リンクが無効または期限切れです。']);
}

// 4. 子アカウントの既存グループチェック（重要）
if ($childUser->group_id !== null) {
    return redirect()->route('register')
        ->withErrors(['parent_invite' => 'お子様は既に別のグループに所属しています。']);
}

// 5. 保護者アカウント作成（通常フロー）
$parentUser = $profileService->createUser($userData);

// 6. グループ作成（ランダム8桁名）
$groupName = Str::random(8); // 例: "aB3cDe5F"
$group = Group::create([
    'name' => $groupName,
    'master_user_id' => $parentUser->id,
]);

// 7. 保護者アカウントにグループ設定
$parentUser->update([
    'group_id' => $group->id,
    'group_edit_flg' => true, // グループ編集権限: ON
]);

// 8. 子アカウントに親子紐付け + グループ参加
$childUser->update([
    'parent_user_id' => $parentUser->id,
    'group_id' => $group->id,
    'parent_invitation_token' => null, // トークン無効化（再利用防止）
]);

Log::info('Parent account linked to child account via invitation', [
    'parent_user_id' => $parentUser->id,
    'child_user_id' => $childUser->id,
    'group_id' => $group->id,
    'group_name' => $groupName,
]);
```

**エラーハンドリング**:
| エラー | 条件 | メッセージ |
|--------|------|-----------|
| トークン無効 | `$childUser === null` | 招待リンクが無効です |
| 期限切れ | `$childUser->isParentInvitationExpired() === true` | 招待リンクの有効期限が切れています（30日以内に登録してください） |
| 既存グループ所属 | `$childUser->group_id !== null` | お子様は既に別のグループに所属しています |

#### 2.1.5 招待リンク経由の登録処理（Mobile API）

**エンドポイント**: `POST /api/auth/register?parent_invite_token={token}`

**Action**: `RegisterApiAction::__invoke()`

**リクエスト例**:
```json
{
  "username": "parent_taro",
  "email": "parent@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "privacy_consent": true,
  "terms_consent": true,
  "parent_invite_token": "aB3cDe5FgH7iJ8kL9mN0pQ1rS2tU3vW4xY5zA6bC7dE8fG9hI0jK1lM2nO3pQ4rS"
}
```

**処理フロー**: Web版と同等（上記2.1.4参照）

**レスポンス例（成功）**:
```json
{
  "token": "1|laravel_sanctum_token...",
  "user": {
    "id": 123,
    "username": "parent_taro",
    "email": "parent@example.com",
    "group_id": 456,
    "group_edit_flg": true
  },
  "linked_child": {
    "id": 789,
    "username": "child_hanako",
    "group_id": 456
  },
  "group": {
    "id": 456,
    "name": "aB3cDe5F",
    "master_user_id": 123
  }
}
```

**レスポンス例（エラー）**:
```json
{
  "message": "お子様は既に別のグループに所属しています",
  "errors": {
    "parent_invite_token": ["お子様は既に別のグループに所属しています"]
  }
}
```

---

### 2.2 未紐付け子アカウント検索・紐付けリクエスト機能（フォールバック）

#### 2.2.1 機能概要

**目的**: 招待トークンが期限切れ、または紛失した場合の救済措置

**対象ユーザー**: 保護者（グループ管理画面でのみ利用可能）

**検索条件**:
```sql
SELECT * FROM users
WHERE parent_email = :parent_email  -- 保護者のメールアドレス
  AND is_minor = true
  AND parent_user_id IS NULL        -- 未紐付け
  AND parent_consented_at IS NOT NULL -- 保護者同意済み
  AND deleted_at IS NULL;
```

#### 2.2.2 実装箇所

**グループ管理画面**: `/home/ktr/mtdev/resources/views/profile/group/edit.blade.php`

**追加セクション**:
```blade
{{-- 未紐付け子アカウント検索 --}}
<div class="bento-card rounded-2xl shadow-lg overflow-hidden task-card-enter">
    <div class="px-6 py-4 border-b border-orange-500/20 dark:border-orange-500/30 bg-gradient-to-r from-orange-500/5 to-yellow-50/50 dark:from-orange-500/10 dark:to-yellow-900/10">
        <h2 class="text-sm font-bold bg-gradient-to-r from-orange-600 to-yellow-600 bg-clip-text text-transparent">
            お子様アカウントとの紐付け
        </h2>
    </div>
    <div class="p-6">
        @include('profile.group.partials.search-unlinked-children')
    </div>
</div>
```

**新規Blade部分テンプレート**: `resources/views/profile/group/partials/search-unlinked-children.blade.php`

#### 2.2.3 UI設計

**検索フォーム**:
```blade
<form method="POST" action="{{ route('profile.group.search-children') }}">
    @csrf
    <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            お子様が登録時に入力した保護者のメールアドレスを入力すると、
            未紐付けのお子様アカウントを検索できます。
        </p>
        
        <div>
            <label class="block text-sm font-medium mb-2">
                保護者のメールアドレス
            </label>
            <input 
                type="email" 
                name="parent_email" 
                value="{{ auth()->user()->email }}"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600"
                required
            >
        </div>
        
        <button type="submit" class="btn-primary">
            検索する
        </button>
    </div>
</form>
```

**検索結果表示**（複数件対応）:
```blade
@if(isset($unlinkedChildren) && $unlinkedChildren->count() > 0)
<div class="mt-6 space-y-4">
    <h3 class="font-semibold text-gray-900 dark:text-white">
        見つかったお子様アカウント（{{ $unlinkedChildren->count() }}件）
    </h3>
    
    @foreach($unlinkedChildren as $child)
    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-semibold">{{ $child->username }}</p>
                <p class="text-sm text-gray-600">{{ $child->email }}</p>
                <p class="text-xs text-gray-500">
                    登録日: {{ $child->created_at->format('Y年m月d日') }}
                </p>
            </div>
            
            <form method="POST" action="{{ route('profile.group.send-link-request') }}">
                @csrf
                <input type="hidden" name="child_user_id" value="{{ $child->id }}">
                <button type="submit" class="btn-primary">
                    紐付けリクエスト送信
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@elseif(request()->has('searched'))
<div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        未紐付けのお子様アカウントが見つかりませんでした。
    </p>
</div>
@endif
```

#### 2.2.4 紐付けリクエスト処理

**Action**: `App\Http\Actions\Profile\Group\SendChildLinkRequestAction`

**処理フロー**:
```php
public function __invoke(SendChildLinkRequestRequest $request): RedirectResponse
{
    $parent = auth()->user();
    $childUserId = $request->input('child_user_id');
    
    $childUser = User::where('id', $childUserId)
        ->where('is_minor', true)
        ->where('parent_user_id', null)
        ->where('parent_email', $parent->email)
        ->firstOrFail();
    
    // 1. 保護者のグループ情報取得
    $group = $parent->group;
    
    if (!$group) {
        return redirect()->back()->withErrors([
            'error' => '保護者アカウントにグループが設定されていません。'
        ]);
    }
    
    // 2. 子アカウントの既存グループチェック
    if ($childUser->group_id !== null) {
        return redirect()->back()->withErrors([
            'error' => 'お子様は既に別のグループに所属しています。'
        ]);
    }
    
    // 3. 通知テンプレート作成
    $notificationTemplate = NotificationTemplate::create([
        'sender_id' => $parent->id,
        'source' => 'system',
        'type' => 'parent_link_request', // 新規通知種別
        'priority' => 'important',
        'title' => '保護者アカウントとの紐付けリクエスト',
        'message' => "{$parent->name} さんから親子アカウントの紐付けリクエストが届いています。\n\nグループ名: {$group->name}\n\n承認すると、{$parent->name} さんがあなたのタスクを管理できるようになります。",
        'data' => json_encode([
            'parent_user_id' => $parent->id,
            'parent_username' => $parent->username,
            'parent_name' => $parent->name,
            'group_id' => $group->id,
            'group_name' => $group->name,
        ]),
        'target_type' => 'users',
        'target_ids' => json_encode([$childUser->id]),
        'publish_at' => now(),
        'expire_at' => null, // 期限なし（質疑回答: 案1）
    ]);
    
    // 4. ユーザー通知レコード作成
    UserNotification::create([
        'user_id' => $childUser->id,
        'notification_template_id' => $notificationTemplate->id,
        'is_read' => false,
    ]);
    
    // 5. モバイルプッシュ通知送信（Web + Mobile両方）
    $this->pushNotificationService->sendToUser($childUser, [
        'title' => $notificationTemplate->title,
        'body' => $notificationTemplate->message,
        'data' => [
            'type' => 'parent_link_request',
            'notification_template_id' => $notificationTemplate->id,
        ],
    ]);
    
    Log::info('Parent link request sent', [
        'parent_user_id' => $parent->id,
        'child_user_id' => $childUser->id,
        'notification_template_id' => $notificationTemplate->id,
    ]);
    
    return redirect()->back()->with('status', 'お子様に紐付けリクエストを送信しました。');
}
```

---

### 2.3 子アカウント側の承認・拒否処理

#### 2.3.1 通知表示（Web + Mobile）

**Web**: 通知一覧画面（既存）に表示
**Mobile**: 通知リストScreen + プッシュ通知

**通知カード内容**:
```
タイトル: 保護者アカウントとの紐付けリクエスト

本文: 
{parent_name} さんから親子アカウントの紐付けリクエストが届いています。

グループ名: {group_name}

承認すると、{parent_name} さんがあなたのタスクを管理できるようになります。

[承認する] [拒否する]
```

#### 2.3.2 承認処理

**Web Action**: `App\Http\Actions\Notification\ApproveParentLinkAction`
**Mobile API**: `POST /api/notifications/{id}/approve-parent-link`

**処理フロー**:
```php
public function __invoke(int $notificationTemplateId): RedirectResponse|JsonResponse
{
    $childUser = auth()->user();
    
    // 1. 通知テンプレート取得
    $notification = NotificationTemplate::findOrFail($notificationTemplateId);
    
    if ($notification->type !== 'parent_link_request') {
        abort(400, '無効な通知種別です');
    }
    
    // 2. 通知データからparent_user_id, group_id取得
    $data = json_decode($notification->data, true);
    $parentUserId = $data['parent_user_id'];
    $groupId = $data['group_id'];
    
    // 3. 保護者アカウント・グループ存在確認
    $parentUser = User::findOrFail($parentUserId);
    $group = Group::findOrFail($groupId);
    
    // 4. 子アカウントの既存グループチェック
    if ($childUser->group_id !== null) {
        return response()->json([
            'message' => '既に別のグループに所属しているため、紐付けできません。'
        ], 400);
    }
    
    // 5. 親子紐付け + グループ参加
    DB::transaction(function () use ($childUser, $parentUserId, $groupId, $notification) {
        $childUser->update([
            'parent_user_id' => $parentUserId,
            'group_id' => $groupId,
        ]);
        
        // 通知を既読に
        UserNotification::where('user_id', $childUser->id)
            ->where('notification_template_id', $notification->id)
            ->update(['is_read' => true, 'read_at' => now()]);
    });
    
    Log::info('Child approved parent link request', [
        'child_user_id' => $childUser->id,
        'parent_user_id' => $parentUserId,
        'group_id' => $groupId,
    ]);
    
    // 6. 保護者に承認通知（システム通知）
    $parentNotification = NotificationTemplate::create([
        'sender_id' => 1, // システム管理者ID
        'source' => 'system',
        'type' => 'parent_link_approved',
        'priority' => 'normal',
        'title' => 'お子様が紐付けを承認しました',
        'message' => "{$childUser->username} さんが親子アカウントの紐付けを承認しました。",
        'target_type' => 'users',
        'target_ids' => json_encode([$parentUserId]),
        'publish_at' => now(),
    ]);
    
    UserNotification::create([
        'user_id' => $parentUserId,
        'notification_template_id' => $parentNotification->id,
    ]);
    
    return response()->json(['message' => '紐付けが完了しました']);
}
```

#### 2.3.3 拒否処理（質疑回答: 案3 - 子アカウント削除）

**Web Action**: `App\Http\Actions\Notification\RejectParentLinkAction`
**Mobile API**: `POST /api/notifications/{id}/reject-parent-link`

**処理フロー**:
```php
public function __invoke(int $notificationTemplateId): RedirectResponse|JsonResponse
{
    $childUser = auth()->user();
    
    // 1. 通知テンプレート取得
    $notification = NotificationTemplate::findOrFail($notificationTemplateId);
    
    if ($notification->type !== 'parent_link_request') {
        abort(400, '無効な通知種別です');
    }
    
    // 2. 通知データからparent_user_id取得
    $data = json_decode($notification->data, true);
    $parentUserId = $data['parent_user_id'];
    
    // 3. 通知を既読に（削除前に記録）
    UserNotification::where('user_id', $childUser->id)
        ->where('notification_template_id', $notification->id)
        ->update(['is_read' => true, 'read_at' => now()]);
    
    // 4. 保護者に拒否通知
    $parentNotification = NotificationTemplate::create([
        'sender_id' => 1,
        'source' => 'system',
        'type' => 'parent_link_rejected',
        'priority' => 'important',
        'title' => 'お子様が紐付けを拒否しました',
        'message' => "{$childUser->username} さんが親子アカウントの紐付けを拒否しました。\n\nCOPPA法により、13歳未満のお子様のアカウントは保護者の管理が必要です。お子様のアカウントは削除されました。",
        'target_type' => 'users',
        'target_ids' => json_encode([$parentUserId]),
        'publish_at' => now(),
    ]);
    
    UserNotification::create([
        'user_id' => $parentUserId,
        'notification_template_id' => $parentNotification->id,
    ]);
    
    Log::warning('Child rejected parent link request - account will be deleted', [
        'child_user_id' => $childUser->id,
        'parent_user_id' => $parentUserId,
        'username' => $childUser->username,
    ]);
    
    // 5. 子アカウント削除（ソフトデリート）
    DB::transaction(function () use ($childUser) {
        $childUser->delete(); // soft delete
    });
    
    // 6. レスポンス（ログアウト処理含む）
    Auth::loguot();
    
    return response()->json([
        'message' => 'アカウントが削除されました。COPPA法により、13歳未満の方は保護者の同意と管理が必要です。',
        'deleted' => true,
    ], 200);
}
```

**注意事項**:
- 拒否 = COPPA違反（保護者管理義務の放棄）として扱う
- ソフトデリートで履歴保持（監査対応）
- 保護者に必ず通知（説明責任）

---

## 3. データベース設計

### 3.1 既存テーブル拡張

#### 3.1.1 usersテーブル

**追加カラム** (既に実装済み):
| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|---|------|-----------|------|
| `parent_invitation_token` | string(64) | YES | NULL | 保護者招待トークン（ユニーク） |
| `parent_invitation_expires_at` | timestamp | YES | NULL | 招待トークン有効期限（30日） |

**インデックス**:
```sql
CREATE UNIQUE INDEX idx_parent_invitation_token ON users(parent_invitation_token);
```

### 3.2 notification_templatesテーブル（既存利用）

**新規通知種別** (`config/const.php` に追加):
```php
'notification_types' => [
    // ... 既存の種別
    'parent_link_request',   // 保護者紐付けリクエスト
    'parent_link_approved',  // 紐付け承認通知（保護者向け）
    'parent_link_rejected',  // 紐付け拒否通知（保護者向け）
],
```

### 3.3 user_notificationsテーブル（既存利用）

変更なし。既存の中間テーブルをそのまま利用。

---

## 4. API設計

### 4.1 保護者登録API（招待トークン対応）

**エンドポイント**: `POST /api/auth/register`

**リクエスト**:
```json
{
  "username": "parent_taro",
  "email": "parent@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "privacy_consent": true,
  "terms_consent": true,
  "parent_invite_token": "aB3cDe5F..." // オプション
}
```

**レスポンス**（招待トークン経由）:
```json
{
  "token": "1|laravel_sanctum_token...",
  "user": {
    "id": 123,
    "username": "parent_taro",
    "group_id": 456,
    "group_edit_flg": true
  },
  "linked_child": {
    "id": 789,
    "username": "child_hanako",
    "group_id": 456
  },
  "group": {
    "id": 456,
    "name": "aB3cDe5F",
    "master_user_id": 123
  }
}
```

### 4.2 未紐付け子アカウント検索API

**エンドポイント**: `POST /api/profile/group/search-children`

**リクエスト**:
```json
{
  "parent_email": "parent@example.com"
}
```

**レスポンス**:
```json
{
  "children": [
    {
      "id": 789,
      "username": "child_hanako",
      "email": "child@example.com",
      "created_at": "2025-12-01T10:00:00Z"
    }
  ]
}
```

### 4.3 紐付けリクエスト送信API

**エンドポイント**: `POST /api/profile/group/send-link-request`

**リクエスト**:
```json
{
  "child_user_id": 789
}
```

**レスポンス**:
```json
{
  "message": "お子様に紐付けリクエストを送信しました。",
  "notification_template_id": 456
}
```

### 4.4 紐付け承認API

**エンドポイント**: `POST /api/notifications/{notification_template_id}/approve-parent-link`

**レスポンス**:
```json
{
  "message": "紐付けが完了しました",
  "user": {
    "id": 789,
    "parent_user_id": 123,
    "group_id": 456
  }
}
```

### 4.5 紐付け拒否API

**エンドポイント**: `POST /api/notifications/{notification_template_id}/reject-parent-link`

**レスポンス**:
```json
{
  "message": "アカウントが削除されました。COPPA法により、13歳未満の方は保護者の同意と管理が必要です。",
  "deleted": true
}
```

---

## 5. UI/UX設計

### 5.1 保護者同意完了画面（Web）

**ファイル**: `resources/views/legal/parent-consent-complete.blade.php`

**レイアウト**:
```
┌─────────────────────────────────┐
│  ✅ 同意が完了しました            │
│                                 │
│  お子様のアカウント情報           │
│  ・ユーザー名: child_hanako      │
│  ・メールアドレス: child@...     │
│                                 │
│  🔗 保護者アカウント作成案内      │
│  下記リンクから登録すると自動紐付け│
│  [招待リンク] [コピー]            │
│  ⚠️ 有効期限: 30日間             │
│                                 │
│  [保護者アカウントを作成する]     │
│                                 │
│  📱 モバイルアプリ                │
│  [App Store] [Google Play]      │
│                                 │
│  [お子様のログイン画面へ]         │
└─────────────────────────────────┘
```

**ダークモード対応**: 必須（`dark:` プレフィックス使用）

### 5.2 グループ管理画面（未紐付け子検索セクション）

**ファイル**: `resources/views/profile/group/edit.blade.php`

**追加セクション位置**: グループ基本情報の下

**レイアウト**:
```
┌─────────────────────────────────┐
│  お子様アカウントとの紐付け        │
│                                 │
│  保護者のメールアドレス:          │
│  [parent@example.com    ] [検索] │
│                                 │
│  見つかったお子様（2件）           │
│  ┌─────────────────────────────┐ │
│  │ child_hanako                │ │
│  │ child@example.com          │ │
│  │ 登録日: 2025-12-01         │ │
│  │                [リクエスト]  │ │
│  └─────────────────────────────┘ │
│  ┌─────────────────────────────┐ │
│  │ child_taro                  │ │
│  │ ...                         │ │
│  └─────────────────────────────┘ │
└─────────────────────────────────┘
```

### 5.3 モバイル通知画面（紐付けリクエスト）

**Screen**: `NotificationDetailScreen.tsx` (新規またはモーダル)

**レイアウト** (Dimensions API使用):
```typescript
import { useResponsive, getFontSize, getSpacing } from '@/utils/responsive';

const NotificationDetailScreen = ({ notification }) => {
  const { width } = useResponsive();
  const theme = 'adult'; // または useChildTheme() から取得
  
  return (
    <View style={{
      padding: getSpacing(16, width),
    }}>
      <Text style={{
        fontSize: getFontSize(18, width, theme),
        fontWeight: 'bold',
      }}>
        {notification.title}
      </Text>
      
      <Text style={{
        fontSize: getFontSize(14, width, theme),
        marginTop: getSpacing(12, width),
      }}>
        {notification.message}
      </Text>
      
      {notification.type === 'parent_link_request' && (
        <View style={{
          flexDirection: 'row',
          marginTop: getSpacing(24, width),
          gap: getSpacing(12, width),
        }}>
          <Button 
            title="承認する" 
            onPress={() => handleApprove(notification.id)}
            style={{ flex: 1 }}
          />
          <Button 
            title="拒否する" 
            onPress={() => handleReject(notification.id)}
            variant="danger"
            style={{ flex: 1 }}
          />
        </View>
      )}
    </View>
  );
};
```

**レスポンシブ対応**:
- フォントサイズ: `getFontSize(baseSize, width, theme)` 使用
- 余白: `getSpacing(baseSpacing, width)` 使用
- ボタン配置: 小型端末では縦並び、大型では横並び

---

## 6. セキュリティ要件

### 6.1 トークン管理

1. **招待トークン**:
   - 64文字ランダム文字列（`Str::random(64)`）
   - ユニーク制約（`unique` index）
   - 有効期限30日
   - 使用後無効化（`parent_invitation_token = NULL`）

2. **トークン検証**:
   ```php
   if (!$childUser || $childUser->isParentInvitationExpired()) {
       return response()->json(['error' => 'Invalid or expired token'], 400);
   }
   ```

### 6.2 権限チェック

1. **グループ管理画面アクセス**:
   ```php
   // Middleware: グループ所属必須
   if (!auth()->user()->group_id) {
       abort(403, 'グループに所属していません');
   }
   ```

2. **紐付けリクエスト送信**:
   ```php
   // parent_emailが一致する子アカウントのみ検索可能
   $children = User::where('parent_email', auth()->user()->email)
       ->where('is_minor', true)
       ->where('parent_user_id', null)
       ->get();
   ```

3. **承認・拒否権限**:
   ```php
   // 通知の対象ユーザーのみ実行可能
   if (!$notification->isTargetUser(auth()->id())) {
       abort(403, '権限がありません');
   }
   ```

### 6.3 COPPA対応

1. **拒否時のアカウント削除**:
   - 保護者管理義務の放棄 = COPPA違反
   - ソフトデリートで履歴保持
   - 保護者に削除通知必須

2. **既存グループ所属チェック**:
   ```php
   if ($childUser->group_id !== null) {
       return response()->json([
           'error' => '既に別のグループに所属しています'
       ], 400);
   }
   ```

---

## 7. 実装タスク一覧

### 7.1 Phase 1: 招待トークン機能（完了済み）

- [x] マイグレーション作成（`parent_invitation_token`, `parent_invitation_expires_at`）
- [x] Userモデル拡張（`$fillable`, `$casts`, `isParentInvitationExpired()`）
- [x] RegisterAction修正（招待トークン生成・検証）
- [x] RegisterApiAction修正（同上）
- [x] ParentConsentResponder修正（完了画面ルート変更）
- [x] 保護者同意完了画面Blade作成（招待リンク表示）
- [x] ルート追加（`/parent-consent-complete/{token}`）

### 7.2 Phase 2: グループ自動作成機能（✅ 完了: 2025-12-17）

- [x] GroupServiceInterface拡張（`createFamilyGroup()`メソッド追加）
- [x] GroupService実装（既存サービスに統合）
  - [x] `createFamilyGroup()`メソッド実装
  - [x] DB::transaction()使用
  - [x] 既存グループチェック
  - [x] ランダム8文字グループ名生成
  - [x] 詳細ログ出力
- [x] RegisterAction修正（グループ作成処理追加）
  - [x] ランダム8文字グループ名生成
  - [x] `groups.master_user_id`設定
  - [x] 保護者に`group_edit_flg`設定
  - [x] 子アカウントにグループ参加設定
  - [x] エラーハンドリング実装
- [x] RegisterApiAction修正（同上）
  - [x] Web版と同一ロジック
  - [x] 拡張レスポンス（linked_child, group追加）
- [x] OpenAPI定義更新（`/auth/register`エンドポイント）
  - [x] parent_invite_token, birthdate, parent_emailパラメータ追加
  - [x] group_id, group_edit_flg, linked_child, groupレスポンス追加
  - [x] 400エラーレスポンス追加
- [x] テスト実行（10 passed, 24 assertions）
- [x] Intellephense警告チェック（0件）

### 7.3 Phase 3: 未紐付け子検索機能（Web）（✅ 完了: 2025-12-17）

- [x] Blade部分テンプレート作成
  - [x] `search-unlinked-children.blade.php`（162行）
  - [x] 検索フォーム（parent_email入力）
  - [x] 検索結果表示（複数件対応、カード表示）
  - [x] ダークモード対応
  - [x] レスポンシブデザイン対応
- [x] SearchUnlinkedChildrenAction作成（60行）
  - [x] parent_email検索ロジック実装
  - [x] GroupResponder経由でレスポンス返却
  - [x] FormRequest統合（SearchUnlinkedChildrenRequest）
- [x] SendChildLinkRequestAction作成（126行）
  - [x] 既存グループ所属チェック
  - [x] NotificationTemplate作成（type: parent_link_request）
  - [x] UserNotification作成
  - [x] SendPushNotificationJobディスパッチ（非同期プッシュ通知）
  - [x] DB::transaction()使用
  - [x] FormRequest統合（SendChildLinkRequestRequest）
- [x] FormRequest作成
  - [x] SearchUnlinkedChildrenRequest（email検証）
  - [x] SendChildLinkRequestRequest（child_user_id検証）
- [x] ルート追加
  - [x] `POST /profile/group/search-children`
  - [x] `POST /profile/group/send-link-request`
- [x] グループ管理画面Blade修正（セクション追加）
  - [x] search-unlinked-childrenパーシャルをインクルード
  - [x] グループ所属時のみ表示
- [x] Intellephenseエラー解決（0件達成）

### 7.4 Phase 4: 通知システム統合（✅ 完了: 2025-12-17）

- [x] NotificationTemplate作成
  - [x] `parent_link_request`
  - [x] `parent_link_approved`
  - [x] `parent_link_rejected`
- [x] config/const.php修正（通知種別追加）
- [x] PushNotificationService拡張（Web + Mobile対応確認）

### 7.5 Phase 5: 承認・拒否処理（Web）（✅ 完了: 2025-12-17）

- [x] ApproveParentLinkAction作成
- [x] RejectParentLinkAction作成
- [x] 通知詳細画面Blade修正（承認・拒否ボタン追加）
- [x] ルート追加
  - [x] `POST /notifications/{id}/approve-parent-link`
  - [x] `POST /notifications/{id}/reject-parent-link`

### 7.6 Phase 6: Mobile API実装

- [x] 未紐付け子検索API
  - [x] `POST /api/profile/group/search-children`
  - [x] SearchUnlinkedChildrenApiAction
- [x] 紐付けリクエストAPI
  - [x] `POST /api/profile/group/send-link-request`
  - [x] SendChildLinkRequestApiAction
- [x] 承認・拒否API
  - [x] `POST /api/notifications/{id}/approve-parent-link`
  - [x] `POST /api/notifications/{id}/reject-parent-link`
  - [x] ApproveParentLinkApiAction
  - [x] RejectParentLinkApiAction

### 7.7 Phase 7: Mobile UI実装

- [x] NotificationDetailScreen作成（または既存修正）
  - [x] 紐付けリクエスト表示
  - [x] 承認・拒否ボタン
  - [x] Dimensions API使用
- [x] GroupManagementScreen拡張
  - [x] 未紐付け子検索機能
  - [x] 検索結果表示
  - [x] レスポンシブ対応
- [x] useNotifications Hook拡張
  - [x] 紐付けリクエスト処理
  - [x] 承認・拒否処理

### 7.8 Phase 8: テスト実装（✅ 完了: 2025-12-17）

**完了報告**: [docs/reports/2025-12-17-phase8-parent-child-linking-test-completion-report.md](../docs/reports/2025-12-17-phase8-parent-child-linking-test-completion-report.md)

**実行結果**: 67 tests passed (346 assertions), Duration: 100.42s

- [x] Unit Tests
  - [x] UserモデルTest（`isParentInvitationExpired()`）- Integration Testでカバー
  - [x] GroupServiceTest（グループ作成）- Integration Testでカバー
- [x] Integration Tests
  - [x] 招待トークン経由登録Test（Web 7 + API 8 = 15テスト）
  - [x] 未紐付け子検索Test（Web 7 + API 6 = 13テスト）
  - [x] 紐付けリクエスト送信Test（Web 6 + API 6 = 12テスト）
  - [x] 承認処理Test（Web 6 + API 6 = 12テスト）
  - [x] 拒否処理Test（Web 7 + API 8 = 15テスト）
- [x] Feature Tests
  - [x] E2Eシナリオテスト - Integration Testで全フロー検証済み

**テスト内訳**:

| カテゴリ | Web | API | 合計 | アサーション |
|---------|-----|-----|------|------------|
| 招待トークン機能 | 7 | 8 | 15 | 78 |
| 未紐付け子検索 | 7 | 6 | 13 | 52 |
| リクエスト送信 | 6 | 6 | 12 | 48 |
| 承認処理 | 6 | 6 | 12 | 72 |
| 拒否処理 | 7 | 8 | 15 | 96 |
| **合計** | **33** | **34** | **67** | **346** |

### 7.9 Phase 9: ドキュメント作成（⏳ 予定: 2025-02-15）

- [x] 実装レポート作成（`docs/reports/`）- Phase 8完了報告作成済み
- [x] OpenAPI仕様更新（`docs/api/openapi.yaml`）- Phase 8完了時に更新済み
- [ ] モバイルルール更新（必要に応じて）
- [ ] 運用マニュアル作成
- [ ] トラブルシューティングガイド作成

---

## 8. テストケース

### 8.1 招待トークン機能

| # | テストケース | 期待結果 |
|---|-------------|---------|
| 1 | 子アカウント作成時にトークン生成 | `parent_invitation_token`が64文字、`expires_at`が30日後 |
| 2 | 保護者同意完了画面に招待リンク表示 | URLに`?parent_invite={token}`含む |
| 3 | 招待リンク経由で保護者登録 | `parent_user_id`, `group_id`設定、トークン無効化 |
| 4 | 期限切れトークンで登録試行 | エラーメッセージ表示 |
| 5 | 既存グループ所属の子で登録試行 | エラーメッセージ表示 |

### 8.2 グループ自動作成

| # | テストケース | 期待結果 |
|---|-------------|---------|
| 6 | 招待リンク経由登録時にグループ作成 | ランダム8桁名、`master_user_id`設定 |
| 7 | 保護者に`group_edit_flg=true`設定 | 編集権限付与 |
| 8 | 子アカウントに同じ`group_id`設定 | グループ参加 |

### 8.3 未紐付け子検索

| # | テストケース | 期待結果 |
|---|-------------|---------|
| 9 | `parent_email`一致で検索 | 対象の子アカウント表示 |
| 10 | 複数子アカウント存在時 | 全件リスト表示 |
| 11 | 未紐付けアカウントなし | 「見つかりませんでした」表示 |

### 8.4 紐付けリクエスト

| # | テストケース | 期待結果 |
|---|-------------|---------|
| 12 | 紐付けリクエスト送信 | `notification_templates`に作成、子に通知 |
| 13 | 既存グループ所属の子にリクエスト | エラーメッセージ |
| 14 | モバイルプッシュ通知送信 | プッシュ通知受信 |

### 8.5 承認・拒否

| # | テストケース | 期待結果 |
|---|-------------|---------|
| 15 | 子が承認 | `parent_user_id`, `group_id`設定、保護者に通知 |
| 16 | 子が拒否 | 子アカウント削除、保護者に通知 |
| 17 | 既存グループ所属状態で承認 | エラーメッセージ |

---

## 9. 非機能要件

### 9.1 パフォーマンス

- 招待トークン検証: 50ms以内
- グループ作成処理: 100ms以内
- 未紐付け子検索: 200ms以内
- プッシュ通知送信: 500ms以内

### 9.2 スケーラビリティ

- 同時招待リンク経由登録: 100req/s
- 未紐付け子検索API: 50req/s
- 通知送信: 1000件/分

### 9.3 可用性

- サービス稼働率: 99.9%
- 招待トークン有効期限: 30日間
- 紐付けリクエスト保持期限: 無期限（削除時のみクリア）

---

## 10. 監視・ログ

### 10.1 監視項目

- 招待トークン生成失敗率
- 招待リンク経由登録成功率
- 紐付けリクエスト送信成功率
- 承認・拒否処理成功率
- COPPA違反（拒否）によるアカウント削除件数

### 10.2 ログ出力

```php
// 招待トークン生成
Log::info('Parent invitation token generated', [
    'child_user_id' => $childUser->id,
    'token_expires_at' => $invitationExpiresAt,
]);

// 招待リンク経由登録
Log::info('Parent account linked to child via invitation', [
    'parent_user_id' => $parentUser->id,
    'child_user_id' => $childUser->id,
    'group_id' => $group->id,
]);

// 紐付けリクエスト送信
Log::info('Parent link request sent', [
    'parent_user_id' => $parent->id,
    'child_user_id' => $childUser->id,
    'notification_template_id' => $notificationTemplate->id,
]);

// 承認処理
Log::info('Child approved parent link request', [
    'child_user_id' => $childUser->id,
    'parent_user_id' => $parentUserId,
    'group_id' => $groupId,
]);

// 拒否処理（COPPA違反）
Log::warning('Child rejected parent link - COPPA violation, account deleted', [
    'child_user_id' => $childUser->id,
    'parent_user_id' => $parentUserId,
    'deleted_at' => now(),
]);
```

---

## 11. 制約事項・既知の問題

### 11.1 制約事項

1. **グループ所属は1つまで**: 現在のDB設計では`users.group_id`は単一のみ
2. **招待トークン有効期限固定**: 30日間（変更不可）
3. **拒否 = アカウント削除**: COPPA法遵守のため、拒否時は必ず削除

### 11.2 既知の問題

なし（初版）

---

## 12. 今後の拡張案

1. **招待リンクメール送信**: 同意完了時に保護者に招待リンクをメール送信
2. **複数グループ所属**: `users.group_id`を廃止、多対多リレーションに変更
3. **招待トークン有効期限カスタマイズ**: 管理画面で設定可能に
4. **紐付けリクエスト有効期限**: 現在無期限だが、90日等に制限
5. **ディープリンク対応**: モバイルアプリで招待リンクを直接開く

---

## 13. 参考資料

- **COPPA法**: https://www.ftc.gov/enforcement/rules/rulemaking-regulatory-reform-proceedings/childrens-online-privacy-protection-rule
- **Phase 5-1実装**: `/home/ktr/mtdev/definitions/GroupTaskManagement.md`
- **Phase 5-2実装**: `/home/ktr/mtdev/definitions/Notification.md`（保護者同意）
- **モバイル開発規則**: `/home/ktr/mtdev/docs/mobile/mobile-rules.md`
- **レスポンシブ設計**: `/home/ktr/mtdev/definitions/mobile/ResponsiveDesignGuideline.md`
