# 本番環境データベーススキーマ不一致調査レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-18 | GitHub Copilot | 初版作成: 本番環境のマイグレーション実行状況とスキーマ不一致の調査 |

## 概要

本番環境のusersテーブルにおいて、マイグレーションファイルで定義されている`email_verified_at`カラムが存在しないことが判明しました。この問題により、プロフィール更新時にPostgreSQLエラー（`SQLSTATE[42703]: Undefined column`）が発生しています。

## 問題の詳細

### 発見されたスキーマ不一致

| マイグレーションファイル | 定義されているカラム | 本番環境の状態 | ステータス |
|------------------------|---------------------|---------------|----------|
| `0001_01_01_000000_create_users_table.php` | `email_verified_at` (34行目) | **存在しない** | ❌ 不一致 |
| `2025_12_16_142013_add_consent_tracking_columns_to_users_table.php` | `created_by_user_id`, `consent_given_by_user_id`, `privacy_policy_version`, `terms_version`, `privacy_policy_agreed_at`, `terms_agreed_at`, `self_consented_at` | **全て存在** | ✅ 一致 |
| `2025_12_16_181539_add_parent_invitation_token_to_users_table.php` | `parent_invitation_token`, `parent_invitation_expires_at` | **全て存在** | ✅ 一致 |
| `2025_12_17_000000_add_parent_user_id_to_users_table.php` | `parent_user_id` | **存在** | ✅ 一致 |

### マイグレーション実行履歴

```
php artisan migrate:status
```

| マイグレーション | バッチ | ステータス |
|----------------|--------|-----------|
| `0001_01_01_000000_create_users_table` | 1 | Ran |
| `2025_12_16_115636_add_minor_consent_columns_to_users_table` | 10 | Ran |
| `2025_12_16_142013_add_consent_tracking_columns_to_users_table` | 10 | Ran |
| `2025_12_16_181539_add_parent_invitation_token_to_users_table` | 10 | Ran |
| `2025_12_17_000000_add_parent_user_id_to_users_table` | 10 | Ran |

**重要**: すべてのマイグレーションが「Ran」ステータスになっていますが、実際には`email_verified_at`カラムが作成されていません。

### マイグレーション実行ログ（CloudWatch Logs）

```
[2025-12-17 17:04:24] production.INFO: HTTPS scheme forced for all URLs  

   INFO  Running migrations.  

  2025_12_16_115636_add_minor_consent_columns_to_users_table .... 77.05ms DONE
  2025_12_16_142013_add_consent_tracking_columns_to_users_table  152.08ms DONE
  2025_12_16_181539_add_parent_invitation_token_to_users_table .. 25.40ms DONE
  2025_12_17_000000_add_parent_user_id_to_users_table ........... 25.13ms DONE
```

**マイグレーション実行日時**: 2025-12-17 17:04:24 (UTC) = 日本時間 2025-12-18 02:04:24

昨日（12月17日）のデプロイで追加されたカラムは**正常に作成されている**ことが確認できます。

## 本番環境のusersテーブル構造

### 実際に存在するカラム（50カラム）

```bash
php artisan db:table users
```

存在するカラム:
```
allowed_ips, auth_provider, birthdate, cognito_sub, consent_given_by_user_id,
created_at, created_by_user_id, deleted_at, email, failed_login_attempts,
group_edit_flg, group_id, id, is_admin, is_locked, is_minor,
last_failed_login_at, last_login_at, locked_at, locked_reason, name,
notification_settings, parent_consent_expires_at, parent_consent_token,
parent_consented_at, parent_email, parent_invitation_expires_at,
parent_invitation_token, parent_user_id, password, pm_last_four, pm_type,
privacy_policy_agreed_at, privacy_policy_version, remember_token,
requires_purchase_approval, self_consented_at, stripe_id, terms_agreed_at,
terms_version, theme, timezone, token_mode, trial_ends_at,
two_factor_confirmed_at, two_factor_enabled, two_factor_recovery_codes,
two_factor_secret, updated_at, username
```

### 存在しないカラム

- ❌ **`email_verified_at`** - マイグレーションファイル `0001_01_01_000000_create_users_table.php:34` で定義されているが存在しない

## 根本原因の推定

### 仮説1: 初期テーブル作成時に手動で作成された

バッチ1として記録されている`0001_01_01_000000_create_users_table`マイグレーションが実行された際、実際には既にusersテーブルが存在していた可能性があります。

Laravelのマイグレーションは、`Schema::create()`実行時にテーブルが既に存在する場合、エラーをスローせずにスキップする動作があります（`createOrFail()`を使っていない場合）。

### 仮説2: マイグレーションファイルが後から修正された

初期デプロイ時の`0001_01_01_000000_create_users_table.php`には`email_verified_at`カラムが含まれていなかった可能性があります。その後、マイグレーションファイルが修正されたが、本番環境では再実行されていない。

### 仮説3: データベースの直接編集

開発初期段階で、マイグレーション実行ではなくSQLコマンドやGUIツール（DBeaver、pgAdmin等）で直接テーブルを作成した可能性があります。

## 影響範囲

### 直接的な影響

1. **プロフィール更新エラー**
   - `UpdateProfileAction.php` でメールアドレス変更時に`email_verified_at = null`を設定しようとして`SQLSTATE[42703]`エラー発生
   - ユーザーには500エラーが404エラーページとして表示される

2. **メール認証機能の不在**
   - メールアドレス認証フローが機能していない可能性
   - `email_verified_at`を参照するコードでエラーが発生する

### 間接的な影響

- ローカル環境と本番環境のスキーマ差異により、ローカルで動作確認したコードが本番でエラーになる
- テストデータと本番データの構造が異なるため、デバッグが困難

## 対策

### 短期対策（実施済み）

1. ✅ **`UpdateProfileAction.php`の修正**
   - `Schema::hasColumn('users', 'email_verified_at')` チェックを追加
   - カラムが存在する場合のみ`email_verified_at = null`を設定
   - コード修正コミット: 未プッシュ

### 中期対策（推奨）

1. **マイグレーションの追加実行**
   
   新規マイグレーションファイルを作成し、`email_verified_at`カラムを追加：
   
   ```php
   // database/migrations/2025_12_18_XXXXXX_add_email_verified_at_to_users_table.php
   Schema::table('users', function (Blueprint $table) {
       if (!Schema::hasColumn('users', 'email_verified_at')) {
           $table->timestamp('email_verified_at')
                 ->nullable()
                 ->after('email')
                 ->comment('メール認証日時');
       }
   });
   ```

2. **スキーマ検証スクリプトの作成**
   
   デプロイ時に自動でスキーマ差異を検出するコマンドを実装：
   
   ```php
   php artisan schema:validate --table=users
   ```

### 長期対策

1. **インフラコード化（Infrastructure as Code）**
   - Terraformによるデータベーススキーマ管理
   - GitHub Actionsでのスキーマ差分チェック自動化

2. **マイグレーション実行ログの保存**
   - CloudWatch Logsだけでなく、S3にもマイグレーション実行ログを永続保存
   - マイグレーション実行前後のスキーマスナップショットを取得

3. **ステージング環境の構築**
   - 本番と同じインフラ構成のステージング環境を用意
   - 本番デプロイ前にステージングで動作確認

## 次のアクション

### 優先度：高

- [ ] `UpdateProfileAction.php`の修正をコミット・プッシュ
- [ ] GitHub Actionsで自動デプロイ実行
- [ ] 本番環境で動作確認（メールアドレス更新テスト）

### 優先度：中

- [ ] `email_verified_at`カラム追加マイグレーションファイル作成
- [ ] 本番環境でマイグレーション実行
- [ ] メール認証機能の動作確認

### 優先度：低

- [ ] スキーマ検証スクリプト実装
- [ ] ステージング環境構築計画策定

## 教訓

1. **マイグレーション履歴を過信しない**
   - `php artisan migrate:status`が「Ran」でも、実際のスキーマと一致しているとは限らない
   - 定期的にスキーマ差分チェックを実施すべき

2. **ローカルと本番の環境差異を早期に発見**
   - CI/CDパイプラインにスキーマ検証ステップを追加
   - デプロイ前にスキーマ差分レポートを自動生成

3. **防御的プログラミングの重要性**
   - カラム存在確認（`Schema::hasColumn()`）を適宜使用
   - 本番環境特有のエラーを想定したコード設計

4. **インフラコード化の推進**
   - データベーススキーマもコードで管理し、バージョン管理下に置く
   - 手動でのDB操作を最小限に抑える

---

**関連ドキュメント**:
- [プロフィール更新404エラー調査レポート](/home/ktr/mtdev/docs/reports/2025-12-18-profile-update-404-investigation-report.md)
- [親子アカウント連携機能実装レポート](/home/ktr/mtdev/docs/reports/2025-12-17-parent-child-linking-implementation-report.md)
