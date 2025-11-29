# Phase 1.5 テストインフラ修正完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-11-29 | GitHub Copilot | 初版作成: Phase 1.5テスト実行環境の問題修正完了 |
| 2025-11-29 | GitHub Copilot | 既存ソース修正セクション追加: email/nameカラム対応（Phase 1-3） |

## 概要

MyTeacherアプリケーションの**Phase 1.5（Cognito JWT認証・Mobile API）のテストインフラ構築と問題修正**を完了しました。この作業により、以下の目標を達成しました：

- ✅ **テスト実行環境の構築**: SQLiteインメモリDBを使用したテスト環境を整備
- ✅ **テストコードの修正**: 実装とテストコードの引数・キー名の不一致を解消（34テストメソッド）
- ✅ **マイグレーションの互換性改善**: PostgreSQL/SQLite両対応のマイグレーションファイルに修正
- ✅ **PHP拡張機能の追加**: 不足していたmbstringとsqlite3拡張をインストール
- ✅ **全テスト成功**: AuthHelper（11テスト）とCognitoAuth（8テスト）の計19テストが正常に実行可能に

## 計画との対応

**参照ドキュメント**: Phase 1実装（definitions/alpine-js-removal-completion-report.md、ci-cd-migration.md）

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| Phase 1.1-1.4: API実装 | ✅ 完了 | 13個のAPI Action、AuthHelper、ルート定義完了 | なし |
| Phase 1.5: テスト作成 | ⚠️ 一部修正 | テストファイル作成済みだが実行不可状態を修正 | テストコードと実装の不一致を発見・修正 |
| テスト実行環境構築 | ✅ 完了 | SQLite拡張、mbstring拡張インストール | 計画外だが必須対応 |
| マイグレーション修正 | ✅ 完了 | SQLite互換性問題を修正 | 計画外だが必須対応 |

## 実施内容詳細

### 完了した作業

#### 1. テスト実行環境の問題調査と原因特定

**発見した問題**:
- 日本語メソッド名によるテスト認識失敗（Pest/PHPUnit仕様）
- PHP拡張機能の不足（mbstring, sqlite3）
- マイグレーションファイルのSQLite非互換性
- テストコードと実装コードの引数不一致
- UserFactoryの不足カラム

**調査手順**:
1. `php artisan test`実行 → "No tests found"エラー
2. SQLite拡張機能確認 → 未インストール判明
3. mbstring確認 → 未インストール判明
4. マイグレーション実行 → CHECK制約エラー
5. テストメソッド名確認 → 日本語識別子使用を発見
6. AuthHelper実装確認 → 引数の型不一致を発見

#### 2. テストメソッド名の英語化（34メソッド）

**対象ファイル**:
- `tests/Unit/Helpers/AuthHelperTest.php` (11メソッド)
- `tests/Feature/Api/CognitoAuthTest.php` (8メソッド)
- `tests/Feature/Api/TaskApiTest.php` (15メソッド)

**修正パターン**:
```php
// 修正前
public function 有効なjwtトークンで認証成功する(): void

// 修正後
public function test_valid_jwt_token_authenticates_successfully(): void
```

**使用コマンド**:
```bash
multi_replace_string_in_file tool (34個所を一括修正)
```

#### 3. PHP拡張機能のインストール

**インストール内容**:
```bash
# SQLite拡張（テストDB必須）
sudo apt-get install -y php8.3-sqlite3

# mbstring拡張（Laravel 12 Str::studly()で必須）
sudo apt-get install -y php8.3-mbstring
```

**検証**:
```bash
php -m | grep sqlite   # → sqlite, pdo_sqlite
php -m | grep mbstring # → mbstring
```

#### 4. テストコードの引数修正（16箇所）

**AuthHelperTest.php修正**:
```php
// 修正前（❌ 実装と不一致）
$request = Request::create('/test', 'GET');
$request->attributes->set('cognito_sub', 'cognito-sub-12345');
$request->attributes->set('cognito_email', 'test@example.com');
$request->attributes->set('cognito_username', 'testuser');
$user = AuthHelper::getOrCreateCognitoUser($request);

// 修正後（✅ 実装と一致）
$user = AuthHelper::getOrCreateCognitoUser('cognito-sub-12345', 'test@example.com', 'testuser');
```

**CognitoAuthTest.php修正**:
- リクエスト属性キーを`cognito_email` → `email`、`cognito_username` → `username`に変更
- `getOrCreateCognitoUser()`の呼び出しを文字列引数に変更

**修正ファイル数**: 2ファイル、16メソッド

#### 5. マイグレーションファイルのSQLite互換性対応（3ファイル）

**問題**: ALTER TABLEでのCHECK制約追加がSQLiteで非サポート

**修正対象**:
1. `database/migrations/2025_11_21_235232_create_maintenances_table.php`
2. `database/migrations/2025_11_21_235239_create_contact_submissions_table.php`

**修正内容**:
```php
// 修正前（❌ SQLiteエラー）
DB::statement("ALTER TABLE maintenances ADD CONSTRAINT check_status CHECK (status IN ('scheduled', 'in_progress', 'completed', 'cancelled'))");

// 修正後（✅ PostgreSQL専用）
if (DB::connection()->getDriverName() !== 'sqlite') {
    DB::statement("ALTER TABLE maintenances ADD CONSTRAINT check_status CHECK (status IN ('scheduled', 'in_progress', 'completed', 'cancelled'))");
}
```

#### 6. usersテーブルマイグレーション修正

**問題**: Userモデル・UserFactoryで使用するカラムがマイグレーションに未定義

**追加カラム**:
```php
$table->string('email')->unique()->comment('メールアドレス');
$table->string('name')->nullable()->comment('表示名');
$table->timestamp('email_verified_at')->nullable()->comment('メール認証日時');
$table->string('cognito_sub')->nullable()->unique()->comment('Cognito User Sub (UUID)');
$table->string('auth_provider', 50)->default('breeze')->comment('認証プロバイダー: breeze, cognito');
$table->index('auth_provider');
$table->index('cognito_sub');
```

**重複マイグレーション削除**:
```bash
mv database/migrations/2025_11_25_000001_add_cognito_fields_to_users_table.php \
   database/migrations/2025_11_25_000001_add_cognito_fields_to_users_table.php.backup
```

#### 7. UserFactoryの修正

**追加内容**:
```php
public function definition(): array
{
    return [
        'name' => fake()->name(),
        'username' => fake()->unique()->userName(),  // 追加
        'email' => fake()->unique()->safeEmail(),
        'email_verified_at' => now(),
        'password' => static::$password ??= Hash::make('password'),
        'remember_token' => Str::random(10),
        'auth_provider' => 'breeze',  // 追加
    ];
}
```

#### 8. AuthHelperのバグ修正

**問題**: 引数で渡されたusernameの重複チェックが機能していない

**修正内容**:
```php
// 修正前（❌ 重複チェックなし）
$user = User::create([
    'username' => $username ?? self::generateUsernameFromEmail($email),
]);

// 修正後（✅ 重複チェック実装）
$finalUsername = $username ?? self::generateUsernameFromEmail($email);

if ($username) {
    $baseUsername = $username;
    $counter = 1;
    
    while (User::where('username', $finalUsername)->exists()) {
        $finalUsername = $baseUsername . $counter;
        $counter++;
    }
}

$user = User::create([
    'username' => $finalUsername,
]);
```

#### 9. テスト環境設定の追加

**phpunit.xml修正**:
```xml
<!-- Cognito Test Configuration -->
<env name="COGNITO_REGION" value="ap-northeast-1"/>
<env name="COGNITO_USER_POOL_ID" value="test-pool-id"/>
<env name="COGNITO_CLIENT_ID" value="test-client-id"/>
```

**理由**: VerifyCognitoTokenミドルウェアのコンストラクタでCognito設定を要求

#### 10. テスト実行と検証

**最終テスト結果**:
```bash
cd /home/ktr/mtdev && php artisan test tests/Unit/Helpers/AuthHelperTest.php tests/Feature/Api/CognitoAuthTest.php

Tests:    0 failed, 19 passed (47 assertions)
Duration: 0.69s
```

**テストカバレッジ**:
- AuthHelperTest: 11/11テスト成功
- CognitoAuthTest: 8/8テスト成功
- 合計: 19/19テスト成功（100%）

## 成果と効果

### 定量的効果

- **テスト成功率**: 0% → 100%（19/19テスト）
- **テストメソッド修正**: 34メソッド（英語化）
- **コード修正箇所**: 16箇所（引数・キー名修正）
- **マイグレーション修正**: 3ファイル（SQLite互換性）
- **PHP拡張追加**: 2パッケージ（mbstring, sqlite3）
- **テスト実行時間**: 0.69秒（SQLiteインメモリDB使用）

### 定性的効果

- **テスト環境の信頼性向上**: SQLiteインメモリDBによる高速・分離されたテスト実行
- **開発効率の改善**: テストが実行可能になり、CI/CD統合の準備完了
- **コード品質の向上**: 実装とテストの不一致を解消、バグを早期発見
- **保守性の向上**: 英語メソッド名により国際標準に準拠
- **PostgreSQL/SQLite両対応**: 本番と開発・テスト環境で異なるDBを使用可能
- **技術的負債の解消**: マイグレーションファイルの互換性問題を解決

### 発見した技術的問題

1. **テストコードと実装の乖離**: Phase 1実装時にテストを作成したが、実装変更時にテストが未更新
2. **PHP拡張の不足**: 開発環境にLaravel 12必須の拡張機能が未インストール
3. **マイグレーションの互換性**: PostgreSQL専用構文をSQLiteテストで使用
4. **日本語識別子の問題**: Pest/PHPUnitは日本語メソッド名を認識できない

## 未完了項目・次のステップ

### 完了した項目（確認済み）

- ✅ テストメソッド名の英語化（34メソッド）
- ✅ PHP拡張機能のインストール（mbstring, sqlite3）
- ✅ マイグレーションのSQLite互換性対応（3ファイル）
- ✅ テストコードの引数修正（16箇所）
- ✅ usersテーブル定義の修正
- ✅ UserFactoryの修正
- ✅ AuthHelperのバグ修正
- ✅ テスト環境設定の追加
- ✅ 全テストの実行成功確認

### 今後の推奨事項

1. **TaskApiTest.phpの実装（Phase 1.5継続）**
   - 現状: 15テストメソッドが作成済みだが、未実行
   - 理由: Cognitoミドルウェアのモックが必要
   - 期限: Phase 1完了前（2025-12-05）
   - 推奨アプローチ:
     ```php
     // テストでCognitoミドルウェアをバイパス
     $this->withoutMiddleware(VerifyCognitoToken::class);
     // または
     Route::middleware(['auth:sanctum'])->group(function () {
         // テスト用ルート
     });
     ```

2. **CI/CD統合（GitHub Actions）**
   - 目的: プルリクエスト時に自動テスト実行
   - 期限: Phase 1完了後（2025-12-10）
   - 必要な作業:
     - `.github/workflows/test.yml`作成
     - SQLite/mbstring拡張のインストールステップ追加
     - テストカバレッジレポート生成

3. **テストカバレッジの拡張**
   - 現状: AuthHelper + CognitoAuthのみテスト済み
   - 推奨: 以下のクラスのテスト追加
     - `VerifyCognitoToken`ミドルウェア
     - 13個のAPI Actionクラス
     - TaskManagementService
     - TaskEloquentRepository
   - 目標カバレッジ: 80%以上

4. **テストデータの整備**
   - Factoryクラスの拡張（Task, TaskImage, Tag等）
   - Seederクラスのテストデータ版作成
   - テスト用フィクスチャファイルの準備

5. **E2Eテストの準備（Phase 2以降）**
   - Laravel Duskの導入検討
   - モバイルアプリからのAPI呼び出しテスト
   - 認証フローの統合テスト

## 技術的知見

### PHP拡張機能の重要性

Laravel 12では以下の拡張が必須：
- `mbstring`: 多バイト文字列処理（`Str::studly()`, `mb_split()`等で使用）
- `sqlite3` + `pdo_sqlite`: SQLiteデータベース（テスト環境で推奨）
- `xml`: Composer、PHPUnit動作に必須
- `curl`: HTTP通信（Guzzle等で使用）

### SQLiteとPostgreSQLの互換性

**注意すべき違い**:
1. **CHECK制約**: SQLiteはCREATE TABLE時のみ、PostgreSQLはALTER TABLEで追加可能
2. **外部キー**: SQLiteはデフォルト無効（`PRAGMA foreign_keys = ON`必須）
3. **データ型**: SQLiteは動的型付け、PostgreSQLは厳格
4. **ALTER TABLE制限**: SQLiteは多くの変更操作が不可

**推奨パターン**:
```php
if (DB::connection()->getDriverName() !== 'sqlite') {
    // PostgreSQL専用の構文
}
```

### テストメソッド命名規則

**Pest/PHPUnit標準**:
- メソッド名は英語ASCII文字のみ
- `test_`プレフィックス または `@test`アノテーション
- snake_caseが推奨（PSR-12準拠）

**日本語コメントの活用**:
```php
/**
 * @test
 * 有効なJWTトークンで認証成功すること
 */
public function test_valid_jwt_token_authenticates_successfully(): void
```

### AuthHelperの設計パターン

**静的メソッドの利点**:
- ミドルウェアから簡単に呼び出し可能
- DIコンテナ不要（シンプル）

**注意点**:
- テストでモック化が困難
- 将来的にはServiceクラス化を検討
- 重複チェックロジックの改善余地あり

## 既存ソース修正（email/nameカラム対応）

Phase 1.5でusersテーブルに`email`および`name`カラムを追加したことに伴い、既存の画面・機能で該当カラムを使用できるように修正を実施しました。

### 修正対象と実施内容

#### Phase 1: ユーザー登録画面（Register）

**修正ファイル**: 8ファイル + 1テストファイル

1. **app/Http/Actions/Auth/ValidateEmailAction.php** (NEW)
   - 非同期emailバリデーション用エンドポイント
   - `exclude_user_id`パラメータで自己除外をサポート（プロフィール編集用）
   - 空チェック、形式チェック、重複チェックを実施

2. **routes/web.php**
   - ゲストルート追加: `POST /validate/email`
   - 認証済みルート追加: `POST /validate/member-email`

3. **resources/views/auth/register.blade.php**
   - emailフィールド追加（必須、非同期バリデーション付き）
   - スピナー、エラーメッセージ、成功メッセージのUI要素追加

4. **resources/js/auth/register-validation.js**
   - `validateEmail()`関数有効化（コメントアウト解除）
   - `initValidationState(['username', 'email', 'password', 'password_confirmation'])`
   - デバウンス500msで非同期バリデーション実行

5. **app/Http/Actions/Auth/RegisterAction.php**
   - `'email' => $request->input('email')`追加
   - `'name' => $request->input('username')`追加（表示名としてusernameを使用）

6. **app/Http/Requests/Auth/RegisterRequest.php**
   - emailバリデーションルール追加: `'required', 'string', 'email', 'max:255', 'unique:users,email'`
   - カスタムエラーメッセージ追加

7. **app/Helpers/AuthHelper.php**
   - Cognito認証ユーザー作成時に`'name' => $finalUsername`追加

8. **database/seeders/AdminUserSeeder.php**
   - 管理者・テストユーザーにemail, name追加

9. **tests/Feature/Auth/EmailValidationTest.php** (NEW)
   - 6テストメソッド、12アサーション、全テスト成功
   - 有効email、重複email、自己除外、空、無効形式をテスト

**テスト結果**: ✅ 6/6テスト成功（12アサーション）

#### Phase 2: プロフィール編集画面（Profile Edit）

**修正ファイル**: 5ファイル + 1テストファイル

1. **app/Http/Requests/Profile/UpdateProfileRequest.php** (NEW)
   - username, email, nameのバリデーションルール定義
   - `Rule::unique()->ignore($user->id)`で自己除外

2. **resources/views/profile/partials/update-profile-information-form.blade.php**
   - username, email（必須、非同期バリデーション）、name（任意）フィールド追加
   - スピナー、エラー、成功メッセージのUI要素追加
   - Alpine.js構文削除（`x-data`, `x-show`等）

3. **resources/views/profile/edit.blade.php**
   - Alpine.jsスクリプト削除
   - `@vite(['resources/js/profile/profile-edit-validation.js'])`追加

4. **resources/js/profile/profile-edit-validation.js** (NEW)
   - `validateUsername()`, `validateEmail()`関数実装
   - `exclude_user_id`パラメータで自己除外
   - デバウンス500ms、フォーム送信時のバリデーション

5. **app/Http/Actions/Profile/UpdateProfileAction.php**
   - UpdateProfileRequest使用に変更
   - username, email, name更新処理追加
   - nameが空の場合はusernameをnameとして使用

6. **tests/Feature/Profile/ProfileUpdateTest.php** (NEW)
   - 9テストメソッド、24アサーション、全テスト成功
   - 全フィールド更新、name省略、自己除外、重複エラー、必須チェック、無効形式をテスト

**テスト結果**: ✅ 9/9テスト成功（24アサーション）

#### Phase 3: グループメンバー追加画面（Group Member Add）

**修正ファイル**: 6ファイル + 1テストファイル

1. **app/Http/Requests/Profile/Group/AddMemberRequest.php** (NEW)
   - username, email, password, name, group_edit_flgのバリデーションルール
   - 全て新規ユーザー用のため自己除外不要

2. **resources/views/profile/group/partials/add-member.blade.php**
   - username, email（必須、非同期）、name（任意）、passwordフィールド修正
   - スピナー、エラー、成功メッセージのUI要素追加
   - Alpine.js構文削除

3. **resources/views/profile/group/edit.blade.php**
   - Alpine.js構文削除（`x-data`, `x-effect`）

4. **resources/js/profile/profile-validation.js**
   - `validateMemberEmail()`関数追加
   - `initValidationState(['username', 'email', 'password'])`に変更
   - ボタン状態管理をusername/email/passwordの3項目に更新

5. **app/Http/Actions/Profile/Group/AddMemberAction.php**
   - AddMemberRequest使用に変更
   - email, nameパラメータを追加してGroupServiceに渡す

6. **app/Services/Profile/GroupServiceInterface.php**
   - `addMember()`シグネチャ変更: `string $email, ?string $name`追加

7. **app/Services/Profile/GroupService.php**
   - `addMember()`実装変更: email, nameパラメータ追加
   - ユーザー作成時にemail, name（nullの場合はusername）を設定

8. **tests/Feature/Profile/Group/AddMemberTest.php** (NEW)
   - 9テストメソッド、26アサーション、全テスト成功
   - 全フィールド追加、name省略、編集権限、重複エラー、必須チェック、無効形式、権限なしをテスト

**テスト結果**: ✅ 9/9テスト成功（26アサーション）

### アセットビルド

```bash
npm run build
# profile-validation.js: 3.37 kB
# profile-edit-validation.js: 含まれる（register-validation.jsと同様のパターン）
# ビルド成功: 110 modules transformed, 1.59s
```

### 成果と効果

**定量的効果**:
- 修正ファイル数: 19ファイル（8 + 5 + 6）
- 新規作成ファイル数: 8ファイル（Request 3, Validation JS 2, Test 3）
- テスト総数: 24テスト（6 + 9 + 9）
- アサーション総数: 62（12 + 24 + 26）
- テスト成功率: 100%（24/24）

**定性的効果**:
- ✅ email必須化により重複アカウント作成を防止
- ✅ 非同期バリデーションによりUX向上（即座にフィードバック）
- ✅ 自己除外機能によりプロフィール編集時の誤エラー防止
- ✅ nameフィールドにより表示名のカスタマイズが可能
- ✅ Alpine.js依存削除によりバンドルサイズ削減とiPad互換性確保
- ✅ 包括的なテストにより品質保証

### 未完了項目・次のステップ

**残作業**: なし（Phase 1-3完了）

**今後の推奨事項**:
- Cognito認証フローでのemail属性活用（Phase 1.1-1.4で対応済み）
- メール認証機能の実装（email_verified_atカラム活用）
- プロフィール画面でのアバター表示にname使用
- グループメンバー一覧でのname表示

## 関連ドキュメント

- Phase 1実装: `definitions/alpine-js-removal-completion-report.md`
- CI/CD移行: `definitions/ci-cd-migration.md`
- テスト戦略: `docs/TESTING.md`
- プロジェクト構造: `.github/copilot-instructions.md`

## まとめ

Phase 1.5のテストインフラ構築において、複数の技術的問題（PHP拡張不足、マイグレーション互換性、テストコード不一致、日本語メソッド名）を発見・解決しました。

これにより、19個のテストが正常に実行可能となり、Cognito JWT認証とAuthHelperの品質が保証されました。今後はTaskApiTestの実装とCI/CD統合により、継続的な品質保証体制を確立していきます。

**重要な教訓**:
1. テスト環境構築は実装と並行して行うべき（後回しにするとギャップが拡大）
2. PHP拡張機能の確認は開発環境セットアップ時に必須
3. マイグレーションはPostgreSQL/SQLite両対応で設計すべき
4. 日本語コメントは活用するが、識別子（メソッド名、変数名）は英語を使用
5. テストコードも実装コードと同様にレビュー・リファクタリングが必要
