# データベースマイグレーション計画書

**作成日**: 2025年11月27日  
**対象**: Task Service マイクロサービス移行  
**Phase**: Phase 2実行前の準備  

---

## 1. 概要

### 1.1 目的

Laravel MonolithからTask Service Microserviceへのデータベース移行戦略を定義する。

### 1.2 目標

- ✅ ゼロダウンタイム移行
- ✅ データ整合性保証
- ✅ ロールバック可能な設計
- ✅ 段階的移行による低リスク化

---

## 2. 現状分析

### 2.1 Laravel（現行）のテーブル構造

#### 2.1.1 tasks テーブル

```sql
CREATE TABLE tasks (
    id                      BIGSERIAL PRIMARY KEY,
    user_id                 BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- 外部キー
    source_proposal_id      BIGINT NULL REFERENCES task_proposals(id) ON DELETE SET NULL,
    assigned_by_user_id     BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    approved_by_user_id     BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    
    -- タスク基本情報
    title                   VARCHAR(255) NOT NULL,
    description             TEXT,
    due_date                VARCHAR(255),
    span                    INTEGER,
    priority                SMALLINT DEFAULT 3,
    
    -- グループタスク関連
    group_task_id           UUID NULL,  -- グループタスク共通識別子
    reward                  INTEGER,    -- 報酬額
    requires_approval       BOOLEAN DEFAULT FALSE,
    requires_image          BOOLEAN DEFAULT FALSE,
    approved_at             TIMESTAMP,
    
    -- 完了状態
    is_completed            BOOLEAN DEFAULT FALSE,
    completed_at            TIMESTAMP,
    
    -- タイムスタンプ
    created_at              TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP NOT NULL DEFAULT NOW(),
    deleted_at              TIMESTAMP,  -- ソフトデリート
    
    INDEX idx_user_id (user_id),
    INDEX idx_group_task_id (group_task_id)
);
```

**現在のレコード数**: 約150件（開発環境）

#### 2.1.2 task_images テーブル

```sql
CREATE TABLE task_images (
    id          BIGSERIAL PRIMARY KEY,
    task_id     BIGINT NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
    file_path   VARCHAR(255) NOT NULL COMMENT '画像ファイルパス（S3キー）',
    approved_at TIMESTAMP COMMENT '承認日時',
    delete_at   TIMESTAMP COMMENT '削除予定日時（承認後3日）',
    created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    
    INDEX idx_task_id (task_id)
);
```

**現在のレコード数**: 約80件

#### 2.1.3 task_tag（中間テーブル）

```sql
CREATE TABLE task_tag (
    task_id BIGINT NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
    tag_id  BIGINT NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (task_id, tag_id)
);
```

**現在のレコード数**: 約200件

#### 2.1.4 task_executions テーブル

```sql
CREATE TABLE task_executions (
    id                          BIGSERIAL PRIMARY KEY,
    task_id                     BIGINT NOT NULL REFERENCES tasks(id),
    proposal_id                 BIGINT NOT NULL REFERENCES task_proposals(id) ON DELETE CASCADE,
    estimated_effort_minutes    INTEGER COMMENT '予想所要時間（分）',
    actual_effort_minutes       INTEGER COMMENT '実際の所要時間（分）',
    completion_status           VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, completed, abandoned, partial',
    is_high_quality             BOOLEAN COMMENT '高品質フラグ',
    created_at                  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at                  TIMESTAMP NOT NULL DEFAULT NOW(),
    
    INDEX idx_task_id (task_id)
);
```

**現在のレコード数**: 約100件

#### 2.1.5 task_proposals テーブル

```sql
CREATE TABLE task_proposals (
    id                          BIGSERIAL PRIMARY KEY,
    user_id                     BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    original_task_text          VARCHAR(255) NOT NULL COMMENT 'ユーザーが入力した元のタスク',
    proposal_context            TEXT COMMENT '分割を依頼した際のプロンプト/観点',
    proposed_tasks_json         JSONB COMMENT 'AIが提案した全分割タスクのJSON配列',
    model_used                  VARCHAR(100) COMMENT '使用したAIモデル名',
    
    -- トークン使用量
    prompt_tokens               INTEGER DEFAULT 0,
    completion_tokens           INTEGER DEFAULT 0,
    total_tokens                INTEGER DEFAULT 0,
    
    adopted_proposed_tasks_json JSONB COMMENT 'ユーザが採用したタスク群のJSON配列',
    was_adopted                 BOOLEAN DEFAULT FALSE,
    
    created_at                  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at                  TIMESTAMP NOT NULL DEFAULT NOW(),
    
    INDEX idx_user_id (user_id)
);
```

**現在のレコード数**: 約50件

### 2.2 Task Service（マイクロサービス）のモデル定義

#### 2.2.1 tasks テーブル（Sequelize）

```javascript
{
  id: INTEGER PRIMARY KEY AUTO_INCREMENT,
  user_id: STRING(255) NOT NULL COMMENT 'Cognito Sub (UUID)',  // ← Laravel: BIGINT
  title: STRING(100) NOT NULL,
  description: TEXT,
  difficulty: INTEGER DEFAULT 1,  // ← Laravel: priority (SMALLINT)
  category_id: INTEGER,           // ← Laravel: なし
  status: ENUM('pending', 'in_progress', 'completed', 'approved', 'rejected') DEFAULT 'pending',
  completed_at: DATE,
  approved_at: DATE,
  approved_by: STRING(255) COMMENT '承認者のCognito Sub',  // ← Laravel: approved_by_user_id (BIGINT)
  reflection: TEXT COMMENT '振り返りコメント',  // ← Laravel: なし
  tags: JSONB DEFAULT [] COMMENT 'タグ配列',    // ← Laravel: task_tag中間テーブル
  created_at: DATE NOT NULL,
  updated_at: DATE NOT NULL,
  
  INDEX (user_id),
  INDEX (status),
  INDEX (created_at)
}
```

#### 2.2.2 task_images テーブル（Sequelize）

```javascript
{
  id: INTEGER PRIMARY KEY AUTO_INCREMENT,
  task_id: INTEGER NOT NULL REFERENCES tasks(id),
  s3_key: STRING(500) NOT NULL COMMENT 'S3オブジェクトキー',
  s3_url: STRING(1000) NOT NULL COMMENT 'S3署名付きURL',
  uploaded_by: STRING(255) NOT NULL COMMENT 'Cognito Sub',
  created_at: DATE NOT NULL,
  updated_at: DATE NOT NULL
}
```

#### 2.2.3 task_approvals テーブル（Sequelize）

```javascript
{
  id: INTEGER PRIMARY KEY AUTO_INCREMENT,
  task_id: INTEGER NOT NULL REFERENCES tasks(id),
  approver_id: STRING(255) NOT NULL COMMENT 'Cognito Sub',
  status: ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  comment: TEXT,
  approved_at: DATE,
  created_at: DATE NOT NULL,
  updated_at: DATE NOT NULL
}
```

#### 2.2.4 group_tasks テーブル（Sequelize）

```javascript
{
  id: INTEGER PRIMARY KEY AUTO_INCREMENT,
  group_id: UUID NOT NULL COMMENT 'グループタスクID',
  task_id: INTEGER NOT NULL REFERENCES tasks(id),
  reward: INTEGER COMMENT '報酬額',
  requires_approval: BOOLEAN DEFAULT FALSE,
  requires_image: BOOLEAN DEFAULT FALSE,
  created_at: DATE NOT NULL,
  updated_at: DATE NOT NULL
}
```

---

## 3. スキーマ差異分析

### 3.1 重要な差異

| カラム | Laravel | Task Service | 移行戦略 |
|--------|---------|--------------|---------|
| **user_id** | BIGINT (RDS users.id) | STRING(255) (Cognito Sub) | ✅ Cognito移行マッピングテーブル使用 |
| **priority** | SMALLINT (1-5) | difficulty: INTEGER (1-5) | ✅ カラム名変更、値そのままコピー |
| **タグ** | task_tag中間テーブル | tags: JSONB配列 | ✅ JOIN結果をJSON配列化 |
| **承認者** | approved_by_user_id: BIGINT | approved_by: STRING(255) | ✅ Cognito Sub変換 |
| **グループタスク** | group_task_id: UUID（tasks内） | 独立テーブル group_tasks | ✅ group_task_id存在時に別テーブルへ分割 |
| **削除** | deleted_at（ソフトデリート） | 物理削除 | ⚠️ deleted_at != NULLのレコードは移行対象外 |

### 3.2 移行対象外のデータ

以下のデータは **Task Service に移行しない**:

1. ❌ **task_proposals** テーブル
   - AI提案履歴は将来的に「AI Service」へ分離予定
   - 現段階ではLaravelに残す

2. ❌ **task_executions** テーブル
   - タスク実行統計は将来的に「Analytics Service」へ分離予定
   - 現段階ではLaravelに残す

3. ❌ **削除済みタスク**（deleted_at != NULL）
   - マイクロサービスでは物理削除を採用

### 3.3 新規追加カラム

Task Serviceで新規追加されるカラム:

| カラム | 型 | 説明 | デフォルト値 |
|--------|-----|------|------------|
| category_id | INTEGER | カテゴリーID | NULL |
| reflection | TEXT | 振り返りコメント | NULL |
| tags | JSONB | タグ配列 | [] |

---

## 4. データマイグレーション戦略

### 4.1 移行フェーズ

#### Phase 2.1: 準備フェーズ（Week 1: 12/15-12/21）

**目的**: マイクロサービスDBの初期構築

**実施内容**:

1. **RDS PostgreSQL新規作成**
   ```bash
   # Terraform apply
   cd infrastructure/terraform/environments/production
   terraform apply -target=module.task_service_rds
   ```

   - インスタンスタイプ: db.t4g.micro
   - Multi-AZ: Yes（ap-northeast-1a, 1c）
   - Storage: 20GB gp3
   - セキュリティグループ: Task Service ECSからのみアクセス許可

2. **Sequelizeマイグレーション実行**
   ```bash
   # Task Service コンテナ内で実行
   npx sequelize-cli db:migrate
   ```

   - tasksテーブル作成
   - task_imagesテーブル作成
   - task_approvalsテーブル作成
   - group_tasksテーブル作成

3. **Cognito ユーザーマッピングテーブル作成**
   
   Laravel RDS内に一時テーブルを作成:

   ```sql
   CREATE TABLE cognito_user_mapping (
       laravel_user_id BIGINT PRIMARY KEY REFERENCES users(id),
       cognito_sub     VARCHAR(255) NOT NULL UNIQUE,
       created_at      TIMESTAMP DEFAULT NOW()
   );
   
   -- 既存の移行済みユーザーを登録（Phase 1.5で7ユーザー移行済み）
   INSERT INTO cognito_user_mapping (laravel_user_id, cognito_sub)
   SELECT id, cognito_sub FROM users WHERE cognito_sub IS NOT NULL;
   ```

4. **データ移行スクリプト作成**

   `/infrastructure/scripts/migrate-tasks-data.js`:
   
   ```javascript
   /**
    * タスクデータ移行スクリプト
    * Laravel RDS → Task Service RDS
    */
   
   import { Sequelize } from 'sequelize';
   import pg from 'pg';
   
   // Laravel RDS接続
   const laravelDb = new pg.Client({
       host: process.env.LARAVEL_DB_HOST,
       database: process.env.LARAVEL_DB_NAME,
       user: process.env.LARAVEL_DB_USER,
       password: process.env.LARAVEL_DB_PASSWORD,
       port: 5432,
   });
   
   // Task Service RDS接続
   const taskServiceDb = new Sequelize(
       process.env.TASK_SERVICE_DB_NAME,
       process.env.TASK_SERVICE_DB_USER,
       process.env.TASK_SERVICE_DB_PASSWORD,
       {
           host: process.env.TASK_SERVICE_DB_HOST,
           dialect: 'postgres',
       }
   );
   
   async function migrateTaskData() {
       await laravelDb.connect();
       
       // 1. Cognito マッピング取得
       const mappingResult = await laravelDb.query(`
           SELECT laravel_user_id, cognito_sub 
           FROM cognito_user_mapping
       `);
       const userMapping = new Map(
           mappingResult.rows.map(row => [row.laravel_user_id, row.cognito_sub])
       );
       
       // 2. タスク取得（削除済み除外）
       const tasksResult = await laravelDb.query(`
           SELECT 
               t.*,
               COALESCE(
                   json_agg(
                       json_build_object('id', tg.tag_id, 'name', tg.name)
                   ) FILTER (WHERE tg.tag_id IS NOT NULL),
                   '[]'::json
               ) as tags
           FROM tasks t
           LEFT JOIN task_tag tt ON t.id = tt.task_id
           LEFT JOIN tags tg ON tt.tag_id = tg.id
           WHERE t.deleted_at IS NULL
           GROUP BY t.id
           ORDER BY t.created_at ASC
       `);
       
       console.log(`移行対象タスク: ${tasksResult.rows.length}件`);
       
       // 3. タスク移行
       for (const task of tasksResult.rows) {
           const cognitoSub = userMapping.get(task.user_id);
           
           if (!cognitoSub) {
               console.warn(`[SKIP] user_id=${task.user_id} はCognito未移行`);
               continue;
           }
           
           // approved_by_user_id も変換
           let approvedBy = null;
           if (task.approved_by_user_id) {
               approvedBy = userMapping.get(task.approved_by_user_id);
           }
           
           await taskServiceDb.query(`
               INSERT INTO tasks (
                   id, user_id, title, description, 
                   difficulty, status, completed_at, approved_at, 
                   approved_by, tags, created_at, updated_at
               ) VALUES (
                   :id, :user_id, :title, :description,
                   :difficulty, :status, :completed_at, :approved_at,
                   :approved_by, :tags, :created_at, :updated_at
               )
           `, {
               replacements: {
                   id: task.id,
                   user_id: cognitoSub,
                   title: task.title,
                   description: task.description,
                   difficulty: task.priority || 3,  // priority → difficulty
                   status: task.is_completed ? 'completed' : 'pending',
                   completed_at: task.completed_at,
                   approved_at: task.approved_at,
                   approved_by: approvedBy,
                   tags: JSON.stringify(task.tags),
                   created_at: task.created_at,
                   updated_at: task.updated_at,
               },
           });
           
           // 4. グループタスク移行
           if (task.group_task_id) {
               await taskServiceDb.query(`
                   INSERT INTO group_tasks (
                       group_id, task_id, reward, 
                       requires_approval, requires_image, 
                       created_at, updated_at
                   ) VALUES (
                       :group_id, :task_id, :reward,
                       :requires_approval, :requires_image,
                       :created_at, :updated_at
                   )
               `, {
                   replacements: {
                       group_id: task.group_task_id,
                       task_id: task.id,
                       reward: task.reward,
                       requires_approval: task.requires_approval,
                       requires_image: task.requires_image,
                       created_at: task.created_at,
                       updated_at: task.updated_at,
                   },
               });
           }
       }
       
       // 5. task_images 移行
       const imagesResult = await laravelDb.query(`
           SELECT ti.*, m.cognito_sub as uploaded_by_cognito
           FROM task_images ti
           JOIN tasks t ON ti.task_id = t.id
           JOIN cognito_user_mapping m ON t.user_id = m.laravel_user_id
           WHERE t.deleted_at IS NULL
       `);
       
       for (const image of imagesResult.rows) {
           await taskServiceDb.query(`
               INSERT INTO task_images (
                   id, task_id, s3_key, s3_url, 
                   uploaded_by, created_at, updated_at
               ) VALUES (
                   :id, :task_id, :s3_key, :s3_url,
                   :uploaded_by, :created_at, :updated_at
               )
           `, {
               replacements: {
                   id: image.id,
                   task_id: image.task_id,
                   s3_key: image.file_path,
                   s3_url: `https://${process.env.S3_BUCKET}.s3.amazonaws.com/${image.file_path}`,
                   uploaded_by: image.uploaded_by_cognito,
                   created_at: image.created_at,
                   updated_at: image.updated_at,
               },
           });
       }
       
       console.log('✅ データ移行完了');
   }
   
   migrateTaskData()
       .then(() => process.exit(0))
       .catch(err => {
           console.error('❌ 移行エラー:', err);
           process.exit(1);
       });
   ```

#### Phase 2.2: 検証フェーズ（Week 2: 12/22-12/28）

**目的**: 移行データの整合性確認

**実施内容**:

1. **レコード数比較**
   ```sql
   -- Laravel RDS
   SELECT COUNT(*) FROM tasks WHERE deleted_at IS NULL;
   
   -- Task Service RDS
   SELECT COUNT(*) FROM tasks;
   ```

2. **サンプルデータ比較**
   ```sql
   -- 最新10件のタスクIDが一致するか
   -- Laravel
   SELECT id, title, user_id FROM tasks 
   WHERE deleted_at IS NULL 
   ORDER BY created_at DESC LIMIT 10;
   
   -- Task Service
   SELECT id, title, user_id FROM tasks 
   ORDER BY created_at DESC LIMIT 10;
   ```

3. **タグデータ検証**
   ```sql
   -- Laravel: task_tag JOIN結果
   SELECT t.id, array_agg(tg.name) as tag_names
   FROM tasks t
   LEFT JOIN task_tag tt ON t.id = tt.task_id
   LEFT JOIN tags tg ON tt.tag_id = tg.id
   WHERE t.deleted_at IS NULL
   GROUP BY t.id
   LIMIT 10;
   
   -- Task Service: JSONBから展開
   SELECT id, tags FROM tasks LIMIT 10;
   ```

4. **ロールバックテスト**
   ```bash
   # Task Service RDSを削除して再作成
   terraform destroy -target=module.task_service_rds
   terraform apply -target=module.task_service_rds
   
   # 再度マイグレーション実行
   node infrastructure/scripts/migrate-tasks-data.js
   ```

#### Phase 2.3: 並行運用フェーズ（Week 3-4: 12/29-1/11）

**目的**: LaravelとTask Serviceの二重書き込みによるデータ同期

**実施戦略**: **Dual Write Pattern**

```
┌─────────────┐
│  Laravel    │ タスク作成リクエスト
│  Action     │
└──────┬──────┘
       │
       ├──────────► Laravel RDS (tasks) に書き込み
       │
       └──────────► Task Service API 呼び出し
                    └─► Task Service RDS に書き込み
```

**実装例**:

```php
// Laravel: App\Services\Task\TaskManagementService.php

public function createTask(User $user, array $data): Task
{
    DB::beginTransaction();
    try {
        // 1. Laravel RDSに保存（既存処理）
        $task = $this->taskRepository->create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'] ?? 3,
            'is_completed' => false,
        ]);
        
        // 2. Task Service APIに同期（Phase 2.3で追加）
        try {
            $this->syncToTaskService($task);
        } catch (\Exception $e) {
            // Task Service同期失敗時はログのみ（Laravel優先）
            Log::error('[TaskService] Sync failed', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
        
        DB::commit();
        return $task;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

private function syncToTaskService(Task $task): void
{
    $apiUrl = config('services.task_service.api_url');
    $accessToken = Auth::user()->cognito_access_token;
    
    Http::withToken($accessToken)
        ->timeout(5)
        ->post("{$apiUrl}/api/tasks", [
            'id' => $task->id,  // IDを指定して同一性保証
            'title' => $task->title,
            'description' => $task->description,
            'difficulty' => $task->priority,
            'status' => $task->is_completed ? 'completed' : 'pending',
            'tags' => $task->tags->pluck('name')->toArray(),
        ]);
}
```

**Task Service側の実装**:

```javascript
// Task Service: controllers/task.controller.js

create = async (req, res, next) => {
    try {
        const taskData = {
            id: req.body.id,  // LaravelからIDを受け取る（任意）
            user_id: req.cognitoUser.sub,
            title: req.body.title,
            description: req.body.description,
            difficulty: req.body.difficulty || 3,
            status: req.body.status || 'pending',
            tags: req.body.tags || [],
        };
        
        // IDが指定されている場合はupsert（既存なら更新）
        const task = await this.taskService.upsertTask(taskData);
        
        res.status(201).json({ success: true, data: task });
    } catch (error) {
        next(error);
    }
};
```

**整合性チェックスクリプト**:

```javascript
// infrastructure/scripts/check-data-consistency.js

async function checkConsistency() {
    // Laravel RDSとTask Service RDSのレコード数比較
    const laravelCount = await laravelDb.query(
        'SELECT COUNT(*) FROM tasks WHERE deleted_at IS NULL'
    );
    const taskServiceCount = await taskServiceDb.query(
        'SELECT COUNT(*) FROM tasks'
    );
    
    if (laravelCount.rows[0].count !== taskServiceCount.rows[0].count) {
        console.error('❌ レコード数不一致');
        console.error(`Laravel: ${laravelCount.rows[0].count}`);
        console.error(`Task Service: ${taskServiceCount.rows[0].count}`);
        
        // 差分レコード特定
        const diff = await laravelDb.query(`
            SELECT l.id, l.title, l.created_at
            FROM tasks l
            LEFT JOIN task_service.tasks t ON l.id = t.id
            WHERE l.deleted_at IS NULL AND t.id IS NULL
        `);
        
        console.error(`未同期タスク: ${diff.rows.length}件`);
        diff.rows.forEach(row => {
            console.error(`- ID=${row.id}: ${row.title}`);
        });
    } else {
        console.log('✅ データ整合性OK');
    }
}

// Cron: 1時間ごとに実行
setInterval(checkConsistency, 3600000);
```

#### Phase 2.4: 切り替えフェーズ（Week 5: 1/12-1/18）

**目的**: Task Serviceをプライマリデータソースに切り替え

**切り替え戦略**: **Canary Deployment**

1. **5% トラフィック移行**（1/12-1/13）
   ```yaml
   # API Gateway設定
   /api/tasks:
     x-amazon-apigateway-integration:
       type: http_proxy
       uri: 
         - weight: 5
           endpoint: ${TASK_SERVICE_ALB_URL}
         - weight: 95
           endpoint: ${LARAVEL_ALB_URL}
   ```

2. **25% トラフィック移行**（1/14）
   - エラー率 < 0.1%を確認

3. **50% トラフィック移行**（1/15）
   - レイテンシ < 200msを確認

4. **100% 移行**（1/16-1/18）
   - 完全にTask Serviceへ切り替え
   - Laravelへの書き込みを停止（読み取りのみ残す）

#### Phase 2.5: クリーンアップフェーズ（Week 6: 1/19-1/25）

**目的**: Laravel側のタスク関連コードを削除

**実施内容**:

1. **Laravelコード削除**
   ```bash
   # 削除対象ファイル
   rm -rf app/Http/Actions/Task/*
   rm -rf app/Services/Task/*
   rm -rf app/Repositories/Task/*
   rm -rf resources/views/tasks/*
   
   # ルート削除
   # routes/web.php から /tasks/* 関連を削除
   ```

2. **Laravel RDSテーブル保持**
   - `tasks`, `task_images`, `task_tag` は**削除しない**
   - 理由: task_proposals, task_executionsから参照される可能性
   - 将来的にAI Service, Analytics Service移行時に削除

3. **モニタリング継続**
   - Task Serviceのメトリクス監視
   - エラーログ確認
   - パフォーマンス測定

---

## 5. ロールバック計画

### 5.1 Phase 2.3（並行運用）中のロールバック

**トリガー条件**:
- Task Service API障害（エラー率 > 5%）
- データ不整合（整合性チェック失敗）
- パフォーマンス劣化（レイテンシ > 500ms）

**手順**:
1. Task Service APIへの同期処理を無効化
   ```php
   // config/services.php
   'task_service' => [
       'enabled' => false,  // ← false に変更
   ],
   ```

2. Laravel単独運用に戻る
3. Task Service RDSは保持（再同期に備える）

### 5.2 Phase 2.4（切り替え）中のロールバック

**トリガー条件**:
- Task Service完全障害
- データ損失検知

**手順**:
1. API Gatewayルーティングを100% Laravelに戻す
2. Task Service RDSからLaravel RDSへ差分同期
   ```javascript
   // 逆方向の同期スクリプト実行
   node infrastructure/scripts/sync-back-to-laravel.js
   ```

3. 原因調査と修正

---

## 6. 成功基準

### 6.1 データ整合性

- ✅ レコード数一致率: 100%
- ✅ タスクタイトル・内容一致率: 100%
- ✅ タグ情報一致率: 99%以上（JSON変換誤差許容）

### 6.2 パフォーマンス

- ✅ Task Service API レイテンシ: p95 < 200ms
- ✅ Laravel → Task Service同期時間: < 100ms
- ✅ データベースCPU使用率: < 70%

### 6.3 可用性

- ✅ Task Service稼働率: 99.9%以上
- ✅ API エラー率: < 0.1%
- ✅ ダウンタイム: 0秒

---

## 7. リスクと対策

| リスク | 影響度 | 対策 |
|--------|-------|------|
| **Cognito未移行ユーザーのデータ移行漏れ** | 高 | Phase 1.5で全ユーザーCognito移行完了を前提とする |
| **タグのJSONB変換エラー** | 中 | 変換前後の検証スクリプト実行 |
| **二重書き込みの整合性不一致** | 中 | 1時間ごとの整合性チェックスクリプト |
| **Task Service RDS障害** | 高 | Multi-AZ構成 + 自動フェイルオーバー |
| **データ移行スクリプトのバグ** | 高 | 開発環境で3回以上の実行テスト |

---

## 8. スケジュール

| フェーズ | 期間 | 担当 | 成果物 |
|---------|------|------|--------|
| Phase 2.1: 準備 | 12/15-12/21（1週間） | Infrastructure | RDS構築、マイグレーションスクリプト |
| Phase 2.2: 検証 | 12/22-12/28（1週間） | Development | 整合性確認レポート |
| Phase 2.3: 並行運用 | 12/29-1/11（2週間） | Full Team | 同期ログ、整合性レポート |
| Phase 2.4: 切り替え | 1/12-1/18（1週間） | DevOps | トラフィック移行完了 |
| Phase 2.5: クリーンアップ | 1/19-1/25（1週間） | Development | Laravel コード削除完了 |

**Total**: 6週間（2025年12月15日 〜 2026年1月25日）

---

## 9. チェックリスト

### Phase 2.1: 準備

- [ ] Task Service RDS作成完了
- [ ] Sequelizeマイグレーション実行完了
- [ ] Cognito マッピングテーブル作成完了
- [ ] データ移行スクリプト作成完了
- [ ] 開発環境で移行テスト3回成功

### Phase 2.2: 検証

- [ ] レコード数一致確認
- [ ] サンプルデータ比較完了
- [ ] タグデータ検証完了
- [ ] ロールバックテスト成功

### Phase 2.3: 並行運用

- [ ] Laravel → Task Service同期実装完了
- [ ] 整合性チェックスクリプト稼働中
- [ ] エラー率 < 0.1%
- [ ] データ不整合ゼロ

### Phase 2.4: 切り替え

- [ ] 5% トラフィック移行成功
- [ ] 25% トラフィック移行成功
- [ ] 50% トラフィック移行成功
- [ ] 100% トラフィック移行成功
- [ ] Laravel書き込み停止

### Phase 2.5: クリーンアップ

- [ ] Laravel タスク関連コード削除
- [ ] ドキュメント更新
- [ ] モニタリング継続設定
- [ ] 完了レポート作成

---

## 10. 関連ドキュメント

- [マイクロサービス移行計画書](../definitions/microservices-migration-plan.md)
- [Phase 2実装レポート](./2025-11-27_PHASE2_TASK_SERVICE_IMPLEMENTATION.md)
- [環境変数管理](../definitions/environment-variables.md)
- [データベーススキーマ](../definitions/database-schema.md)

---

**承認**: 未承認  
**次回レビュー**: Phase 2.1開始前（2025年12月14日）
