# Task Service クエリ最適化ガイドライン

**作成日**: 2025-11-27  
**対象**: Task Service Repository Layer  
**目的**: N+1問題の解決とクエリパフォーマンス向上

---

## 📋 目次

1. [N+1問題の修正](#1-n1問題の修正)
2. [グループタスク取得の最適化](#2-グループタスク取得の最適化)
3. [タグ付きタスク取得の最適化](#3-タグ付きタスク取得の最適化)
4. [Sequelizeベストプラクティス](#4-sequelizeベストプラクティス)
5. [インデックスヒント](#5-インデックスヒント)
6. [実装チェックリスト](#6-実装チェックリスト)

---

## 1. N+1問題の修正

### 1.1 現状の問題

**TaskRepository.findAll()**は適切にEager Loadingを使用していますが、将来的な拡張で問題が発生する可能性があります。

#### ✅ 現在の実装（Good）

```javascript
/**
 * タスク一覧取得（画像付き）
 * 
 * @param {Object} filters - フィルタ条件
 * @returns {Promise<{tasks: Task[], totalCount: number}>}
 */
async findAll(filters = {}) {
  const where = {};
  
  // ソフトデリート除外
  where.deleted_at = null;
  
  // ユーザーフィルタ
  if (filters.userId) {
    where.user_id = filters.userId;
  }
  
  // 完了状態フィルタ
  if (typeof filters.isCompleted === 'boolean') {
    where.is_completed = filters.isCompleted;
  }
  
  // 期限フィルタ
  if (filters.dueDateFrom || filters.dueDateTo) {
    where.due_date = {};
    if (filters.dueDateFrom) {
      where.due_date[Op.gte] = filters.dueDateFrom;
    }
    if (filters.dueDateTo) {
      where.due_date[Op.lte] = filters.dueDateTo;
    }
  }
  
  // ✅ Eager Loading - 画像は JOIN で一括取得
  const { rows: tasks, count: totalCount } = await Task.findAndCountAll({
    where,
    include: [
      {
        model: TaskImage,
        as: 'images',
        attributes: ['id', 'image_path', 's3_bucket', 'uploaded_at'],
        required: false, // LEFT JOIN
      }
    ],
    order: [['due_date', 'ASC'], ['created_at', 'DESC']],
    limit: filters.limit || 50,
    offset: filters.offset || 0,
  });
  
  return { tasks, totalCount };
}
```

**クエリ実行例**（1回のクエリで完結）:
```sql
SELECT 
  tasks.*,
  task_images.id AS "images.id",
  task_images.image_path AS "images.image_path"
FROM tasks
LEFT JOIN task_images ON tasks.id = task_images.task_id
WHERE tasks.deleted_at IS NULL
ORDER BY tasks.due_date ASC, tasks.created_at DESC
LIMIT 50;
```

---

## 2. グループタスク取得の最適化

### 2.1 現状の問題（2クエリ実行）

```javascript
// ❌ 問題のあるコード（2回のクエリ）
if (filters.groupId) {
  // クエリ1: グループタスクID取得
  const groupTasks = await GroupTask.findAll({
    where: { group_id: filters.groupId },
    attributes: ['task_id'],
  });
  groupTaskIds = groupTasks.map((gt) => gt.task_id);
  
  // クエリ2: タスク取得
  where.id = { [Op.in]: groupTaskIds };
}
```

**実行されるSQL**:
```sql
-- クエリ1
SELECT task_id FROM group_tasks WHERE group_id = 'xxx';

-- クエリ2
SELECT * FROM tasks WHERE id IN ('id1', 'id2', 'id3', ...);
```

### 2.2 最適化後（1クエリで完結）

```javascript
/**
 * グループタスク一覧取得（最適化版）
 * 
 * @param {string} groupId - グループID
 * @param {Object} filters - フィルタ条件
 * @returns {Promise<{tasks: Task[], totalCount: number}>}
 */
async findByGroupId(groupId, filters = {}) {
  const where = {
    deleted_at: null,
  };
  
  // 完了状態フィルタ
  if (typeof filters.isCompleted === 'boolean') {
    where.is_completed = filters.isCompleted;
  }
  
  // ✅ JOIN で1クエリで取得
  const { rows: tasks, count: totalCount } = await Task.findAndCountAll({
    where,
    include: [
      {
        model: GroupTask,
        as: 'groupTasks',
        where: { group_id: groupId },
        attributes: [], // GROUP_TASK のカラムは不要
        required: true, // INNER JOIN
      },
      {
        model: TaskImage,
        as: 'images',
        attributes: ['id', 'image_path', 's3_bucket', 'uploaded_at'],
        required: false, // LEFT JOIN
      }
    ],
    order: [['created_at', 'DESC']],
    limit: filters.limit || 50,
    offset: filters.offset || 0,
    distinct: true, // COUNT対策
  });
  
  return { tasks, totalCount };
}
```

**実行されるSQL**（1クエリ）:
```sql
SELECT DISTINCT
  tasks.*,
  task_images.id AS "images.id",
  task_images.image_path AS "images.image_path"
FROM tasks
INNER JOIN group_tasks ON tasks.id = group_tasks.task_id
LEFT JOIN task_images ON tasks.id = task_images.task_id
WHERE 
  tasks.deleted_at IS NULL
  AND group_tasks.group_id = 'xxx'
ORDER BY tasks.created_at DESC
LIMIT 50;
```

**パフォーマンス比較**:

| 方式 | クエリ数 | 実行時間（推定） | メリット |
|-----|---------|----------------|---------|
| ❌ 旧方式（Op.in） | 2回 | 800ms | - |
| ✅ 新方式（JOIN） | 1回 | **200ms** | インデックス活用可能 |

---

## 3. タグ付きタスク取得の最適化

### 3.1 将来実装時の推奨パターン

```javascript
/**
 * タグ別タスク一覧取得
 * 
 * @param {string} tagId - タグID
 * @param {string} userId - ユーザーID
 * @returns {Promise<Task[]>}
 */
async findByTagId(tagId, userId) {
  return await Task.findAll({
    where: {
      user_id: userId,
      deleted_at: null,
    },
    include: [
      {
        model: TaskTag,
        as: 'taskTags',
        where: { tag_id: tagId },
        attributes: [],
        required: true, // INNER JOIN
      },
      {
        model: TaskImage,
        as: 'images',
        attributes: ['id', 'image_path'],
        required: false,
      }
    ],
    order: [['created_at', 'DESC']],
  });
}
```

**実行されるSQL**:
```sql
SELECT 
  tasks.*,
  task_images.id AS "images.id"
FROM tasks
INNER JOIN task_tag ON tasks.id = task_tag.task_id
LEFT JOIN task_images ON tasks.id = task_images.task_id
WHERE 
  tasks.user_id = 'xxx'
  AND tasks.deleted_at IS NULL
  AND task_tag.tag_id = 'yyy'
ORDER BY tasks.created_at DESC;
```

**インデックス活用**:
- `idx_task_tag_tag_id` - タグID検索
- `idx_tasks_user_dashboard` - ユーザーID + deleted_at

---

## 4. Sequelizeベストプラクティス

### 4.1 attributes指定（不要なカラム除外）

```javascript
// ❌ 全カラム取得（無駄）
const tasks = await Task.findAll({
  where: { user_id: userId },
});

// ✅ 必要なカラムのみ取得
const tasks = await Task.findAll({
  where: { user_id: userId },
  attributes: ['id', 'title', 'due_date', 'is_completed', 'priority'],
});
```

### 4.2 サブクエリの回避

```javascript
// ❌ サブクエリ（遅い）
const taskIds = await Task.findAll({
  where: { user_id: userId },
  attributes: ['id'],
});
const images = await TaskImage.findAll({
  where: {
    task_id: { [Op.in]: taskIds.map(t => t.id) },
  },
});

// ✅ JOIN（速い）
const tasks = await Task.findAll({
  where: { user_id: userId },
  include: [{ model: TaskImage, as: 'images' }],
});
```

### 4.3 COUNT最適化

```javascript
// ❌ 全レコード取得してカウント（遅い）
const tasks = await Task.findAll({ where: { user_id: userId } });
const count = tasks.length;

// ✅ count()使用（速い）
const count = await Task.count({ where: { user_id: userId } });
```

### 4.4 distinct: true （JOIN時のCOUNT対策）

```javascript
// ❌ JOIN時にCOUNTが重複
const result = await Task.findAndCountAll({
  include: [{ model: TaskImage, as: 'images' }],
});
// count = 10 だが、実際のタスク数は 5（画像が2枚ずつ紐づいている場合）

// ✅ distinct: true で重複回避
const result = await Task.findAndCountAll({
  include: [{ model: TaskImage, as: 'images' }],
  distinct: true, // タスクIDでユニーク化
});
// count = 5（正しい）
```

---

## 5. インデックスヒント

### 5.1 Sequelizeでの強制インデックス利用（必要時）

```javascript
// PostgreSQLでは通常不要（クエリプランナーが自動選択）
// ただし、強制的に特定インデックスを使用したい場合:

const tasks = await sequelize.query(
  `
  SELECT * FROM tasks
  WHERE user_id = :userId 
    AND deleted_at IS NULL 
    AND is_completed = false
  ORDER BY due_date ASC
  `,
  {
    replacements: { userId },
    type: QueryTypes.SELECT,
    // RawクエリでPostgreSQLのクエリプランナーに任せる
  }
);
```

### 5.2 EXPLAIN ANALYZE（クエリ分析）

```javascript
/**
 * クエリプラン分析（開発環境のみ）
 */
if (process.env.NODE_ENV === 'development') {
  const [results, metadata] = await sequelize.query(
    `
    EXPLAIN ANALYZE
    SELECT * FROM tasks
    WHERE user_id = :userId AND deleted_at IS NULL
    ORDER BY due_date ASC
    LIMIT 50
    `,
    { replacements: { userId } }
  );
  console.log('Query Plan:', results);
}
```

**出力例**:
```
Index Scan using idx_tasks_user_dashboard on tasks
  (cost=0.29..8.31 rows=1 width=200)
  (actual time=0.015..0.018 rows=10 loops=1)
  Index Cond: (user_id = 'xxx'::text)
  Filter: (deleted_at IS NULL)
Planning Time: 0.112 ms
Execution Time: 0.045 ms
```

---

## 6. 実装チェックリスト

### Phase 1: 緊急対応（移行前必須）

- [ ] **グループタスク取得をJOINに変更**
  - ファイル: `src/repositories/task.repository.js`
  - メソッド: `findAll()` にグループフィルタ時のJOIN追加
  - 期待効果: クエリ時間 800ms → 200ms

- [ ] **Eager Loadingの確認**
  - 全てのfindAll()でinclude指定
  - required: false (LEFT JOIN) vs required: true (INNER JOIN) の選択

- [ ] **attributes指定の追加**
  - 不要なカラムを除外してデータ転送量削減

- [ ] **distinct: true 追加**
  - findAndCountAll()でJOIN使用時

### Phase 2: 改善対応（移行後1週間以内）

- [ ] **EXPLAIN ANALYZE 実行**
  - 主要クエリのプラン確認
  - インデックスが使用されているか検証

- [ ] **スロークエリログ分析**
  - RDS CloudWatch Logs Insightsで1秒以上のクエリ抽出
  - 最適化対象の優先順位付け

- [ ] **Connection Pool設定**
  - Sequelize connection pool設定（max: 20推奨）

### Phase 3: 長期対応（1ヶ月後）

- [ ] **Read/Write分離**
  - Read Replica導入時のSequelize設定
  - replication機能の活用

- [ ] **キャッシュ戦略**
  - RedisでTasks一覧のキャッシュ
  - TTL: 5分推奨

---

## 📊 パフォーマンス目標

| 操作 | 現在 | 目標 | 最適化後 |
|-----|------|------|---------|
| ダッシュボード表示（50件） | 500ms | 150ms | **80ms** |
| グループタスク取得 | 800ms | 200ms | **200ms** |
| タスク詳細取得 | 100ms | 50ms | **50ms** |
| タスク作成 | 200ms | 100ms | **100ms** |
| 画像アップロード（DB部分） | 150ms | 80ms | **80ms** |

---

## 🔗 関連ドキュメント

- [最適化スキーマ](../terraform/modules/task-service-db/schema_optimized.sql)
- [パフォーマンス分析レポート](./2025-11-27_DATABASE_PERFORMANCE_TUNING_ANALYSIS.md)
- [Sequelize公式ドキュメント - Eager Loading](https://sequelize.org/docs/v6/advanced-association-concepts/eager-loading/)
- [PostgreSQL公式 - EXPLAIN](https://www.postgresql.org/docs/16/sql-explain.html)

---

**次のアクション**: Repository層の実装修正を行い、パフォーマンステストで効果を検証します。
