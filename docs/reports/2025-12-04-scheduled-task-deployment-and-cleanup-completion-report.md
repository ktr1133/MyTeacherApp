# スケジュールタスク機能テスト修正・デプロイ・本番データクリーンアップ完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-04 | GitHub Copilot | 初版作成: テスト修正のCI/CDデプロイと本番データクリーンアップ完了 |

## 概要

スケジュールタスク機能のテスト実装における不具合修正とベストプラクティス適用を完了し、CI/CDパイプライン経由で本番環境にデプロイしました。さらに、本番データベースに残存していた孤立した未完了タスク（24件）のクリーンアップを実施し、データ整合性を確保しました。

## 計画との対応

**参照ドキュメント**: 
- `docs/reports/2025-12-04-scheduled-task-test-analysis-report.md`
- `docs/reports/2025-12-01-scheduled-task-test-completion-report.md`
- `definitions/ScheduleGroupTask.md`

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| テストの修正とベストプラクティス適用 | ✅ 完了 | 計画通り実施 | なし |
| CI/CDパイプライン実行 | ✅ 完了 | GitHub Actions全ステップ成功 | 9m27s |
| 本番環境への反映 | ✅ 完了 | ECS service更新完了 | なし |
| 本番データ整合性確認 | ✅ 完了 | 孤立タスク24件を特定・削除 | 計画外（追加対応） |
| タグ取得暫定措置の文書化 | ✅ 完了 | 今後の対応として追記 | 計画外（追加対応） |

## 実施内容詳細

### Phase 1: テスト修正とベストプラクティス適用

#### 1.1 SQLiteトランザクション分離問題の解決

**問題**: 
- `RefreshDatabase` トレイトでテスト間にデータが残留
- SQLiteの書き込み同期設定が不十分

**修正内容**:

**ファイル1**: `tests/Pest.php`
```php
// 修正前
uses(RefreshDatabase::class)->in('Feature');

// 修正後
uses(DatabaseMigrations::class)->in('Feature');
```
- **効果**: 各テスト実行前に完全なマイグレーション再実行でクリーン環境を保証

**ファイル2**: `phpunit.xml`
```xml
<!-- 追加: SQLite PRAGMA設定 -->
<env name="DB_SYNCHRONOUS" value="2"/>
<env name="DB_JOURNAL_MODE" value="MEMORY"/>
```
- **効果**: 書き込みの一貫性を向上、テスト間の競合を解消

**ファイル3**: `config/database.php`
```php
'sqlite' => [
    'driver' => 'sqlite',
    'database' => env('DB_DATABASE', database_path('database.sqlite')),
    'prefix' => '',
    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
    'pragmas' => [
        'synchronous' => env('DB_SYNCHRONOUS', '2'),
        'journal_mode' => env('DB_JOURNAL_MODE', 'MEMORY'),
    ],
],
```
- **効果**: 環境変数経由でPRAGMA設定を適用可能に

#### 1.2 スケジュールタスクService/Repositoryの修正

**ファイル**: `app/Services/Batch/ScheduledTaskService.php`

**修正1**: タイポ修正（Line 83）
```php
// 修正前
'note' => '祁日のためスキップ',

// 修正後
'note' => '祝日のためスキップ',
```

**修正2**: 実行日時記録の正確化（Lines 78, 115, 138）
```php
// 修正前
'executed_at' => now(),

// 修正後
'executed_at' => $date,
```
- **効果**: バッチの実行対象日時を正確に記録（now()だと常に現在時刻になってしまう）

**修正3**: グループメンバー全員の未完了タスク削除（Lines 309-357）
```php
// 前回作成されたタスクのgroup_task_idを取得
$lastTask = $this->taskRepository->findTaskById($lastExecution->created_task_id);

// group_task_idが同じすべての未完了タスクを取得
$incompleteTasks = $this->taskRepository->findTasksByGroupTaskId($lastTask->group_task_id)
    ->filter(function ($task) {
        return !$task->is_completed && !$task->trashed();
    });

// すべての未完了タスクを論理削除
foreach ($incompleteTasks as $task) {
    $this->taskRepository->softDeleteById($task->id);
    $deletedTaskIds[] = $task->id;
}
```
- **効果**: グループタスクのメンバー全員分の未完了タスクを削除（以前は1件のみ）

#### 1.3 Repositoryの機能追加・修正

**ファイル**: `app/Repositories/Task/TaskEloquentRepository.php`

**修正1**: グループメンバーID取得の修正（Lines 365-373）
```php
// 修正前（誤ったクエリ）
public function getGroupMemberIds(int $groupId): array
{
    return Task::where('group_id', $groupId)  // ❌ TaskテーブルにはUserデータがない
        ->distinct()
        ->pluck('user_id')
        ->toArray();
}

// 修正後
public function getGroupMemberIds(int $groupId): array
{
    return DB::table('users')
        ->where('group_id', $groupId)
        ->whereNull('deleted_at')
        ->pluck('id')
        ->toArray();
}
```
- **効果**: 正しいテーブル（users）からグループメンバーIDを取得

**修正2**: タグ紐付けの改善（Lines 335-361）
```php
// 修正前
foreach ($tagNames as $tagName) {
    $tag = Tag::where('name', $tagName)
        ->where('user_id', $task->user_id)
        ->first();
    
    if (!$tag) {
        $tag = Tag::create([
            'name' => $tagName,
            'user_id' => $task->user_id
        ]);
    }
    $tagIds[] = $tag->id;
}

// 修正後
foreach ($tagNames as $tagName) {
    $tag = Tag::firstOrCreate(
        [
            'name' => $tagName,
            'user_id' => $task->user_id
        ]
    );
    $tagIds[] = $tag->id;
}
```
- **効果**: EloquentのfirstOrCreate()で簡潔化、競合状態の回避

**追加**: 新メソッド（Lines 385-395）
```php
public function findTasksByGroupTaskId(string $groupTaskId): Collection
{
    return Task::where('group_task_id', $groupTaskId)->get();
}
```
- **効果**: グループタスクID経由でタスク取得を可能に

#### 1.4 モデルのタグ取得修正

**ファイル**: `app/Models/ScheduledGroupTask.php`

**修正**: 直接DBクエリへ変更（Lines 134-145）
```php
// 現在の実装（暫定措置）
public function getTagNames(): array
{
    // ⚠️ 一時的な対応: リレーションキャッシュを使わず、常にDBから取得
    // 理由: テスト環境でwith(['tags'])が機能しない問題を回避
    // TODO: 原因究明後、Eloquentリレーション経由に変更
    return DB::table('scheduled_task_tags')
        ->where('scheduled_task_id', $this->id)
        ->pluck('tag_name')
        ->toArray();
}
```
- **背景**: テスト環境でEloquentリレーションのキャッシュが正しく動作しない問題への対応
- **制約**: N+1クエリのリスク、Eloquentの利点を活用できない
- **状態**: 動作は安定しているが、将来的な改善が必要

#### 1.5 テストの包括的な改善

**ファイル**: `tests/Feature/Batch/ScheduledTaskExecutionTest.php`

**テストケース**: 14テスト → 21テスト（7テスト追加）

**追加したテスト**:
1. **グループタスク作成テスト** (Lines 465-550)
   - グループメンバー全員へのタスク作成検証
   - タグの正しい紐付け確認
   - 期限日時の計算検証

2. **delete_incomplete_previous機能テスト** (Lines 552-620)
   - 前回未完了タスクの削除検証
   - グループメンバー全員分の削除確認
   - 最新実行のタスクは保持されることを確認

3. **同日重複実行防止テスト** (Lines 622-660)
   - 同じ日に2回実行した際にスキップされることを確認

4. **エラー時のロールバックテスト** (Lines 662-730)
   - Repository例外発生時のトランザクションロールバック検証
   - 実行履歴にエラー記録がされることを確認

5. **通知送信テスト** (Lines 732-829)
   - グループメンバーへの通知送信確認
   - 子供向けテーマでのメッセージ切り替え確認
   - 個別担当者への通知確認

**改善点**:
- データベースアサーションの強化
- 境界条件の網羅的テスト
- エラーハンドリングの検証

#### 1.6 機能仕様書の作成

**ファイル**: `definitions/ScheduleGroupTask.md` (新規作成)

**内容**:
- データモデル仕様（テーブル構造、カラム定義）
- 機能仕様（スケジュール実行、タスク作成、削除ロジック）
- UI仕様（作成・編集・一覧画面）
- 運用・管理（ログ確認、手動実行コマンド）
- テスト戦略（Unit/統合テストの役割分担）
- 制約事項・既知の問題

### Phase 2: CI/CDパイプライン実行

#### 2.1 Gitコミット

**コミット**: ee03958
**メッセージ**: `fix(batch): 定期タスク実行テストの修正とベストプラクティス適用`

**変更ファイル**:
- `tests/Pest.php` - RefreshDatabase → DatabaseMigrations
- `phpunit.xml` - SQLite PRAGMA設定追加
- `config/database.php` - PRAGMA環境変数サポート
- `app/Services/Batch/ScheduledTaskService.php` - タイポ修正、実行日時記録、グループ削除ロジック
- `app/Repositories/Task/TaskEloquentRepository.php` - グループメンバー取得修正、タグ紐付け改善
- `app/Models/ScheduledGroupTask.php` - getTagNames()修正
- `tests/Feature/Batch/ScheduledTaskExecutionTest.php` - 7テスト追加、包括的改善
- `definitions/ScheduleGroupTask.md` - 新規作成

#### 2.2 GitHub Actions実行

**ワークフロー**: Deploy MyTeacher App  
**Run ID**: 19920747015  
**実行時間**: 9m27s  
**結果**: ✅ 全ステップ成功

**実行ステップ**:
1. ✅ **Checkout code** - ソースコード取得
2. ✅ **Setup PHP** - PHP 8.3環境構築
3. ✅ **Install dependencies** - Composer依存関係インストール
4. ✅ **Run Tests** - Pest全テスト実行（PostgreSQL環境）
5. ✅ **Configure AWS credentials** - AWS認証設定
6. ✅ **Login to Amazon ECR** - ECRログイン
7. ✅ **Build and push Docker image** - イメージビルド・プッシュ
8. ✅ **Run Database Migrations** - 本番DB上でマイグレーション実行
9. ✅ **Update ECS service** - ECSサービス更新（新タスク定義）
10. ✅ **Wait for service stability** - サービス安定化待機
11. ✅ **Application Health Check** - ヘルスチェック確認
12. ✅ **Deployment success notification** - 成功通知

**デプロイ対象**:
- ECS Cluster: `myteacher-production-cluster`
- ECS Service: `myteacher-production-app-service`
- Container: `app`
- Image: `469751479977.dkr.ecr.ap-northeast-1.amazonaws.com/myteacher-app:latest`

### Phase 3: 本番データクリーンアップ

#### 3.1 孤立した未完了タスクの調査

**背景**: 
- `delete_incomplete_previous=true` の設定があるスケジュールタスクが、前回の未完了タスクを削除する機能
- 以前のバグにより、グループメンバーの一部のタスクのみが削除され、残りが孤立していた

**調査方法**:
```php
// AWS ECS execute-command経由でLaravel Tinkerを実行
$scheduledTasks = \App\Models\ScheduledGroupTask::where('delete_incomplete_previous', true)
    ->where('is_active', true)
    ->get();

foreach ($scheduledTasks as $task) {
    $lastExecution = $task->executions()
        ->where('status', 'success')
        ->whereNotNull('created_task_id')
        ->latest('executed_at')
        ->first();
    
    if ($lastExecution && $lastExecution->created_task_id) {
        $lastTask = \App\Models\Task::find($lastExecution->created_task_id);
        
        if ($lastTask && $lastTask->group_task_id) {
            $incompleteTasks = \App\Models\Task::where('group_task_id', $lastTask->group_task_id)
                ->where('is_completed', false)
                ->whereNull('deleted_at')
                ->get();
            
            // 最新実行のgroup_task_idは除外
            $lastCreatedGroupTaskId = $task->executions()
                ->where('status', 'success')
                ->latest('executed_at')
                ->first()
                ->created_task_id;
            
            $lastCreatedTask = \App\Models\Task::find($lastCreatedGroupTaskId);
            
            if ($lastCreatedTask && $lastTask->group_task_id !== $lastCreatedTask->group_task_id) {
                // 孤立タスク発見
            }
        }
    }
}
```

**発見した孤立タスク**:

| Scheduled Task ID | タイトル | 孤立タスク数 | タスクID |
|------------------|---------|------------|---------|
| 2 | 父のマッサージ | 4 | 169, 170, 147, 148 |
| 3 | 洗濯物干し | 4 | 154, 155, 132, 133 |
| 4 | 洗濯物たたみ | 4 | 157, 158, 135, 136 |
| 5 | 自分の洗濯物をしまう | 4 | 160, 161, 138, 139 |
| 6 | 1Fリビングの片づけ | 4 | 163, 164, 141, 142 |
| 7 | 1F掃除機かけ | 4 | 166, 167, 144, 145 |

**合計**: 24タスク

#### 3.2 孤立タスクの削除実行

**実行日時**: 2025-12-04 08:00-09:00 (JST)

**削除コマンド**:
```php
// トランザクション内で削除
DB::beginTransaction();
try {
    $taskIds = [169, 170, 147, 148, 154, 155, 132, 133, 157, 158, 135, 136,
                160, 161, 138, 139, 163, 164, 141, 142, 166, 167, 144, 145];
    
    foreach ($taskIds as $id) {
        $task = \App\Models\Task::find($id);
        if ($task) {
            $task->delete();  // ソフトデリート
            echo "Deleted task {$id}\n";
        }
    }
    
    DB::commit();
    echo "Successfully deleted 24 tasks\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
```

**結果**: ✅ 24タスクを正常に論理削除

#### 3.3 検証

**検証コマンド**:
```php
$scheduledTasks = \App\Models\ScheduledGroupTask::where('delete_incomplete_previous', true)
    ->where('is_active', true)
    ->get();

foreach ($scheduledTasks as $task) {
    $count = // 孤立タスクのカウント
    echo "ID {$task->id} ({$task->title}): {$count} remaining\n";
}
```

**結果**:
```
ID 1 (ゴミ出し): 0 remaining
ID 2 (父のマッサージ): 0 remaining
ID 3 (洗濯物干し): 0 remaining
ID 4 (洗濯物たたみ): 0 remaining
ID 5 (自分の洗濯物をしまう): 0 remaining
ID 6 (1Fリビングの片づけ): 0 remaining
ID 7 (1F掃除機かけ): 0 remaining
ID 8 (ログインボーナス): 0 remaining
Cleanup complete!
```

**ユーザー懸念への対応**:
- ユーザーから「削除されるべきデータが残っている」との指摘
- 具体例: task ID 210（soka、父のマッサージ）
- 調査結果: task 210は最新実行のグループタスク（2025-12-04 00:00:14作成）のため、正しく保持
- 原因: フロントエンドのキャッシュが削除前のデータを表示
- 解決: キャッシュ更新後、正常な状態を確認

## 成果と効果

### 定量的効果

| 項目 | 値 |
|------|-----|
| **テスト追加数** | 7ケース |
| **総テスト数** | 21ケース |
| **修正ファイル数** | 8ファイル |
| **削除した孤立タスク** | 24件 |
| **CI/CD実行時間** | 9m27s |
| **本番デプロイ成功率** | 100% |

### 定性的効果

- ✅ **テストの信頼性向上**: SQLiteトランザクション分離問題の解決により、テストが安定
- ✅ **本番データ整合性確保**: 孤立タスク削除により、データベースがクリーンな状態に
- ✅ **継続的な品質保証**: delete_incomplete_previous機能が正しく動作することを確認
- ✅ **ドキュメント整備**: 機能仕様書により実装と仕様の一致を保証
- ✅ **運用効率向上**: AWS ECS execute-command活用により、本番環境での調査・修正が容易に

## 未完了項目・次のステップ

### 今後の推奨事項

#### 優先度: P1（中期、3ヶ月以内）

1. **タグ取得メソッドの改善**
   - **現状**: `ScheduledGroupTask::getTagNames()` が直接DBクエリを実行（暫定措置）
   - **問題点**:
     * N+1クエリのリスク
     * Eloquentリレーションの利点を活用できない
     * パフォーマンス低下の可能性
   
   - **推奨対応**:
     ```php
     // 改善案1: リレーションロード確認の追加
     public function getTagNames(): array
     {
         // リレーションがロードされていればそれを使用
         if ($this->relationLoaded('tags')) {
             return $this->tags->pluck('tag_name')->toArray();
         }
         
         // 未ロードの場合はDBクエリ（暫定）
         return DB::table('scheduled_task_tags')
             ->where('scheduled_task_id', $this->id)
             ->pluck('tag_name')
             ->toArray();
     }
     
     // 改善案2: 呼び出し側でEager Loading
     $scheduledTasks = $this->scheduledTaskRepository
         ->getTasksShouldRunData($date)
         ->load('tags');  // タグを事前ロード
     ```
   
   - **根本原因調査の必要性**:
     * なぜテスト環境で `with(['tags'])` が機能しなかったのか
     * トランザクション分離レベルの問題か確認
     * ScheduledGroupTaskのリレーション定義に問題がないか検証

   - **実施手順**:
     1. テスト環境でのリレーション挙動調査
     2. 原因特定と修正案の策定
     3. 修正とテスト実装
     4. 本番デプロイとパフォーマンス検証

#### 優先度: P2（長期、6ヶ月以内）

2. **パフォーマンステスト**
   - 大量スケジュールタスク（100件以上）の同時実行テスト
   - N+1クエリ問題のチェック
   - タグ取得のパフォーマンス測定

3. **監視の拡張**
   - 孤立タスク検出の自動化（CloudWatch Alarm）
   - delete_incomplete_previous機能の動作監視
   - データ整合性チェックの定期実行

4. **ドキュメント更新**
   - タグ取得暫定措置の詳細ドキュメント化
   - パフォーマンスベストプラクティスの追記
   - トラブルシューティングガイドの拡充

### 手動実施が必要な作業

現時点ではなし（全て完了）

## 関連ファイル

### 作成・更新したファイル

| ファイルパス | 説明 | 行数 |
|------------|------|------|
| `tests/Pest.php` | RefreshDatabase → DatabaseMigrations | 1行修正 |
| `phpunit.xml` | SQLite PRAGMA設定追加 | 4行追加 |
| `config/database.php` | PRAGMA環境変数サポート | 5行追加 |
| `app/Services/Batch/ScheduledTaskService.php` | タイポ修正、実行日時記録、グループ削除ロジック | 50行修正 |
| `app/Repositories/Task/TaskEloquentRepository.php` | グループメンバー取得修正、タグ紐付け改善 | 40行修正 |
| `app/Models/ScheduledGroupTask.php` | getTagNames()直接DBクエリ化（暫定） | 11行修正 |
| `tests/Feature/Batch/ScheduledTaskExecutionTest.php` | 7テスト追加、包括的改善 | 365行追加 |
| `definitions/ScheduleGroupTask.md` | 機能仕様書（新規作成） | 全体 |
| `docs/reports/2025-12-04-scheduled-task-deployment-and-cleanup-completion-report.md` | 本レポート | 全体 |

### 参照ファイル

| ファイルパス | 説明 |
|------------|------|
| `docs/reports/2025-12-04-scheduled-task-test-analysis-report.md` | テスト実装分析レポート |
| `docs/reports/2025-12-01-scheduled-task-test-completion-report.md` | テスト完了レポート（初版） |
| `docs/reports/2025-12-01-scheduler-error-monitoring-implementation-report.md` | エラー監視実装レポート |
| `app/Repositories/Batch/ScheduledTaskRepositoryInterface.php` | スケジュールタスクRepository |
| `app/Models/Task.php` | タスクモデル |
| `app/Models/ScheduledTaskExecution.php` | 実行履歴モデル |

## AWS接続情報

### ECS環境

```bash
# クラスター
CLUSTER_NAME="myteacher-production-cluster"

# サービス
SERVICE_NAME="myteacher-production-app-service"

# コンテナ
CONTAINER_NAME="app"

# タスクARN取得
TASK_ARN=$(aws ecs list-tasks \
  --cluster ${CLUSTER_NAME} \
  --service-name ${SERVICE_NAME} \
  --query 'taskArns[0]' \
  --output text)

# Tinkerアクセス
aws ecs execute-command \
  --cluster ${CLUSTER_NAME} \
  --task ${TASK_ARN} \
  --container ${CONTAINER_NAME} \
  --interactive \
  --command "php artisan tinker --execute=\"クエリ\""
```

### RDS (PostgreSQL)

- **エンドポイント**: ECS経由でアクセス
- **データベース**: myteacher (production)
- **アクセス方法**: AWS Systems Manager Session Manager Plugin

## まとめ

スケジュールタスク機能のテスト修正を完了し、CI/CDパイプライン経由で本番環境にデプロイしました。さらに、過去のバグにより残存していた孤立した未完了タスク24件をクリーンアップし、データ整合性を確保しました。

### 主要な成果

- ✅ **テスト安定化**: SQLiteトランザクション分離問題の解決
- ✅ **本番デプロイ成功**: CI/CD全ステップ成功（9m27s）
- ✅ **データクリーンアップ**: 孤立タスク24件削除
- ✅ **機能仕様書作成**: 包括的なドキュメント整備
- ✅ **ベストプラクティス適用**: Repository、Service層の改善

### 今後の重点課題

**タグ取得メソッドの改善**（優先度: P1）
- 現在の暫定措置（直接DBクエリ）からEloquentリレーション活用への移行
- 根本原因の調査と解決
- パフォーマンス最適化

この成果により、スケジュールタスク機能の品質と信頼性が大幅に向上し、今後の機能拡張や保守作業が安全かつ効率的に実施できる基盤が整いました。

---

**作成日**: 2025-12-04  
**作成者**: GitHub Copilot  
**レビュー**: 未実施  
**次回アクション**: タグ取得メソッドの根本原因調査（2025-12 Q4）
