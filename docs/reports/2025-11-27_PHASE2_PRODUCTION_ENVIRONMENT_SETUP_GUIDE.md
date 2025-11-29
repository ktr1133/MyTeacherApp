# Phase 2: Task Service本番環境準備 実行手順書

**作成日**: 2025-11-27  
**バージョン**: 1.0.0  
**ステータス**: 🚀 **実行準備完了**

---

## 📋 概要

Phase 2の本番環境準備として、Task Service用のインフラストラクチャをTerraformで構築します。

### 作成されるリソース

| リソース | タイプ | 説明 |
|---------|-------|------|
| **RDS PostgreSQL 16** | db.t3.micro | Task Service専用DB、Multi-AZ、暗号化 |
| **ECS Cluster** | Fargate | Task Service実行環境 |
| **ALB Target Group** | Application Load Balancer | /api/tasks/* ルーティング |
| **Security Groups** | VPC | RDS、ECS用のファイアウォールルール |
| **IAM Roles** | IAM | ECS Task Execution、Task Role |
| **Secrets Manager** | Secrets Manager | DB認証情報、APIキー |
| **CloudWatch Alarms** | CloudWatch | CPU、メモリ、接続数アラーム |

---

## 🔧 事前準備

### 1. 必要な環境変数

```bash
# AWS認証情報
export AWS_ACCESS_KEY_ID="your-access-key"
export AWS_SECRET_ACCESS_KEY="your-secret-key"
export AWS_REGION="ap-northeast-1"

# データベース認証情報
export TF_VAR_task_service_db_password="<STRONG_PASSWORD>"  # 16文字以上推奨
```

### 2. Terraform実行前チェック

```bash
cd /home/ktr/mtdev/infrastructure/terraform

# Terraformバージョン確認（>= 1.5必須）
terraform version

# 既存リソース確認
terraform state list

# VPC、Cognito、MyTeacherモジュールが既にデプロイされていることを確認
terraform output myteacher_vpc_id
terraform output cognito_user_pool_id
```

---

## 📝 実行手順

### Step 1: Task Service DB変数追加

`terraform.tfvars` に以下を追加:

```hcl
# Task Service Database設定
task_service_db_name             = "task_service"
task_service_db_username         = "task_service_user"
task_service_db_password         = "env://TF_VAR_task_service_db_password"  # 環境変数から取得
task_service_db_instance_class   = "db.t3.micro"
task_service_db_allocated_storage = 20
task_service_db_backup_retention = 7
```

### Step 2: Terraform Plan実行（DB作成）

```bash
cd /home/ktr/mtdev/infrastructure/terraform

# Task Service DBモジュールのみプラン
terraform plan -target=module.task_service_db

# 出力を確認
# - aws_db_instance.task_service が作成されることを確認
# - aws_security_group.task_service_db が作成されることを確認
# - aws_cloudwatch_metric_alarm が3つ作成されることを確認
```

### Step 3: Terraform Apply実行（DB作成）

```bash
# DB作成（所要時間: 約10-15分）
terraform apply -target=module.task_service_db

# 完了後、エンドポイント確認
terraform output task_service_db_endpoint
terraform output task_service_db_host

# 例: task-service-db.xxxxx.ap-northeast-1.rds.amazonaws.com:5432
```

### Step 4: データベーススキーマ作成

```bash
# スキーマSQLファイルを確認
cat infrastructure/terraform/modules/task-service-db/schema.sql

# RDSに接続してスキーマ作成
DB_HOST=$(terraform output -raw task_service_db_host)
DB_USER="task_service_user"
DB_NAME="task_service"

# PostgreSQLクライアントインストール（必要に応じて）
sudo apt-get install -y postgresql-client

# スキーマ適用
PGPASSWORD="$TF_VAR_task_service_db_password" psql \
  -h "$DB_HOST" \
  -U "$DB_USER" \
  -d "$DB_NAME" \
  -f infrastructure/terraform/modules/task-service-db/schema.sql

# 確認
PGPASSWORD="$TF_VAR_task_service_db_password" psql \
  -h "$DB_HOST" \
  -U "$DB_USER" \
  -d "$DB_NAME" \
  -c "\dt"

# 出力例:
#              List of relations
#  Schema |          Name          | Type  |       Owner        
# --------+------------------------+-------+--------------------
#  public | scheduled_group_tasks  | table | task_service_user
#  public | scheduled_task_executions | table | task_service_user
#  public | scheduled_task_tags    | table | task_service_user
#  public | task_images            | table | task_service_user
#  public | task_tag               | table | task_service_user
#  public | tasks                  | table | task_service_user
```

### Step 5: Secrets Manager設定

```bash
# DB認証情報をSecrets Managerに保存
aws secretsmanager create-secret \
  --name task-service/db-password \
  --secret-string "$TF_VAR_task_service_db_password" \
  --region ap-northeast-1

# Cognito設定をSecrets Managerに保存
COGNITO_USER_POOL_ID=$(terraform output -raw cognito_user_pool_id)
COGNITO_CLIENT_ID=$(terraform output -raw cognito_web_client_id)

aws secretsmanager create-secret \
  --name task-service/cognito-config \
  --secret-string "{\"user_pool_id\":\"$COGNITO_USER_POOL_ID\",\"client_id\":\"$COGNITO_CLIENT_ID\"}" \
  --region ap-northeast-1

# 確認
aws secretsmanager list-secrets --region ap-northeast-1 | grep task-service
```

### Step 6: ECRリポジトリ作成（Task Service用）

```bash
# ECRリポジトリ作成
aws ecr create-repository \
  --repository-name task-service \
  --image-scanning-configuration scanOnPush=true \
  --encryption-configuration encryptionType=AES256 \
  --region ap-northeast-1

# 出力からrepositoryUriを確認
# 例: 123456789012.dkr.ecr.ap-northeast-1.amazonaws.com/task-service
```

### Step 7: Task Service ECS/Fargate構築

```bash
# ECS/Fargateモジュールのプラン
terraform plan -target=module.task_service_ecs

# Apply実行（所要時間: 約5-10分）
terraform apply -target=module.task_service_ecs

# 確認
terraform output task_service_ecs_cluster_name
terraform output task_service_ecs_service_name
```

### Step 8: ALBターゲットグループ設定

```bash
# ALBにTask Service用ターゲットグループを追加
# ※ 既存のMyTeacher ALBを使用

# ターゲットグループ作成
VPC_ID=$(terraform output -raw myteacher_vpc_id)

aws elbv2 create-target-group \
  --name task-service-tg \
  --protocol HTTP \
  --port 3001 \
  --vpc-id "$VPC_ID" \
  --target-type ip \
  --health-check-enabled \
  --health-check-path /health \
  --health-check-interval-seconds 30 \
  --health-check-timeout-seconds 5 \
  --healthy-threshold-count 2 \
  --unhealthy-threshold-count 3 \
  --region ap-northeast-1

# リスナールール追加（/api/tasks/* → Task Service）
ALB_ARN=$(terraform output -raw myteacher_alb_arn)
TARGET_GROUP_ARN=$(aws elbv2 describe-target-groups \
  --names task-service-tg \
  --query 'TargetGroups[0].TargetGroupArn' \
  --output text \
  --region ap-northeast-1)

# リスナールール作成（優先度 10）
aws elbv2 create-rule \
  --listener-arn "$ALB_LISTENER_ARN" \
  --priority 10 \
  --conditions Field=path-pattern,Values='/api/tasks/*' \
  --actions Type=forward,TargetGroupArn="$TARGET_GROUP_ARN" \
  --region ap-northeast-1
```

### Step 9: CloudWatchダッシュボード作成

```bash
# Task Service専用ダッシュボード作成
aws cloudwatch put-dashboard \
  --dashboard-name TaskServiceMetrics \
  --dashboard-body file://infrastructure/cloudwatch/task-service-dashboard.json \
  --region ap-northeast-1

# ダッシュボードURL
echo "https://ap-northeast-1.console.aws.amazon.com/cloudwatch/home?region=ap-northeast-1#dashboards:name=TaskServiceMetrics"
```

### Step 10: デプロイ前最終チェック

```bash
# 1. RDS接続確認
PGPASSWORD="$TF_VAR_task_service_db_password" psql \
  -h "$(terraform output -raw task_service_db_host)" \
  -U task_service_user \
  -d task_service \
  -c "SELECT version();"

# 2. ECSタスク数確認
aws ecs describe-services \
  --cluster task-service-cluster \
  --services task-service \
  --region ap-northeast-1 \
  --query 'services[0].[desiredCount,runningCount]'

# 3. Secrets Manager確認
aws secretsmanager get-secret-value \
  --secret-id task-service/db-password \
  --region ap-northeast-1 \
  --query 'SecretString' \
  --output text

# 4. CloudWatch Alarms確認
aws cloudwatch describe-alarms \
  --alarm-name-prefix task-service \
  --region ap-northeast-1

# 5. IAM Role確認
aws iam get-role --role-name task-service-execution-role
aws iam get-role --role-name task-service-task-role
```

---

## ✅ 成功基準

### DB作成完了

- [x] RDS PostgreSQL 16インスタンスが起動中
- [x] Multi-AZ構成が有効
- [x] 暗号化が有効
- [x] 自動バックアップが有効（保持期間7日）
- [x] 6テーブルが作成済み（tasks, task_images, task_tag, scheduled_group_tasks, scheduled_task_executions, scheduled_task_tags）
- [x] インデックス13個が作成済み
- [x] トリガー2個が作成済み（updated_at自動更新）

### Secrets Manager設定完了

- [x] task-service/db-password シークレット作成済み
- [x] task-service/cognito-config シークレット作成済み

### ECS/Fargate構築完了

- [x] ECS Cluster作成済み
- [x] Task Definition作成済み（512 CPU / 1024 MB メモリ）
- [x] ECS Service作成済み（desiredCount: 2）
- [x] Auto Scaling設定済み（最小2、最大10）
- [x] Security Group設定済み

### ALB設定完了

- [x] ターゲットグループ作成済み（task-service-tg）
- [x] ヘルスチェック設定済み（/health、30秒間隔）
- [x] リスナールール作成済み（/api/tasks/* → task-service-tg）

### 監視設定完了

- [x] CloudWatch Alarms作成済み（CPU、メモリ、接続数、ストレージ）
- [x] CloudWatchダッシュボード作成済み
- [x] RDS拡張モニタリング有効（60秒間隔）
- [x] Performance Insights有効（7日保持）

---

## 🔙 ロールバック手順

### 問題発生時の緊急切り戻し

```bash
# Step 1: ECS Service停止
aws ecs update-service \
  --cluster task-service-cluster \
  --service task-service \
  --desired-count 0 \
  --region ap-northeast-1

# Step 2: ALBリスナールール削除
aws elbv2 delete-rule \
  --rule-arn <RULE_ARN> \
  --region ap-northeast-1

# Step 3: ターゲットグループ削除
aws elbv2 delete-target-group \
  --target-group-arn <TARGET_GROUP_ARN> \
  --region ap-northeast-1

# Step 4: Terraform Destroy（必要に応じて）
terraform destroy -target=module.task_service_ecs
terraform destroy -target=module.task_service_db
```

---

## 📊 コスト見積もり

| リソース | スペック | 月額コスト（概算） |
|---------|---------|------------------|
| RDS PostgreSQL | db.t3.micro, 20GB, Multi-AZ | $40 |
| ECS Fargate | 2タスク常時、512 CPU/1024 MB | $30 |
| ALB | 既存ALB使用（追加コストなし） | $0 |
| Secrets Manager | 2シークレット | $0.80 |
| CloudWatch | ログ、メトリクス、アラーム | $5 |
| **合計** | | **$75.80/月** |

※ データ転送料、Auto Scaling時の追加タスクは含まず

---

## 📝 次のステップ

- [ ] Docker イメージビルド（services/task-service/）
- [ ] ECRへプッシュ
- [ ] GitHub Actions CI/CDトリガー
- [ ] Blue/Greenデプロイメント実行
- [ ] 統合テスト実行
- [ ] データマイグレーション開始（AWS DMS）

---

## 🔗 関連ドキュメント

- [Phase 2: データベースマイグレーション計画](./2025-11-27_PHASE2_DATABASE_MIGRATION_PLAN.md)
- [Phase 2: Task Service実装レポート](./2025-11-27_PHASE2_TASK_SERVICE_IMPLEMENTATION.md)
- [マイクロサービス移行計画](../../definitions/microservices-migration-plan.md)
- [データベーススキーマ](../../definitions/database-schema.md)

---

**注意事項**:
- 本番環境での実行は必ずメンテナンス時間帯に行ってください
- バックアップを事前に取得してください
- ロールバック手順を事前に確認してください
- Secrets Managerのシークレット値は厳重に管理してください
