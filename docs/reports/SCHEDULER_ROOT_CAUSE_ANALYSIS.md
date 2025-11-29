# スケジューラログ出力問題 - 根本原因分析レポート

**作成日**: 2025年11月26日  
**対象環境**: 本番環境（AWS ECS Fargate）  
**問題**: Laravel Schedulerは起動しているがログが出力されない

---

## 🔍 根本原因

### 問題の特定

**症状**:
```bash
# CloudWatch Logsに起動メッセージは存在
Starting Laravel Scheduler in background...
✓ Scheduler started (PID: 123)

# しかし、schedule:run の実行ログが全く出力されていない
```

**根本原因**: **ログファイルへの書き込み権限不足**

`docker/entrypoint-production.sh` の91行目:
```bash
su -s /bin/bash www-data -c "php artisan schedule:run" >> /var/log/scheduler.log 2>&1
```

### 3つの問題点

#### 1. ログファイルが事前作成されていない

`/var/log/scheduler.log` ファイルが存在しないため、リダイレクトが失敗

#### 2. www-dataユーザーに/var/log/への書き込み権限がない

```bash
# /var/log/ は通常 root:root 所有、755 権限
drwxr-xr-x  1 root root  4096 Nov 26 12:00 /var/log/
```

`www-data` ユーザーは `/var/log/` ディレクトリに新規ファイルを作成できない

#### 3. エラーがサイレントに失敗している

バックグラウンドプロセス `&` で実行されているため、リダイレクトエラーが表示されない

---

## 🧪 検証方法

### コンテナ内で手動確認（推奨）

```bash
# ECS Execでコンテナに接続
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

# コンテナ内で実行
# 1. ログファイル確認
ls -la /var/log/scheduler.log
# → 存在しない、またはサイズ0

# 2. 手動でコマンド実行
su -s /bin/bash www-data -c "php artisan schedule:run" >> /var/log/scheduler.log 2>&1
# → エラー: bash: /var/log/scheduler.log: Permission denied

# 3. ディレクトリ権限確認
ls -ld /var/log/
# → drwxr-xr-x  1 root root  4096 Nov 26 12:00 /var/log/

# 4. プロセス確認
ps aux | grep "schedule:run"
# → プロセスは存在しない（リダイレクトエラーで即終了）
```

---

## ✅ 解決策

### 推奨方法: Laravelの storage/logs/ ディレクトリを使用

#### 修正案A: ログパスを変更（最小限の変更）

**docker/entrypoint-production.sh** を修正:

```bash
# 修正前（91行目）
su -s /bin/bash www-data -c "php artisan schedule:run" >> /var/log/scheduler.log 2>&1

# 修正後
su -s /bin/bash www-data -c "php artisan schedule:run" >> storage/logs/scheduler.log 2>&1
```

**メリット**:
- `storage/logs/` は既に `www-data:www-data` 所有
- 書き込み権限あり（775）
- Laravel の他のログと同じ場所
- CloudWatch Logs へ自動出力される（既存設定）

**デメリット**:
- なし（推奨）

---

### 代替方法B: /var/log/scheduler.log を事前作成

**docker/entrypoint-production.sh** の冒頭に追加:

```bash
# スケジューラ起動前に追加（65行目あたり）
# ログファイルを作成して権限付与
touch /var/log/scheduler.log
chown www-data:www-data /var/log/scheduler.log
chmod 644 /var/log/scheduler.log

# 既存のスケジューラ起動コード（そのまま）
echo "Starting Laravel Scheduler in background..."
(
    while true; do
        su -s /bin/bash www-data -c "php artisan schedule:run" >> /var/log/scheduler.log 2>&1
        sleep 60
    done
) &
```

**メリット**:
- `/var/log/` にログを集約
- システムログとの統一性

**デメリット**:
- 追加の権限変更が必要
- CloudWatch Logs への出力設定が必要

---

### 代替方法C: 標準出力/標準エラー出力を使用（最も簡単）

**docker/entrypoint-production.sh** を修正:

```bash
# 修正前（91行目）
su -s /bin/bash www-data -c "php artisan schedule:run" >> /var/log/scheduler.log 2>&1

# 修正後（ファイル出力なし）
su -s /bin/bash www-data -c "php artisan schedule:run" 2>&1
```

**メリット**:
- 最も簡単（権限問題なし）
- CloudWatch Logs に自動出力
- ファイル管理不要

**デメリット**:
- ログが他のApacheログと混在
- フィルタリングが必要

---

## 🚀 推奨実装手順

### Step 1: entrypoint-production.sh の修正

```bash
cd /home/ktr/mtdev

# 修正案A: storage/logs/ を使用（推奨）
```

**変更内容**:
```diff
--- a/docker/entrypoint-production.sh
+++ b/docker/entrypoint-production.sh
@@ -88,7 +88,7 @@ echo "✓ Scheduler started (PID: $QUEUE_PID)"
 echo "Starting Laravel Scheduler in background..."
 (
     while true; do
-        su -s /bin/bash www-data -c "php artisan schedule:run" >> /var/log/scheduler.log 2>&1
+        su -s /bin/bash www-data -c "php artisan schedule:run" >> storage/logs/scheduler.log 2>&1
         sleep 60
     done
 ) &
```

### Step 2: 変更をコミット

```bash
git add docker/entrypoint-production.sh
git commit -m "Fix scheduler log output to use writable storage directory

- Changed log path from /var/log/scheduler.log to storage/logs/scheduler.log
- Fixes permission denied error for www-data user
- Logs now appear in CloudWatch Logs via existing configuration
- Resolves issue where scheduler runs but produces no output"

git push origin feature/dev-structure
```

### Step 3: ECRにプッシュ

```bash
cd infrastructure/terraform

# 本番環境にデプロイ
terraform apply -auto-approve
```

### Step 4: ECSサービス更新（新しいタスク定義でデプロイ）

```bash
aws ecs update-service \
  --cluster myteacher-production-cluster \
  --service myteacher-production-app-service \
  --force-new-deployment \
  --region ap-northeast-1
```

### Step 5: デプロイ確認（5分後）

```bash
# 1. タスクが新しいイメージで起動しているか確認
aws ecs describe-services \
  --cluster myteacher-production-cluster \
  --services myteacher-production-app-service \
  --region ap-northeast-1 \
  --query 'services[0].deployments'

# 2. スケジューラログを確認
aws logs tail /ecs/myteacher-production --since 5m --region ap-northeast-1 | grep -i "schedule\|No scheduled commands"

# 3. エラーがないか確認
aws logs tail /ecs/myteacher-production --since 5m --region ap-northeast-1 | grep -i "error\|permission denied"
```

**期待される出力**:
```
No scheduled commands are ready to run.
```
または
```
Running scheduled command: redis:monitor
```

---

## 📊 影響範囲

### 現在の影響

**動作していないスケジュールタスク**:
- ✅ `batch:execute-scheduled-tasks` - 毎時実行（バッチタスク自動生成）
- ✅ `notifications:delete-expired` - 毎日3:00（期限切れ通知削除）
- ✅ `redis:monitor` - 5分ごと（Redis健全性監視）
- ✅ 祝日キャッシュ更新 - 毎日0:00
- ✅ 古い実行履歴削除 - 毎週日曜3:00
- ✅ 古いキャッシュクリア - 毎日3:00

**ユーザーへの影響**:
- スケジュールタスクが自動生成されない
- 期限切れ通知が蓄積される（軽微）
- Redis監視アラートが発報されない
- キャッシュが古いまま蓄積される可能性

### 修正後の期待動作

- ✅ すべてのスケジュールタスクが正常実行
- ✅ ログが `storage/logs/scheduler.log` に出力
- ✅ CloudWatch Logs で確認可能
- ✅ 監視アラートが正常動作

---

## 🔒 再発防止策

### 1. ログパスのベストプラクティス

**推奨**: Laravelアプリケーションのログは `storage/logs/` 配下に統一

```bash
# Good
storage/logs/scheduler.log
storage/logs/queue-worker.log
storage/logs/laravel.log

# Avoid
/var/log/scheduler.log  # 権限管理が複雑
/tmp/scheduler.log      # 再起動で消える
```

### 2. entrypoint.sh のログ出力テスト

**開発環境での確認**:
```bash
# ローカルDockerで権限テスト
docker-compose exec app bash
su - www-data -c "touch /var/log/test.log"  # 失敗するはず
su - www-data -c "touch storage/logs/test.log"  # 成功するはず
```

### 3. CloudWatch Logs 設定の見直し

**現在の設定**:
```hcl
# infrastructure/terraform/modules/myteacher/ecs.tf
log_configuration = {
  logDriver = "awslogs"
  options = {
    "awslogs-group"         = "/ecs/myteacher-production"
    "awslogs-region"        = "ap-northeast-1"
    "awslogs-stream-prefix" = "ecs"
  }
}
```

✅ 標準出力/標準エラー出力は自動的に CloudWatch Logs に送信される

### 4. 監視アラートの追加

**推奨設定**:
```hcl
# infrastructure/terraform/modules/myteacher/cloudwatch.tf
resource "aws_cloudwatch_log_metric_filter" "scheduler_no_output" {
  name           = "scheduler-no-output"
  log_group_name = "/ecs/myteacher-production"
  pattern        = "Starting Laravel Scheduler"

  metric_transformation {
    name      = "SchedulerStartCount"
    namespace = "MyTeacher/Scheduler"
    value     = "1"
  }
}

resource "aws_cloudwatch_metric_alarm" "scheduler_not_running" {
  alarm_name          = "myteacher-scheduler-not-running"
  comparison_operator = "LessThanThreshold"
  evaluation_periods  = "2"
  metric_name         = "SchedulerStartCount"
  namespace           = "MyTeacher/Scheduler"
  period              = "3600"  # 1時間
  statistic           = "Sum"
  threshold           = "1"
  alarm_description   = "Scheduler has not run in the last hour"
  treat_missing_data  = "breaching"
  alarm_actions       = [aws_sns_topic.alerts.arn]
}
```

---

## 📝 関連ドキュメント

- [SCHEDULER_TROUBLESHOOTING_GUIDE.md](./SCHEDULER_TROUBLESHOOTING_GUIDE.md) - 診断手順書
- [2025-11-25_SESSION_AND_QUEUE_FIX_REPORT.md](./2025-11-25_SESSION_AND_QUEUE_FIX_REPORT.md) - スケジューラ実装履歴

---

**作成者**: AI Development Assistant  
**最終更新**: 2025年11月26日  
**ステータス**: 修正実装待ち
