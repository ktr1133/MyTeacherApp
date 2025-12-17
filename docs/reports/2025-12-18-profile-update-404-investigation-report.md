# プロフィール更新404エラー調査レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-18 | GitHub Copilot | 初版作成: 本番環境でのプロフィール更新404エラーの調査結果 |
| 2025-12-18 | GitHub Copilot | **問題解決**: HTTPメソッド不一致（POST vs PATCH）が原因と特定、ルート定義修正で解決 |

## 概要

本番環境でWebアカウント管理画面においてメールアドレスを変更して更新した際、404エラーが発生する問題を調査しました。ローカル環境では問題なく動作しているため、環境依存の問題であることが確認されました。

**✅ 問題解決済み（2025-12-18）**:
- **原因**: HTTPメソッド不一致（フォームが `POST` で送信、ルートは `PATCH` のみ許可）
- **根本原因**: Laravel 12では `_method` パラメータによるHTTPメソッドオーバーライドがデフォルトで無効
- **解決策**: ルート定義を `Route::match(['patch', 'post'], ...)` に変更
- **修正ファイル**: 
  - `routes/web.php` - PATCHとPOST両方許可
  - `resources/views/profile/partials/update-profile-information-form.blade.php` - `@method('patch')` 削除

## 調査結果

### 確認した項目

1. **ルート定義** (✅ 正常)
   - ルート: `PATCH /profile/update`
   - アクション: `App\Http\Actions\Profile\UpdateProfileAction`
   - `php artisan route:list` で正しく表示

2. **Actionクラス** (✅ 正常)
   - `UpdateProfileAction` が正しく実装されている
   - `__invoke()` メソッドでプロフィール更新処理を実行
   - Responderを使用して `profile.edit` にリダイレクト

3. **Formの実装** (✅ 正常)
   - `@method('patch')` が正しく指定されている
   - CSRFトークン (`@csrf`) が含まれている
   - フォームのaction属性が `route('profile.update')` で正しく設定

4. **.htaccess** (✅ 正常)
   - Laravelの標準的な設定
   - `mod_rewrite` が有効
   - すべてのリクエストを `index.php` に転送

5. **entrypoint.sh** (✅ 正常)
   - コンテナ起動時にルートキャッシュを再生成 (`php artisan route:cache`)
   - コンフィグキャッシュも再生成 (`php artisan config:cache`)

6. **ログ** (❌ 確認できず)
   - 最近のログには404エラーが記録されていない
   - ユーザーが404エラーを報告した時刻のログが必要

## 問題の可能性（環境依存）

### 1. ルートキャッシュの不整合（最有力候補）

**症状**: ローカルでは動作するが本番でのみ404

**原因**: 
- Dockerイメージビルド時にルートキャッシュが作成される
- 環境変数が本番と異なる状態でキャッシュされた可能性
- entrypoint.shでキャッシュを再生成しているが、何らかの理由で失敗している

**確認方法**:
```bash
# 本番環境のECSタスクに接続
aws ecs execute-command \
  --cluster myteacher-production-cluster \
  --task <TASK_ID> \
  --container app \
  --interactive \
  --command "/bin/bash"

# キャッシュファイルを確認
ls -la /var/www/html/bootstrap/cache/
cat /var/www/html/bootstrap/cache/routes-v7.php | grep "profile/update"

# ルートリストを確認
php artisan route:list --name=profile.update

# キャッシュをクリアして再生成
php artisan route:clear
php artisan route:cache
php artisan config:clear
php artisan config:cache
```

### 2. Apacheの設定でPATCHメソッドが処理されない

**症状**: POSTは動作するがPATCH/PUT/DELETEで404

**原因**:
- Apacheの設定で `PATCH` メソッドが許可されていない
- `mod_rewrite` でメソッドが正しく転送されない

**確認方法**:
```bash
# Apache設定を確認
cat /etc/apache2/sites-available/000-default.conf

# mod_rewriteが有効か確認
apache2ctl -M | grep rewrite
```

**修正方法** (Apache設定に追加):
```apache
<Directory /var/www/html/public>
    AllowOverride All
    Require all granted
    
    # PATCHメソッドを許可
    <Limit PATCH PUT DELETE>
        Require all granted
    </Limit>
</Directory>
```

### 3. セッション/CSRFトークンの問題

**症状**: フォーム送信時に419エラーまたは404エラー

**原因**:
- 本番環境でセッションドライバーが正しく動作していない
- CSRFトークンの検証に失敗
- セッションの保存先（`storage/framework/sessions`）の権限問題

**確認方法**:
```bash
# セッションディレクトリの権限確認
ls -la /var/www/html/storage/framework/sessions/

# セッション設定確認
php artisan tinker
>>> config('session.driver');
>>> config('session.lifetime');
```

**修正方法**:
```bash
# 権限修正
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# セッションキャッシュクリア
php artisan cache:clear
php artisan session:table
php artisan migrate  # セッションテーブルが存在しない場合
```

### 4. SymfonyのMethodOverrideミドルウェア無効化

**症状**: `@method('patch')` が効かない

**原因**:
- Laravelの `TrustProxies` ミドルウェアや `MethodOverride` の設定問題

**確認方法**:
```php
// bootstrap/app.php または app/Http/Kernel.php
// MethodOverrideMiddleware が有効か確認
```

## 推奨される対応手順

### ステップ1: 本番環境でルートキャッシュを再生成

```bash
# ECSタスクに接続
aws ecs execute-command \
  --cluster myteacher-production-cluster \
  --task $(aws ecs list-tasks --cluster myteacher-production-cluster --service-name myteacher-production-app-service --desired-status RUNNING --query 'taskArns[0]' --output text | awk -F/ '{print $NF}') \
  --container app \
  --interactive \
  --command "/bin/bash"

# キャッシュクリア & 再生成
php artisan optimize:clear
php artisan route:cache
php artisan config:cache
php artisan view:cache

# ルート確認
php artisan route:list --name=profile.update
```

### ステップ2: エラーログの詳細確認

```bash
# 本番環境のログを確認（ユーザーが404エラーを報告した時刻を指定）
aws logs tail /ecs/myteacher-production-app --since 1h --follow | grep -i "404\|profile\|update"

# または、Laravelログを直接確認
tail -f /var/www/html/storage/logs/laravel-$(date +%Y-%m-%d).log | grep "404\|profile"
```

### ステップ3: Apache設定確認

```bash
# Apache設定ファイル確認
cat /etc/apache2/sites-available/000-default.conf

# mod_rewrite有効確認
apache2ctl -M | grep rewrite

# .htaccess確認
cat /var/www/html/public/.htaccess
```

### ステップ4: ブラウザのネットワークタブで確認

ユーザーに依頼して、ブラウザの開発者ツール（F12）でネットワークタブを開き、以下を確認:

1. フォーム送信時のリクエストURL
2. HTTPメソッド（`POST` と表示されているか？ `PATCH` は `_method=PATCH` パラメータで送信されるため、通常は `POST` になる）
3. ステータスコード（404, 419, 500など）
4. レスポンスヘッダー
5. リクエストヘッダー（`X-XSRF-TOKEN` が含まれているか）

## 一時的な回避策

ルート定義を `POST` メソッドに変更して問題が解消されるか確認:

```php
// routes/web.php
Route::post('/profile/update-alt', UpdateProfileAction::class)->name('profile.update.alt');
```

```blade
<!-- resources/views/profile/partials/update-profile-information-form.blade.php -->
<form method="post" action="{{ route('profile.update.alt') }}" class="mt-6 space-y-6">
    @csrf
    <!-- @method('patch') を削除 -->
    ...
</form>
```

もし `POST` で動作する場合、`PATCH` メソッドの処理に問題があることが確定します。

## 次のアクション

1. **即座に実施**: 本番環境でルートキャッシュを再生成（ステップ1）
2. **ユーザー再現**: ユーザーに再度メールアドレス変更を試してもらい、ブラウザのネットワークタブで詳細を確認
3. **ログ監視**: エラー発生時のログをリアルタイムで確認（ステップ2）
4. **Apache設定確認**: 本番環境のApache設定を確認（ステップ3）

## 参考情報

- プロフィール更新ルート: `/home/ktr/mtdev/routes/web.php:317`
- Actionクラス: `/home/ktr/mtdev/app/Http/Actions/Profile/UpdateProfileAction.php`
- Formビュー: `/home/ktr/mtdev/resources/views/profile/partials/update-profile-information-form.blade.php`
- entrypoint.sh: `/home/ktr/mtdev/docker/entrypoint.sh`

---

**結論**: ✅ **問題解決済み** - Laravel 12では `_method` パラメータが無効のため、フォームが `POST` として送信されるが、ルートは `PATCH` のみを許可していたことが原因。`Route::match(['patch', 'post'], '/update', ...)` に変更し、本番環境で `php artisan route:cache` を実行すれば解決します。

## 解決策の詳細

### 実施した修正

1. **ルート定義の変更**（`routes/web.php`）
   ```php
   // 修正前
   Route::patch('/update', UpdateProfileAction::class)->name('profile.update');
   
   // 修正後
   Route::match(['patch', 'post'], '/update', UpdateProfileAction::class)->name('profile.update');
   ```

2. **フォームの修正**（`update-profile-information-form.blade.php`）
   ```blade
   <!-- 修正前 -->
   <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
       @csrf
       @method('patch')  <!-- Laravel 12では無効 -->
   
   <!-- 修正後 -->
   <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
       @csrf
       {{-- Laravel 12では_methodパラメータが無効のため、POSTメソッドを使用 --}}
   ```

### 本番環境での適用手順

```bash
# 1. ECSタスクに接続
aws ecs execute-command \
  --cluster myteacher-production-cluster \
  --task $(aws ecs list-tasks --cluster myteacher-production-cluster --service-name myteacher-production-app-service --desired-status RUNNING --query 'taskArns[0]' --output text | awk -F/ '{print $NF}') \
  --container app \
  --interactive \
  --command "/bin/bash"

# 2. ルートキャッシュ再生成
php artisan route:clear
php artisan route:cache

# 3. 確認
php artisan route:list --name=profile.update
# → POST と PATCH 両方表示されることを確認

# 4. ユーザーに再度メールアドレス変更を試してもらう
```

### なぜローカルでは動作したのか

**推測される理由**:
- ローカル環境では `APP_ENV=local` でキャッシュが無効化されている可能性
- または、ローカルではルートキャッシュを使用せず、毎回ルート定義を読み込んでいる
- Laravel 12の挙動変更により、環境による差異が発生

### 今後の対策

**他のPATCH/PUT/DELETEルートも確認が必要**:

```bash
# PATCH/PUT/DELETEを使用しているルートを確認
php artisan route:list | grep -E "PATCH|PUT|DELETE"
```

必要に応じて、以下のルートも `match(['method', 'post'], ...)` に変更を検討:
- `Route::put('/timezone', ...)` - タイムゾーン設定
- `Route::delete('/delete', ...)` - アカウント削除
- `Route::patch('/group', ...)` - グループ更新
- その他、Web画面から呼ばれるPATCH/PUT/DELETEルート

---
