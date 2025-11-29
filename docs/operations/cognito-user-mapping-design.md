# Cognitoユーザーマッピング設計書

**作成日**: 2025-11-29  
**対象Phase**: Phase 1 - MyTeacher Mobile API実装  
**目的**: Cognito JWT認証とLaravelユーザーのシームレスな統合

---

## 📋 概要

Cognito JWT認証を導入する際、既存コードで多用されている `$request->user()` をそのまま使えるようにするため、ヘルパー関数とミドルウェア拡張を実装します。

---

## 🎯 設計目標

1. **既存コードの変更最小化**: `$request->user()` をそのまま使用可能
2. **自動ユーザーマッピング**: Cognito Sub → Laravelユーザーの自動紐付け
3. **初回ログイン対応**: Cognitoユーザー初回ログイン時に自動でLaravelユーザー作成
4. **デバッグ容易性**: ログによる追跡、エラーハンドリング

---

## 🏗️ アーキテクチャ

### 1. データフロー

```
[モバイルアプリ] 
    ↓ Authorization: Bearer {Cognito JWT}
[VerifyCognitoToken ミドルウェア]
    ↓ JWT検証、cognito_sub取得
[CognitoUserResolver ヘルパー]
    ↓ cognito_sub → Laravelユーザー検索
[User::where('cognito_sub', $sub)->first()]
    ↓ 存在しない場合
[自動ユーザー作成]
    ↓
[request->setUserResolver()]
    ↓
[API Action] $request->user() で取得可能 ✅
```

### 2. データベース構造（既存）

```sql
-- users テーブル（既に実装済み）
ALTER TABLE users ADD COLUMN cognito_sub VARCHAR(100) UNIQUE;
ALTER TABLE users ADD COLUMN auth_provider ENUM('breeze', 'cognito') DEFAULT 'breeze';
```

**確認**: マイグレーションファイル `2025_11_25_000001_add_cognito_fields_to_users_table.php` で既に実装済み ✅

---

## 🛠️ 実装詳細

### Phase 1: ヘルパー関数の作成

#### ファイル: `app/Helpers/AuthHelper.php`

```php
<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 認証ヘルパー
 * Cognito JWT認証とLaravel Breeze認証を統合的に扱うヘルパー関数
 */
class AuthHelper
{
    /**
     * Cognitoユーザーを取得または作成
     * 
     * @param string $cognitoSub Cognito User Sub
     * @param string $email メールアドレス
     * @param string|null $name ユーザー名（任意）
     * @return User Laravelユーザーモデル
     */
    public static function getOrCreateCognitoUser(
        string $cognitoSub, 
        string $email, 
        ?string $name = null
    ): User {
        // 既存ユーザーを検索
        $user = User::where('cognito_sub', $cognitoSub)->first();

        if ($user) {
            Log::info('Cognito user found', [
                'cognito_sub' => $cognitoSub,
                'user_id' => $user->id
            ]);
            return $user;
        }

        // 初回ログイン: 新規ユーザー作成
        Log::info('Creating new Cognito user', [
            'cognito_sub' => $cognitoSub,
            'email' => $email
        ]);

        $user = User::create([
            'cognito_sub' => $cognitoSub,
            'email' => $email,
            'name' => $name ?? self::generateDefaultName($email),
            'auth_provider' => 'cognito',
            'email_verified_at' => now(), // Cognitoで認証済み
        ]);

        return $user;
    }

    /**
     * デフォルトユーザー名を生成
     * 
     * @param string $email メールアドレス
     * @return string ユーザー名
     */
    private static function generateDefaultName(string $email): string
    {
        $localPart = explode('@', $email)[0];
        return ucfirst($localPart);
    }

    /**
     * リクエストからCognitoユーザー情報を取得
     * 
     * @param Request $request
     * @return array|null [sub, email, username] or null
     */
    public static function getCognitoInfo(Request $request): ?array
    {
        if (!$request->has('cognito_sub')) {
            return null;
        }

        return [
            'sub' => $request->cognito_sub,
            'email' => $request->cognito_email,
            'username' => $request->cognito_username ?? null,
        ];
    }

    /**
     * 認証プロバイダーを判定
     * 
     * @param Request $request
     * @return string 'cognito' | 'breeze' | 'unknown'
     */
    public static function getAuthProvider(Request $request): string
    {
        if ($request->has('cognito_sub')) {
            return 'cognito';
        }

        if ($request->user()) {
            return 'breeze';
        }

        return 'unknown';
    }
}
```

#### Composerオートロード設定

`composer.json` に追加:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        },
        "files": [
            "app/Helpers/AuthHelper.php"
        ]
    }
}
```

### Phase 2: ミドルウェア拡張

#### ファイル: `app/Http/Middleware/VerifyCognitoToken.php`（既存を修正）

**修正箇所**:

```php
// 既存のLaravelユーザーとのマッピング（オプション）
// Phase 1の並行運用期間中に使用
if (isset($decoded['sub'])) {
    // ❌ 旧コード
    // $user = \App\Models\User::where('cognito_sub', $decoded['sub'])->first();

    // ✅ 新コード（ヘルパー使用）
    $user = \App\Helpers\AuthHelper::getOrCreateCognitoUser(
        $decoded['sub'],
        $decoded['email'] ?? 'unknown@example.com',
        $decoded['name'] ?? null
    );

    if ($user) {
        $request->setUserResolver(fn() => $user);
    }
}
```

### Phase 3: グローバルヘルパー関数（任意）

より簡潔に使いたい場合は、グローバル関数を追加:

#### ファイル: `app/helpers.php`（新規作成）

```php
<?php

use App\Helpers\AuthHelper;
use Illuminate\Http\Request;

if (!function_exists('current_user')) {
    /**
     * 現在の認証ユーザーを取得（Cognito/Breeze両対応）
     * 
     * @return \App\Models\User|null
     */
    function current_user(): ?\App\Models\User
    {
        return request()->user();
    }
}

if (!function_exists('auth_provider')) {
    /**
     * 認証プロバイダーを取得
     * 
     * @return string 'cognito' | 'breeze' | 'unknown'
     */
    function auth_provider(): string
    {
        return AuthHelper::getAuthProvider(request());
    }
}

if (!function_exists('is_cognito_auth')) {
    /**
     * Cognito認証かどうか判定
     * 
     * @return bool
     */
    function is_cognito_auth(): bool
    {
        return auth_provider() === 'cognito';
    }
}
```

`composer.json` に追加:

```json
{
    "autoload": {
        "files": [
            "app/Helpers/AuthHelper.php",
            "app/helpers.php"
        ]
    }
}
```

---

## 📊 既存コード影響範囲の洗い出し

### 1. $request->user() の使用箇所

**影響を受けるコード**:
- ✅ **API Actions**: 全てのAPI Actionで `$request->user()` を使用
- ✅ **Middleware**: 認証関連ミドルウェア
- ✅ **Service Layer**: ユーザー依存のビジネスロジック

**対策**:
- `VerifyCognitoToken` ミドルウェアで `setUserResolver()` を使用
- ヘルパー関数 `AuthHelper::getOrCreateCognitoUser()` で自動マッピング
- 既存コードの変更不要 ✅

### 2. 影響を受けないコード

以下は変更不要:
- ✅ Web版のBreezeログイン（既存機能継続）
- ✅ Service/Repository層（ユーザーはActionから渡される）
- ✅ Blade Views（Webは従来通り）

---

## 🧪 テスト戦略

### Unit Tests

```php
// tests/Unit/Helpers/AuthHelperTest.php
public function test_cognito_user_creation()
{
    $user = AuthHelper::getOrCreateCognitoUser(
        'cognito-sub-123',
        'test@example.com',
        'Test User'
    );

    $this->assertNotNull($user);
    $this->assertEquals('cognito-sub-123', $user->cognito_sub);
    $this->assertEquals('cognito', $user->auth_provider);
}

public function test_cognito_user_retrieval()
{
    // 既存ユーザー作成
    $existingUser = User::factory()->create([
        'cognito_sub' => 'cognito-sub-456',
        'auth_provider' => 'cognito'
    ]);

    // 同じsubで再取得
    $user = AuthHelper::getOrCreateCognitoUser(
        'cognito-sub-456',
        'test@example.com'
    );

    $this->assertEquals($existingUser->id, $user->id);
}
```

### Feature Tests

```php
// tests/Feature/Api/CognitoAuthTest.php
public function test_api_with_cognito_token()
{
    $token = $this->generateMockCognitoToken([
        'sub' => 'cognito-sub-789',
        'email' => 'mobile@example.com'
    ]);

    $response = $this->withHeaders([
        'Authorization' => "Bearer {$token}"
    ])->getJson('/v1/tasks');

    $response->assertOk();
    $this->assertAuthenticatedAs($user);
}
```

---

## 📝 実装チェックリスト

### Phase 1: ヘルパー関数作成

- [ ] `app/Helpers/AuthHelper.php` 作成
  - [ ] `getOrCreateCognitoUser()` 実装
  - [ ] `getCognitoInfo()` 実装
  - [ ] `getAuthProvider()` 実装
- [ ] `app/helpers.php` 作成（グローバル関数）
  - [ ] `current_user()` 実装
  - [ ] `auth_provider()` 実装
  - [ ] `is_cognito_auth()` 実装
- [ ] `composer.json` autoload更新
- [ ] `composer dump-autoload` 実行

### Phase 2: ミドルウェア拡張

- [ ] `VerifyCognitoToken.php` 修正
  - [ ] `AuthHelper::getOrCreateCognitoUser()` 使用
  - [ ] `setUserResolver()` 実装
  - [ ] ログ記録追加
- [ ] ミドルウェアエイリアス確認（`app/Http/Kernel.php`）
  - [ ] `'cognito'` エイリアス存在確認

### Phase 3: API Action実装

- [ ] `StoreTaskApiAction.php` 修正
  - [ ] Cognito認証対応
  - [ ] グループタスク対応（`$request->boolean('is_group_task')`）
  - [ ] Responder使用
- [ ] `IndexTaskApiAction.php` 修正
  - [ ] Cognito認証対応
  - [ ] ページネーション実装
- [ ] `DestroyTaskApiAction.php` 修正
  - [ ] Cognito認証対応
  - [ ] 権限チェック実装

### Phase 4: ルート設定

- [ ] `routes/api.php` 更新
  - [ ] `/v1/tasks` ルート追加（`cognito` ミドルウェア）
  - [ ] use文追加（API Actionクラス）

### Phase 5: テスト

- [ ] Unit Tests作成
  - [ ] `AuthHelperTest.php`
- [ ] Feature Tests作成
  - [ ] `CognitoAuthTest.php`
  - [ ] `TaskApiTest.php`（Cognito認証付き）
- [ ] 全テスト実行（`composer test`）

### Phase 6: 動作確認

- [ ] ローカル環境でCognito認証テスト
- [ ] モバイルアプリとの連携テスト
- [ ] 既存Web機能動作確認（影響がないこと）
- [ ] ログ確認（ユーザー作成、認証成功/失敗）

---

## 🚨 注意事項・制約

### 1. Cognitoユーザー初回ログイン

**挙動**:
- Cognitoで認証成功 → Laravelユーザー自動作成
- `email_verified_at` は自動設定（Cognito認証済み）

**制約**:
- メールアドレス重複チェック不要（Cognitoで一意性保証）
- パスワードなし（Cognito管理）

### 2. 既存Breezeユーザーとの共存

**Phase 1.5並行運用期間**:
- Web版: Breeze認証継続
- モバイル版: Cognito JWT認証
- 同じ `users` テーブル使用（`auth_provider` で区別）

### 3. マイグレーション考慮事項

既存Breezeユーザーを後からCognitoに移行する場合:

```php
// 既存ユーザーにcognito_sub追加マイグレーション
User::where('auth_provider', 'breeze')
    ->where('cognito_sub', null)
    ->update([
        'cognito_sub' => Str::uuid(), // 仮Sub（後で上書き）
        'auth_provider' => 'cognito'
    ]);
```

### 4. ロールバック手順

Cognito認証で問題が発生した場合:

```bash
# 1. Cognitoルートを無効化
# routes/api.php でコメントアウト

# 2. ミドルウェア無効化
# Kernel.php で 'cognito' エイリアス削除

# 3. データベースロールバック（必要な場合）
php artisan migrate:rollback --step=1
```

---

## 📚 参照ドキュメント

1. **AWS Cognito公式**:
   - [JWT検証方法](https://docs.aws.amazon.com/cognito/latest/developerguide/amazon-cognito-user-pools-using-tokens-verifying-a-jwt.html)
   - [User Pool設定](https://docs.aws.amazon.com/cognito/latest/developerguide/user-pool-settings.html)

2. **Laravel公式**:
   - [認証システム](https://laravel.com/docs/12.x/authentication)
   - [ミドルウェア](https://laravel.com/docs/12.x/middleware)

3. **プロジェクトドキュメント**:
   - `docs/architecture/multi-app-hub-infrastructure-strategy.md` - アーキテクチャ全体設計
   - `docs/operations/microservice-removal-plan.md` - マイクロサービス削除計画
   - `docs/reports/2025-11-29-microservice-removal-completion-report.md` - 削除完了レポート

---

## ✅ まとめ

### 実装後の効果

1. **既存コード互換性**: `$request->user()` がそのまま動作 ✅
2. **自動ユーザー管理**: 初回ログイン時に自動作成 ✅
3. **シームレスな認証**: Web版（Breeze）とモバイル版（Cognito）の共存 ✅
4. **保守性向上**: ヘルパー関数化により変更箇所を集中管理 ✅

### 次のステップ

1. ヘルパー関数実装（Phase 1）
2. ミドルウェア拡張（Phase 2）
3. API Action実装（Phase 3）
4. テスト作成・実行（Phase 5）

すべての実装が完了するまで、段階的に進めます。