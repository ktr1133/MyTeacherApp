# グループタスク自動作成機能 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-04 | GitHub Copilot | 初版作成: 実装コードとテストから仕様を書き起こし |
| 2025-12-04 | GitHub Copilot | テスト状況を更新: Unit テストの実装が複雑なため、統合テストでのカバーを推奨 |

## 1. 概要

グループメンバーに対して、定期的に自動でタスクを作成する機能です。毎日、毎週、毎月といったスケジュールに従って、指定したタスクを自動生成し、メンバーに割り当てます。前回作成した未完了タスクの処理（削除または残す）も設定可能です。

### 1.1 目的

- 定期的なタスクの手動作成の手間を削減
- 定期タスクの作成漏れを防止
- グループメンバー全員または特定メンバーへの効率的なタスク配布
- 未完了タスクの自動整理による管理負担軽減

### 1.2 主要機能

- ✅ **スケジュール設定**: 日次・週次・月次でタスクを自動作成
- ✅ **担当者設定**: 特定メンバーまたはグループ全員（編集権限なし）への割り当て
- ✅ **祝日対応**: 祝日をスキップまたは翌営業日に実行
- ✅ **前回未完了タスクの処理**: 削除または保持を選択可能
- ✅ **タグ・報酬・承認設定**: 通常タスクと同等の機能を設定可能
- ✅ **実行履歴管理**: タスク作成の成功・失敗・スキップを記録

## 2. データモデル

### 2.1 scheduled_group_tasks テーブル

| カラム名 | 型 | NULL | デフォルト | 説明 |
|---------|-----|------|-----------|------|
| id | bigint | NO | AUTO | 主キー |
| group_id | bigint | NO | - | グループID |
| created_by | bigint | NO | - | 作成者（ユーザーID） |
| title | varchar | NO | - | タスクタイトル |
| description | text | YES | NULL | タスク説明 |
| requires_image | boolean | NO | false | 画像添付必須フラグ |
| reward | integer | NO | 0 | 報酬トークン数 |
| requires_approval | boolean | NO | false | 承認必須フラグ |
| assigned_user_id | bigint | YES | NULL | 担当者ID（NULL=グループ全員） |
| auto_assign | boolean | NO | false | ランダム割り当てフラグ（将来拡張用） |
| schedules | json | NO | - | スケジュール設定（配列） |
| due_duration_days | integer | YES | NULL | 作成からの期限日数 |
| due_duration_hours | integer | YES | NULL | 作成からの期限時間 |
| start_date | date | NO | - | 開始日 |
| end_date | date | YES | NULL | 終了日（NULL=無期限） |
| skip_holidays | boolean | NO | false | 祝日をスキップ |
| move_to_next_business_day | boolean | NO | false | 祝日時は翌営業日実行 |
| **delete_incomplete_previous** | **boolean** | **NO** | **true** | **前回未完了タスクを削除** |
| tags | json | YES | NULL | タグ名の配列 |
| is_active | boolean | NO | true | 有効/無効 |
| paused_at | timestamp | YES | NULL | 一時停止日時 |
| created_at | timestamp | NO | CURRENT | 作成日時 |
| updated_at | timestamp | NO | CURRENT | 更新日時 |
| deleted_at | timestamp | YES | NULL | 論理削除日時 |

**インデックス**:
- `(group_id, is_active)`
- `start_date`
- `end_date`

### 2.2 scheduled_task_executions テーブル（実行履歴）

| カラム名 | 型 | NULL | 説明 |
|---------|-----|------|------|
| id | bigint | NO | 主キー |
| scheduled_task_id | bigint | NO | スケジュールタスクID |
| created_task_id | bigint | YES | 作成されたタスクID（1つ目のタスクID） |
| deleted_task_id | bigint | YES | 削除されたタスクID（1つ目のタスクID） |
| executed_at | timestamp | NO | 実行日時 |
| status | varchar | NO | 実行結果（success/failed/skipped） |
| note | text | YES | 備考（スキップ理由等） |
| error_message | text | YES | エラーメッセージ |

### 2.3 schedules フィールドの構造（JSON配列）

#### 日次スケジュール
```json
[
  {
    "type": "daily",
    "time": "09:00"
  }
]
```

#### 週次スケジュール
```json
[
  {
    "type": "weekly",
    "time": "09:00",
    "days": [1, 3, 5]  // 0=日曜, 1=月曜, ..., 6=土曜
  }
]
```

#### 月次スケジュール
```json
[
  {
    "type": "monthly",
    "time": "09:00",
    "dates": [1, 15, 28]  // 日付（1-31）
  }
]
```

**複数スケジュール設定可能**: 配列に複数のスケジュールオブジェクトを含めることで、1つのタスク設定で複数のタイミングで実行可能（例: 月曜と金曜の両方に実行）。

## 3. 機能仕様

### 3.1 スケジュール実行システム

#### 3.1.1 実行タイミング

- **Cronジョブ**: 毎分実行（`* * * * *`）
- **実行コマンド**: `php artisan schedule:run`
- **エントリーポイント**: `App\Console\Kernel::schedule()` → `batch:execute-scheduled-tasks`
- **サービスクラス**: `App\Services\Batch\ScheduledTaskService`

#### 3.1.2 実行フロー

```
1. 今日実行すべきスケジュールタスクを取得
   ↓
2. 各スケジュールタスクについて以下を実行
   ├── a. 祝日チェック（skip_holidays または move_to_next_business_day が true の場合）
   ├── b. スケジュールマッチチェック（時刻・曜日・日付）
   ├── c. 重複実行チェック（同日に既に実行済みか）
   ├── d. 前回未完了タスクの処理（delete_incomplete_previous が true の場合）
   ├── e. 新規タスク作成（グループメンバー全員または特定ユーザー）
   ├── f. タグの紐付け
   ├── g. 実行履歴の記録
   └── h. 通知送信（トランザクション成功後）
   ↓
3. 実行結果サマリーをログ出力
```

### 3.2 前回未完了タスク削除機能

#### 3.2.1 概要

`delete_incomplete_previous` が `true` の場合、新しいタスクを作成する前に、前回作成した未完了タスクをすべて削除します。

#### 3.2.2 削除対象の判定ロジック

1. **前回実行履歴を取得**: `scheduled_task_executions` から最後の成功実行を取得
2. **group_task_id を取得**: 前回作成されたタスクの `group_task_id` を取得
3. **関連タスクを全取得**: 同じ `group_task_id` を持つすべてのタスクを取得
4. **未完了タスクをフィルタ**: `is_completed = false` かつ `deleted_at IS NULL` のタスクのみ抽出
5. **論理削除を実行**: 該当するすべてのタスクを `soft delete`

**重要**: グループメンバー全員に作成されたタスク（複数タスク）をすべて削除します。

#### 3.2.3 削除しない条件

以下の場合、削除は実行されません：

- ✅ `delete_incomplete_previous` が `false` の場合
- ✅ 前回の実行履歴が存在しない場合（初回実行時）
- ✅ 前回作成されたタスクが見つからない場合
- ✅ 前回作成されたタスクにすべて完了している場合（`is_completed = true`）

#### 3.2.4 ログ出力

削除実行時には以下の情報をログに記録：

```php
Log::info("Previous incomplete tasks deleted", [
    'deleted_count' => 3,              // 削除されたタスク数
    'deleted_task_ids' => [101, 102, 103],  // 削除されたタスクID配列
    'group_task_id' => 'uuid-xxxx',    // グループタスクID
    'scheduled_task_id' => 1,          // スケジュールタスクID
]);
```

### 3.3 タスク作成ロジック

#### 3.3.1 担当者が未指定の場合（グループタスク）

- **対象**: グループの「編集権限を持たないメンバー」全員
- **処理**: メンバー1人につき1つのタスクを作成
- **group_task_id**: 全タスクに同じUUIDを付与（関連付け）
- **通知**: 各メンバーに個別通知を送信

**例**: グループに編集権限なしのメンバーが3人いる場合、3つのタスクが作成される

#### 3.3.2 担当者が指定されている場合（個別タスク）

- **対象**: 指定されたユーザーのみ
- **処理**: 1つのタスクを作成
- **group_task_id**: 単一タスクでもUUIDを付与
- **通知**: 指定ユーザーに通知を送信

#### 3.3.3 作成されるタスクのデータ

| フィールド | 値 |
|-----------|-----|
| title | scheduled_group_tasks.title |
| span | config('const.task_spans.short') |
| description | scheduled_group_tasks.description |
| group_id | scheduled_group_tasks.group_id |
| user_id | 担当者ID（グループタスクの場合は各メンバーID） |
| assigned_by_user_id | scheduled_group_tasks.created_by |
| group_task_id | 新規UUID（関連タスクで共通） |
| due_date | 計算された期限日時 |
| requires_image | scheduled_group_tasks.requires_image |
| requires_approval | scheduled_group_tasks.requires_approval |
| reward | scheduled_group_tasks.reward |
| created_by | scheduled_group_tasks.created_by |

### 3.4 期限日時の計算

```php
$dueDate = Carbon::parse($date)
    ->addDays($scheduledTask->due_duration_days ?? 0)
    ->addHours($scheduledTask->due_duration_hours ?? 0);
```

**例**:
- `due_duration_days = 3, due_duration_hours = 0` → 3日後の同時刻
- `due_duration_days = 0, due_duration_hours = 12` → 12時間後
- 両方NULL → 即座に期限（同日同時刻）

### 3.5 祝日対応

#### 3.5.1 skip_holidays = true

- **動作**: 祝日の場合、タスク作成をスキップ
- **実行履歴**: `status = 'skipped'`, `note = '祝日のためスキップ'`

#### 3.5.2 move_to_next_business_day = true

- **動作**: 祝日の場合、翌営業日に実行（現在の実装では未完全）
- **優先順位**: `skip_holidays` より低い（`skip_holidays` が優先）

#### 3.5.3 祝日判定

- **データソース**: `holidays` テーブル
- **キャッシュ**: 祝日データはキャッシュされる（`HolidayRepository`）

### 3.6 タイムゾーン対応

- **ユーザータイムゾーン**: 作成者（`created_by`）のタイムゾーン設定を使用
- **デフォルト**: `Asia/Tokyo`
- **変換処理**: UTC時刻をユーザータイムゾーンに変換してスケジュールマッチ判定
- **ログ**: タイムゾーン変換の詳細をログに記録

### 3.7 通知機能

#### 3.7.1 グループタスクの場合

```php
$message = $member->useChildTheme()
    ? 'あたらしいタスクができたよ！🎯 がんばってやってみよう！'
    : '定期タスクが自動作成されました';

$this->notificationService->sendNotification(
    $createdBy,
    $memberId,
    config('const.notification_types.group_task_created'),
    $message,
    '新しいタスク: ' . $taskTitle . 'が作成されました。タスクリストを確認してください。',
    'important'
);
```

#### 3.7.2 個別タスクの場合

同様のメッセージで、指定ユーザーのみに送信。

**テーマ対応**: 子供向けテーマ使用時は絵文字付きの親しみやすいメッセージに変更。

## 4. UI仕様

### 4.1 設定画面（create.blade.php / edit.blade.php）

#### 4.1.1 基本情報セクション

- **件名** (必須): タスクタイトル
- **説明** (任意): タスク詳細説明
- **画像必須**: チェックボックス
- **承認必須**: チェックボックス
- **報酬**: トークン数（数値入力）

#### 4.1.2 スケジュール設定セクション

- **実行タイミング**: ドロップダウン
  - 毎日
  - 毎週（曜日選択）
  - 毎月（日付選択）
- **実行時刻**: 時刻ピッカー（HH:MM形式）
- **開始日**: 日付ピッカー（必須）
- **終了日**: 日付ピッカー（任意、無期限も可能）

#### 4.1.3 担当者設定セクション

- **担当者**: ドロップダウン
  - 「未設定（グループメンバー全員）」
  - グループメンバーリスト

#### 4.1.4 期限設定セクション

- **期限（日）**: 数値入力
- **期限（時間）**: 数値入力
- **説明テキスト**: 「作成から○日○時間後が期限になります」

#### 4.1.5 祝日対応セクション

- **祝日をスキップ**: チェックボックス
- **祝日の場合翌営業日に実行**: チェックボックス

#### 4.1.6 前回タスク処理セクション

- **未完了の前回タスクを削除**: チェックボックス（デフォルト: ON）
- **説明テキスト**: 「新しいタスクを作成する際、前回作成した未完了タスクを削除します」

#### 4.1.7 タグ設定セクション

- **タグ**: テキスト入力（複数入力可能）

### 4.2 一覧画面（index.blade.php）

表示内容：

- タイトル
- スケジュール種別（日次/週次/月次）
- 実行時刻
- 担当者（未設定/ユーザー名）
- 次回実行予定日時
- 有効/一時停止状態
- アクション（編集/削除/一時停止/再開）

## 5. テスト仕様

### 5.1 現在の実装状況

**Unit テストの課題**:
- `ScheduledTaskService` のメソッドの多くは、Laravel の Container、Config、DB 接続などのフレームワーク機能に強く依存している
- 適切なモック作成には複雑なセットアップが必要で、テストの保守性が低下する
- 特に以下のメソッドは Unit テストでのカバーが困難:
  * `createTaskFromSchedule()`: `config()` ヘルパーの呼び出し、タグの紐付け
  * `matchesSchedule()`: `ScheduledGroupTask` モデルの `getSchedules()` が DB 接続を要求
  * `sendNotifications()`: 通知サービスの複雑な依存関係

**推奨アプローチ**:
- ✅ **Unit テスト**: ビジネスロジックが明確で依存が少ないヘルパーメソッド
  * `calculateDueDate()`: 期限日時の計算ロジック
  * `shouldSkipDueToHoliday()`: 祝日判定ロジック
  
- ✅ **統合テスト（Feature テスト）**: 実際の DB とフレームワーク機能を使用
  * タスク作成の全体フロー
  * スケジュールマッチの判定
  * 通知送信
  * 前回未完了タスクの削除
  * エラーハンドリングとロールバック

### 5.2 統合テストで実装すべきテストケース

#### 5.2.1 タスク作成機能

- [ ] グループメンバー全員へのタスク作成
  - 編集権限のないメンバー全員にタスクが作成される
  - 各タスクに同じ `group_task_id` が設定される
  - タグが正しく紐付けられる
  
- [ ] 特定ユーザーへの個別タスク作成
  - 指定ユーザーにのみタスクが作成される
  - タスクデータが正しく設定される（報酬、承認必須など）
  
- [ ] 期限日時の計算
  - `due_duration_days` と `due_duration_hours` が正しく適用される
  - 期限未設定時の動作

#### 5.2.2 スケジュールマッチ

- [ ] 日次スケジュールの判定
  - 指定時刻に毎日実行される
  
- [ ] 週次スケジュールの判定
  - 指定曜日と時刻に実行される
  - 指定外の曜日では実行されない
  
- [ ] 月次スケジュールの判定
  - 指定日付と時刻に実行される
  - 指定外の日付では実行されない
  
- [ ] タイムゾーン対応
  - ユーザーのタイムゾーン設定が正しく適用される

#### 5.2.3 前回未完了タスクの削除

- [ ] `delete_incomplete_previous = true` の場合、前回タスクを削除
  - グループメンバー全員分のタスクが削除される
  - 完了済みタスクは削除されない
  
- [ ] `delete_incomplete_previous = false` の場合、削除しない
  
- [ ] 初回実行時は何もしない

#### 5.2.4 祝日対応

- [ ] `skip_holidays = true` の場合、祝日をスキップ
  - 実行履歴に「スキップ」が記録される
  
- [ ] `skip_holidays = false` の場合、祝日でも実行
  
- [ ] 平日は通常通り実行

#### 5.2.5 通知機能

- [ ] グループメンバーへの個別通知
  - 各メンバーに通知が送信される
  - 子供向けテーマ使用時はメッセージが変わる
  
- [ ] 個別ユーザーへの通知
  - 指定ユーザーにのみ通知が送信される

#### 5.2.6 エラーハンドリング

- [ ] タスク作成失敗時のロールバック
  - DB トランザクションがロールバックされる
  - 実行履歴に失敗が記録される
  
- [ ] 通知送信失敗時の動作
  - タスク作成は成功したまま
  - エラーログが記録される

### 5.3 Unit テスト実装の技術的課題

**課題1: config() ヘルパーのモック**
```php
// 問題: config('const.task_spans.short') の呼び出しがコンテナ解決を要求
'span' => config('const.task_spans.short'),

// 解決策: Facade モックでは不十分、コンテナレベルのモックが必要
Config::shouldReceive('get')->with('const.task_spans.short')->andReturn(1);
// しかし、config() ヘルパーは app('config') を呼び出すため、完全なモックが困難
```

**課題2: Eloquent モデルの DB 依存**
```php
// 問題: getSchedules() が DB 接続を要求
$schedules = $scheduledTask->getSchedules();

// 解決策: モデルメソッドのスタブが必要だが、Eloquent の内部処理がDB接続を要求
$scheduledTask = Mockery::mock(ScheduledGroupTask::class)->makePartial();
$scheduledTask->shouldReceive('getSchedules')->andReturn([...]);
// しかし、他のプロパティアクセスで DB resolver エラーが発生
```

**課題3: Mockery のプロパティアクセス**
```php
// 問題: Eloquent モデルのプロパティ設定が setAttribute() を呼び出す
$user = Mockery::mock(User::class);
$user->id = 10; // BadMethodCallException: setAttribute() の期待値なし

// 解決策: makePartial() を使用し、getAttribute() もモック
$user = Mockery::mock(User::class)->makePartial();
$user->shouldReceive('getAttribute')->with('id')->andReturn(10);
$user->id = 10;
// しかし、複数のプロパティで同様の処理が必要で、テストが肥大化
```

### 5.4 テスト実装の推奨方針

1. **Unit テスト**: シンプルで依存の少ないメソッドのみ
   - `calculateDueDate()`: 日時計算のみ
   - `shouldSkipDueToHoliday()`: 祝日判定のみ
   
2. **統合テスト（Feature テスト）**: 実際のフレームワーク機能を使用
   - DB マイグレーション実行
   - Seeder でテストデータ作成
   - 実際の Service 呼び出し
   - データベースアサーション

3. **テスト戦略**:
   - Unit テストで個別ロジックの正確性を保証
   - 統合テストで全体の動作を保証
   - 保守性とカバレッジのバランスを重視

**具体的な実装例（統合テスト）**:
```php
// tests/Feature/Batch/ScheduledTaskExecutionTest.php
test('グループメンバー全員へのタスクを自動作成できる', function () {
    // Arrange: テストデータ作成
    $group = Group::factory()->create();
    $members = User::factory()->count(3)->create(['group_id' => $group->id]);
    $creator = User::factory()->create(['group_id' => $group->id]);
    
    $scheduledTask = ScheduledGroupTask::factory()->create([
        'group_id' => $group->id,
        'created_by' => $creator->id,
        'assigned_user_id' => null, // グループ全員
        'schedules' => [['type' => 'daily', 'time' => '09:00']],
    ]);
    
    // Act: スケジュール実行
    $service = app(ScheduledTaskService::class);
    $result = $service->executeScheduledTask($scheduledTask, now());
    
    // Assert: タスク作成確認
    expect($result)->toBe('success');
    expect(Task::where('group_task_id', '!=', null)->count())->toBe(3);
    
    // 各メンバーにタスクが作成されていることを確認
    foreach ($members as $member) {
        expect(Task::where('user_id', $member->id)->exists())->toBeTrue();
    }
});
```

このアプローチにより、テストの保守性を保ちつつ、重要な機能のカバレッジを確保できます。

## 6. 運用・管理

### 6.1 実行確認方法

#### 6.1.1 ログ確認

```bash
# リアルタイムログ監視
tail -f storage/logs/laravel.log

# スケジューラーログ（要root）
tail -f /var/log/laravel-scheduler.log

# バッチ実行ログ
tail -f storage/logs/scheduled-tasks.log
```

#### 6.1.2 データベース確認

```sql
-- 実行履歴確認
SELECT 
    ste.id,
    sgt.title,
    ste.status,
    ste.executed_at,
    ste.created_task_id,
    ste.deleted_task_id,
    ste.note
FROM scheduled_task_executions ste
JOIN scheduled_group_tasks sgt ON ste.scheduled_task_id = sgt.id
ORDER BY ste.executed_at DESC
LIMIT 20;

-- 未完了タスク確認
SELECT 
    t.id,
    t.title,
    t.group_task_id,
    t.is_completed,
    t.created_at
FROM tasks t
WHERE t.group_task_id IS NOT NULL
  AND t.is_completed = false
  AND t.deleted_at IS NULL
ORDER BY t.created_at DESC;
```

### 6.2 手動実行コマンド

```bash
# 全スケジュールタスク実行
php artisan batch:execute-scheduled-tasks

# 特定タスク実行
php artisan batch:execute-task {id}

# タスク一覧表示
php artisan batch:list-tasks --group=1
```

### 6.3 トラブルシューティング

#### 6.3.1 タスクが作成されない

1. **スケジュールマッチ確認**: ログで `Schedule match check` を確認
2. **祝日チェック**: 祝日設定が影響していないか確認
3. **重複実行チェック**: 既に今日実行済みでないか確認
4. **is_active フラグ**: スケジュールタスクが有効になっているか確認

#### 6.3.2 削除されるべきタスクが残っている

1. **delete_incomplete_previous フラグ**: `true` になっているか確認
2. **group_task_id**: 前回作成タスクに正しく設定されているか確認
3. **is_completed フラグ**: タスクが本当に未完了か確認

#### 6.3.3 タイムゾーンがずれている

1. **ユーザー設定確認**: 作成者の `timezone` カラムを確認
2. **ログ確認**: `utc_time` と `local_time` の変換が正しいか確認

### 6.4 パフォーマンス考慮事項

- **N+1問題**: グループメンバー取得時に `with()` でリレーション先読み
- **トランザクション**: タスク作成と実行履歴記録は同一トランザクション
- **通知送信**: トランザクション外で実行（ロールバック時は通知しない）
- **祝日キャッシュ**: 祝日データはキャッシュされるため、高速判定可能

## 7. 制約事項・既知の問題

### 7.1 現在の制約

- ✅ `move_to_next_business_day` 機能は未完全（祝日判定のみで翌営業日への移動処理なし）
- ✅ `auto_assign`（ランダム割り当て）機能は実装されているがUI未公開
- ✅ 実行履歴の `created_task_id` は最初の1つのみ記録（グループタスクで複数作成される場合）

### 7.2 今後の拡張予定

- 🔲 翌営業日への自動移動機能の完全実装
- 🔲 ランダム割り当て機能のUI公開
- 🔲 実行履歴に作成されたすべてのタスクIDを記録
- 🔲 実行結果のメール通知
- 🔲 スケジュール実行の手動トリガー（管理画面から）

## 8. 関連ファイル

### 8.1 コアファイル

| ファイルパス | 説明 |
|------------|------|
| `app/Services/Batch/ScheduledTaskService.php` | メインサービスクラス |
| `app/Repositories/Batch/ScheduledTaskRepository.php` | スケジュールタスクデータアクセス |
| `app/Repositories/Task/TaskEloquentRepository.php` | タスクデータアクセス |
| `app/Models/ScheduledGroupTask.php` | スケジュールタスクモデル |
| `app/Models/ScheduledTaskExecution.php` | 実行履歴モデル |

### 8.2 UIファイル

| ファイルパス | 説明 |
|------------|------|
| `resources/views/batch/create.blade.php` | 新規作成画面 |
| `resources/views/batch/edit.blade.php` | 編集画面 |
| `resources/views/batch/index.blade.php` | 一覧画面 |
| `resources/views/batch/partials/scheduled-task-form-create.blade.php` | フォーム部品（作成用） |
| `resources/views/batch/partials/scheduled-task-form-edit.blade.php` | フォーム部品（編集用） |

### 8.3 テストファイル

| ファイルパス | 説明 |
|------------|------|
| `tests/Unit/Services/Batch/ScheduledTaskServiceTest.php` | Unitテスト |

### 8.4 マイグレーション

| ファイルパス | 説明 |
|------------|------|
| `database/migrations/2025_11_07_000001_create_scheduled_group_tasks_table.php` | scheduled_group_tasks テーブル |
| `database/migrations/2025_11_07_000002_create_scheduled_task_tags_table.php` | scheduled_task_tags テーブル |
| `database/migrations/2025_11_07_000003_create_scheduled_task_executions_table.php` | scheduled_task_executions テーブル |

### 8.5 設定ファイル

| ファイルパス | 説明 |
|------------|------|
| `app/Console/Kernel.php` | スケジューラー設定 |
| `config/const.php` | 定数定義（通知タイプ、タスクスパン等） |

## 9. まとめ

グループタスク自動作成機能は、定期的なタスク管理を効率化する重要な機能です。特に「前回未完了タスク削除機能」により、タスクの重複や管理負担を軽減できます。

**重要なポイント**:
- ✅ `delete_incomplete_previous = true`（デフォルト）で、前回作成した未完了タスクをすべて削除
- ✅ グループタスク（複数メンバー）の場合、メンバー全員分のタスクをまとめて削除
- ✅ 完了済みタスクは削除対象外
- ✅ テストにより、削除機能の正常動作を保証

今後のテスト拡充により、タスク作成機能やスケジュールマッチ機能の品質保証も強化していく予定です。
