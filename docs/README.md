#---------------------------
# デプロイ時のセットアップ手順
#---------------------------

# スケジュールタスク機能セットアップ手順

## 1. 前提条件

- EC2インスタンス（Linux）にLaravelアプリケーションがデプロイ済み
- Apache + PostgreSQL環境
- SSH接続可能

## 2. マイグレーション実行

```bash
cd /var/www/html
php artisan migrate
```

## 3. Cron設定

### 3-1. 自動セットアップスクリプト使用（推奨）

```bash
cd /var/www/html
chmod +x scripts/setup-cron.sh
sudo ./scripts/setup-cron.sh
```

### 3-2. 手動設定

```bash
# www-dataユーザーのcrontabを編集
sudo crontab -e -u www-data

# 以下を追加
* * * * * cd /var/www/html && /usr/bin/php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1
```

## 4. 動作確認

### 4-1. Dry-runで実行予定を確認

```bash
php artisan scheduled-tasks:execute --dry-run
```

### 4-2. 手動実行テスト

```bash
php artisan scheduled-tasks:execute
```

### 4-3. ログ確認

```bash
# リアルタイム監視
tail -f /var/log/laravel-scheduler.log

# スケジュールタスク専用ログ
tail -f storage/logs/scheduled-tasks.log

# Laravelアプリケーションログ
tail -f storage/logs/laravel.log
```

## 5. 祝日データの登録

```bash
# データベースに祝日データを登録
php artisan db:seed --class=HolidaySeeder

# または直接SQL実行
psql -U your_user -d your_database -f database/seeds/holidays_2024.sql
```

## 6. トラブルシューティング

### Cronが動作しない場合

```bash
# Cronサービスの状態確認
sudo systemctl status cron

# Cronサービスの再起動
sudo systemctl restart cron

# www-dataユーザーのcron設定確認
sudo crontab -l -u www-data
```

### ログが出力されない場合

```bash
# ログファイルの権限確認
ls -la /var/log/laravel-scheduler.log

# 権限修正
sudo chown www-data:www-data /var/log/laravel-scheduler.log
sudo chmod 644 /var/log/laravel-scheduler.log
```

### タスクが作成されない場合

```bash
# データベース接続確認
php artisan tinker
>>> \App\Models\ScheduledGroupTask::count();

# スケジュール設定確認
php artisan scheduled-tasks:execute --dry-run -vvv
```

## 7. 監視設定（オプション）

### CloudWatch Logsへの出力（AWS環境の場合）

```bash
# awslogs設定
sudo yum install awslogs

# /etc/awslogs/awslogs.confに追加
[/var/log/laravel-scheduler.log]
datetime_format = %Y-%m-%d %H:%M:%S
file = /var/log/laravel-scheduler.log
buffer_duration = 5000
log_stream_name = {instance_id}
initial_position = start_of_file
log_group_name = /laravel/scheduler
```

## 8. パフォーマンス最適化

### キュー使用（大量タスク作成時）

```bash
# .envにキュー設定追加
QUEUE_CONNECTION=database

# キューワーカー起動
php artisan queue:work --daemon
```

## 9. 定期メンテナンス

```bash
# 実行履歴クリーンアップ（Kernel.phpで自動実行設定済み）
# 手動実行する場合
php artisan tinker
>>> \App\Models\ScheduledTaskExecution::where('created_at', '<', now()->subMonths(6))->delete();

# ログローテーション確認
sudo logrotate -f /etc/logrotate.d/laravel-scheduler
```

#---------------------------
# 実行権限の付与
#---------------------------

#!/bin/bash

# スクリプトに実行権限を付与
chmod +x scripts/setup-cron.sh
chmod +x scripts/run-scheduled-tasks.sh

echo "実行権限を付与しました"

#----------------------------------------------------
#----------------------------------------------------

# 定期バッチ機能ドキュメント

## 機能概要

定期バッチ機能により、指定したスケジュールに従って自動的にタスクを作成できます。

## 主な機能

### 1. スケジュール設定

- **毎日**: 毎日指定時刻に実行
- **毎週**: 指定曜日の指定時刻に実行
- **毎月**: 指定日の指定時刻に実行

複数のスケジュールを組み合わせることも可能です。

### 2. 担当者設定

- **固定担当者**: 特定のユーザーに割り当て
- **ランダム割り当て**: グループメンバーからランダムに選択

### 3. 祝日対応

- **祝日をスキップ**: 祝日の場合はタスクを作成しない
- **翌営業日に移動**: 祝日の場合は次の平日に実行

### 4. タスク管理

- **未完了タスクの削除**: 新タスク作成時に前回の未完了タスクを削除
- **期限設定**: タスク作成から期限までの時間を設定
- **報酬ポイント**: 完了時の報酬を設定

### 5. 実行履歴

- 実行日時
- 作成されたタスク
- 担当者
- 完了状態
- エラー情報

## 使い方

### スケジュールタスクの作成

1. グループ管理画面から「定期バッチ設定」をクリック
2. 「新規作成」ボタンをクリック
3. 必要な情報を入力
   - タイトル（必須）
   - 説明
   - 報酬ポイント
   - 担当者設定
   - スケジュール設定（必須）
   - 期限設定
   - 実行期間
   - その他オプション
4. 「作成する」ボタンをクリック

### スケジュールタスクの編集

1. 一覧画面から編集したいタスクの編集ボタンをクリック
2. 情報を変更
3. 「更新する」ボタンをクリック

### スケジュールタスクの一時停止/再開

- **一時停止**: 一覧画面の一時停止ボタンをクリック
- **再開**: 一覧画面の再開ボタンをクリック

### 実行履歴の確認

1. 一覧画面から実行履歴を確認したいタスクの履歴ボタンをクリック
2. 実行履歴が時系列で表示されます

## コマンドライン操作

### 全スケジュールタスクを実行

```bash
php artisan batch:execute-scheduled-tasks
```

### 特定のスケジュールタスクを実行

```bash
php artisan batch:execute-task {id}
```

### スケジュールタスク一覧を表示

```bash
# 全タスク
php artisan batch:list-tasks

# 特定グループ
php artisan batch:list-tasks --group=1
```

## 技術仕様

### データベーステーブル

- `scheduled_tasks`: スケジュール定義
- `scheduled_task_executions`: 実行履歴

### 実行フロー

1. Cronが毎時Laravelスケジューラーを起動
2. スケジューラーが`batch:execute-scheduled-tasks`コマンドを実行
3. 現在時刻に該当するスケジュールタスクを検索
4. 各スケジュールタスクに対してタスクを作成
5. 実行履歴を記録

### エラーハンドリング

- タスク作成失敗時は実行履歴に記録
- ログファイルにエラー詳細を出力
- 一つのタスクの失敗が他のタスクに影響しない

## 注意事項

### パフォーマンス

- 大量のスケジュールタスクを設定する場合、実行時間を考慮してください
- 必要に応じて実行頻度を調整してください（Kernel.phpで設定）

### データ整合性

- スケジュールタスク削除時、作成済みのタスクは削除されません
- 実行履歴は保持されます

### 権限

- スケジュールタスクの作成・編集・削除には`canEditGroup`権限が必要です
- 実行履歴の閲覧にはグループメンバーである必要があります

## FAQ

### Q: スケジュールタスクが実行されない

A: 以下を確認してください：
1. Cronが正しく設定されているか
2. スケジュールタスクが有効になっているか
3. 実行期間内であるか
4. ログファイルにエラーが記録されていないか

### Q: 特定の日だけタスクを作成したくない

A: 「祝日をスキップ」オプションを有効にしてください。

### Q: 担当者をランダムに割り当てたい

A: 「グループメンバーにランダム割り当て」オプションを有効にしてください。

### Q: 前回のタスクが未完了のまま新しいタスクを作成したくない

A: 「未完了の前回タスクを削除」オプションを有効にすると、新タスク作成時に前回の未完了タスクが削除されます。

## 10. サブスクリプション期間終了クリーンアップ

### 10-1. 概要

サブスクリプション期間終了後、Groupsテーブルを無料プランに自動リセットします。

**実行タイミング**: 毎日深夜3時（JST）  
**目的**: Webhook失敗時のフォールバック処理

### 10-2. 手動実行（開発環境）

**重要**: ホスト側から実行する場合は環境変数を上書きしてください。

**方法1: Dockerコンテナ内で実行（推奨）**
```bash
# Dockerコンテナ内で実行（本番環境と同じ環境）
docker exec mtdev-app-1 php artisan subscription:cleanup-expired

# 実行結果
期間終了サブスクリプションのクリーンアップを開始します...
対象サブスクリプション: 0件
クリーンアップ完了: 0件のGroupをリセットしました
```

**方法2: ホスト側から実行（環境変数上書き）**
```bash
# ❌ NG: DB接続エラー（DB_HOST=db はDockerコンテナ名）
php artisan subscription:cleanup-expired

# ✅ OK: 環境変数を上書き（ローカルPostgreSQL使用時）
DB_HOST=localhost DB_PORT=5432 DB_PASSWORD=laravel_password php artisan subscription:cleanup-expired
```

**推奨**: **Dockerコンテナ内での実行**が本番環境に最も近い動作確認方法です。

### 10-3. ログ確認

```bash
# 専用ログファイル
tail -f storage/logs/subscription-cleanup.log

# 実行結果例
[2025-12-08 03:00:00] INFO: サブスクリプションクリーンアップ成功
[2025-12-08 03:00:01] INFO: 処理済み: 2件
```

### 10-4. テスト実行

```bash
# Integration Test（Artisan::call()でコマンド実行）
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Console/CleanupExpiredSubscriptionsTest.php
```

### 10-5. 監視項目

- **ログファイル**: `storage/logs/subscription-cleanup.log`
- **確認項目**: 処理件数、失敗件数、孤児データ件数
- **アラート**: `onFailure()` でエラーログ記録

---

## 11. Faviconの生成・更新

### 11-1. 概要

Webアプリケーションのfaviconは、モバイルアプリのアイコン（`mobile/assets/icon.png`）と同じデザインを使用しています。

### 11-2. 生成スクリプト

```bash
# Pillow（Python画像処理ライブラリ）のインストール（初回のみ）
sudo apt-get install -y python3-pil

# favicon生成
cd /home/ktr/mtdev
python3 generate-favicons.py
```

**生成されるファイル**:
- `public/favicon-16x16.png` (16×16px)
- `public/favicon-32x32.png` (32×32px)
- `public/favicon.ico` (複数サイズを含むICO形式)
- `public/apple-touch-icon.png` (180×180px、iOS用)
- `public/android-chrome-192x192.png` (192×192px、Android用)
- `public/android-chrome-512x512.png` (512×512px、Android用)
- `public/favicon.svg` (SVGベクター形式、`mobile/assets/icon.svg`から自動コピー)

### 11-3. HTMLでの参照

生成されたfaviconは以下のレイアウトファイルで自動的に参照されます:

- `resources/views/layouts/app.blade.php` (認証済みユーザー画面)
- `resources/views/layouts/guest.blade.php` (ゲスト画面)
- `resources/views/layouts/portal.blade.php` (ポータルサイト)

**HTML例**:
```html
<!-- Favicons -->
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="alternate icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="alternate icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
```

### 11-4. ブラウザキャッシュのクリア

favicon変更後、ブラウザキャッシュをクリアします:

```bash
# Laravelキャッシュクリア
php artisan view:clear
php artisan cache:clear
```

**注意**: ブラウザ側のfaviconキャッシュは強力なため、ハードリフレッシュ（Ctrl+Shift+R / Cmd+Shift+R）が必要な場合があります。

### 11-5. モバイルアイコン更新時の対応

モバイルアプリのアイコン（`mobile/assets/icon.png`または`icon.svg`）を更新した場合:

1. **Webアプリのfavicon再生成**: 上記11-2のスクリプトを再実行
2. **キャッシュクリア**: 上記11-4を実行
3. **デプロイ**: 本番環境に新しいfaviconをデプロイ

---