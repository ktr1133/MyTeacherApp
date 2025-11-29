# Task Service スキーマ差異分析レポート

**作成日**: 2025-11-27  
**分析者**: Database Migration Team  
**目的**: 既存LaravelマイグレーションとTask Service用スキーマの差異を洗い出し

---

## 📋 分析結果サマリー

### 🚨 重大な問題（修正必須）

1. **❌ `task_approvals`テーブル**: 存在しない（承認は`tasks`テーブル内で管理）
2. **❌ `task_images`の余分なカラム**: `file_size`, `s3_bucket`, `user_id`, `uploaded_at` は存在しない
3. **❌ `task_tag`のPK構造**: 複合主キー`(task_id, tag_id)`のみ（`id`カラムなし）
4. **❌ `scheduled_task_tags`の構造**: `tag_id`ではなく`tag_name`（文字列）を使用

### ⚠️ カラムの差異

5. **tasks.user_id**: `BIGINT`（既存）vs `VARCHAR(36)`（Cognito Sub想定）
6. **tasks.due_date**: `VARCHAR`（既存）vs `TIMESTAMP`（想定）
7. **tasks.priority**: `SMALLINT`（既存）vs `INTEGER`（想定）
8. **tasks.group_task_id**: `UUID`型で存在（想定と一致）

---

## 🔍 テーブルごとの詳細分析

### 1. tasksテーブル

#### 既存Laravel（正）

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();  // BIGINT AUTO_INCREMENT
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();  // BIGINT
    
    // 外部キー
    $table->unsignedBigInteger('source_proposal_id')->nullable();
    $table->unsignedBigInteger('assigned_by_user_id')->nullable();
    $table->unsignedBigInteger('approved_by_user_id')->nullable();
    
    // 基本情報
    $table->string('title');                    // VARCHAR(255)
    $table->text('description')->nullable();
    $table->string('due_date')->nullable();     // ⚠️ VARCHAR型（YYYY-MM-DD形式の文字列）
    $table->integer('span')->nullable();
    $table->smallInteger('priority')->default(3);
    
    // グループタスク
    $table->uuid('group_task_id')->nullable()->index();
    $table->integer('reward')->nullable();
    $table->boolean('requires_approval')->default(false);
    $table->boolean('requires_image')->default(false);
    $table->timestamp('approved_at')->nullable();
    
    // 完了状態
    $table->boolean('is_completed')->default(false);
    $table->timestamp('completed_at')->nullable();
    
    $table->timestamps();       // created_at, updated_at
    $table->softDeletes();      // deleted_at
    
    // 外部キー制約
    $table->foreign('source_proposal_id')->references('id')->on('task_proposals')->onDelete('set null');
    $table->foreign('assigned_by_user_id')->references('id')->on('users')->onDelete('set null');
    $table->foreign('approved_by_user_id')->references('id')->on('users')->onDelete('set null');
});
```

#### 私の誤ったスキーマ（誤）

```sql
CREATE TABLE tasks (
  id UUID PRIMARY KEY,  -- ❌ 既存はBIGINT AUTO_INCREMENT
  user_id VARCHAR(36),  -- ❌ 既存はBIGINT（usersテーブルのid）
  due_date TIMESTAMP,   -- ❌ 既存はVARCHAR
  parent_task_id UUID,  -- ❌ 既存にこのカラムなし
  -- ... 他のカラムも型が異なる
);
```

#### ❓ 質問事項

**Q1**: Task Serviceのマイクロサービス化において、`user_id`は以下のどちらにすべきですか？

- **A案**: `BIGINT`のまま（既存usersテーブルのidを参照）
  - メリット: 既存データとの互換性
  - デメリット: MyTeacher本体のusersテーブルに依存（結合度高い）

- **B案**: `VARCHAR(36)` Cognito Sub（UUID）に変更
  - メリット: マイクロサービス間の疎結合
  - デメリット: 既存データの移行時に変換が必要

**推奨**: マイクロサービス化の目的を考えると**B案**ですが、移行時の対応が複雑になります。

**Q2**: `due_date`は`VARCHAR`型で保存されていますが、なぜTIMESTAMP型にしないのでしょうか？

- 既存: `'2025-11-27'` のような文字列
- 理由: 時刻を含めず日付のみ管理？

**推奨**: `DATE`型に変更すべきか確認が必要

**Q3**: `parent_task_id`は既存スキーマに存在しませんが、繰り返しタスク機能は実装されていますか？

- 既存コードには`parent_task_id`の参照なし
- `group_task_id`で複数タスクを紐づけている

---

### 2. task_imagesテーブル

#### 既存Laravel（正）

```php
Schema::create('task_images', function (Blueprint $table) {
    $table->id();  // BIGINT
    $table->unsignedBigInteger('task_id')->comment('タスクID');
    $table->string('file_path')->comment('画像ファイルパス');  // S3パスのみ
    $table->timestamp('approved_at')->nullable()->comment('承認日時');
    $table->timestamp('delete_at')->nullable()->comment('削除予定日時（承認後3日）');
    $table->timestamps();
    
    $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
});
```

#### 私の誤ったスキーマ（誤）

```sql
CREATE TABLE task_images (
  id UUID PRIMARY KEY,
  task_id UUID,
  user_id VARCHAR(36),    -- ❌ 存在しない
  image_path VARCHAR(500),
  s3_bucket VARCHAR(63),  -- ❌ 存在しない
  file_size BIGINT,       -- ❌ 存在しない
  uploaded_at TIMESTAMP,  -- ❌ 存在しない（created_atのみ）
);
```

#### 正しいスキーマ

```sql
CREATE TABLE task_images (
  id BIGSERIAL PRIMARY KEY,
  task_id BIGINT NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
  file_path VARCHAR(255) NOT NULL,
  approved_at TIMESTAMP,
  delete_at TIMESTAMP,  -- ⚠️ typo? deleted_at では？
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### ❓ 質問事項

**Q4**: `delete_at`は`deleted_at`のtypoですか？それともソフトデリートとは別の概念ですか？

- 既存: `delete_at` （削除予定日時）
- 通常: `deleted_at` （削除済み日時）

**推奨**: コメントには「承認後3日」とあるので、自動削除用のスケジュール日時と思われます。

---

### 3. task_tagテーブル（中間テーブル）

#### 既存Laravel（正）

```php
Schema::create('task_tag', function (Blueprint $table) {
    $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
    $table->primary(['task_id', 'tag_id']);  // ✅ 複合主キー
    // ⚠️ created_at, updated_at なし
});
```

#### 私の誤ったスキーマ（誤）

```sql
CREATE TABLE task_tag (
  id UUID PRIMARY KEY,       -- ❌ 主キーは複合キー
  task_id UUID,
  tag_id UUID,               -- ✅ 外部サービス参照（外部キーなし）
  created_at TIMESTAMP,      -- ❌ 既存にない
  UNIQUE(task_id, tag_id)
);
```

#### 正しいスキーマ

```sql
CREATE TABLE task_tag (
  task_id BIGINT NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
  tag_id BIGINT NOT NULL,  -- 外部サービス（Tag Service）参照
  PRIMARY KEY (task_id, tag_id)
);
```

---

### 4. scheduled_group_tasksテーブル

#### 既存Laravel（正）

```php
Schema::create('scheduled_group_tasks', function (Blueprint $table) {
    $table->id();  // BIGINT
    $table->foreignId('group_id')->constrained()->onDelete('cascade');
    $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
    
    // タスク情報
    $table->string('title');
    $table->text('description')->nullable();
    $table->boolean('requires_image')->default(false);
    $table->integer('reward')->default(0);
    $table->boolean('requires_approval')->default(false);
    
    // 担当者
    $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->boolean('auto_assign')->default(false);
    
    // スケジュール（JSON配列）
    $table->json('schedules');
    
    // 期限設定
    $table->integer('due_duration_days')->nullable();
    $table->integer('due_duration_hours')->nullable();
    
    // 期間
    $table->date('start_date');
    $table->date('end_date')->nullable();
    
    // 祝日設定
    $table->boolean('skip_holidays')->default(false);
    $table->boolean('move_to_next_business_day')->default(false);
    
    // 前回タスク処理
    $table->boolean('delete_incomplete_previous')->default(true);
    
    // タグ（JSON配列）
    $table->json('tags')->nullable();
    
    // 状態
    $table->boolean('is_active')->default(true);
    $table->timestamp('paused_at')->nullable();
    
    $table->timestamps();
    $table->softDeletes();
});
```

#### 私の誤ったスキーマ（誤）

```sql
CREATE TABLE scheduled_group_tasks (
  id UUID PRIMARY KEY,
  group_id UUID,
  group_name VARCHAR(100),  -- ❌ 既存にこのカラムなし
  scheduled_time TIME,      -- ❌ 既存はJSON配列 'schedules'
  is_active BOOLEAN,
  -- ... 他の重要なカラムが不足
);
```

#### ❓ 質問事項

**Q5**: `scheduled_group_tasks`テーブルは非常に複雑ですが、Task Serviceのマイクロサービスに含めるべきですか？

- 既存: `group_id`, `created_by`, `assigned_user_id` 等、usersテーブルへの外部キーが多数
- マイクロサービス原則: 外部テーブルへの依存を最小化

**推奨**: このテーブルは**MyTeacher本体に残す**べきでは？

---

### 5. scheduled_task_tagsテーブル

#### 既存Laravel（正）

```php
Schema::create('scheduled_task_tags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('scheduled_task_id')
        ->constrained('scheduled_group_tasks')
        ->onDelete('cascade');
    $table->string('tag_name');  // ⚠️ tag_idではなくtag_name（文字列）
    $table->timestamps();
    
    $table->unique(['scheduled_task_id', 'tag_name']);
});
```

#### 私の誤ったスキーマ（誤）

```sql
CREATE TABLE scheduled_task_tags (
  id UUID PRIMARY KEY,
  scheduled_group_task_id UUID,
  tag_id UUID,  -- ❌ 既存は tag_name（文字列）
);
```

#### ❓ 質問事項

**Q6**: なぜ`tag_name`（文字列）なのでしょうか？Tag Serviceの`tag_id`（UUID）を参照しないのですか？

- 既存: `tag_name VARCHAR`
- 予想: `tag_id BIGINT` 外部参照

**推奨**: 既存仕様に合わせて`tag_name`を使用

---

### 6. scheduled_task_executionsテーブル

#### 既存Laravel（正）

```php
Schema::create('scheduled_task_executions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('scheduled_task_id')
        ->constrained('scheduled_group_tasks')
        ->onDelete('cascade');
    
    $table->timestamp('executed_at');
    $table->enum('status', ['success', 'failed', 'skipped'])->default('success');
    
    $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
    
    $table->text('error_message')->nullable();
    $table->text('skip_reason')->nullable();
    
    $table->timestamps();
    
    $table->index(['scheduled_task_id', 'executed_at']);
    $table->index('status');
});
```

#### 私のスキーマ（ほぼ正）

```sql
CREATE TABLE scheduled_task_executions (
  id UUID PRIMARY KEY,  -- ⚠️ 既存はBIGINT
  task_id UUID,         -- ⚠️ 既存はBIGINT
  executed_at TIMESTAMP,
  status VARCHAR(20),   -- ✅ 'success', 'failed', 'skipped'
  error_message TEXT,   -- ✅ 正しい
  created_at TIMESTAMP
);
```

---

## 🚨 最重要質問

### Q7: Task Serviceのスコープ（どこまでマイクロサービス化するか）

現在の移行計画では以下6テーブルをTask Serviceに移行予定でしたが、**依存関係が複雑**です：

| テーブル | usersテーブルへの依存 | groupsテーブルへの依存 | 推奨 |
|---------|---------------------|----------------------|------|
| tasks | ✅ user_id, assigned_by_user_id, approved_by_user_id | ⚠️ group_task_id (UUID) | **移行可能** |
| task_images | ⚠️ tasksテーブル経由 | - | **移行可能** |
| task_tag | ⚠️ tasksテーブル経由 | - | **移行可能** |
| scheduled_group_tasks | ✅ group_id, created_by, assigned_user_id | ✅ group_id | ❌ **本体に残すべき** |
| scheduled_task_tags | ⚠️ scheduled_group_tasks経由 | ⚠️ scheduled_group_tasks経由 | ❌ **本体に残すべき** |
| scheduled_task_executions | ✅ assigned_user_id | ⚠️ scheduled_group_tasks経由 | ❌ **本体に残すべき** |

**提案**:

**Phase 2では以下3テーブルのみをTask Serviceに移行**:
1. ✅ `tasks`
2. ✅ `task_images`
3. ✅ `task_tag`

**MyTeacher本体に残す（Phase 3以降で別サービス化検討）**:
4. ❌ `scheduled_group_tasks`
5. ❌ `scheduled_task_tags`
6. ❌ `scheduled_task_executions`

理由: `scheduled_*`テーブル群は`groups`, `users`テーブルへの依存が強く、マイクロサービス化のメリットが薄い。

---

## 📝 まとめ

### 即座に修正が必要な項目

1. ✅ `task_approvals`テーブルを削除（存在しない）
2. ✅ `task_images`から不要カラム削除（`file_size`, `s3_bucket`, `user_id`, `uploaded_at`）
3. ✅ `task_tag`のPK構造を複合主キーに変更
4. ✅ `scheduled_task_tags.tag_id` → `tag_name`に変更

### 要確認事項（推測実装禁止）

| 質問 | 内容 | 優先度 |
|-----|------|-------|
| Q1 | `user_id`の型: BIGINT vs VARCHAR(36) | 🔴 高 |
| Q2 | `due_date`の型: VARCHAR vs DATE/TIMESTAMP | 🟡 中 |
| Q3 | `parent_task_id`は必要か？ | 🟡 中 |
| Q4 | `delete_at` vs `deleted_at` | 🟢 低 |
| Q5 | `scheduled_*`テーブルはTask Serviceに含めるか？ | 🔴 高 |
| Q6 | `tag_name` vs `tag_id` の理由 | 🟡 中 |
| Q7 | Task Serviceのスコープ決定 | 🔴 最重要 |

---

**次のアクション**: 上記質問への回答をいただいた後、正しいスキーマを作成します。
