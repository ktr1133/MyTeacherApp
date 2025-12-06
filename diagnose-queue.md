# アバター生成ジョブ待機問題 診断レポート

**日時**: 2025-12-06  
**環境**: 本番環境 (myteacher-production-cluster)

## 症状
アバター画像生成が「待機中(pending)」のまま処理されない

## 調査結果

### ✅ 正常に動作している部分
1. ECSタスクは正常稼働 (2台)
2. Redisクラスタは正常稼働 (available)
3. キューワーカープロセスは起動している (PID: 70)
4. アプリケーションからのジョブ登録コードは正常

### ❌ 問題点
1. CloudWatch Logsにジョブ処理ログが一切ない
2. `Processing:`, `Processed:`, `Failed:` などのログが見つからない
3. キューワーカーが実際にジョブを処理している形跡がない

## 推奨対応手順

### 1. ECSタスクに接続してRedis接続を確認

```bash
# ECSタスクIDを取得
TASK_ID=$(aws ecs list-tasks \
  --cluster myteacher-production-cluster \
  --service-name myteacher-production-app-service \
  --region ap-northeast-1 \
  --query 'taskArns[0]' \
  --output text | rev | cut -d/ -f1 | rev)

# Systems Manager Session Managerでタスクに接続
aws ecs execute-command \
  --cluster myteacher-production-cluster \
  --task "$TASK_ID" \
  --container app \
  --command "/bin/bash" \
  --interactive \
  --region ap-northeast-1
```

**タスク内で実行するコマンド**:
```bash
# キューワーカーのログを確認
tail -f storage/logs/queue-$(date +%Y%m%d).log

# Redis接続確認
php artisan tinker
>>> \Illuminate\Support\Facades\Redis::connection()->ping()
>>> \Illuminate\Support\Facades\Redis::connection()->llen('queues:default')

# ジョブを手動実行
php artisan queue:work redis --once --verbose

# キューワーカープロセス確認
ps aux | grep queue:work
```

### 2. セキュリティグループの確認

```bash
# Redisのセキュリティグループを確認
aws elasticache describe-cache-clusters \
  --cache-cluster-id myteacher-production-redis \
  --show-cache-node-info \
  --region ap-northeast-1 \
  --query 'CacheClusters[0].{SecurityGroups:SecurityGroups,CacheSubnetGroupName:CacheSubnetGroupName}'

# ECSタスクのセキュリティグループを確認
aws ecs describe-tasks \
  --cluster myteacher-production-cluster \
  --tasks "$TASK_ID" \
  --region ap-northeast-1 \
  --query 'tasks[0].attachments[0].details[?name==`securityGroups`].value' \
  --output table
```

**確認ポイント**:
- ECSタスクのセキュリティグループからRedis (port 6379) へのアウトバウンドが許可されているか
- RedisのセキュリティグループでECSタスクからのインバウンド (port 6379) が許可されているか

### 3. キューテーブル直接確認（代替手段）

Redisキューでなくdatabaseキューに切り替えて確認:

```sql
-- jobsテーブル確認
SELECT id, queue, payload, attempts, created_at 
FROM jobs 
ORDER BY id DESC 
LIMIT 10;

-- failed_jobsテーブル確認  
SELECT id, queue, payload, exception, failed_at
FROM failed_jobs
ORDER BY id DESC
LIMIT 10;
```

### 4. 緊急対応: databaseキューへの切り替え

Redisキューが原因の場合、一時的にdatabaseキューに切り替え:

```bash
# ECSタスク定義を更新
aws ecs register-task-definition \
  --cli-input-json file://task-definition-with-database-queue.json

# サービスを更新
aws ecs update-service \
  --cluster myteacher-production-cluster \
  --service myteacher-production-app-service \
  --task-definition myteacher-production-app:85 \
  --force-new-deployment \
  --region ap-northeast-1
```

**task-definition-with-database-queue.json**:
```json
{
  "environment": [
    {
      "name": "QUEUE_CONNECTION",
      "value": "database"
    }
  ]
}
```

### 5. キューワーカーログの転送確認

CloudWatch Logsにキューワーカーログが転送されていない可能性:

```bash
# ECSタスク定義のlogConfiguration確認
aws ecs describe-task-definition \
  --task-definition myteacher-production-app:84 \
  --region ap-northeast-1 \
  --query 'taskDefinition.containerDefinitions[0].logConfiguration'
```

## 次のステップ

1. **最優先**: ECSタスクに接続してRedis接続を確認
2. セキュリティグループの設定を確認・修正
3. 必要に応じてdatabaseキューへ一時的に切り替え
4. 根本原因を特定後、恒久対策を実施

---

**作成者**: GitHub Copilot  
**参照ドキュメント**: `.github/copilot-instructions.md` 不具合対応方針

## 追加調査結果 (2025-12-06 16:50)

### セキュリティグループ確認結果

✅ **セキュリティグループは正しく設定されている**
- Redisセキュリティグループ: `sg-03bc2a75095831e03`
- ECSタスクセキュリティグループ: `sg-0e94db2289e5cb5b0`
- ✅ ECSタスク → Redis (port 6379) のインバウンドルールが存在
- ✅ ECS Exec機能も有効化済み

### 新たな仮説

1. **キューワーカーのログが`storage/logs/`に書き込まれているが、CloudWatchに転送されていない**
   - entrypoint.shでは `storage/logs/queue-YYYYMMDD.log` にログ出力
   - CloudWatch Logsの設定ではstdout/stderrのみ転送している可能性

2. **Redis接続は成功しているが、キューにジョブが登録されていない**
   - ジョブ登録時にRedis接続エラーで失敗している可能性
   - エラーがアプリケーションログに出力されていない可能性

## 緊急対応: 即座に確認可能な方法

### 方法1: ECS Execで直接確認（推奨）

```bash
# タスクIDを取得
TASK_ID=$(aws ecs list-tasks \
  --cluster myteacher-production-cluster \
  --service-name myteacher-production-app-service \
  --region ap-northeast-1 \
  --query 'taskArns[0]' \
  --output text | rev | cut -d/ -f1 | rev)

# タスクに接続
aws ecs execute-command \
  --cluster myteacher-production-cluster \
  --task "$TASK_ID" \
  --container app \
  --command "/bin/bash" \
  --interactive \
  --region ap-northeast-1
```

**タスク内で実行するコマンド（コピペ用）**:
```bash
# 1. キューワーカーのログ確認
echo "=== Queue Worker Log ==="
tail -100 storage/logs/queue-$(date +%Y%m%d).log

# 2. キューワーカープロセス確認
echo "=== Queue Worker Processes ==="
ps aux | grep "queue:work"

# 3. Redis接続テスト
echo "=== Redis Connection Test ==="
php -r "require 'vendor/autoload.php'; \$redis = new Redis(); \$redis->connect('${REDIS_HOST}', 6379); echo 'PING: ' . \$redis->ping() . PHP_EOL; echo 'Queue length: ' . \$redis->llen('queues:default') . PHP_EOL;"

# 4. ジョブを手動実行（最も重要）
echo "=== Manual Queue Processing ==="
php artisan queue:work redis --once --verbose --timeout=60

# 5. 失敗ジョブ確認
echo "=== Failed Jobs ==="
php artisan queue:failed
```

### 方法2: キューワーカーログをstdoutに出力する修正（恒久対策）

**entrypoint.shの修正**:
```bash
# 現在の実装（ファイルに出力）
php artisan queue:work "$QUEUE_DRIVER" --sleep=3 --tries=3 --max-time=3600 --timeout=300 >> "$QUEUE_LOGFILE" 2>&1

# 修正後（stdoutとファイルの両方に出力）
php artisan queue:work "$QUEUE_DRIVER" --sleep=3 --tries=3 --max-time=3600 --timeout=300 2>&1 | tee -a "$QUEUE_LOGFILE"
```

### 方法3: teacher_avatarsテーブル確認

アバターレコードの状態を確認:
```sql
SELECT 
  id,
  user_id,
  name,
  generation_status,
  created_at,
  updated_at
FROM teacher_avatars
WHERE generation_status = 'pending'
ORDER BY created_at DESC
LIMIT 10;
```

## 推奨される即時対応（優先順位順）

1. **【最優先】ECS Execでタスクに接続し、手動でキューワーカーを実行**
   - 上記の「方法1」を実行
   - `php artisan queue:work redis --once --verbose`で1ジョブだけ処理
   - エラーメッセージを確認

2. **entrypoint.shを修正してキューワーカーログをCloudWatchに転送**
   - `tee`コマンドでstdoutとファイルの両方に出力
   - 新しいDockerイメージをビルド・デプロイ

3. **databaseキューへの一時的切り替え（Redisが原因の場合）**
   - `QUEUE_CONNECTION=database`に変更
   - ジョブテーブルで直接状態確認可能

---

**次回更新**: ECS Exec実行結果を追記予定
