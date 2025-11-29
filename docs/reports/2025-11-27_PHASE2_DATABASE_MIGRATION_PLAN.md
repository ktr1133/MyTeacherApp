# Phase 2: Task Service データベースマイグレーション計画書

**作成日**: 2025-11-27  
**バージョン**: 1.0.0  
**対象**: Phase 2 - タスクサービス分離  
**優先度**: 🔴 最高

---

## 📋 概要

マイクロサービス移行計画Phase 2において、タスク関連機能を独立したマイクロサービス（Task Service）として分離するため、以下のテーブル群を既存のPostgreSQLデータベースから新しいTask Service専用データベースへ移行します。

## 🎯 移行目標

| 項目 | 目標値 |
|-----|--------|
| **ダウンタイム** | ゼロダウンタイム移行 |
| **データ損失** | ゼロ（検証済みバックアップ必須） |
| **切り戻し時間** | 5分以内 |
| **データ整合性** | 100%保証 |

---

## 📊 移行対象テーブル

### 1. 必須移行テーブル（6テーブル）

| テーブル名 | レコード数（推定） | 依存関係 | 優先度 |
|-----------|----------------|---------|--------|
| **tasks** | 1,000+ | users(FK), task_proposals(FK), tags(M2M) | 🔴 最高 |
| **task_images** | 500+ | tasks(FK) | 🔴 最高 |
| **task_tag** | 1,500+ | tasks(FK), tags(FK) | 🟡 高 |
| **scheduled_group_tasks** | 50+ | groups(FK), users(FK) | 🟡 高 |
| **scheduled_task_executions** | 200+ | scheduled_group_tasks(FK), tasks(FK) | 🟡 高 |
| **scheduled_task_tags** | 100+ | scheduled_group_tasks(FK) | 🟢 中 |

### 2. 参照のみ（移行しない）

| テーブル名 | 理由 | 対応方法 |
|-----------|------|---------|
| **users** | 認証サービス管理 | REST API経由で参照 |
| **groups** | グループサービス管理 | REST API経由で参照 |
| **tags** | Tag Service移行（Phase 3） | 現時点では既存DBから参照 |
| **task_proposals** | AI Serviceで管理 | REST API経由で参照 |

---

## 🏗️ アーキテクチャ構成

### 移行前（現在）

```
┌─────────────────────────────────────────┐
│     Laravel Monolith                    │
│                                         │
│  ┌──────────────────────────────────┐  │
│  │  PostgreSQL (Single Database)    │  │
│  │                                  │  │
│  │  - users                         │  │
│  │  - groups                        │  │
│  │  - tasks ←──────────────────┐    │  │
│  │  - task_images               │    │  │
│  │  - task_tag                  │    │  │
│  │  - scheduled_group_tasks     │    │  │
│  │  - scheduled_task_executions │    │  │
│  │  - tags                       │    │  │
│  │  - token_balances             │    │  │
│  │  - teacher_avatars            │    │  │
│  │  ... (他30テーブル)           │    │  │
│  └──────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

### 移行後（目標）

```
┌──────────────────────────┐     ┌──────────────────────────┐
│  Task Service            │     │  Laravel Monolith        │
│  (ECS/Fargate)          │     │  (既存アプリ)             │
│                          │     │                          │
│  ┌────────────────────┐  │     │  ┌────────────────────┐  │
│  │ Task Service DB    │  │     │  │ MyTeacher DB       │  │
│  │ (RDS PostgreSQL)   │  │     │  │ (RDS PostgreSQL)   │  │
│  │                    │  │     │  │                    │  │
│  │ - tasks            │  │     │  │ - users            │  │
│  │ - task_images      │  │     │  │ - groups           │  │
│  │ - task_tag         │  │     │  │ - tags             │  │
│  │ - scheduled_*      │  │     │  │ - token_balances   │  │
│  └────────────────────┘  │     │  │ - teacher_avatars  │  │
│                          │     │  │ ... (他25テーブル)  │  │
│  REST API (8 endpoints) │     │  └────────────────────┘  │
└──────────┬───────────────┘     └───────────┬──────────────┘
           │                                 │
           │ ◄─── Cognito JWT 認証 ─────────►│
           │                                 │
           └─────── API Gateway ─────────────┘
```

---

## 📝 移行ステップ（詳細）

### Step 0: 事前準備（1日前）

#### 0.1 バックアップ作成

```bash
# 全データベースのバックアップ（pg_dump）
cd /home/ktr/mtdev/infrastructure/scripts

# 本番環境データベースをバックアップ
./backup-production-db.sh

# 確認
aws s3 ls s3://myteacher-backups/database/ --recursive | grep "2025-11-27"
```

**バックアップ対象**:
- 全テーブルデータ（INSERT文付き）
- スキーマ定義（CREATE TABLE文）
- インデックス・外部キー制約
- シーケンス（AUTO_INCREMENT値）

#### 0.2 新RDSインスタンス作成

```bash
# Terraform で Task Service用RDSを作成
cd /home/ktr/mtdev/infrastructure/terraform

terraform plan -target=module.task_service_db
terraform apply -target=module.task_service_db
```

**RDS構成**:
- Engine: PostgreSQL 16
- Instance Class: `db.t3.medium`（初期）
- Storage: 100GB gp3
- Multi-AZ: Yes（本番のみ）
- Backup Retention: 7日
- Encryption: 有効（AWS KMS）

#### 0.3 スキーマ作成

```bash
# Laravel migration を Task Service DBに適用
cd /home/ktr/mtdev/laravel

# 環境変数を Task Service DB に向ける
export DB_HOST=task-service-db.xxxxx.ap-northeast-1.rds.amazonaws.com
export DB_DATABASE=task_service_production
export DB_USERNAME=task_service_user
export DB_PASSWORD=<TASK_SERVICE_DB_PASSWORD>

# 対象テーブルのmigration を実行
php artisan migrate --path=database/migrations/2025_10_27_135127_tasks.php
php artisan migrate --path=database/migrations/2025_10_27_150000_create_task_images_table.php
php artisan migrate --path=database/migrations/2025_10_27_135339_task_tag.php
php artisan migrate --path=database/migrations/2025_11_07_000001_create_scheduled_group_tasks_table.php
php artisan migrate --path=database/migrations/2025_11_07_000003_create_scheduled_task_executions_table.php
php artisan migrate --path=database/migrations/2025_11_07_000002_create_scheduled_task_tags_table.php
```

---

### Step 1: データ移行（ゼロダウンタイム）

#### 1.1 初期データコピー（深夜メンテナンス時間帯）

```bash
# 移行スクリプト実行
cd /home/ktr/mtdev/infrastructure/scripts

# データコピー（外部キー制約を一時無効化）
./migrate-task-data.sh --initial-copy

# 内部処理:
# 1. 外部キー制約を無効化（SET session_replication_role = 'replica';）
# 2. tasks, task_images, task_tag, scheduled_* をコピー
# 3. AUTO_INCREMENT値を同期
# 4. 外部キー制約を再有効化
```

**推定時間**: 5-10分（レコード数に依存）

#### 1.2 差分レプリケーション設定（AWS DMS利用）

**オプション1: AWS Database Migration Service（推奨）**

```bash
# DMS レプリケーションインスタンス作成
aws dms create-replication-instance \
  --replication-instance-identifier myteacher-task-replication \
  --replication-instance-class dms.t3.medium \
  --allocated-storage 50

# ソースエンドポイント（既存DB）
aws dms create-endpoint \
  --endpoint-identifier myteacher-source \
  --endpoint-type source \
  --engine-name postgres \
  --server-name myteacher-db.xxxxx.rds.amazonaws.com \
  --port 5432 \
  --database-name myteacher_production

# ターゲットエンドポイント（Task Service DB）
aws dms create-endpoint \
  --endpoint-identifier task-service-target \
  --endpoint-type target \
  --engine-name postgres \
  --server-name task-service-db.xxxxx.rds.amazonaws.com \
  --port 5432 \
  --database-name task_service_production

# レプリケーションタスク作成（CDC: Change Data Capture）
aws dms create-replication-task \
  --replication-task-identifier myteacher-task-migration \
  --source-endpoint-arn arn:aws:dms:... \
  --target-endpoint-arn arn:aws:dms:... \
  --replication-instance-arn arn:aws:dms:... \
  --migration-type cdc \
  --table-mappings file://task-table-mappings.json
```

**table-mappings.json**:
```json
{
  "rules": [
    {
      "rule-type": "selection",
      "rule-id": "1",
      "rule-name": "tasks-table",
      "object-locator": {
        "schema-name": "public",
        "table-name": "tasks"
      },
      "rule-action": "include"
    },
    {
      "rule-type": "selection",
      "rule-id": "2",
      "rule-name": "task-images-table",
      "object-locator": {
        "schema-name": "public",
        "table-name": "task_images"
      },
      "rule-action": "include"
    },
    {
      "rule-type": "selection",
      "rule-id": "3",
      "rule-name": "task-tag-table",
      "object-locator": {
        "schema-name": "public",
        "table-name": "task_tag"
      },
      "rule-action": "include"
    },
    {
      "rule-type": "selection",
      "rule-id": "4",
      "rule-name": "scheduled-group-tasks-table",
      "object-locator": {
        "schema-name": "public",
        "table-name": "scheduled_group_tasks"
      },
      "rule-action": "include"
    },
    {
      "rule-type": "selection",
      "rule-id": "5",
      "rule-name": "scheduled-task-executions-table",
      "object-locator": {
        "schema-name": "public",
        "table-name": "scheduled_task_executions"
      },
      "rule-action": "include"
    },
    {
      "rule-type": "selection",
      "rule-id": "6",
      "rule-name": "scheduled-task-tags-table",
      "object-locator": {
        "schema-name": "public",
        "table-name": "scheduled_task_tags"
      },
      "rule-action": "include"
    }
  ]
}
```

**オプション2: PostgreSQL Logical Replication（高度）**

```sql
-- ソースDB（既存MyTeacher DB）
ALTER TABLE tasks REPLICA IDENTITY FULL;
ALTER TABLE task_images REPLICA IDENTITY FULL;
ALTER TABLE task_tag REPLICA IDENTITY FULL;
ALTER TABLE scheduled_group_tasks REPLICA IDENTITY FULL;
ALTER TABLE scheduled_task_executions REPLICA IDENTITY FULL;
ALTER TABLE scheduled_task_tags REPLICA IDENTITY FULL;

-- Publication 作成
CREATE PUBLICATION task_service_pub FOR TABLE 
  tasks, task_images, task_tag, 
  scheduled_group_tasks, scheduled_task_executions, scheduled_task_tags;

-- ターゲットDB（Task Service DB）
CREATE SUBSCRIPTION task_service_sub 
  CONNECTION 'host=myteacher-db.xxxxx.rds.amazonaws.com dbname=myteacher_production user=replication_user password=xxx' 
  PUBLICATION task_service_pub;
```

---

### Step 2: アプリケーション切り替え（Blue/Green Deployment）

#### 2.1 Task Serviceデプロイ（Green環境）

```bash
cd /home/ktr/mtdev/services/task-service

# Docker イメージビルド
docker build -t myteacher-task-service:latest .

# ECR プッシュ
aws ecr get-login-password --region ap-northeast-1 | docker login --username AWS --password-stdin 123456789012.dkr.ecr.ap-northeast-1.amazonaws.com
docker tag myteacher-task-service:latest 123456789012.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-task-service:latest
docker push 123456789012.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-task-service:latest

# ECS サービス作成（初回デプロイ）
cd /home/ktr/mtdev/infrastructure/terraform
terraform apply -target=module.task_service_ecs
```

#### 2.2 カナリアデプロイ（段階的切り替え）

**フェーズ1: 5%のトラフィックをTask Serviceへ**

```bash
# API Gateway でウェイトを変更
aws apigatewayv2 update-route \
  --api-id xxxxx \
  --route-id xxxxx \
  --target "integrations/xxxxx,integrations/yyyyy" \
  --route-response-selection-expression '$default' \
  --authorization-type JWT

# ウェイト設定（Laravel Monolith: 95%, Task Service: 5%）
# CloudWatch Metricsで監視（エラー率、レスポンスタイム）
```

**監視ダッシュボード**:
- エラー率: <1%
- P50レスポンスタイム: <200ms
- P95レスポンスタイム: <500ms
- CPU使用率: <70%
- メモリ使用率: <80%

**フェーズ2: 25% → 50% → 100%**

各フェーズで30分間監視し、異常がなければ次のフェーズへ進む。

#### 2.3 完全切り替え（100%）

```bash
# API Gateway でTask Serviceのみにルーティング
# Laravel Monolithのタスクエンドポイントを無効化
```

---

### Step 3: データ整合性検証

#### 3.1 レコード数検証

```sql
-- ソースDB（Laravel Monolith DB）
SELECT 'tasks' AS table_name, COUNT(*) AS count FROM tasks
UNION ALL
SELECT 'task_images', COUNT(*) FROM task_images
UNION ALL
SELECT 'task_tag', COUNT(*) FROM task_tag
UNION ALL
SELECT 'scheduled_group_tasks', COUNT(*) FROM scheduled_group_tasks
UNION ALL
SELECT 'scheduled_task_executions', COUNT(*) FROM scheduled_task_executions
UNION ALL
SELECT 'scheduled_task_tags', COUNT(*) FROM scheduled_task_tags;

-- ターゲットDB（Task Service DB）
-- 同じクエリを実行し、カウントを比較
```

#### 3.2 チェックサム検証

```bash
# MD5ハッシュでデータ整合性を確認
cd /home/ktr/mtdev/infrastructure/scripts
./verify-data-integrity.sh
```

---

### Step 4: レプリケーション停止

#### 4.1 DMS タスク停止

```bash
# レプリケーション停止
aws dms stop-replication-task --replication-task-arn arn:aws:dms:...

# 確認
aws dms describe-replication-tasks --filters Name=replication-task-arn,Values=arn:aws:dms:...
```

#### 4.2 Laravel Monolithのタスクテーブルを読み取り専用化（オプション）

```sql
-- 既存DBのタスク関連テーブルを読み取り専用に
REVOKE INSERT, UPDATE, DELETE ON tasks FROM myteacher_app_user;
REVOKE INSERT, UPDATE, DELETE ON task_images FROM myteacher_app_user;
REVOKE INSERT, UPDATE, DELETE ON task_tag FROM myteacher_app_user;
REVOKE INSERT, UPDATE, DELETE ON scheduled_group_tasks FROM myteacher_app_user;
REVOKE INSERT, UPDATE, DELETE ON scheduled_task_executions FROM myteacher_app_user;
REVOKE INSERT, UPDATE, DELETE ON scheduled_task_tags FROM myteacher_app_user;

-- 読み取り権限のみ保持（Phase 2完了後、Phase 5で完全削除）
GRANT SELECT ON tasks, task_images, task_tag, 
  scheduled_group_tasks, scheduled_task_executions, scheduled_task_tags 
  TO myteacher_app_user;
```

---

## 🔄 ロールバック計画

### シナリオ1: データ移行失敗（Step 1でエラー）

**対応**:
1. DMS/レプリケーション停止
2. Task Service DBを削除
3. 再度Step 0からやり直し

**影響**: なし（既存システムは継続稼働）

### シナリオ2: Task Serviceデプロイ失敗（Step 2でエラー）

**対応**:
1. API Gatewayのルーティングを100% Laravel Monolithへ戻す
2. Task Service ECSタスクを停止
3. 原因調査・修正後に再デプロイ

**影響**: なし（5分以内に切り戻し完了）

**切り戻しコマンド**:
```bash
# API Gatewayでルーティング変更
aws apigatewayv2 update-route --api-id xxxxx --route-id xxxxx --target "integrations/laravel-monolith-integration"

# ECS タスク停止
aws ecs update-service --cluster myteacher-cluster --service task-service --desired-count 0
```

### シナリオ3: データ不整合検出（Step 3でエラー）

**対応**:
1. 即座にAPI Gatewayを100% Laravel Monolithへ戻す
2. Task Service DBをバックアップから復元
3. Step 1からやり直し

**影響**: 最大30分のデータ不整合（DMS/レプリケーション遅延）

---

## 📊 外部キー依存関係の解決

### 問題: Task Serviceが他テーブルを参照する

| Task Service内テーブル | 参照先テーブル | 参照先サービス | 対応方法 |
|---------------------|--------------|--------------|---------|
| tasks.user_id | users.id | Auth Service | REST API経由で検証 |
| tasks.assigned_by_user_id | users.id | Auth Service | REST API経由で検証 |
| tasks.approved_by_user_id | users.id | Auth Service | REST API経由で検証 |
| tasks.source_proposal_id | task_proposals.id | AI Service | REST API経由で検証（Phase 3） |
| task_tag.tag_id | tags.id | Tag Service | Phase 3までは既存DBから参照 |
| scheduled_group_tasks.group_id | groups.id | Group Service | REST API経由で検証 |
| scheduled_group_tasks.created_by | users.id | Auth Service | REST API経由で検証 |
| scheduled_group_tasks.assigned_user_id | users.id | Auth Service | REST API経由で検証 |

### 解決策1: データベースレベルの外部キー削除

```sql
-- Task Service DB で外部キー制約を削除
ALTER TABLE tasks DROP CONSTRAINT tasks_user_id_foreign;
ALTER TABLE tasks DROP CONSTRAINT tasks_assigned_by_user_id_foreign;
ALTER TABLE tasks DROP CONSTRAINT tasks_approved_by_user_id_foreign;
ALTER TABLE tasks DROP CONSTRAINT tasks_source_proposal_id_foreign;

ALTER TABLE task_tag DROP CONSTRAINT task_tag_tag_id_foreign;

ALTER TABLE scheduled_group_tasks DROP CONSTRAINT scheduled_group_tasks_group_id_foreign;
ALTER TABLE scheduled_group_tasks DROP CONSTRAINT scheduled_group_tasks_created_by_foreign;
ALTER TABLE scheduled_group_tasks DROP CONSTRAINT scheduled_group_tasks_assigned_user_id_foreign;
```

### 解決策2: アプリケーションレベルでバリデーション

```javascript
// services/task-service/src/services/task.service.js

class TaskService {
  async createTask(userId, taskData) {
    // 1. ユーザーIDの存在確認（Auth Service経由）
    const userExists = await this.authServiceClient.verifyUser(userId);
    if (!userExists) {
      throw new Error('User not found');
    }

    // 2. assigned_by_user_idの存在確認
    if (taskData.assigned_by_user_id) {
      const assignerExists = await this.authServiceClient.verifyUser(taskData.assigned_by_user_id);
      if (!assignerExists) {
        throw new Error('Assigner user not found');
      }
    }

    // 3. group_idの存在確認（Group Service経由）
    if (taskData.group_id) {
      const groupExists = await this.groupServiceClient.verifyGroup(taskData.group_id);
      if (!groupExists) {
        throw new Error('Group not found');
      }
    }

    // 4. タスク作成
    const task = await this.taskRepository.create({
      user_id: userId,
      ...taskData
    });

    return task;
  }
}
```

---

## 🧪 テスト計画

### 単体テスト（Jest）

```bash
cd /home/ktr/mtdev/services/task-service
npm test

# 実行内容:
# - Controller: リクエスト/レスポンス検証
# - Service: ビジネスロジック検証
# - Repository: データベース操作検証（モック）
```

### 統合テスト（Postman/Newman）

```bash
# API エンドポイント統合テスト
newman run tests/integration/task-service-api.postman_collection.json \
  --environment tests/integration/staging.postman_environment.json
```

**テストケース**:
- GET /api/tasks（ページネーション、フィルター）
- POST /api/tasks（作成、バリデーション）
- PUT /api/tasks/:id（更新）
- DELETE /api/tasks/:id（削除）
- POST /api/tasks/:id/complete（完了）
- POST /api/tasks/:id/approve（承認）
- POST /api/tasks/:id/reject（却下）

### 負荷テスト（Locust）

```bash
cd /home/ktr/mtdev/infrastructure/load-testing
locust -f task_service_load_test.py --host=https://api.myteacher.com
```

**目標**:
- 同時ユーザー: 100
- RPS: 50
- P95レスポンスタイム: <500ms
- エラー率: <1%

---

## 📈 監視とアラート

### CloudWatch Metrics

| メトリクス | 閾値 | アラート |
|----------|------|---------|
| ECS CPU使用率 | >80% | Warning |
| ECS メモリ使用率 | >85% | Warning |
| RDS CPU使用率 | >70% | Warning |
| RDS 接続数 | >80（最大100） | Critical |
| API エラー率 | >1% | Critical |
| API レスポンスタイム（P95） | >500ms | Warning |

### CloudWatch Logs Insights クエリ

```sql
-- エラーログ集計
fields @timestamp, @message
| filter @message like /ERROR/
| stats count() by bin(5m)

-- レスポンスタイム分析
fields @timestamp, responseTime
| filter @type = "api_request"
| stats avg(responseTime), max(responseTime), pct(responseTime, 95) by bin(5m)
```

---

## 📅 スケジュール

| フェーズ | 期間 | タスク | 担当 |
|---------|------|-------|------|
| **準備** | 11/28-11/29 | RDS作成、スキーマ作成、テスト | DevOps |
| **初期コピー** | 11/30 深夜 | データ初期コピー（DMS/レプリケーション開始） | DevOps |
| **レプリケーション** | 12/1-12/14 | 差分同期（並行運用期間中） | 自動 |
| **Canaryデプロイ** | 12/15 | 5% → 25% → 50% → 100% | DevOps |
| **検証** | 12/16 | データ整合性検証、負荷テスト | QA |
| **レプリケーション停止** | 12/17 | DMS停止、Laravel Monolith読み取り専用化 | DevOps |

---

## ✅ 成功基準

- [ ] 全テーブルのレコード数が一致
- [ ] データチェックサム検証が成功
- [ ] Task Service APIが正常稼働（8エンドポイント）
- [ ] 負荷テスト合格（RPS: 50, P95<500ms）
- [ ] ゼロダウンタイム達成
- [ ] ロールバック手順が検証済み

---

## 📚 関連ドキュメント

- [マイクロサービス移行計画書](../../definitions/microservices-migration-plan.md)
- [Phase 2: Task Service実装完了レポート](./2025-11-27_PHASE2_TASK_SERVICE_IMPLEMENTATION.md)
- [データベーススキーマ定義](../../definitions/database-schema.md)
- [Phase 1: Cognito認証統合完了レポート](./PHASE1_COMPLETION_REPORT.md)

---

**次のステップ**:
1. ✅ このマイグレーション計画をレビュー
2. ⏳ 単体・統合テストの実装（Task 2）
3. ⏳ CI/CDパイプラインの構築（Task 3）
4. ⏳ 本番環境準備（Task 4）

**承認者**: 未承認  
**実行予定日**: 2025年12月15日

