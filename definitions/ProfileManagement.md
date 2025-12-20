# アカウント管理画面（プロフィール・メールアドレス変更連動機能） 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-18 | GitHub Copilot | 初版作成: アカウント管理画面の機能要件定義、メールアドレス変更時の親子連動機能を含む包括的仕様 |
| 2025-12-20 | GitHub Copilot | Section 13追加: 子アカウント一括紐づけ機能（Phase 6拡張）- サブスクリプション別メンバー数上限チェック、部分成功対応、Web/Mobile統合仕様 |

---

## 1. 概要

### 1.1 背景

MyTeacher AIタスク管理プラットフォームにおける**アカウント管理画面**は、ユーザーが自身のプロフィール情報、アカウント設定を管理する中核機能です。特に、親子紐付け機能（Phase 5-1、Phase 5-2）との連携により、**親ユーザーがメールアドレスを変更した際、紐付く全ての子ユーザーの`parent_email`カラムを自動更新する連動処理**が必要となりました。

**問題点（修正前）**:
```
親ユーザーがメールアドレス変更
    ↓
parent_emailが古いまま残る（子ユーザー）
    ↓
❌ 子ユーザーのプロフィール画面に古いメールアドレス表示
❌ 保護者への通知メールが届かない（アドレス不一致）
❌ Phase 5-1/5-2の親子連携機能に不整合が発生
```

**解決策（修正後）**:
```
親ユーザーがメールアドレス変更
    ↓
UpdateProfileAction/UpdateProfileApiAction で検出
    ↓
parent_user_id で紐づく子ユーザーを取得（Repository層）
    ↓
子ユーザーの parent_email を新アドレスに一括更新
    ↓
✅ 親子間で常に最新メールアドレスを同期
✅ 通知・表示が正確
✅ email_verified_at をリセット（再認証が必要な場合）
```

### 1.2 目的

本要件定義書は、以下を網羅的に定義します:

1. **アカウント管理画面の全機能**: プロフィール情報編集、パスワード変更、アカウント削除、タイムゾーン設定、グループ管理
2. **メールアドレス変更時の連動処理**: 親子紐付け機能との統合仕様
3. **Webプラットフォーム実装**: Laravel Blade、Action-Service-Repositoryパターン
4. **Mobileプラットフォーム実装**: React Native、Settings画面、API連携
5. **データ整合性**: トランザクション処理、エラーハンドリング、ログ記録
6. **テストカバレッジ**: Web（ProfileTest）、Mobile API（ProfileApiTest）の統合テスト仕様

### 1.3 対象ユーザー

- **全ユーザー**: プロフィール編集、パスワード変更、アカウント削除機能
- **親ユーザー（大人テーマ）**: グループ管理、子ユーザー追加・削除機能
- **子ユーザー（子どもテーマ）**: 簡易プロフィール編集（一部機能制限）

### 1.4 関連機能との依存関係

```
アカウント管理画面
├── Phase 5-1（親が子アカウント作成）
│   └── parent_user_id, parent_email の初期設定
├── Phase 5-2（子が先に作成、保護者同意）
│   └── parent_email の設定（招待リンク経由）
├── Phase 5-2拡張（招待トークン、未紐付け検索）
│   └── parent_user_id の後付け設定
└── 本機能（メールアドレス変更連動）
    └── parent_email の同期更新
```

---

## 2. 機能要件

### 2.1 アカウント管理画面の全体構成

#### 2.1.1 アクセスルート

**Web**:
```
GET  /profile/edit       → ShowProfileAction（画面表示）
PATCH /profile           → UpdateProfileAction（更新処理）
```

**Mobile API**:
```
GET  /api/profile        → GetProfileApiAction（プロフィール取得）
PATCH /api/profile       → UpdateProfileApiAction（プロフィール更新）
```

#### 2.1.2 画面構成（Web）

**ファイル**: `resources/views/profile/edit.blade.php`

**表示セクション**:

| セクション | 機能 | 表示条件 |
|-----------|------|---------|
| **プロフィール情報** | username, email, name, avatar, bio 編集 | 全ユーザー |
| **グループ管理** | グループ作成・編集へのリンク | 親ユーザー（`!$user->isChild()`） |
| **タイムゾーン設定** | タイムゾーン選択（独立フォーム） | 全ユーザー |
| **セキュリティ設定** | パスワード変更フォーム | 全ユーザー |
| **アカウント削除** | アカウント削除の警告と実行ボタン | 全ユーザー（13歳以上） |

**レイアウト特性**:
- **レスポンシブデザイン**: Tailwind CSS ブレークポイント（sm, md, lg）対応
- **ダークモード**: `dark:` プレフィックスによるカラーパレット切り替え
- **テーマ切り替え**: 大人テーマ（高度な説明文）、子どもテーマ（ひらがな多用、簡潔な表現）

#### 2.1.3 画面構成（Mobile）

**ファイル**: `mobile/screens/settings/SettingsScreen.tsx`

**表示セクション**:

| セクション | 機能 | 実装コンポーネント |
|-----------|------|-------------------|
| **プロフィール編集** | username, email, name 編集（モーダル） | `EditProfileModal` |
| **アバター設定** | 教師アバター選択・生成 | `TeacherAvatarScreen` へ遷移 |
| **通知設定** | プッシュ通知ON/OFF | `Switch` コンポーネント |
| **タイムゾーン設定** | タイムゾーン選択 | `timezone.service.ts` |
| **テーマ設定** | 大人/子どもテーマ切り替え | `useTheme()` フック |
| **グループ管理** | グループメンバー管理 | `GroupManagementScreen` へ遷移（親のみ） |
| **アカウント削除** | アカウント削除確認ダイアログ | `Alert.alert()` |

**レスポンシブデザイン仕様**:
- **デバイスブレークポイント**: 
  - xs: ≤320px (iPhone SE)
  - sm: 321-374px
  - md: 375-413px (標準スマホ)
  - lg: 414-767px (大画面スマホ)
  - tablet-sm: 768-1023px (小型タブレット)
  - tablet: 1024px+ (iPad Pro)
- **フォントスケール**: 大人テーマは `getFontSize()` で動的計算、子どもテーマは+20%
- **レイアウト**: `Dimensions.get('window')` で動的サイズ計算
- **参照ドキュメント**: `definitions/mobile/ResponsiveDesignGuideline.md`

---

## 3. プロフィール情報更新機能

### 3.1 基本仕様

#### 3.1.1 更新可能項目

| 項目 | 型 | 必須 | 制約 | デフォルト | 説明 |
|------|-----|------|------|-----------|------|
| `username` | string | ✓ | max:255, unique | - | ユーザー名（ログインID） |
| `email` | string | ✓ | email, max:255, unique | - | メールアドレス |
| `name` | string | - | max:255 | username | 表示名（空の場合はusernameを使用） |
| `avatar` | file | - | image, max:5MB | null | プロフィール画像（public/avatars保存） |
| `bio` | text | - | max:1000 | null | 自己紹介文（カラム存在時のみ） |

**バリデーションルール**:
```php
// UpdateProfileRequest.php
public function rules(): array
{
    $userId = $this->user()->id;
    
    return [
        'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($userId)],
        'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
        'name' => ['nullable', 'string', 'max:255'],
        'avatar' => ['nullable', 'image', 'max:5120'], // 5MB
        'bio' => ['nullable', 'string', 'max:1000'],
    ];
}
```

#### 3.1.2 処理フロー（Web）

**Action**: `UpdateProfileAction::__invoke()`

```
1. バリデーション実行（UpdateProfileRequest）
   ├── username 重複チェック（自分以外）
   ├── email 重複チェック（自分以外）
   └── avatar ファイル形式・サイズ検証

2. 画像アップロード処理（任意）
   ├── 既存画像があれば削除（Storage::disk('public')->delete）
   ├── 新画像を public/avatars に保存
   └── $validated['avatar_path'] に格納

3. メールアドレス変更検出
   ├── $emailChanged = ($user->email !== $validated['email'])
   └── true の場合、email_verified_at = null（カラム存在確認後）

4. ユーザー情報更新
   ├── username, email, name を代入
   ├── name が空の場合は username を使用
   ├── avatar_path を更新（画像アップロード時）
   └── $user->save()

5. 子ユーザーの parent_email 更新（メールアドレス変更時のみ）
   ├── ProfileUserRepositoryInterface::getChildrenByParentUserId() 呼び出し
   ├── Collection が空でない場合
   │   ├── ProfileUserRepositoryInterface::updateChildrenParentEmail() 呼び出し
   │   └── Log::info() で更新件数記録
   └── 例外発生時は Log::error() 記録（親更新は継続）

6. Responder 経由でリダイレクト
   └── return $responder->respondUpdateSuccess()
       → /profile/edit へリダイレクト、success flash メッセージ
```

#### 3.1.3 処理フロー（Mobile API）

**Action**: `UpdateProfileApiAction::__invoke()`

```
1. バリデーション実行（UpdateProfileRequest）
   ※ Web と同じルール

2. 画像アップロード処理（任意）
   ※ Web と同じ処理（Storage::disk('public')）

3. メールアドレス変更検出
   ※ Web と同じロジック

4. ユーザー情報更新
   ※ Web と同じロジック

5. 子ユーザーの parent_email 更新
   ※ Web と同じロジック

6. JSON レスポンス返却
   └── return response()->json([
         'success' => true,
         'message' => 'プロフィールを更新しました。',
         'user' => [
             'id' => $user->id,
             'username' => $user->username,
             'email' => $user->email,
             'name' => $user->name,
             'avatar_url' => $user->avatar_url,
             'email_verified_at' => $user->email_verified_at,
         ],
       ], 200);
```

**Mobile側の呼び出し**:
```typescript
// mobile/services/profile.service.ts
export const updateProfile = async (data: UpdateProfileData): Promise<User> => {
  const formData = new FormData();
  Object.entries(data).forEach(([key, value]) => {
    if (value !== null && value !== undefined) {
      formData.append(key, value);
    }
  });

  const response = await apiClient.patch('/profile', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });

  return response.data.user;
};
```

---

## 4. メールアドレス変更時の連動処理（親子機能統合）

### 4.1 機能要件

#### 4.1.1 概要

親ユーザーが `UpdateProfileAction` または `UpdateProfileApiAction` でメールアドレスを変更した場合、以下の処理を**自動的に**実行する:

1. **email_verified_at のリセット**: メール再認証が必要な場合（カラムが存在する場合のみ）
2. **子ユーザーの parent_email 更新**: parent_user_id で紐づく全ての子ユーザーの parent_email を新アドレスに更新
3. **エラーハンドリング**: 子ユーザー更新失敗時もログ記録のみで親更新を継続

#### 4.1.2 連動処理の判定条件

**対象ユーザー**: 
- `parent_user_id` で紐づく子ユーザーが**1人以上存在する**親ユーザー
- グループ所属の有無は**関係なし**（parent_user_id のみで判定）

**トリガー条件**:
```php
$emailChanged = ($user->email !== $validated['email']);
```

#### 4.1.3 子ユーザーの取得と更新

**Repository層の実装**:

**インターフェース**: `ProfileUserRepositoryInterface.php`
```php
namespace App\Repositories\Profile;

use Illuminate\Database\Eloquent\Collection;

interface ProfileUserRepositoryInterface
{
    /**
     * 指定された親ユーザーIDに紐づく子ユーザー一覧を取得
     * 
     * @param int $parentUserId 親ユーザーのID
     * @return Collection 子ユーザーのCollection（空の場合もあり）
     */
    public function getChildrenByParentUserId(int $parentUserId): Collection;

    /**
     * 子ユーザーの parent_email を一括更新
     * 
     * @param Collection $children 更新対象の子ユーザーCollection
     * @param string $newEmail 新しいメールアドレス
     * @return int 更新された行数
     */
    public function updateChildrenParentEmail(Collection $children, string $newEmail): int;
}
```

**実装クラス**: `ProfileUserEloquentRepository.php`
```php
namespace App\Repositories\Profile;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProfileUserEloquentRepository implements ProfileUserRepositoryInterface
{
    public function getChildrenByParentUserId(int $parentUserId): Collection
    {
        return User::query()
            ->where('parent_user_id', $parentUserId)
            ->get();
    }

    public function updateChildrenParentEmail(Collection $children, string $newEmail): int
    {
        if ($children->isEmpty()) {
            return 0;
        }

        $userIds = $children->pluck('id')->toArray();

        return User::whereIn('id', $userIds)
            ->update(['parent_email' => $newEmail]);
    }
}
```

**DIバインディング**: `AppServiceProvider::register()`
```php
$this->app->bind(
    ProfileUserRepositoryInterface::class,
    ProfileUserEloquentRepository::class
);
```

#### 4.1.4 Action層の実装

**UpdateProfileAction.php**（抜粋）:
```php
// メールアドレスが変更された場合、子ユーザーのparent_emailも更新
if ($emailChanged) {
    try {
        $children = $this->userRepository->getChildrenByParentUserId($user->id);
        if ($children->isNotEmpty()) {
            $updatedCount = $this->userRepository->updateChildrenParentEmail($children, $validated['email']);
            Log::info('子ユーザーのparent_email更新完了', [
                'parent_user_id' => $user->id,
                'new_email' => $validated['email'],
                'updated_count' => $updatedCount,
            ]);
        }
    } catch (\Exception $e) {
        Log::error('子ユーザーのparent_email更新失敗', [
            'parent_user_id' => $user->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        // 親ユーザーの更新は成功しているため、エラーをログに記録するのみ
    }
}
```

**重要ポイント**:
- **トランザクション不使用**: 親の更新と子の更新は独立（親は必ず成功させる）
- **エラー時の挙動**: 子の更新失敗は親の成功に影響しない（ログのみ記録）
- **パフォーマンス**: 一括更新（`whereIn()->update()`）でN+1問題回避

### 4.2 データモデル

#### 4.2.1 usersテーブル

**関連カラム**:
```sql
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    parent_user_id BIGINT NULL,                   -- 親ユーザーのID（子アカウントのみ）
    parent_email VARCHAR(255) NULL,                -- 親のメールアドレス（子アカウントのみ）
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NULL,
    avatar_path VARCHAR(255) NULL,
    bio TEXT NULL,
    email_verified_at TIMESTAMP NULL,              -- メール認証日時
    password VARCHAR(255) NOT NULL,
    theme VARCHAR(50) DEFAULT 'adult',             -- 'adult' または 'child'
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_parent_user_id (parent_user_id)
);
```

**カラム詳細**:

| カラム | 型 | Null | 説明 | 連動処理での扱い |
|--------|-----|------|------|-----------------|
| `parent_user_id` | bigint | ✓ | 親ユーザーのID | 子ユーザー検索キー |
| `parent_email` | varchar(255) | ✓ | 親のメールアドレス | **更新対象**（親のメール変更時） |
| `email` | varchar(255) | ✗ | ユーザーのメールアドレス | 変更検出元 |
| `email_verified_at` | timestamp | ✓ | メール認証日時 | メール変更時に null にリセット |

#### 4.2.2 データ整合性の保証

**外部キー制約**:
```sql
FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE SET NULL
```
- 親ユーザーが削除された場合、`parent_user_id` を `NULL` に設定（孤立防止）

**更新時のロジック**:
1. `parent_user_id` で子ユーザーを取得（Collection）
2. Collection が空でない場合のみ更新実行
3. `whereIn('id', $userIds)->update(['parent_email' => $newEmail])` で一括更新

---

## 5. その他のアカウント管理機能

### 5.1 パスワード変更機能

**アクセスルート**:
```
PATCH /profile/password → UpdatePasswordAction
```

**入力項目**:
- `current_password` (required): 現在のパスワード
- `password` (required, confirmed, min:8): 新しいパスワード
- `password_confirmation` (required): 新しいパスワード（確認用）

**処理フロー**:
```
1. 現在のパスワード検証（Hash::check）
2. 新しいパスワードをハッシュ化
3. ユーザーの password カラム更新
4. セッション再生成（security対策）
5. 成功メッセージと共にリダイレクト
```

**バリデーションルール**:
```php
'current_password' => ['required', 'current_password'],
'password' => ['required', Password::defaults(), 'confirmed'],
```

### 5.2 アカウント削除機能

**アクセスルート**:
```
DELETE /profile → DestroyAction
```

**削除条件**:
- **13歳以上**: 本人の確認（パスワード入力）で削除可能
- **13歳未満**: COPPA法遵守、親の承認が必要（Phase 5-2拡張の拒否フローで自動削除）

**処理フロー**:
```
1. パスワード検証（required_with_all）
2. セッション無効化（Auth::logout）
3. ユーザーレコード削除（$user->delete() - ソフトデリート）
4. 関連データの削除
   ├── タスク（tasks.user_id - CASCADE）
   ├── グループメンバーシップ（group_user.user_id - CASCADE）
   ├── トークントランザクション（token_transactions.user_id - CASCADE）
   └── 通知（notifications.user_id - CASCADE）
5. ログイン画面へリダイレクト
```

**バリデーションルール**:
```php
'password' => ['required_with_all', 'current_password'],
```

### 5.3 タイムゾーン設定機能

**アクセスルート**:
```
GET  /profile/timezone → ShowTimezoneAction
PATCH /profile/timezone → UpdateTimezoneAction
```

**処理フロー**:
```
1. タイムゾーン一覧を取得（DateTimeZone::listIdentifiers）
2. ユーザーの現在のタイムゾーンを表示
3. 選択されたタイムゾーンを users.timezone カラムに保存
4. 成功メッセージと共にリダイレクト
```

### 5.4 グループ管理機能

**アクセスルート**:
```
GET  /group/edit → GroupEditAction
POST /group      → GroupStoreAction
PATCH /group/{id} → GroupUpdateAction
```

**表示条件**: 親ユーザー（`!$user->isChild()`）のみ

**機能**:
- グループ作成（新規登録）
- グループ名編集
- メンバー追加・削除
- グループマスターの変更（master_user_id）

**参照**: `definitions/GroupTaskManagement.md`, `definitions/ParentChildLinking.md`

---

## 6. テスト仕様

### 6.1 Web統合テスト（ProfileTest.php）

**テストクラス**: `tests/Feature/ProfileTest.php`

**カバレッジ**: 7テスト

| テストケース | 概要 | アサーション |
|-------------|------|-------------|
| `profile information can be updated` | プロフィール情報の正常更新 | username, email, name が更新されること |
| `email address is verified` | メール認証状態の確認 | email_verified_at が null でないこと |
| `user can delete their account` | アカウント削除処理 | ソフトデリートが正常に動作すること |
| `profile screen can be rendered` | プロフィール画面表示 | ステータスコード 200 |
| `authenticated user can view profile edit page` | プロフィール編集画面表示 | 認証ユーザーのみアクセス可能 |
| **`parent email is updated for children when parent changes email`** | 親のメール変更時の子への連動 | 子ユーザーの parent_email が新アドレスに更新されること |
| **`parent email is not updated for children when parent email unchanged`** | メール未変更時の非連動 | parent_email が変更されないこと |

**重要テスト（親子連動）の実装**:
```php
test('親のメールアドレス変更時、子ユーザーのparent_emailも更新される', function () {
    // 親ユーザーと子ユーザーを作成
    $parent = User::factory()->create(['email' => 'parent@example.com']);
    $child = User::factory()->create([
        'parent_user_id' => $parent->id,
        'parent_email' => 'parent@example.com',
    ]);

    // 親ユーザーがメールアドレスを変更
    $this->actingAs($parent)
        ->patch('/profile', [
            'username' => $parent->username,
            'email' => 'new-parent@example.com',
            'name' => $parent->name,
        ]);

    // 子ユーザーのparent_emailが更新されていることを確認
    expect($child->fresh()->parent_email)->toBe('new-parent@example.com');
});

test('親のメールアドレスが変更されない場合、子ユーザーのparent_emailも更新されない', function () {
    $parent = User::factory()->create(['email' => 'parent@example.com']);
    $child = User::factory()->create([
        'parent_user_id' => $parent->id,
        'parent_email' => 'parent@example.com',
    ]);

    // 親ユーザーがメールアドレス以外を変更
    $this->actingAs($parent)
        ->patch('/profile', [
            'username' => 'new-username',
            'email' => 'parent@example.com', // 変更なし
            'name' => 'New Name',
        ]);

    // 子ユーザーのparent_emailが変更されていないことを確認
    expect($child->fresh()->parent_email)->toBe('parent@example.com');
});
```

### 6.2 Mobile API統合テスト（ProfileApiTest.php）

**テストクラス**: `tests/Feature/Api/ProfileApiTest.php`

**カバレッジ**: 4テスト

| テストケース | 概要 | アサーション |
|-------------|------|-------------|
| `authenticated user can get profile via API` | プロフィール取得 | JSON構造、ステータス 200 |
| `authenticated user can update profile via API` | プロフィール更新（API） | username, email, name が更新されること |
| **`parent email is updated for children when parent changes email via API`** | 親のメール変更時の子への連動（API） | 子ユーザーの parent_email が新アドレスに更新されること |
| `authenticated user can update theme via API` | テーマ変更 | theme が 'child' に更新されること |

**重要テスト（親子連動）の実装**:
```php
test('API経由で親のメールアドレス変更時、子ユーザーのparent_emailも更新される', function () {
    $parent = User::factory()->create(['email' => 'parent@example.com']);
    $child = User::factory()->create([
        'parent_user_id' => $parent->id,
        'parent_email' => 'parent@example.com',
    ]);

    Sanctum::actingAs($parent);

    $this->patchJson('/api/profile', [
        'username' => $parent->username,
        'email' => 'new-parent@example.com',
        'name' => $parent->name,
    ])->assertOk()
      ->assertJson(['success' => true]);

    expect($child->fresh()->parent_email)->toBe('new-parent@example.com');
});
```

### 6.3 テスト実行方法

**全テスト実行**:
```bash
cd /home/ktr/mtdev
CACHE_STORE=array DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
```

**特定テストファイルのみ**:
```bash
CACHE_STORE=array DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/ProfileTest.php
CACHE_STORE=array DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test tests/Feature/Api/ProfileApiTest.php
```

**フィルタリング実行**:
```bash
CACHE_STORE=array DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter="parent email"
```

**注意事項**:
- `CACHE_STORE=array`: Redis接続を回避（テスト環境でRedis未起動のため）
- `DB_CONNECTION=sqlite`: SQLiteインメモリデータベース使用（高速化）
- `phpunit.xml` に環境変数設定あり（`.env` の設定が優先されるためコマンドライン指定必須）

---

## 7. エラーハンドリング

### 7.1 バリデーションエラー

**発生条件**:
- username 重複（自分以外）
- email 重複（自分以外）
- email 形式不正
- avatar ファイルサイズ超過（5MB以上）
- avatar ファイル形式不正（非画像ファイル）

**エラーレスポンス（Web）**:
```php
return redirect()->back()
    ->withErrors($validator)
    ->withInput();
```

**エラーレスポンス（Mobile API）**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["このメールアドレスは既に使用されています。"],
    "username": ["このユーザー名は既に使用されています。"]
  }
}
```

### 7.2 子ユーザー更新エラー

**発生条件**:
- `parent_user_id` が存在するが、users テーブルに該当レコードが存在しない（外部キー整合性違反）
- データベース接続エラー
- トランザクションタイムアウト

**ハンドリング**:
```php
try {
    $children = $this->userRepository->getChildrenByParentUserId($user->id);
    if ($children->isNotEmpty()) {
        $updatedCount = $this->userRepository->updateChildrenParentEmail($children, $validated['email']);
        Log::info('子ユーザーのparent_email更新完了', [...]);
    }
} catch (\Exception $e) {
    Log::error('子ユーザーのparent_email更新失敗', [
        'parent_user_id' => $user->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    // 親ユーザーの更新は成功しているため、エラーをログに記録するのみ
}
```

**重要ポイント**:
- **親の更新は必ず成功**: 子の更新失敗が親の更新をロールバックしない
- **ログ記録**: CloudWatch Logs（本番）、storage/logs/（開発）に記録
- **運用対応**: ログを定期監視し、エラー発生時は手動でparent_emailを修正

### 7.3 認証エラー

**発生条件**:
- 未認証ユーザーがプロフィール編集にアクセス
- セッションタイムアウト
- トークン無効（Mobile API）

**エラーレスポンス（Web）**:
```
302 Redirect → /login
```

**エラーレスポンス（Mobile API）**:
```json
{
  "message": "Unauthenticated."
}
```
ステータスコード: 401

---

## 8. セキュリティ要件

### 8.1 メールアドレス変更時の再認証

**仕様**:
- メールアドレス変更時、`email_verified_at` カラムを `null` にリセット
- カラムの存在を事前確認（`Schema::hasColumn('users', 'email_verified_at')`）
- 本番環境のみカラムが存在（開発環境は未実装）

**実装**:
```php
if ($emailChanged && Schema::hasColumn('users', 'email_verified_at')) {
    $user->email_verified_at = null;
}
```

**理由**:
- メールアドレスの所有権確認（新アドレスへの認証メール送信が前提）
- セキュリティベストプラクティスに準拠

### 8.2 パスワード検証

**適用箇所**:
- パスワード変更時: 現在のパスワード検証必須
- アカウント削除時: 現在のパスワード検証必須（13歳以上）

**実装**:
```php
'current_password' => ['required', 'current_password'],
```

### 8.3 画像アップロードのセキュリティ

**制約**:
- ファイル形式: 画像のみ（MIME type検証）
- ファイルサイズ: 最大5MB
- 保存先: `storage/app/public/avatars`（シンボリックリンク経由で公開）

**実装**:
```php
'avatar' => ['nullable', 'image', 'max:5120'], // 5MB = 5120KB
```

**保存処理**:
```php
if ($request->hasFile('avatar')) {
    if (!empty($user->avatar_path)) {
        Storage::disk('public')->delete($user->avatar_path);
    }
    $path = $request->file('avatar')->store('avatars', 'public');
    $validated['avatar_path'] = $path;
}
```

### 8.4 CSRF保護

**Web**:
- 全フォームに `@csrf` ディレクティブ必須
- Laravel の CSRF ミドルウェアで自動検証

**Mobile API**:
- Sanctum トークン認証（`Authorization: Bearer {token}`）
- CSRF不要（ステートレス認証）

---

## 9. パフォーマンス最適化

### 9.1 N+1問題の回避

**実装**: Repository層での一括更新
```php
public function updateChildrenParentEmail(Collection $children, string $newEmail): int
{
    if ($children->isEmpty()) {
        return 0;
    }

    $userIds = $children->pluck('id')->toArray();

    return User::whereIn('id', $userIds)
        ->update(['parent_email' => $newEmail]);
}
```

**効果**:
- 子ユーザー数に関係なく、1回のUPDATE文で処理完了
- 100人の子がいても1クエリで更新

### 9.2 インデックス設計

**必須インデックス**:
```sql
CREATE INDEX idx_parent_user_id ON users(parent_user_id);
```

**理由**:
- `WHERE parent_user_id = ?` クエリの高速化
- 親ユーザーの子検索が頻繁に発生するため

### 9.3 キャッシュクリア

**適用箇所**:
- プロフィール更新成功時にキャッシュクリア不要（現在はキャッシュ使用なし）
- 将来的にキャッシュ導入時は `Cache::forget("user.{$user->id}.profile")` を検討

---

## 10. 運用・監視

### 10.1 ログ記録

**記録内容**:

| イベント | ログレベル | 記録項目 |
|---------|-----------|---------|
| プロフィール更新成功 | info | user_id, updated_fields |
| メールアドレス変更 | info | user_id, old_email, new_email, email_verified_at |
| 子ユーザー parent_email 更新成功 | info | parent_user_id, new_email, updated_count |
| 子ユーザー parent_email 更新失敗 | error | parent_user_id, error, trace |

**ログファイル**:
- 開発環境: `storage/logs/laravel-YYYY-MM-DD.log`
- 本番環境: CloudWatch Logs `/ecs/myteacher-production`

### 10.2 監視アラート

**推奨アラート設定**:
1. **子ユーザー更新エラー**: 
   - 検索パターン: `子ユーザーのparent_email更新失敗`
   - 閾値: 1件/時間
   - 対応: 手動でparent_email修正

2. **email_verified_at カラム不存在エラー**:
   - 検索パターン: `SQLSTATE[42703].*email_verified_at`
   - 閾値: 1件/日
   - 対応: マイグレーション実行（2025_12_18_011735_add_email_verified_at_to_users_table.php）

### 10.3 定期確認タスク

**週次タスク**:
1. 子ユーザーの parent_email と親の email の整合性確認
   ```sql
   SELECT c.id, c.username, c.parent_email, p.email
   FROM users c
   INNER JOIN users p ON c.parent_user_id = p.id
   WHERE c.parent_email <> p.email;
   ```
2. 不整合が検出された場合、手動で parent_email を修正

**月次タスク**:
1. email_verified_at が null の親ユーザーを確認
2. メール認証を促すリマインダー送信（将来実装予定）

---

## 11. 今後の拡張予定

### 11.1 メール認証機能の実装

**Phase**: 未定

**概要**:
- メールアドレス変更時、新アドレスへ認証リンク送信
- リンククリックで `email_verified_at` を現在時刻に更新
- 未認証の場合、一部機能（パスワードリセット等）を制限

**関連ファイル**:
- `app/Notifications/VerifyEmailNotification.php` (新規作成)
- `routes/web.php` (認証ルート追加)

### 11.2 親子メールアドレス同期の通知

**Phase**: 未定

**概要**:
- 親のメールアドレス変更時、子ユーザーに通知を送信
- 通知内容: 「保護者のメールアドレスが {old} から {new} に変更されました」
- プッシュ通知 + アプリ内通知の両方に対応

**関連ファイル**:
- `app/Notifications/ParentEmailChangedNotification.php` (新規作成)
- `mobile/screens/notifications/NotificationDetailScreen.tsx` (表示対応)

### 11.3 プロフィール履歴管理

**Phase**: 未定

**概要**:
- プロフィール変更履歴をテーブルに保存（audit log）
- 管理者が不正変更を追跡可能にする

**関連テーブル**:
```sql
CREATE TABLE user_profile_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT NOW(),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 12. 関連ドキュメント

| ドキュメント | パス | 概要 |
|------------|------|------|
| 親子紐付け機能 | `definitions/ParentChildLinking.md` | Phase 5-1, 5-2, 5-2拡張の仕様 |
| グループタスク管理 | `definitions/GroupTaskManagement.md` | グループ作成・管理機能 |
| タスク機能 | `definitions/Task.md` | タスク登録・承認・完了の仕様 |
| レスポンシブデザイン | `definitions/mobile/ResponsiveDesignGuideline.md` | Mobileのデバイスブレークポイント、フォントスケール |
| モバイル開発規約 | `docs/mobile/mobile-rules.md` | ディレクトリ構成、命名規則 |
| OpenAPI仕様 | `docs/api/openapi.yaml` | Mobile API のエンドポイント定義 |
| 調査レポート | `docs/reports/2025-12-18-production-database-schema-mismatch-investigation.md` | email_verified_at カラム不存在の根本原因調査 |

---

## 13. 子アカウント一括紐づけ機能（Phase 6拡張）

### 13.1 概要

**Phase**: Phase 6拡張（2025-12-20実装完了）

**背景**:
親子紐づけ機能の簡略化により、従来のメール通知→同意プロセスを廃止し、**親が検索した子アカウントを即座にグループに紐づける**機能を実装しました。同時に、**サブスクリプションプラン別のメンバー数上限チェック**を追加し、ビジネスルールを厳密に適用します。

**主要機能**:
1. **未紐付け子アカウント検索**: 親のメールアドレス（`parent_email`）で未紐付けの子を検索
2. **検索結果モーダル表示**: 子アカウント一覧を表示、各子に「×」ボタンで除外可能
3. **一括紐づけ実行**: 選択した子アカウントを即座にグループに追加
4. **メンバー数上限チェック**: サブスクリプションプラン別に上限を適用
   - 無料プラン（`subscription_active=false`）: 最大6名、超過時はアップグレード案内
   - Familyプラン（`subscription_plan='family'`）: 最大6名
   - Enterpriseプラン（`subscription_plan='enterprise'`）: 最大20名
5. **部分成功対応**: 一部の子のみ紐づけ成功時は206 Partial Contentを返却
6. **全失敗対応**: 全員スキップ時は400 Bad Requestを返却

### 13.2 データモデル

#### 13.2.1 関連テーブル

**users**テーブル:
| カラム | 型 | 説明 |
|--------|-----|------|
| `group_id` | bigint | 所属グループID（紐づけ時に設定） |
| `parent_user_id` | bigint | 親ユーザーID（紐づけ時に設定） |
| `group_edit_flg` | boolean | グループ編集権限（紐づけ時はfalse） |
| `parent_email` | varchar(255) | 保護者のメールアドレス（検索キー） |
| `is_minor` | boolean | 未成年フラグ |

**groups**テーブル:
| カラム | 型 | 説明 |
|--------|-----|------|
| `subscription_active` | boolean | サブスクリプション有効フラグ |
| `subscription_plan` | varchar(50) | プラン名（'family', 'enterprise', null） |
| `max_members` | integer | メンバー数上限（6 or 20） |

### 13.3 処理フロー（Web）

**Action**: `LinkChildrenAction::__invoke()`

```
1. バリデーション実行（LinkChildrenRequest）
   ├── child_user_ids: required, array, min:1
   ├── child_user_ids.*: integer, exists:users,id
   └── 認可チェック: 親ユーザーがgroup_idを持つこと

2. グループのメンバー数上限を取得
   ├── $group = $parentUser->group
   ├── $maxMembers = $group->subscription_active ? $group->max_members : 6
   └── $currentMemberCount = User::where('group_id', $parentUser->group_id)->count()

3. トランザクション内で各子アカウントをループ処理
   DB::transaction(function () {
       foreach ($childUserIds as $childUserId) {
           // 3-1. メンバー数上限チェック（各紐づけ前）
           if ($currentMemberCount >= $maxMembers) {
               $limitMessage = $group->subscription_active
                   ? "グループメンバーの上限（{$maxMembers}名）に達しています。"
                   : "グループメンバーの上限（{$maxMembers}名）に達しています。エンタープライズプランにアップグレードしてください。";
               
               $skippedChildren[] = ['username' => ..., 'reason' => $limitMessage];
               continue; // スキップ
           }

           // 3-2. 子アカウント存在チェック
           $childUser = User::find($childUserId);
           if (!$childUser) {
               $skippedChildren[] = ['username' => 'ID: ' . $childUserId, 'reason' => '子アカウントが見つかりませんでした。'];
               continue;
           }

           // 3-3. 既にグループ所属チェック
           if ($childUser->group_id !== null) {
               $skippedChildren[] = ['username' => $childUser->username, 'reason' => '既に別のグループに所属しています。'];
               continue;
           }

           // 3-4. 紐づけ実行
           $childUser->update([
               'group_id' => $parentUser->group_id,
               'group_edit_flg' => false,
               'parent_user_id' => $parentUser->id,
           ]);

           // 3-5. 成功カウント増加
           $currentMemberCount++;
           $linkedChildren[] = $childUser->username;

           Log::info('Web: Child account linked', [
               'parent_user_id' => $parentUser->id,
               'child_user_id' => $childUser->id,
               'group_id' => $parentUser->group_id,
               'current_member_count' => $currentMemberCount,
               'max_members' => $maxMembers,
           ]);
       }
   });

4. レスポンス判定
   ├── 全て成功: 200 OK
   ├── 一部成功・一部スキップ: 206 Partial Content
   └── 全て失敗: 400 Bad Request

5. AJAX/JSON応答の場合
   return response()->json([
       'success' => !empty($linkedChildren),
       'message' => '...',
       'data' => [
           'linked_children' => [...],
           'skipped_children' => [...],
           'summary' => ['total_requested' => ..., 'linked' => ..., 'skipped' => ...]
       ]
   ], $statusCode);
```

### 13.4 処理フロー（Mobile API）

**Action**: `LinkChildrenApiAction::__invoke()`

**処理内容**: Web版と同一ロジック、レスポンス形式のみ異なる（`user_id`, `email`を含む）

**レスポンス例**:
```json
{
  "success": true,
  "message": "2人を紐づけました。1人はスキップされました。",
  "data": {
    "linked_children": [
      {"user_id": 10, "username": "child1", "name": "太郎", "email": "child1@example.com"}
    ],
    "skipped_children": [
      {"user_id": 11, "username": "child2", "name": "花子", "reason": "グループメンバーの上限（6名）に達しています。"}
    ],
    "summary": {
      "total_requested": 2,
      "linked": 1,
      "skipped": 1
    }
  }
}
```

### 13.5 UI/UX仕様（Web）

**ファイル**: `resources/views/profile/group/partials/search-unlinked-children.blade.php`

**検索フォーム**:
```blade
<form id="search-children-form" method="POST" action="{{ route('profile.group.search-children') }}">
    <input type="email" name="parent_email" value="{{ auth()->user()->email }}" required>
    <button type="submit">検索</button>
</form>
```

**検索結果モーダル**:
```
[モーダルヘッダー] 検索結果
[子アカウントカード×N]
  ├── アバター（username頭文字）
  ├── 名前（name || username）
  ├── @username
  ├── email
  ├── 13歳未満バッジ（is_minor=trueの場合）
  └── [×]ボタン（除外用、クリックでカードを削除）
[フッター]
  ├── 「×」ボタンで対象から除外できます
  └── [選択したN人を紐づける]ボタン（選択数を表示）
```

**JavaScript**: `resources/js/group-link-children.js`（新規作成）

**主要機能**:
1. 検索フォーム送信（AJAX）
2. 検索結果モーダル表示
3. 「×」ボタンで子アカウントを除外
4. 選択数リアルタイム更新
5. 一括紐づけフォーム送信（JSON形式）
6. 結果表示（成功・スキップメッセージ）

### 13.6 UI/UX仕様（Mobile）

**ファイル**: `mobile/src/components/group/SearchChildrenModal.tsx`

**変更内容**:
- 従来: 各子アカウントに「送信」ボタン（個別送信）
- 新仕様: 各子アカウントに「×」ボタン（除外用）+ モーダル下部に一括紐づけボタン

**APIサービス**: `mobile/src/services/group.service.ts`

```typescript
export const linkChildren = async (childUserIds: number[]): Promise<{
  success: boolean;
  message: string;
  data: {
    linked_children: Array<{...}>;
    skipped_children: Array<{...}>;
    summary: {...};
  };
}> => {
  const response = await api.post('/profile/group/link-children', {
    child_user_ids: childUserIds,
  });
  return response.data;
};
```

### 13.7 エラーハンドリング

| エラー種別 | ステータスコード | メッセージ | 対処方法 |
|-----------|----------------|-----------|---------|
| バリデーションエラー | 400 | 「紐づけする子アカウントを選択してください。」 | 最低1人選択を促す |
| 認可エラー | 403 | 「グループに所属していないため、子アカウントを紐づけできません。」 | グループ作成を案内 |
| 全員スキップ | 400 | 「選択した子アカウント全員が既にグループに所属しているため、紐づけできませんでした。」 | スキップ理由を表示 |
| 部分成功 | 206 | 「2人を紐づけました。1人はスキップされました。」 | 成功・スキップ詳細を表示 |
| サーバーエラー | 500 | 「子アカウントの紐づけ中にエラーが発生しました。」 | エラーログを記録、管理者に通知 |

### 13.8 テスト仕様

**テストファイル**: `tests/Feature/Profile/Group/LinkChildrenMemberLimitTest.php`（新規作成）

**テストケース**（全7ケース、29アサーション）:

| # | テストケース | 想定シナリオ | 期待結果 |
|---|-------------|-------------|---------|
| 1 | 無料プラン: 6名超える紐づけスキップ | 5名+3名→6名 | 1成功, 2スキップ, 206 |
| 2 | Familyプラン: 6名上限適用 | 6名+2名 | 全スキップ, 400 |
| 3 | Enterpriseプラン: 20名上限適用 | 20名+2名 | 全スキップ, 400 |
| 4 | Enterprise: 19名→1名のみ成功 | 19名+3名→20名 | 1成功, 2スキップ, 206 |
| 5 | 無料: 上限ピッタリ（5+1=6） | 5名+1名→6名 | 1成功, 200 |
| 6 | API無料プラン: 6名超えスキップ | 6名+2名 | 全スキップ, 400 |
| 7 | API Enterprise: 部分成功 | 19名+3名→20名 | 1成功, 2スキップ, 206 |

**テスト実行結果**:
```
PASS Tests\Feature\Profile\Group\LinkChildrenMemberLimitTest
Tests: 7 passed (29 assertions)
Duration: 8.58s
```

### 13.9 関連ドキュメント

| ドキュメント | パス | 概要 |
|------------|------|------|
| **実装完了レポート** | `docs/reports/2025-12-20-subscription-member-limit-implementation-report.md` | 実装内容・テスト結果の詳細 |
| **API仕様書** | `docs/api/openapi.yaml` | `/profile/group/link-children` APIドキュメント |
| **テストコード** | `tests/Feature/Profile/Group/LinkChildrenMemberLimitTest.php` | 7テストケース |
| **モバイル開発規約** | `docs/mobile/mobile-rules.md` | React Native規約 |
| **レスポンシブデザイン** | `definitions/mobile/ResponsiveDesignGuideline.md` | ダークモード、ブレークポイント |

---

## 14. 変更履歴の記録方法

**ルール**:
1. 本ドキュメントを修正する際は、冒頭の「更新履歴」セクションに追記
2. 既存コンテンツは保持し、新規セクションを追加
3. 削除する場合は取り消し線（~~削除内容~~）を使用

**フォーマット**:
```markdown
| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| YYYY-MM-DD | 更新者名 | 変更内容の概要 |
```

---

## 15. まとめ

本要件定義書は、MyTeacher AIタスク管理プラットフォームにおける**アカウント管理画面**および**親子紐づけ機能**の全機能を包括的に定義しました。

**主要機能**:
1. **親ユーザーのメールアドレス変更時に子ユーザーのparent_emailを自動更新する連動処理**: 親子紐付け機能（Phase 5-1、5-2、5-2拡張）との整合性を保つ
2. **子アカウント一括紐づけ機能（Phase 6拡張）**: サブスクリプションプラン別のメンバー数上限チェック、部分成功対応、Web/Mobile統合

**実装のポイント**:
- **Repository層の活用**: データアクセスロジックをインターフェース化し、テスタビリティを確保
- **エラーハンドリング**: 親の更新成功を最優先し、子の更新失敗は影響させない
- **パフォーマンス**: 一括更新クエリでN+1問題を回避
- **テストカバレッジ**: 
  - メールアドレス連動: Web・Mobile APIの両方で統合テストを実装（11テスト、全パス）
  - 一括紐づけ: 7テストケース、29アサーション、100%成功

**新機能（2025-12-20追加）**:
- **サブスクリプション別上限チェック**: 無料6名、Family6名、Enterprise20名
- **部分成功対応**: 一部の子のみ紐づけ成功時は206 Partial Content返却
- **全失敗対応**: 全員スキップ時は400 Bad Request返却
- **UI改善**: Web検索結果モーダル、Mobile「×」ボタン除外機能
- **API統合**: Web/Mobile共通ロジック、JSON応答統一

**今後の展開**:
- メール認証機能の実装（email_verified_at の活用）
- 親子メールアドレス同期の通知機能
- プロフィール履歴管理（audit log）
- プラン変更時のメンバー数調整フロー

この要件定義書を基に、開発・テスト・運用が一貫した品質で実施されることを期待します。
