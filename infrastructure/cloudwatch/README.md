# CloudWatch監視設定 - スケジュールタスク

## 概要

MyTeacherのスケジュールタスクの実行状況をCloudWatchで監視するための設定です。

### 監視内容

1. **タスク実行失敗の検知** - ERROR レベルログの監視
2. **個別タスク失敗の検知** - 個別タスクのエラー監視
3. **スケジューラー停止の検知** - 1時間以上実行がない場合にアラート

## ファイル構成

```
infrastructure/cloudwatch/
├── README.md                                    # このファイル
├── metric-filters.json                          # メトリクスフィルター定義
├── alarms.json                                  # アラーム定義
└── setup-scheduled-tasks-monitoring.sh          # セットアップスクリプト
```

## セットアップ手順

### 前提条件

- AWS CLIがインストールされていること
- 適切なAWS認証情報が設定されていること
- `/ecs/myteacher-production` ロググループが存在すること

### 1. SNSトピックの作成（初回のみ）

```bash
# SNSトピックを作成
aws sns create-topic --name myteacher-alerts

# 出力されたARNを環境変数にセット
export SNS_TOPIC_ARN="arn:aws:sns:ap-northeast-1:ACCOUNT_ID:myteacher-alerts"

# メール通知を登録
aws sns subscribe \
  --topic-arn $SNS_TOPIC_ARN \
  --protocol email \
  --notification-endpoint your-email@example.com

# 送信された確認メールのリンクをクリック
```

### 2. 監視設定の適用

```bash
# Dry-runで確認（実際の変更は行わない）
cd /home/ktr/mtdev/infrastructure/cloudwatch
./setup-scheduled-tasks-monitoring.sh --dry-run

# 実際に適用
export SNS_TOPIC_ARN="arn:aws:sns:ap-northeast-1:ACCOUNT_ID:myteacher-alerts"
./setup-scheduled-tasks-monitoring.sh
```

### 3. 設定確認

```bash
# メトリクスフィルターを確認
aws logs describe-metric-filters \
  --log-group-name /ecs/myteacher-production \
  --query 'metricFilters[?starts_with(filterName, `ScheduledTask`)].filterName'

# アラームを確認
aws cloudwatch describe-alarms \
  --alarm-name-prefix "MyTeacher-ScheduledTasks" \
  --query 'MetricAlarms[].[AlarmName,StateValue]' \
  --output table
```

### 4. テストアラームの送信

```bash
# アラームをテスト（実際に通知が送信される）
aws cloudwatch set-alarm-state \
  --alarm-name MyTeacher-ScheduledTasks-Failures \
  --state-value ALARM \
  --state-reason 'Testing alarm notification'
```

## 監視設定の詳細

### メトリクスフィルター

| フィルター名 | 検知パターン | メトリクス名 |
|------------|------------|------------|
| ScheduledTasksFailures | `Scheduled tasks execution completed with failures` | ScheduledTasksFailureCount |
| ScheduledTaskIndividualFailures | `Failed to execute scheduled task` | ScheduledTaskIndividualFailureCount |
| ScheduledTasksSuccess | `Scheduled tasks executed successfully` | ScheduledTasksSuccessCount |

### アラーム

| アラーム名 | 条件 | 優先度 | 説明 |
|----------|------|--------|------|
| MyTeacher-ScheduledTasks-Failures | 失敗数 >= 1 (5分間) | HIGH | タスク実行失敗を即座に検知 |
| MyTeacher-ScheduledTasks-NoExecutions | 成功数 < 1 (1時間) | CRITICAL | スケジューラー停止を検知 |

## CloudWatchコンソールでの確認

### メトリクス

```
https://console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#metricsV2:graph=~();namespace=MyTeacher/ScheduledTasks
```

### アラーム

```
https://console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#alarmsV2:
```

### ロググループ

```
https://console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#logsV2:log-groups/log-group/$252Fecs$252Fmyteacher-production
```

## トラブルシューティング

### アラームが発火しない

1. **メトリクスが記録されているか確認**
   ```bash
   aws cloudwatch get-metric-statistics \
     --namespace MyTeacher/ScheduledTasks \
     --metric-name ScheduledTasksFailureCount \
     --start-time $(date -u -d '1 hour ago' +%Y-%m-%dT%H:%M:%S) \
     --end-time $(date -u +%Y-%m-%dT%H:%M:%S) \
     --period 300 \
     --statistics Sum
   ```

2. **ログパターンが一致しているか確認**
   ```bash
   aws logs filter-log-events \
     --log-group-name /ecs/myteacher-production \
     --filter-pattern '[time, request_id, level=ERROR*, msg="Scheduled tasks execution completed with failures*"]' \
     --start-time $(date -d '1 hour ago' +%s)000
   ```

3. **アラームの状態を確認**
   ```bash
   aws cloudwatch describe-alarms \
     --alarm-names MyTeacher-ScheduledTasks-Failures
   ```

### メール通知が届かない

1. **SNSサブスクリプションを確認**
   ```bash
   aws sns list-subscriptions-by-topic \
     --topic-arn $SNS_TOPIC_ARN
   ```

2. **サブスクリプションのステータスを確認**
   - ステータスが `PendingConfirmation` の場合、確認メールのリンクをクリック
   - ステータスが `Confirmed` であることを確認

3. **迷惑メールフォルダを確認**

### スケジューラーが動作しない

1. **ECSタスクでcronが実行されているか確認**
   ```bash
   aws ecs list-tasks --cluster myteacher-production
   # タスクARNを取得
   
   aws ecs execute-command \
     --cluster myteacher-production \
     --task TASK_ARN \
     --container app \
     --interactive \
     --command "crontab -l"
   ```

2. **スケジューラーログを確認**
   ```bash
   aws logs tail /ecs/myteacher-production \
     --since 1h \
     --filter-pattern "schedule:run"
   ```

## 設定の更新・削除

### メトリクスフィルターの削除

```bash
aws logs delete-metric-filter \
  --log-group-name /ecs/myteacher-production \
  --filter-name ScheduledTasksFailures
```

### アラームの削除

```bash
aws cloudwatch delete-alarms \
  --alarm-names MyTeacher-ScheduledTasks-Failures
```

### SNSトピックの削除

```bash
aws sns delete-topic --topic-arn $SNS_TOPIC_ARN
```

## 関連ドキュメント

- [Laravel スケジューラー設定](../../docs/CRONSETTING.md)
- [スケジュールタスク実装](../../app/Services/Batch/ScheduledTaskService.php)
- [AWS CloudWatch Logs メトリクスフィルター](https://docs.aws.amazon.com/ja_jp/AmazonCloudWatch/latest/logs/MonitoringLogData.html)
- [AWS CloudWatch アラーム](https://docs.aws.amazon.com/ja_jp/AmazonCloudWatch/latest/monitoring/AlarmThatSendsEmail.html)

## 更新履歴

| 日付 | 更新者 | 内容 |
|------|--------|------|
| 2025-12-01 | GitHub Copilot | 初版作成: スケジュールタスク監視設定 |
