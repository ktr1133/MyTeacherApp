# Laravel Scheduler トラブルシューティングガイド

**作成日**: 2025年11月26日  
**対象環境**: 本番環境（AWS ECS Fargate）  
**問題**: スケジューラが動作していない

---

## 📋 目次

1. [問題の概要](#問題の概要)
2. [現在の実装方式](#現在の実装方式)
3. [診断手順](#診断手順)
4. [よくある原因と対処法](#よくある原因と対処法)
5. [推奨される改善策](#推奨される改善策)
6. [緊急対応手順](#緊急対応手順)

---

## 問題の概要

**症状**: 本番環境でLaravelのスケジュールタスクが実行されていない

**影響範囲**:
- Redis健全性監視（5分ごと）
- 並行運用監視（5分ごと - 12/1-12/14期間）
- バッチ実行（スケジュールタスク）
- 期限切れ通知削除（日次）
- 古い決済履歴クリーンアップ（月次）

**現在の実装**:
- `docker/entrypoint-production.sh` のバックグラウンドプロセスとして実行
- ログ出力先: `/var/log/scheduler.log`
- 実行間隔: 60秒

---

## 現在の実装方式

### docker/entrypoint-production.sh（抜粋）

```bash
# Laravel Schedulerをバックグラウンドで起動（毎分実行）
echo "Starting Laravel Scheduler in background..."
(
    while true; do
        su -s /bin/bash www-data -c "php artisan schedule:run" >> /var/log/scheduler.log 2>&1
        sleep 60
    done
) &
SCHEDULER_PID=$!
```

### app/Console/Kernel.php（スケジュール定義）

```php
protected function schedule(Schedule $schedule): void
{
    // Redis監視（5分ごと）
    $schedule->command('redis:monitor')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/redis-monitoring.log'));

    // 並行運用監視（12/1-12/14のみ、5分ごと）
    if (now()->between('2025-12-01', '2025-12-14')) {
        $schedule->command('auth:monitor-dual-auth --alert')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/dual-auth-monitoring.log'));
    }

    // 期限切れ通知削除（日次 00:00）
    $schedule->command('notifications:delete-expired')
        ->daily()
        ->withoutOverlapping()
        ->onOneServer();

    // 古い決済履歴削除（月次 1日 01:00）
    $schedule->command('payment:clean-old-histories')
        ->monthlyOn(1, '01:00')
        ->withoutOverlapping()
        ->onOneServer();
}
```

---

## 診断手順

### Step 1: ECSタスクログの確認

```bash
# 最新のECSタスクID取得
TASK_ARN=$(aws ecs list-tasks \
  --cluster myteacher-production-cluster \
  --service-name myteacher-production-app-service \
  --desired-status RUNNING \
  --region ap-northeast-1 \
  --query 'taskArns[0]' \
  --output text)

echo "Task ARN: $TASK_ARN"

# タスクログを確認（CloudWatch Logs）
aws logs tail /ecs/myteacher-production --follow --region ap-northeast-1

# スケジューラ起動メッセージを確認
aws logs tail /ecs/myteacher-production --since 30m --region ap-northeast-1 | grep -i "Starting Laravel Scheduler"
```

**期待される出力**:
```
[INFO] Starting Laravel Scheduler in background...
[INFO] Scheduler PID: 123
```

### Step 2: コンテナ内でプロセス確認

```bash
# ECSタスクIDを取得
TASK_ID=$(echo $TASK_ARN | awk -F/ '{print $NF}')

# ECS Execで接続
aws ecs execute-command \
  --cluster myteacher-production-cluster \
  --task $TASK_ID \
  --container app \
  --interactive \
  --command "/bin/bash" \
  --region ap-northeast-1

# コンテナ内で実行
ps aux | grep "schedule:run"
ps aux | grep "artisan"

# スケジューラPIDが存在するか確認
echo $SCHEDULER_PID

# スケジューラログ確認
tail -f /var/log/scheduler.log

# Laravelログ確認
tail -f /var/www/html/storage/logs/laravel.log
```

**期待される出力**:
```
www-data  456  0.0  1.2  php artisan schedule:run
```

### Step 3: スケジューラログの確認

```bash
# コンテナ内で実行
ls -lh /var/log/scheduler.log

# ログ内容確認（最新100行）
tail -n 100 /var/log/scheduler.log

# エラーメッセージを検索
grep -i "error\|fail\|exception" /var/log/scheduler.log
```

**正常な場合の出力例**:
```
[2025-11-26 12:00:00] Running scheduled command: redis:monitor
[2025-11-26 12:00:01] Scheduled command completed successfully
[2025-11-26 12:05:00] Running scheduled command: redis:monitor
```

### Step 4: 手動でスケジュールコマンド実行

```bash
# コンテナ内で手動実行
cd /var/www/html
su -s /bin/bash www-data -c "php artisan schedule:run"

# エラーが出る場合は詳細確認
php artisan schedule:list
php artisan redis:monitor
```

**期待される出力**:
```
Running scheduled command: redis:monitor
Command completed successfully
```

### Step 5: 権限・環境変数の確認

```bash
# ログディレクトリの権限確認
ls -ld /var/log
ls -l /var/log/scheduler.log

# www-dataユーザーで書き込み可能か確認
su -s /bin/bash www-data -c "touch /var/log/test-write && rm /var/log/test-write"

# Laravel環境変数の確認
su -s /bin/bash www-data -c "php artisan env"

# Redis接続確認
su -s /bin/bash www-data -c "php artisan tinker --execute='Redis::ping();'"
```

---

## よくある原因と対処法

### 1. バックグラウンドプロセスが起動していない

**原因**:
- entrypoint-production.sh の実行権限不足
- コンテナ起動時のエラーでバックグラウンド処理がスキップされた

**確認方法**:
```bash
ps aux | grep "while true"
ps aux | grep "schedule:run"
```

**対処法**:
```bash
# entrypoint.sh の権限確認（ローカル）
ls -l /home/ktr/mtdev/docker/entrypoint-production.sh

# 実行権限付与
chmod +x /home/ktr/mtdev/docker/entrypoint-production.sh

# 再デプロイ
cd /home/ktr/mtdev/infrastructure/terraform
terraform apply -auto-approve
```

### 2. ログファイルの書き込み権限不足

**原因**:
- `/var/log/scheduler.log` が存在しない、または権限不足
- `www-data` ユーザーで書き込めない

**確認方法**:
```bash
ls -l /var/log/scheduler.log
su -s /bin/bash www-data -c "echo 'test' >> /var/log/scheduler.log"
```

**対処法**:
```bash
# ログファイル作成と権限付与（コンテナ内）
touch /var/log/scheduler.log
chown www-data:www-data /var/log/scheduler.log
chmod 644 /var/log/scheduler.log

# または entrypoint.sh に追加
echo "touch /var/log/scheduler.log" >> docker/entrypoint-production.sh
echo "chown www-data:www-data /var/log/scheduler.log" >> docker/entrypoint-production.sh
```

### 3. Redisまたはデータベース接続エラー

**原因**:
- Redis/PostgreSQL接続失敗でスケジューラが異常終了
- 環境変数が正しく設定されていない

**確認方法**:
```bash
# Redis接続確認
php artisan tinker --execute="Redis::ping();"

# DB接続確認
php artisan tinker --execute="DB::connection()->getPdo();"

# 環境変数確認
printenv | grep -E "REDIS|DB_"
```

**対処法**:
```bash
# ECS Task Definitionの環境変数を確認
cd /home/ktr/mtdev/infrastructure/terraform/modules/myteacher
cat ecs.tf | grep -A50 "environment ="

# 正しい環境変数に修正してデプロイ
terraform plan
terraform apply -auto-approve
```

### 4. `onOneServer()` の競合

**原因**:
- 複数ECSタスクが起動している場合、Redisキャッシュを使った排他制御が機能していない
- Redisキャッシュストアの設定ミス

**確認方法**:
```bash
# キャッシュドライバー確認
php artisan tinker --execute="echo config('cache.default');"

# Redis接続確認
php artisan tinker --execute="Cache::store('redis')->get('test');"

# ECSタスク数確認
aws ecs describe-services \
  --cluster myteacher-production-cluster \
  --services myteacher-production-app-service \
  --region ap-northeast-1 \
  --query 'services[0].runningCount'
```

**対処法**:
```bash
# キャッシュドライバーをredisに設定
# .env または ECS環境変数
CACHE_STORE=redis
CACHE_DRIVER=redis

# terraform apply で反映
cd /home/ktr/mtdev/infrastructure/terraform
terraform apply -auto-approve
```

### 5. スケジュール定義の日付条件

**原因**:
- `now()->between('2025-12-01', '2025-12-14')` の期間外でコマンドが登録されていない

**確認方法**:
```bash
# 現在日時確認
php artisan tinker --execute="echo now();"

# スケジュール一覧確認
php artisan schedule:list
```

**対処法**:
- 12月1日以降に再確認
- テスト用に日付条件を削除して動作確認

---

## 推奨される改善策

### 問題: バックグラウンドプロセスの監視不足

現在の実装は `entrypoint-production.sh` でバックグラウンドプロセスとして起動しているが、以下の問題がある:

1. プロセスが異常終了しても再起動しない
2. ログ出力が停止しても気づきにくい
3. ECSタスク再起動時にプロセスが起動失敗するリスク

### 改善案 1: AWS EventBridge + ECS Scheduled Tasks（推奨）

**メリット**:
- AWSマネージド（高可用性）
- プロセス監視不要
- ログがCloudWatch Logsに自動出力
- 失敗時のアラート設定が容易

**実装方法**:
```hcl
# infrastructure/terraform/modules/myteacher/eventbridge.tf
resource "aws_cloudwatch_event_rule" "laravel_scheduler" {
  name                = "myteacher-production-scheduler"
  description         = "Run Laravel scheduler every minute"
  schedule_expression = "rate(1 minute)"
}

resource "aws_cloudwatch_event_target" "ecs_scheduled_task" {
  rule      = aws_cloudwatch_event_rule.laravel_scheduler.name
  target_id = "run-scheduled-task"
  arn       = aws_ecs_cluster.main.arn
  role_arn  = aws_iam_role.ecs_events.arn

  ecs_target {
    task_count          = 1
    task_definition_arn = aws_ecs_task_definition.scheduler.arn
    launch_type         = "FARGATE"
    network_configuration {
      subnets          = module.vpc.private_subnets
      security_groups  = [aws_security_group.app.id]
      assign_public_ip = false
    }
  }
}
```

**コスト**: 約 $0.50/月（60分 × 24時間 × 30日 = 43,200回/月）

### 改善案 2: Supervisord（中程度推奨）

**メリット**:
- プロセス監視・自動再起動
- ログローテーション
- ECS内で完結

**実装方法**:
```bash
# docker/supervisor/scheduler.conf
[program:laravel-scheduler]
command=/bin/bash -c "while true; do su -s /bin/bash www-data -c 'php artisan schedule:run' >> /var/log/scheduler.log 2>&1; sleep 60; done"
autostart=true
autorestart=true
stderr_logfile=/var/log/scheduler.err.log
stdout_logfile=/var/log/scheduler.out.log
```

```dockerfile
# Dockerfile.production に追加
RUN apt-get update && apt-get install -y supervisor
COPY docker/supervisor/scheduler.conf /etc/supervisor/conf.d/scheduler.conf
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
```

**コスト**: $0（既存インフラ内）

### 改善案 3: ヘルスチェック強化

**現在のヘルスチェック**: `/health` エンドポイントのみ

**提案**:
- スケジューラ専用ヘルスチェックエンドポイント追加
- 最終実行時刻をRedisに保存し、5分以上更新されていない場合はアラート

```php
// routes/web.php
Route::get('/health/scheduler', function () {
    $lastRun = Cache::get('scheduler:last_run');
    
    if (!$lastRun || $lastRun->lt(now()->subMinutes(5))) {
        return response()->json(['status' => 'unhealthy', 'last_run' => $lastRun], 503);
    }
    
    return response()->json(['status' => 'healthy', 'last_run' => $lastRun]);
});

// app/Console/Kernel.php に追加
protected function schedule(Schedule $schedule): void
{
    $schedule->call(function () {
        Cache::put('scheduler:last_run', now(), now()->addMinutes(10));
    })->everyMinute();
    
    // 既存のスケジュール...
}
```

---

## 緊急対応手順

### 即座の復旧（10分以内）

```bash
# 1. ECSタスク再起動
aws ecs update-service \
  --cluster myteacher-production-cluster \
  --service myteacher-production-app-service \
  --force-new-deployment \
  --region ap-northeast-1

# 2. タスク起動確認（2-3分待機）
aws ecs describe-services \
  --cluster myteacher-production-cluster \
  --services myteacher-production-app-service \
  --region ap-northeast-1 \
  --query 'services[0].deployments'

# 3. ログ確認
aws logs tail /ecs/myteacher-production --follow --region ap-northeast-1
```

### 手動スケジュール実行（応急処置）

```bash
# ECS Execで接続
TASK_ARN=$(aws ecs list-tasks \
  --cluster myteacher-production-cluster \
  --service-name myteacher-production-app-service \
  --desired-status RUNNING \
  --region ap-northeast-1 \
  --query 'taskArns[0]' \
  --output text)

TASK_ID=$(echo $TASK_ARN | awk -F/ '{print $NF}')

aws ecs execute-command \
  --cluster myteacher-production-cluster \
  --task $TASK_ID \
  --container app \
  --interactive \
  --command "/bin/bash" \
  --region ap-northeast-1

# コンテナ内で手動実行
cd /var/www/html
php artisan schedule:run
php artisan redis:monitor
```

---

## 監視とアラート

### CloudWatch Alarms（推奨設定）

```hcl
# infrastructure/terraform/modules/myteacher/cloudwatch.tf
resource "aws_cloudwatch_log_metric_filter" "scheduler_errors" {
  name           = "scheduler-errors"
  log_group_name = "/ecs/myteacher-production"
  pattern        = "[ERROR] Scheduler"

  metric_transformation {
    name      = "SchedulerErrorCount"
    namespace = "MyTeacher/Scheduler"
    value     = "1"
  }
}

resource "aws_cloudwatch_metric_alarm" "scheduler_errors" {
  alarm_name          = "myteacher-scheduler-errors"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = "1"
  metric_name         = "SchedulerErrorCount"
  namespace           = "MyTeacher/Scheduler"
  period              = "300"
  statistic           = "Sum"
  threshold           = "5"
  alarm_description   = "Scheduler errors exceeded threshold"
  alarm_actions       = [aws_sns_topic.alerts.arn]
}
```

---

## チェックリスト

### 診断完了チェックリスト

- [ ] ECSタスクログで "Starting Laravel Scheduler" メッセージ確認
- [ ] `ps aux | grep schedule:run` でプロセス確認
- [ ] `/var/log/scheduler.log` ファイル存在確認
- [ ] スケジューラログに最新のエントリ存在
- [ ] `php artisan schedule:list` で登録コマンド確認
- [ ] 手動で `php artisan schedule:run` 実行成功
- [ ] Redis接続確認（`Redis::ping()`）
- [ ] データベース接続確認
- [ ] キャッシュドライバーが `redis` に設定済み

### 改善実施チェックリスト

- [ ] EventBridge + ECS Scheduled Tasks 実装（推奨）
- [ ] Supervisord 導入（代替案）
- [ ] スケジューラヘルスチェックエンドポイント追加
- [ ] CloudWatch Alarms 設定
- [ ] ログローテーション設定
- [ ] 運用手順書更新

---

**作成者**: AI Development Assistant  
**最終更新**: 2025年11月26日  
**関連ドキュメント**:
- [Phase 0.5 完了レポート](./PHASE0.5_COMPLETION_REPORT.md)
- [2025-11-25 セッション・キュー修正レポート](./2025-11-25_SESSION_AND_QUEUE_FIX_REPORT.md)
