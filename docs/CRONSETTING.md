# Cron設定ガイド

## 概要

定期バッチ機能を使用するには、サーバーでLaravelのスケジューラーを実行するようにCronを設定する必要があります。

## Cron設定

### 1. Crontabを編集

```bash
crontab -e
```

### 2. 以下の行を追加

```cron
* * * * * cd /path/to/your-project && php artisan schedule:run >> /dev/null 2>&1
```

**注意**: `/path/to/your-project` を実際のプロジェクトパスに置き換えてください。

### 3. 設定の確認

```bash
crontab -l
```

## スケジュール実行頻度のカスタマイズ

`app/Console/Kernel.php` で実行頻度を変更できます。

### 毎時実行（デフォルト）

```php
$schedule->command('batch:execute-scheduled-tasks')
    ->hourly()
    ->withoutOverlapping();
```

### 10分ごとに実行

```php
$schedule->command('batch:execute-scheduled-tasks')
    ->everyTenMinutes()
    ->withoutOverlapping();
```

### 毎日午前0時に実行

```php
$schedule->command('batch:execute-scheduled-tasks')
    ->dailyAt('00:00')
    ->withoutOverlapping();
```

### 特定の曜日・時刻に実行

```php
$schedule->command('batch:execute-scheduled-tasks')
    ->weekdays()
    ->at('09:00')
    ->withoutOverlapping();
```

## 手動実行コマンド

### 全スケジュールタスクを実行

```bash
php artisan batch:execute-scheduled-tasks
```

### 特定のスケジュールタスクを実行

```bash
php artisan batch:execute-task {id}
```

例:
```bash
php artisan batch:execute-task 1
```

### スケジュールタスク一覧を表示

```bash
# 全タスク
php artisan batch:list-tasks

# 特定グループのタスク
php artisan batch:list-tasks --group=1
```

## ログの確認

スケジュール実行のログは以下で確認できます：

```bash
tail -f storage/logs/laravel.log
```

## トラブルシューティング

### Cronが動作しない場合

1. **Cronのログを確認**
   ```bash
   grep CRON /var/log/syslog
   ```

2. **PHPのパスを確認**
   ```bash
   which php
   ```
   
   Crontabで絶対パスを使用：
   ```cron
   * * * * * cd /path/to/project && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```

3. **権限を確認**
   ```bash
   ls -la storage/logs
   ```
   
   必要に応じて権限を付与：
   ```bash
   chmod -R 775 storage
   chown -R www-data:www-data storage
   ```

4. **環境変数を設定**
   ```cron
   * * * * * cd /path/to/project && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
   SHELL=/bin/bash
   PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
   ```

### スケジュールが実行されない場合

1. **スケジュールタスクの状態を確認**
   ```bash
   php artisan batch:list-tasks
   ```

2. **手動で実行してエラーを確認**
   ```bash
   php artisan batch:execute-scheduled-tasks
   ```

3. **データベース接続を確認**
   ```bash
   php artisan tinker
   >>> \DB::connection()->getPdo();
   ```

## 開発環境での確認方法

Cronを設定せずにスケジューラーを確認したい場合：

```bash
# 1分ごとにチェック（Ctrl+Cで停止）
while true; do php artisan schedule:run; sleep 60; done
```

または、Laravel Schedulerの監視コマンド（Laravel 8以降）：

```bash
php artisan schedule:work
```