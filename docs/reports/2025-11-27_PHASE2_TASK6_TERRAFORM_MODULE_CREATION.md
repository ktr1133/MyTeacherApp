# Phase 2 Task 6完了: Task Service RDS Terraformモジュール作成

**作成日**: 2025-11-27  
**バージョン**: 1.0.0  
**ステータス**: ✅ **完了**

---

## 📋 実施サマリー

Phase 2の本番環境準備として、Task Service専用のRDS PostgreSQLデータベース用Terraformモジュールを作成しました。

| タスク | ステータス | 完了日 |
|-------|----------|--------|
| **Task 6: RDS Terraformモジュール作成** | ✅ 完了 | 2025-11-27 |

---

## ✅ 作成内容

### 1. Terraformモジュールファイル（4ファイル）

#### **`modules/task-service-db/main.tf`**

Task Service RDSの主要設定:

- **RDSインスタンス**: PostgreSQL 16、db.t3.micro、Multi-AZ対応
- **セキュリティグループ**: ECSタスクからのPostgreSQL接続許可（ポート5432）
- **DBパラメータグループ**: ロギング、接続数、タイムゾーン設定
- **バックアップ設定**: 自動バックアップ7日間保持、スナップショット設定
- **モニタリング**: 拡張モニタリング60秒間隔、Performance Insights有効
- **CloudWatch Alarms**: CPU高負荷、接続数高、ストレージ低アラーム

**主要リソース**:
- `aws_db_instance.task_service` - RDS PostgreSQL 16インスタンス
- `aws_db_subnet_group.task_service` - DBサブネットグループ
- `aws_security_group.task_service_db` - セキュリティグループ
- `aws_db_parameter_group.task_service` - パラメータグループ
- `aws_iam_role.rds_monitoring` - 拡張モニタリング用IAMロール
- `aws_cloudwatch_metric_alarm` x3 - CPU、接続数、ストレージアラーム

#### **`modules/task-service-db/variables.tf`**

入力変数定義（16変数）:

| カテゴリ | 変数名 | デフォルト値 | 説明 |
|---------|--------|-------------|------|
| 環境 | `environment` | - | development/staging/production |
| ネットワーク | `vpc_id` | - | VPC ID |
| ネットワーク | `private_subnet_ids` | - | プライベートサブネットIDリスト |
| ネットワーク | `task_service_security_group_id` | - | ECSタスクSG ID |
| DB | `db_name` | task_service | データベース名 |
| DB | `db_username` | task_service_user | DBユーザー名 |
| DB | `db_password` | - | DBパスワード（機密） |
| DB | `db_engine_version` | 16.1 | PostgreSQLバージョン |
| DB | `db_instance_class` | db.t3.micro | インスタンスクラス |
| DB | `db_allocated_storage` | 20 | ストレージ容量（GB） |
| バックアップ | `backup_retention_period` | 7 | バックアップ保持期間（日） |
| モニタリング | `alarm_sns_topic_arns` | [] | アラーム通知先SNS |

#### **`modules/task-service-db/outputs.tf`**

出力値定義（9出力）:

- `db_instance_id` - RDSインスタンスID
- `db_endpoint` - RDSエンドポイント（ホスト:ポート）
- `db_host` - RDSホスト名
- `db_port` - RDSポート番号
- `db_name` - データベース名
- `db_username` - DBユーザー名（機密）
- `db_security_group_id` - セキュリティグループID
- `db_resource_id` - CloudWatchメトリクス用リソースID
- `monitoring_role_arn` - 拡張モニタリングIAMロールARN

#### **`modules/task-service-db/schema.sql`**

データベーススキーマ定義（6テーブル）:

| テーブル名 | 説明 | カラム数 | インデックス |
|-----------|------|---------|------------|
| `tasks` | タスクメインテーブル | 19 | 6個 |
| `task_images` | タスク完了画像 | 9 | 3個 |
| `task_tag` | タスク・タグ関連付け | 3 | 2個 |
| `scheduled_group_tasks` | スケジュールグループ | 7 | 3個 |
| `scheduled_task_executions` | 実行履歴 | 9 | 4個 |
| `scheduled_task_tags` | グループ・タグ関連付け | 3 | 2個 |

**追加機能**:
- ✅ 自動タイムスタンプ更新トリガー（`updated_at`）
- ✅ 外部キー制約（CASCADE削除）
- ✅ インデックス最適化（user_id, due_date, is_completedなど）
- ✅ コメント付きカラム定義

---

### 2. 実行手順書作成

**ドキュメント**: `infrastructure/reports/2025-11-27_PHASE2_PRODUCTION_ENVIRONMENT_SETUP_GUIDE.md`

#### 含まれる内容

- **事前準備**: 環境変数設定、Terraformバージョン確認
- **10ステップの実行手順**:
  1. Terraform変数追加
  2. Terraform Plan実行（DB）
  3. Terraform Apply実行（DB）
  4. データベーススキーマ作成
  5. Secrets Manager設定
  6. ECRリポジトリ作成
  7. ECS/Fargate構築
  8. ALBターゲットグループ設定
  9. CloudWatchダッシュボード作成
  10. デプロイ前最終チェック

- **成功基準チェックリスト**: 6カテゴリ40項目
- **ロールバック手順**: 緊急時の切り戻し手順
- **コスト見積もり**: 月額$75.80（RDS $40 + ECS $30 + その他 $5.80）

---

## 📁 作成ファイル一覧

### Terraformモジュール（4ファイル）

1. `infrastructure/terraform/modules/task-service-db/main.tf` - メイン設定（312行）
2. `infrastructure/terraform/modules/task-service-db/variables.tf` - 変数定義（91行）
3. `infrastructure/terraform/modules/task-service-db/outputs.tf` - 出力値定義（61行）
4. `infrastructure/terraform/modules/task-service-db/schema.sql` - スキーマSQL（402行）

### ドキュメント（1ファイル）

5. `infrastructure/reports/2025-11-27_PHASE2_PRODUCTION_ENVIRONMENT_SETUP_GUIDE.md` - 実行手順書（361行）

**合計**: 5ファイル（1,227行）

---

## 🎯 次のステップ（Task 7以降）

### Task 7: Terraform Apply実行（RDS作成）

```bash
cd /home/ktr/mtdev/infrastructure/terraform

# 環境変数設定
export TF_VAR_task_service_db_password="<STRONG_PASSWORD>"

# Plan実行
terraform plan -target=module.task_service_db

# Apply実行
terraform apply -target=module.task_service_db
```

**所要時間**: 約10-15分

### Task 8: Secrets Manager設定

```bash
# DB認証情報保存
aws secretsmanager create-secret \
  --name task-service/db-password \
  --secret-string "$TF_VAR_task_service_db_password" \
  --region ap-northeast-1

# Cognito設定保存
aws secretsmanager create-secret \
  --name task-service/cognito-config \
  --secret-string "{\"user_pool_id\":\"...\",\"client_id\":\"...\"}" \
  --region ap-northeast-1
```

### Task 9: ECRリポジトリ作成

```bash
aws ecr create-repository \
  --repository-name task-service \
  --image-scanning-configuration scanOnPush=true \
  --encryption-configuration encryptionType=AES256 \
  --region ap-northeast-1
```

### Task 10: ECS/Fargate構築

```bash
# ECSモジュールのApply
terraform apply -target=module.task_service_ecs
```

### Task 11: ALBターゲットグループ設定

```bash
# ターゲットグループ作成
aws elbv2 create-target-group \
  --name task-service-tg \
  --protocol HTTP \
  --port 3001 \
  --vpc-id "$VPC_ID" \
  --target-type ip \
  --health-check-path /health \
  --region ap-northeast-1

# リスナールール追加（/api/tasks/* → Task Service）
aws elbv2 create-rule \
  --listener-arn "$ALB_LISTENER_ARN" \
  --priority 10 \
  --conditions Field=path-pattern,Values='/api/tasks/*' \
  --actions Type=forward,TargetGroupArn="$TARGET_GROUP_ARN" \
  --region ap-northeast-1
```

---

## 📊 Phase 2進捗状況

| タスク | ステータス | 進捗率 |
|-------|----------|-------|
| Task 1: データベースマイグレーション計画 | ✅ 完了 | 100% |
| Task 2: 単体・統合テスト実装 | ✅ 完了 | 100% |
| Task 3: CI/CDパイプライン構築 | ✅ 完了 | 100% |
| Task 4: テストファイルDocコメント追加 | ✅ 完了 | 100% |
| Task 5: 移行計画ドキュメント更新 | ✅ 完了 | 100% |
| Task 6: RDS Terraformモジュール作成 | ✅ 完了 | 100% |
| Task 7-11: 本番環境構築実行 | ⏳ 未着手 | 0% |
| **Phase 2 全体** | ⏳ **進行中** | **55%** |

---

## 🔗 関連ドキュメント

- [Phase 2: 本番環境準備実行手順書](./2025-11-27_PHASE2_PRODUCTION_ENVIRONMENT_SETUP_GUIDE.md)
- [Phase 2: データベースマイグレーション計画](./2025-11-27_PHASE2_DATABASE_MIGRATION_PLAN.md)
- [Phase 2: Tasks 1-3完了レポート](./2025-11-27_PHASE2_TASKS_COMPLETION_REPORT.md)
- [マイクロサービス移行計画](../../definitions/microservices-migration-plan.md)
- [データベーススキーマ](../../definitions/database-schema.md)

---

## ✅ 成功基準

### Terraformモジュール作成完了

- [x] main.tf作成（RDS、SG、パラメータグループ、IAM、アラーム）
- [x] variables.tf作成（16変数定義、validation付き）
- [x] outputs.tf作成（9出力値定義）
- [x] schema.sql作成（6テーブル、13インデックス、2トリガー）
- [x] 全ファイルにDocコメント追加

### ドキュメント作成完了

- [x] 実行手順書作成（10ステップ詳細）
- [x] 成功基準チェックリスト作成
- [x] ロールバック手順記載
- [x] コスト見積もり記載

---

**次回アクション**: Task 7（Terraform Apply実行）への着手  
**実行予定日**: 2025年11月28日  
**実行担当**: インフラチーム
