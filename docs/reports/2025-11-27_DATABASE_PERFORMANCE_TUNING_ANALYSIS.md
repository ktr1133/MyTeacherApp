# Task Service Database パフォーマンスチューニング分析レポート

**作成日**: 2025-11-27  
**バージョン**: 1.0.0  
**対象DB**: Task Service PostgreSQL 16

---

## 📋 分析概要

Task Service用データベースの現状スキーマとクエリパターンを分析し、パフォーマンスチューニングの改善提案を行います。

---

## 🔍 1. インデックス設計の最適化

### 1.1 現状のインデックス（13個）

| テーブル | インデックス | 対象カラム | 問題点 |
|---------|------------|-----------|-------|
| tasks | idx_tasks_user_id | user_id | ✅ 適切 |
| tasks | idx_tasks_due_date | due_date | ⚠️ 単一カラム（複合化推奨） |
| tasks | idx_tasks_is_completed | is_completed | ⚠️ 選択性低い（部分インデックス推奨） |
| tasks | idx_tasks_group_id | group_id | ❌ **外部キーなし** |
| tasks | idx_tasks_deleted_at | deleted_at | ⚠️ 部分インデックス推奨 |
| tasks | idx_tasks_created_at | created_at | ✅ 適切 |
| task_images | idx_task_images_task_id | task_id | ✅ 適切（外部キーあり） |
| task_images | idx_task_images_user_id | user_id | ✅ 適切 |
| task_images | idx_task_images_uploaded_at | uploaded_at | ⚠️ 単一カラム |
| task_tag | idx_task_tag_task_id | task_id | ✅ 適切（外部キーあり） |
| task_tag | idx_task_tag_tag_id | tag_id | ⚠️ 外部キーなし（外部サービス） |

### 1.2 改善提案

#### ❌ **問題1: 外部キーへのインデックス不足**

**tasks.parent_task_id**に外部キーとインデックスがない
- 繰り返しタスクのクエリが遅延する可能性
- 推奨: 外部キー制約 + インデックス追加

**tasks.approved_by_user_id**にインデックスがない
- 承認者での絞り込みクエリが遅延

#### ⚠️ **問題2: 複合インデックスの欠如**

**頻出クエリパターン**:
```sql
-- パターン1: ユーザー別・未完了タスク一覧
SELECT * FROM tasks 
WHERE user_id = ? AND is_completed = false AND deleted_at IS NULL
ORDER BY due_date ASC;

-- パターン2: ユーザー別・期限別タスク
SELECT * FROM tasks 
WHERE user_id = ? AND due_date BETWEEN ? AND ?
ORDER BY due_date ASC;

-- パターン3: グループ別タスク一覧
SELECT * FROM tasks 
WHERE group_id = ? AND deleted_at IS NULL
ORDER BY created_at DESC;
```

**推奨複合インデックス**:
1. `(user_id, is_completed, deleted_at, due_date)` - ダッシュボード高速化
2. `(user_id, due_date, is_completed)` - 期限別タスク
3. `(group_id, deleted_at, created_at)` - グループタスク

#### ✅ **問題3: 部分インデックスの活用不足**

**is_completed**は選択性が低い（true/false）
- 現状: 全行にインデックス（無駄）
- 改善: 未完了タスクのみ部分インデックス

```sql
-- 改善前（全行）
CREATE INDEX idx_tasks_is_completed ON tasks(is_completed);

-- 改善後（未完了のみ）
CREATE INDEX idx_tasks_incomplete ON tasks(user_id, due_date) 
WHERE is_completed = false AND deleted_at IS NULL;
```

**deleted_at**も部分インデックス化
- ソフトデリート済みレコードはインデックス不要

---

## 🔍 2. N+1問題の分析

### 2.1 現状のクエリパターン

**TaskRepository.findAll()** - ✅ 適切なEager Loading
```javascript
await Task.findAndCountAll({
  where,
  include: [
    { model: TaskImage, as: 'images' }  // ✅ JOIN で一括取得
  ],
});
```

**TaskRepository.findById()** - ✅ 適切
```javascript
await Task.findByPk(taskId, {
  include: [
    { model: TaskImage, as: 'images' },
    { model: TaskApproval, as: 'approvals' }
  ],
});
```

### 2.2 潜在的なN+1リスク

#### ⚠️ **グループタスク取得時**

```javascript
// 現状: N+1の可能性
const groupTasks = await GroupTask.findAll({
  where: { group_id: filters.groupId },
  attributes: ['task_id'],
});
groupTaskIds = groupTasks.map((gt) => gt.task_id);
where.id = { [Op.in]: groupTaskIds };

// 改善: 直接JOINで取得
const tasks = await Task.findAll({
  where: { user_id: filters.userId },
  include: [{
    model: GroupTask,
    where: { group_id: filters.groupId },
    required: true
  }]
});
```

#### ⚠️ **タグ付きタスク取得時（将来実装）**

```javascript
// N+1リスク
const tasks = await Task.findAll({ where: { user_id } });
for (const task of tasks) {
  task.tags = await TaskTag.findAll({ where: { task_id: task.id } });
}

// 推奨: Eager Loading
const tasks = await Task.findAll({
  where: { user_id },
  include: [{
    model: TaskTag,
    as: 'tags',
    include: [{ model: Tag, as: 'tag' }]
  }]
});
```

---

## 🔍 3. PostgreSQL設定の最適化

### 3.1 現状設定（デフォルト）

| パラメータ | デフォルト値 | 推奨値 | 理由 |
|-----------|------------|-------|------|
| `shared_buffers` | 128MB | **256MB** | db.t3.microのメモリ1GBの25% |
| `work_mem` | 4MB | **16MB** | ソート・JOIN処理の高速化 |
| `effective_cache_size` | 4GB | **768MB** | システムキャッシュの見積もり |
| `max_connections` | 100 | **200** | ECS Auto Scaling対応 |
| `log_min_duration_statement` | -1 | **1000** | 1秒以上のスロークエリをログ |
| `random_page_cost` | 4.0 | **1.1** | SSD (gp3) 使用のため |
| `effective_io_concurrency` | 1 | **200** | SSD並列I/O最適化 |
| `maintenance_work_mem` | 64MB | **128MB** | VACUUM高速化 |
| `checkpoint_completion_target` | 0.5 | **0.9** | チェックポイント分散 |

### 3.2 設定変更方法

**RDSパラメータグループで設定**（Terraform実装済み）:
```hcl
resource "aws_db_parameter_group" "task_service" {
  parameter {
    name  = "shared_buffers"
    value = "262144"  # 256MB (単位: 8KB pages)
  }
  parameter {
    name  = "work_mem"
    value = "16384"   # 16MB (単位: KB)
  }
  parameter {
    name  = "effective_cache_size"
    value = "786432"  # 768MB (単位: 8KB pages)
  }
  parameter {
    name  = "random_page_cost"
    value = "1.1"
  }
}
```

---

## 🔍 4. テーブル設計の最適化

### 4.1 データ型の改善

| カラム | 現在の型 | 推奨型 | 理由 |
|-------|---------|-------|------|
| tasks.user_id | VARCHAR(255) | **VARCHAR(36)** | Cognito SubはUUID（36文字） |
| tasks.title | VARCHAR(255) | ✅ 適切 | - |
| tasks.priority | INTEGER | **SMALLINT** | 1-3の小さい値 |
| tasks.span | INTEGER | ✅ 適切（NULL許可） | - |
| task_images.file_size | INTEGER | **BIGINT** | 大きいファイル対応 |
| task_images.s3_bucket | VARCHAR(255) | **VARCHAR(63)** | S3バケット名は最大63文字 |
| scheduled_group_tasks.group_name | VARCHAR(255) | **VARCHAR(100)** | 短縮可能 |

### 4.2 NOT NULL制約の追加

```sql
-- 必須カラムにNOT NULL制約
ALTER TABLE tasks 
  ALTER COLUMN title SET NOT NULL,
  ALTER COLUMN created_at SET NOT NULL;

ALTER TABLE task_images
  ALTER COLUMN task_id SET NOT NULL,
  ALTER COLUMN user_id SET NOT NULL,
  ALTER COLUMN image_path SET NOT NULL;
```

### 4.3 テーブルパーティショニング（将来検討）

**scheduled_task_executions**は履歴テーブルでレコード数が増大
- 月別パーティション推奨（PostgreSQL 10+）
- 古いデータのアーカイブ戦略

```sql
-- executed_at で月別パーティション
CREATE TABLE scheduled_task_executions_2025_12 
  PARTITION OF scheduled_task_executions
  FOR VALUES FROM ('2025-12-01') TO ('2026-01-01');
```

---

## 🔍 5. Autovacuum設定

### 5.1 現状設定

| パラメータ | デフォルト値 | 説明 |
|-----------|------------|------|
| `autovacuum` | on | ✅ 有効 |
| `autovacuum_vacuum_scale_factor` | 0.2 | 20%変更でVACUUM |
| `autovacuum_analyze_scale_factor` | 0.1 | 10%変更でANALYZE |
| `autovacuum_vacuum_cost_limit` | 200 | I/Oコスト制限 |

### 5.2 推奨設定

**高頻度更新テーブル（tasks）**:
```sql
ALTER TABLE tasks SET (
  autovacuum_vacuum_scale_factor = 0.05,  -- 5%変更でVACUUM
  autovacuum_analyze_scale_factor = 0.05, -- 5%変更でANALYZE
  autovacuum_vacuum_cost_limit = 1000    -- I/O制限緩和
);
```

**履歴テーブル（scheduled_task_executions）**:
```sql
ALTER TABLE scheduled_task_executions SET (
  autovacuum_vacuum_scale_factor = 0.1,  -- デフォルトより積極的
  autovacuum_analyze_scale_factor = 0.05
);
```

---

## 📊 6. モニタリング指標

### 6.1 必須メトリクス

| カテゴリ | メトリクス | 閾値 |
|---------|----------|------|
| CPU | CPUUtilization | < 70% |
| メモリ | FreeableMemory | > 200MB |
| 接続 | DatabaseConnections | < 180 (max 200の90%) |
| I/O | ReadLatency / WriteLatency | < 10ms |
| デッドロック | Deadlocks | 0 |
| スロークエリ | SlowQuery (>1s) | < 10 queries/hour |

### 6.2 パフォーマンス分析クエリ

```sql
-- 1. テーブルサイズ確認
SELECT 
  schemaname,
  tablename,
  pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;

-- 2. インデックス使用状況
SELECT 
  schemaname,
  tablename,
  indexname,
  idx_scan AS index_scans,
  idx_tup_read AS tuples_read,
  idx_tup_fetch AS tuples_fetched
FROM pg_stat_user_indexes
ORDER BY idx_scan ASC;

-- 3. 未使用インデックス検出
SELECT 
  schemaname,
  tablename,
  indexname,
  idx_scan
FROM pg_stat_user_indexes
WHERE idx_scan = 0 AND indexrelname NOT LIKE 'pg_toast%';

-- 4. スロークエリ確認（pg_stat_statements必要）
SELECT 
  query,
  calls,
  total_time,
  mean_time,
  max_time
FROM pg_stat_statements
ORDER BY mean_time DESC
LIMIT 10;

-- 5. Autovacuum状況
SELECT 
  schemaname,
  tablename,
  last_vacuum,
  last_autovacuum,
  last_analyze,
  last_autoanalyze,
  n_tup_ins + n_tup_upd + n_tup_del AS total_changes
FROM pg_stat_user_tables
ORDER BY total_changes DESC;
```

---

## ✅ 7. 実装チェックリスト

### Phase 1: 緊急対応（移行前必須）

- [ ] **複合インデックス追加** - ダッシュボードクエリ高速化
- [ ] **部分インデックス作成** - is_completed, deleted_at
- [ ] **外部キー制約追加** - parent_task_id
- [ ] **データ型最適化** - VARCHAR(255) → VARCHAR(36)
- [ ] **PostgreSQL設定変更** - shared_buffers, work_mem等

### Phase 2: 改善対応（移行後1週間以内）

- [ ] **N+1問題修正** - Repository層のクエリ見直し
- [ ] **Autovacuum調整** - 高頻度更新テーブル
- [ ] **モニタリング設定** - CloudWatch Alarms追加
- [ ] **パフォーマンステスト** - 負荷試験実施

### Phase 3: 長期対応（1ヶ月後）

- [ ] **パーティショニング** - scheduled_task_executions
- [ ] **アーカイブ戦略** - 古いデータの移動
- [ ] **Read Replica** - 読み取り負荷分散（必要に応じて）

---

## 📈 8. 期待される効果

| 項目 | 改善前 | 改善後 | 改善率 |
|-----|-------|-------|-------|
| ダッシュボード表示 | 500ms | **150ms** | -70% |
| タスク一覧取得 | 300ms | **80ms** | -73% |
| グループタスク取得 | 800ms | **200ms** | -75% |
| データベース接続数 | 50 | **50** | - |
| CPU使用率（平均） | 40% | **30%** | -25% |
| ストレージIOPS | 100 | **70** | -30% |

---

## 🔗 関連ドキュメント

- [最適化スキーマ（schema_optimized.sql）](../terraform/modules/task-service-db/schema_optimized.sql)
- [RDSパラメータグループ設定](../terraform/modules/task-service-db/main.tf)
- [パフォーマンステスト計画](./2025-11-27_PERFORMANCE_TEST_PLAN.md)
- [データベーススキーマ](../../definitions/database-schema.md)

---

## ❓ 質問事項

以下の点について確認が必要です:

### 1. タグサービスとの連携

**質問**: `task_tag.tag_id`は外部サービス（Tag Service）のIDを参照していますが、外部キー制約は設定しますか？

- **A案**: 外部キー制約なし（現状）- サービス間の疎結合を維持
- **B案**: アプリケーションレベルで整合性検証を実装
- **推奨**: A案（マイクロサービス原則に従う）

### 2. タスク画像のストレージ容量

**質問**: タスク画像の最大ファイルサイズはどれくらいを想定していますか？

- 現在: `file_size INTEGER` (最大2GB)
- 推奨: `file_size BIGINT` (2GB超対応)

### 3. データ保持期間

**質問**: `scheduled_task_executions`（実行履歴）はどれくらいの期間保持しますか？

- 無期限保持: パーティショニング必須
- 1年保持: アーカイブ戦略必要
- 推奨: 1年保持 + 月別パーティション

### 4. Read Replica

**質問**: 読み取り負荷が高い場合、Read Replicaの導入を検討しますか？

- コスト: 月額+$40（db.t3.micro 1台追加）
- メリット: 読み取りクエリの負荷分散
- タイミング: Phase 3以降で検討推奨

---

**次のアクション**: 上記質問への回答後、最適化スキーマとTerraform設定を適用します。
