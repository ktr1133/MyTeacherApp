# Task Service パフォーマンステスト計画書

**作成日**: 2025-11-27  
**対象**: Task Service Database & API  
**目的**: 最適化後のパフォーマンス検証

---

## 📋 テスト概要

### テスト目的

1. **データベース最適化の効果検証**
   - 複合インデックス・部分インデックスの効果測定
   - PostgreSQLパラメータチューニングの効果検証

2. **クエリ最適化の効果検証**
   - N+1問題修正の効果測定
   - JOIN最適化の効果測定

3. **スケーラビリティの検証**
   - 同時接続数200での安定性
   - CPU/メモリ使用率の監視

---

## 🎯 テスト環境

### 1. データベース環境

| 項目 | 設定値 |
|-----|-------|
| RDSインスタンス | db.t3.micro |
| CPU | 2 vCPU |
| メモリ | 1GB |
| ストレージ | 50GB gp3 |
| PostgreSQL | 16.x |
| Multi-AZ | 有効 |

### 2. アプリケーション環境

| 項目 | 設定値 |
|-----|-------|
| ECS Task | Fargate 0.5 vCPU, 1GB |
| タスク数 | 3（Auto Scaling） |
| Node.js | 20.x |
| Sequelize | 6.x |

### 3. テストデータ

| テーブル | レコード数 | 備考 |
|---------|----------|------|
| tasks | 10,000件 | ユーザー100人 × 100タスク |
| task_images | 5,000件 | タスクの50%に画像1枚 |
| task_tag | 20,000件 | タスク1件あたり平均2タグ |
| scheduled_task_executions | 50,000件 | 実行履歴 |

---

## 🧪 テストシナリオ

### Scenario 1: ダッシュボード表示（最重要）

#### テスト内容

**API**: `GET /api/tasks?userId={userId}&isCompleted=false&limit=50`

**実行クエリ**:
```sql
SELECT tasks.*, task_images.*
FROM tasks
LEFT JOIN task_images ON tasks.id = task_images.task_id
WHERE 
  tasks.user_id = :userId
  AND tasks.is_completed = false
  AND tasks.deleted_at IS NULL
ORDER BY tasks.due_date ASC
LIMIT 50;
```

**使用インデックス**:
- `idx_tasks_user_dashboard` (user_id, is_completed, due_date) - **新規追加**

#### 目標値

| メトリクス | 最適化前 | 目標 | 測定方法 |
|----------|---------|------|---------|
| レスポンス時間 | 500ms | **150ms** | k6/Artillery |
| クエリ実行時間 | 300ms | **80ms** | EXPLAIN ANALYZE |
| CPU使用率 | 40% | **30%** | CloudWatch |
| メモリ使用率 | 60% | **50%** | CloudWatch |

#### テストコマンド

```bash
# k6負荷テスト
k6 run --vus 50 --duration 5m tests/performance/dashboard.js

# curl単発テスト
time curl -X GET "https://api.myteacher.example.com/api/tasks?userId=xxx&isCompleted=false&limit=50" \
  -H "Authorization: Bearer $TOKEN"
```

---

### Scenario 2: グループタスク取得

#### テスト内容

**API**: `GET /api/tasks?groupId={groupId}&limit=50`

**実行クエリ（最適化後）**:
```sql
SELECT DISTINCT tasks.*, task_images.*
FROM tasks
INNER JOIN group_tasks ON tasks.id = group_tasks.task_id
LEFT JOIN task_images ON tasks.id = task_images.task_id
WHERE 
  group_tasks.group_id = :groupId
  AND tasks.deleted_at IS NULL
ORDER BY tasks.created_at DESC
LIMIT 50;
```

**使用インデックス**:
- `idx_tasks_group_active` (group_id, created_at) - **新規追加**

#### 目標値

| メトリクス | 最適化前 | 目標 |
|----------|---------|------|
| レスポンス時間 | 800ms | **200ms** |
| クエリ数 | 2回 | **1回** |
| クエリ実行時間 | 500ms | **150ms** |

---

### Scenario 3: タスク詳細取得（画像・承認含む）

#### テスト内容

**API**: `GET /api/tasks/{taskId}`

**実行クエリ**:
```sql
SELECT 
  tasks.*,
  task_images.id AS "images.id",
  task_images.image_path AS "images.image_path",
  task_approvals.id AS "approvals.id",
  task_approvals.approved_by_user_id AS "approvals.approved_by_user_id"
FROM tasks
LEFT JOIN task_images ON tasks.id = task_images.task_id
LEFT JOIN task_approvals ON tasks.id = task_approvals.task_id
WHERE tasks.id = :taskId;
```

#### 目標値

| メトリクス | 最適化前 | 目標 |
|----------|---------|------|
| レスポンス時間 | 100ms | **50ms** |
| クエリ実行時間 | 50ms | **20ms** |

---

### Scenario 4: タスク作成（トランザクション）

#### テスト内容

**API**: `POST /api/tasks`

**実行クエリ**:
```sql
BEGIN;
INSERT INTO tasks (...) VALUES (...) RETURNING *;
INSERT INTO task_images (...) VALUES (...);
COMMIT;
```

#### 目標値

| メトリクス | 最適化前 | 目標 |
|----------|---------|------|
| レスポンス時間 | 200ms | **100ms** |
| トランザクション時間 | 150ms | **80ms** |
| デッドロック発生 | 0件 | **0件** |

---

### Scenario 5: 同時接続負荷テスト

#### テスト内容

**負荷パターン**:
- 同時ユーザー数: 100人
- リクエスト/秒: 200 req/s
- 継続時間: 10分

**テストシナリオ**:
1. ダッシュボード表示（50%）
2. タスク詳細取得（30%）
3. タスク作成（15%）
4. タスク更新（5%）

#### 目標値

| メトリクス | 目標 |
|----------|------|
| エラー率 | **< 1%** |
| P95レスポンス時間 | **< 500ms** |
| P99レスポンス時間 | **< 1000ms** |
| スループット | **> 150 req/s** |
| DB接続数 | **< 180** (max 200) |
| CPU使用率 | **< 70%** |

---

## 📊 モニタリング指標

### 1. データベースメトリクス

#### CloudWatch Metrics

| メトリクス | 取得間隔 | 閾値 |
|----------|---------|------|
| CPUUtilization | 1分 | < 70% |
| DatabaseConnections | 1分 | < 180 |
| FreeableMemory | 1分 | > 200MB |
| ReadLatency | 1分 | < 10ms |
| WriteLatency | 1分 | < 10ms |
| ReadIOPS | 1分 | - |
| WriteIOPS | 1分 | - |

#### PostgreSQL内部メトリクス

```sql
-- 1. 実行中クエリの監視
SELECT 
  pid,
  usename,
  application_name,
  state,
  query,
  now() - query_start AS duration
FROM pg_stat_activity
WHERE state != 'idle'
ORDER BY duration DESC;

-- 2. インデックス使用状況
SELECT 
  schemaname,
  tablename,
  indexname,
  idx_scan,
  idx_tup_read,
  idx_tup_fetch
FROM pg_stat_user_indexes
WHERE schemaname = 'public'
ORDER BY idx_scan DESC;

-- 3. テーブルサイズとデッドタプル
SELECT 
  schemaname,
  tablename,
  n_live_tup,
  n_dead_tup,
  last_autovacuum,
  last_autoanalyze
FROM pg_stat_user_tables
WHERE schemaname = 'public'
ORDER BY n_dead_tup DESC;

-- 4. ロック待機の監視
SELECT 
  l.locktype,
  l.relation::regclass,
  l.mode,
  l.granted,
  a.usename,
  a.query,
  a.state
FROM pg_locks l
JOIN pg_stat_activity a ON l.pid = a.pid
WHERE NOT l.granted;
```

### 2. アプリケーションメトリクス

#### カスタムメトリクス（Prometheus形式）

```javascript
// src/middleware/metrics.js
const { register, Counter, Histogram } = require('prom-client');

// リクエストカウンター
const httpRequestsTotal = new Counter({
  name: 'http_requests_total',
  help: 'Total HTTP requests',
  labelNames: ['method', 'route', 'status_code'],
});

// レスポンス時間ヒストグラム
const httpRequestDuration = new Histogram({
  name: 'http_request_duration_seconds',
  help: 'HTTP request duration',
  labelNames: ['method', 'route'],
  buckets: [0.05, 0.1, 0.2, 0.5, 1, 2, 5],
});

// DBクエリ時間
const dbQueryDuration = new Histogram({
  name: 'db_query_duration_seconds',
  help: 'Database query duration',
  labelNames: ['operation', 'table'],
  buckets: [0.01, 0.05, 0.1, 0.2, 0.5, 1],
});
```

---

## 🛠️ テストツール

### 1. k6（負荷テストツール）

#### インストール

```bash
# macOS
brew install k6

# Ubuntu/Debian
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6
```

#### テストスクリプト例（`tests/performance/dashboard.js`）

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '2m', target: 50 },   // Ramp-up
    { duration: '5m', target: 50 },   // Stay at 50 users
    { duration: '2m', target: 100 },  // Ramp-up to 100
    { duration: '5m', target: 100 },  // Stay at 100 users
    { duration: '2m', target: 0 },    // Ramp-down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<1000'], // 95% < 500ms, 99% < 1000ms
    http_req_failed: ['rate<0.01'],                 // Error rate < 1%
  },
};

const BASE_URL = 'https://api.myteacher.example.com';
const TOKEN = __ENV.API_TOKEN;

export default function () {
  const userId = `user-${Math.floor(Math.random() * 100) + 1}`;
  
  // ダッシュボード表示
  const dashboardRes = http.get(
    `${BASE_URL}/api/tasks?userId=${userId}&isCompleted=false&limit=50`,
    {
      headers: { Authorization: `Bearer ${TOKEN}` },
    }
  );
  
  check(dashboardRes, {
    'status is 200': (r) => r.status === 200,
    'response time < 500ms': (r) => r.timings.duration < 500,
    'has tasks': (r) => JSON.parse(r.body).tasks.length > 0,
  });
  
  sleep(1);
}
```

#### 実行コマンド

```bash
# ローカル実行
k6 run tests/performance/dashboard.js

# Cloud実行（K6 Cloud）
k6 cloud tests/performance/dashboard.js

# 結果をInfluxDBに送信
k6 run --out influxdb=http://localhost:8086/k6 tests/performance/dashboard.js
```

### 2. Artillery（シナリオベース負荷テスト）

#### インストール

```bash
npm install -g artillery
```

#### テストスクリプト（`tests/performance/scenario.yml`）

```yaml
config:
  target: "https://api.myteacher.example.com"
  phases:
    - duration: 300
      arrivalRate: 20
      name: "Warm-up"
    - duration: 600
      arrivalRate: 50
      name: "Sustained load"
  variables:
    userId:
      - "user-1"
      - "user-2"
      - "user-3"
  defaults:
    headers:
      Authorization: "Bearer {{ $env.API_TOKEN }}"

scenarios:
  - name: "Dashboard workflow"
    weight: 50
    flow:
      - get:
          url: "/api/tasks?userId={{ userId }}&isCompleted=false&limit=50"
          capture:
            json: "$.tasks[0].id"
            as: "taskId"
      - think: 2
      - get:
          url: "/api/tasks/{{ taskId }}"

  - name: "Task creation"
    weight: 30
    flow:
      - post:
          url: "/api/tasks"
          json:
            user_id: "{{ userId }}"
            title: "Test Task {{ $randomString() }}"
            priority: 2
            is_completed: false
```

#### 実行コマンド

```bash
artillery run tests/performance/scenario.yml
```

### 3. PostgreSQL EXPLAIN ANALYZE

#### テストスクリプト（`tests/performance/query-analysis.sql`）

```sql
-- ===========================
-- 1. ダッシュボードクエリ
-- ===========================
EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON)
SELECT tasks.*, task_images.*
FROM tasks
LEFT JOIN task_images ON tasks.id = task_images.task_id
WHERE 
  tasks.user_id = 'user-1'
  AND tasks.is_completed = false
  AND tasks.deleted_at IS NULL
ORDER BY tasks.due_date ASC
LIMIT 50;

-- ===========================
-- 2. グループタスククエリ
-- ===========================
EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON)
SELECT DISTINCT tasks.*
FROM tasks
INNER JOIN group_tasks ON tasks.id = group_tasks.task_id
WHERE 
  group_tasks.group_id = 'group-1'
  AND tasks.deleted_at IS NULL
ORDER BY tasks.created_at DESC
LIMIT 50;

-- ===========================
-- 3. インデックス使用確認
-- ===========================
SELECT 
  schemaname,
  tablename,
  indexname,
  idx_scan,
  idx_tup_read,
  idx_tup_fetch,
  pg_size_pretty(pg_relation_size(indexrelid)) AS index_size
FROM pg_stat_user_indexes
WHERE schemaname = 'public'
ORDER BY idx_scan DESC;
```

---

## 📈 テスト実施スケジュール

### Phase 1: 単体クエリテスト（1日目）

- [ ] **スキーマ適用**（schema_optimized.sql）
- [ ] **テストデータ生成**（10,000タスク）
- [ ] **EXPLAIN ANALYZEでプラン確認**
- [ ] **単体クエリのレスポンス時間測定**

### Phase 2: API負荷テスト（2日目）

- [ ] **k6ダッシュボードテスト**（50 VU × 5分）
- [ ] **Artilleryシナリオテスト**（混合ワークロード）
- [ ] **CloudWatchメトリクス確認**
- [ ] **スロークエリログ分析**

### Phase 3: スケーラビリティテスト（3日目）

- [ ] **同時接続100ユーザー**（10分間）
- [ ] **CPU/メモリ/接続数監視**
- [ ] **Auto Scaling動作確認**
- [ ] **エラー率・レスポンス時間確認**

### Phase 4: 長時間安定性テスト（4-5日目）

- [ ] **24時間連続負荷**（20 req/s）
- [ ] **メモリリーク確認**
- [ ] **デッドロック発生監視**
- [ ] **Autovacuum動作確認**

---

## ✅ 合格基準

### 必須条件（Phase 1完了基準）

| 項目 | 基準 |
|-----|------|
| ダッシュボード表示 | < 150ms |
| グループタスク取得 | < 200ms |
| タスク詳細取得 | < 50ms |
| インデックス使用率 | > 95% |

### 推奨条件（Phase 2-3完了基準）

| 項目 | 基準 |
|-----|------|
| P95レスポンス時間 | < 500ms |
| P99レスポンス時間 | < 1000ms |
| エラー率 | < 1% |
| CPU使用率 | < 70% |
| DB接続数 | < 180 |

### 安定性条件（Phase 4完了基準）

| 項目 | 基準 |
|-----|------|
| 24時間稼働 | エラーなし |
| メモリリーク | 増加率 < 5% |
| デッドロック | 0件 |
| Autovacuum | 正常動作 |

---

## 🔗 関連ドキュメント

- [データベースパフォーマンス分析レポート](./2025-11-27_DATABASE_PERFORMANCE_TUNING_ANALYSIS.md)
- [クエリ最適化ガイドライン](./2025-11-27_QUERY_OPTIMIZATION_GUIDELINES.md)
- [最適化スキーマ](../terraform/modules/task-service-db/schema_optimized.sql)
- [k6公式ドキュメント](https://k6.io/docs/)
- [Artillery公式ドキュメント](https://www.artillery.io/docs)

---

**次のアクション**: テストデータ生成スクリプトを作成し、Phase 1のテストを開始します。
