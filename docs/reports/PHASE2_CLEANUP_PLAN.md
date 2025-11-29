# Phase 2移行時のクリーンアップ計画

**作成日**: 2025年11月26日  
**対象フェーズ**: Phase 2完了後（Breeze削除）  
**目的**: 並行運用期間限定のコードを削除し、Cognito単一認証に統一

---

## 📋 目次

1. [削除対象ファイル・コード](#削除対象ファイルコード)
2. [変更が必要なファイル](#変更が必要なファイル)
3. [削除手順](#削除手順)
4. [検証チェックリスト](#検証チェックリスト)
5. [ロールバック計画](#ロールバック計画)

---

## 削除対象ファイル・コード

### 1. 完全削除するファイル

| ファイルパス | 理由 | Phase |
|------------|------|-------|
| `app/Http/Middleware/DualAuthMiddleware.php` | 並行運用専用ミドルウェア | Phase 2完了後 |
| `tests/Feature/Auth/DualAuthMiddlewareTest.php` | 並行運用テスト | Phase 2完了後 |
| `app/Console/Commands/MonitorDualAuthCommand.php` | 並行運用監視コマンド | Phase 2完了後 |
| `infrastructure/reports/PHASE1.5_TASK8_DUAL_AUTH_MIGRATION_PLAN.md` | 移行計画書（アーカイブ推奨） | Phase 2完了後 |

**削除コマンド**:
```bash
cd /home/ktr/mtdev/laravel

# ミドルウェア削除
rm app/Http/Middleware/DualAuthMiddleware.php

# テスト削除
rm tests/Feature/Auth/DualAuthMiddlewareTest.php

# 監視コマンド削除
rm app/Console/Commands/MonitorDualAuthCommand.php

# 移行計画書はアーカイブ（削除しない）
# git mv infrastructure/reports/PHASE1.5_TASK8_DUAL_AUTH_MIGRATION_PLAN.md \
#        infrastructure/reports/archive/PHASE1.5_TASK8_DUAL_AUTH_MIGRATION_PLAN.md
```

### 2. Breezeルート削除

**ファイル**: `routes/web.php`

**削除対象**:
- Breezeの認証ルート（`/login`, `/register`, `/forgot-password` など）
- `Route::middleware(['auth'])` → `Route::middleware(['cognito'])` に変更

**注意**: 既存の機能ルート（`/dashboard`, `/tasks` など）は残す

### 3. レガシーAPIルート削除

**ファイル**: `routes/api.php`

**削除対象**:
```php
// レガシーAPI（Sanctum認証 - Phase 2削除）
Route::prefix('api')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/user', function () {
            return auth()->user();
        });
        Route::post('/tasks/propose', ProposeTaskAction::class)->name('api.tasks.propose');
    });
});

// 並行運用ルート（Phase 1.5期間限定 - Phase 2削除）
Route::prefix('v1/dual')->middleware(['dual.auth'])->group(function () {
    Route::get('/user', ...)->name('api.v1.dual.user');
});
```

**残すルート**:
```php
// Cognito JWT専用（Phase 2以降の標準）
Route::prefix('v1')->middleware(['cognito'])->group(function () {
    Route::get('/user', ...)->name('api.v1.user');
    // 新規APIはすべてここに追加
});
```

---

## 変更が必要なファイル

### 1. `bootstrap/app.php`

**変更内容**: `dual.auth` ミドルウェアエイリアスを削除

```php
// 削除前
$middleware->alias([
    'check.tokens' => \App\Http\Middleware\CheckTokenBalance::class,
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'cognito' => \App\Http\Middleware\VerifyCognitoToken::class,
    'dual.auth' => \App\Http\Middleware\DualAuthMiddleware::class, // ← 削除
]);

// 削除後
$middleware->alias([
    'check.tokens' => \App\Http\Middleware\CheckTokenBalance::class,
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
    'cognito' => \App\Http\Middleware\VerifyCognitoToken::class,
]);
```

### 2. `app/Console/Kernel.php`

**変更内容**: 並行運用監視スケジュール削除

```php
// 削除対象
// Phase 1.5: Breeze + Cognito並行運用監視（5分ごと）
// 並行運用期間のみ有効化（2025年12月1日〜12月14日）
if (now()->between('2025-12-01', '2025-12-14')) {
    $schedule->command('auth:monitor-dual-auth --alert')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/dual-auth-monitoring.log'));
}

// $commands配列からも削除
Commands\MonitorDualAuthCommand::class, // ← 削除
```

### 3. `routes/web.php`

**変更内容**: すべての `auth` ミドルウェアを `cognito` に変更

```php
// 変更前
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', IndexTaskAction::class)->name('dashboard');
    // ... その他のルート
});

// 変更後
Route::middleware(['cognito'])->group(function () {
    Route::get('/dashboard', IndexTaskAction::class)->name('dashboard');
    // ... その他のルート
});
```

**注意**: `guest` ミドルウェアのルート（`/login`, `/register` など）も削除し、Cognito UIに置き換え

### 4. データベースクリーンアップ（オプション）

**実行タイミング**: Phase 2完了後、すべてのユーザーがCognitoに移行完了後

```sql
-- Breeze認証ユーザーの確認（残っていないことを確認）
SELECT COUNT(*) FROM users WHERE auth_provider = 'breeze' OR auth_provider IS NULL;

-- すべてCognito移行済みなら、breeze関連カラムを削除（オプション）
-- ALTER TABLE users DROP COLUMN IF EXISTS password;
-- ALTER TABLE users DROP COLUMN IF EXISTS remember_token;
```

**重要**: パスワードカラムは残しておくことを推奨（将来的なロールバックや緊急対応のため）

---

## 削除手順

### Step 1: 事前確認（Phase 2完了後）

```bash
# 1. 全ユーザーがCognito移行済みか確認
php artisan tinker --execute="
    \$breezeUsers = \App\Models\User::whereNull('cognito_sub')->count();
    echo \"Breeze users remaining: \$breezeUsers\n\";
    if (\$breezeUsers > 0) {
        echo \"WARNING: Migration not complete!\n\";
    }
"

# 2. 並行運用ログの確認
tail -n 100 storage/logs/dual-auth-monitoring.log

# 3. 現在の認証方式別利用率を確認
php artisan auth:monitor-dual-auth --period=1440
```

### Step 2: ファイル削除

```bash
cd /home/ktr/mtdev/laravel

# ミドルウェア削除
git rm app/Http/Middleware/DualAuthMiddleware.php

# テスト削除
git rm tests/Feature/Auth/DualAuthMiddlewareTest.php

# 監視コマンド削除
git rm app/Console/Commands/MonitorDualAuthCommand.php

# コミット
git commit -m "Phase 2: Remove dual auth middleware and monitoring

- Removed DualAuthMiddleware (Phase 1.5 parallel operation)
- Removed DualAuthMiddlewareTest
- Removed MonitorDualAuthCommand
- All users migrated to Cognito JWT authentication"
```

### Step 3: コード修正

```bash
# bootstrap/app.php の修正
# app/Console/Kernel.php の修正
# routes/api.php の修正
# routes/web.php の修正

# すべての変更をコミット
git add bootstrap/app.php app/Console/Kernel.php routes/api.php routes/web.php
git commit -m "Phase 2: Update routes and middleware to Cognito-only

- Updated all 'auth' middleware to 'cognito'
- Removed dual.auth middleware alias
- Removed legacy API routes (Sanctum)
- Removed dual operation routes (v1/dual)"
```

### Step 4: キャッシュクリア

```bash
# 開発環境
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# 本番環境（ECS Fargate）
aws ecs update-service \
  --cluster myteacher-production \
  --service myteacher-production-app \
  --force-new-deployment \
  --region ap-northeast-1
```

### Step 5: テスト実行

```bash
# 全テスト実行
php artisan test

# Cognito認証テスト
php artisan test --filter CognitoAuthenticationTest

# 削除されたテストが存在しないことを確認
php artisan test --filter DualAuthMiddlewareTest  # エラーになるはず
```

---

## 検証チェックリスト

### Phase 2削除前の確認

- [ ] 全ユーザーがCognitoに移行完了（`cognito_sub` 存在）
- [ ] Breeze認証ユーザー数 = 0
- [ ] 並行運用期間（2週間）が終了
- [ ] Phase 2のフロントエンドUI統合完了
- [ ] 新規ユーザーがCognito経由で登録可能
- [ ] Cognito認証の成功率 > 99.5%

### Phase 2削除後の確認

- [ ] DualAuthMiddleware ファイルが存在しない
- [ ] `dual.auth` ミドルウェアエイリアスが存在しない
- [ ] `/api/v1/dual/*` ルートが存在しない
- [ ] レガシーAPI（`/api/api/*`）が存在しない
- [ ] すべてのWebルートが `cognito` ミドルウェアを使用
- [ ] 既存機能がすべて正常動作
- [ ] ログインフローが正常動作（Cognito UI）
- [ ] ユーザー登録フローが正常動作
- [ ] パスワードリセットが正常動作
- [ ] 全テストがパス

---

## ロールバック計画

### 緊急時のロールバック手順

**条件**: Phase 2削除後に重大な問題が発生した場合

#### Step 1: Git Revert（5分以内）

```bash
cd /home/ktr/mtdev

# 削除コミットを特定
git log --oneline --grep="Phase 2: Remove dual auth"

# コミットをrevert
git revert <commit-hash>

# プッシュ
git push origin feature/dev-structure
```

#### Step 2: 緊急デプロイ（10分以内）

```bash
# ECRにプッシュ
cd infrastructure/terraform
./deploy.sh

# ECSサービス更新
aws ecs update-service \
  --cluster myteacher-production \
  --service myteacher-production-app \
  --force-new-deployment \
  --region ap-northeast-1
```

#### Step 3: 動作確認（15分以内）

```bash
# ヘルスチェック
curl -f https://my-teacher-app.com/health

# 認証テスト
curl -X POST https://my-teacher-app.com/api/v1/dual/user \
  -H "Authorization: Bearer <test-token>"

# ログ確認
aws logs tail /aws/ecs/myteacher-production-app --follow
```

---

## アーカイブ推奨ファイル

Phase 2完了後、以下のファイルは削除せず `archive/` ディレクトリに移動することを推奨:

```bash
mkdir -p infrastructure/reports/archive

# 移行計画書をアーカイブ
git mv infrastructure/reports/PHASE1.5_TASK8_DUAL_AUTH_MIGRATION_PLAN.md \
       infrastructure/reports/archive/

# 完了レポートもアーカイブ
git mv infrastructure/reports/PHASE1.5_TASK8_COMPLETION_REPORT.md \
       infrastructure/reports/archive/

git commit -m "Archive Phase 1.5 documentation"
```

**理由**: 将来的な参照、トラブルシューティング、新規メンバーへの教育資料として有用

---

## タイムライン

| 日付 | フェーズ | 作業内容 |
|------|---------|---------|
| 2025-11-26 | Phase 1.5 | 並行運用セットアップ完了 |
| 2025-12-01 | Phase 1.5 | 並行運用期間開始（2週間） |
| 2025-12-14 | Phase 1.5 | 並行運用期間終了 |
| 2025-12-15 | Phase 2 | フロントエンドUI統合開始 |
| 2025-12-28 | Phase 2 | フロントエンドUI統合完了 |
| **2026-01-01** | **Phase 3** | **Breeze削除・クリーンアップ実施** ← このドキュメントの実行タイミング |
| 2026-01-07 | Phase 3 | 検証期間（1週間） |
| 2026-01-14 | Phase 3 | Phase 3完了宣言 |

---

## 関連ドキュメント

- [Phase 1.5 Task 8 完了レポート](./PHASE1.5_TASK8_COMPLETION_REPORT.md)
- [Phase 1.5 Task 8 移行計画書](./PHASE1.5_TASK8_DUAL_AUTH_MIGRATION_PLAN.md)
- [Phase 1 完了レポート](./PHASE1_COMPLETION_REPORT.md)
- [Microservices Migration Plan](../../definitions/microservices-migration-plan.md)

---

**作成者**: AI Development Assistant  
**最終更新**: 2025年11月26日
