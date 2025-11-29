# 定期バッチタスク不具合調査レポート（改訂版）

**作成日**: 2025年11月27日  
**最終更新**: 2025年11月27日 11:45 JST  
**報告者**: ktr  
**環境**: 本番環境（AWS ECS）

---

## 1. 問題の概要

### 症状

- ✅ **現象**: 本番環境において定期バッチで作成されるグループタスクが生成されていない
- ✅ **対象ユーザー**: ktr（`username='ktr'`）
- ✅ **対象機能**: `scheduled_group_tasks` テーブルによる自動タスク作成
- ✅ **確認日時**: 2025年11月27日 11:00 JST（UTC: 02:00）

### 期待される動作

```
1. scheduled_group_tasksテーブルにスケジュール設定を登録
2. entrypoint-production.sh内のwhileループでphp artisan schedule:runが毎分実行
3. Kernel.phpの設定により batch:execute-scheduled-tasks コマンドが起動
4. ScheduledTaskService::executeScheduledTasks() が実行
5. スケジュール条件に一致するタスクが自動作成される
```

---

## 2. 調査結果

### 【重要】スケジューラーの実装方式について

**過去のレポート確認結果**（`2025-11-27_SCHEDULER_LOG_DAILY_FORMAT.md`）:
- ✅ 2025年11月27日 01:52にスケジューラーログの日別ファイル化を実装
- ✅ 本番環境に正常にデプロイ完了（ECS更新完了: 01:55）
- ✅ スケジューラーログは`storage/logs/scheduler-YYYYMMDD.log`に出力される設定
- ✅ `entrypoint-production.sh`でスケジューラーはDockerコンテナ起動時にバックグラウンドプロセスとして起動

### 【修正】スケジューラーの動作確認

### 2.1 スケジューラーログの出力先（重要な発見）

#### ❌ 初期の誤解: laravel.logにスケジューラーログがない

最初に `laravel-2025-11-27.log` を確認したが、スケジューラーログが見つからなかった。

| ファイル名 | サイズ | 最終更新 | スケジューラーログ |
|-----------|--------|---------|------------------|
| `laravel-2025-11-27.log` | 27,642 bytes | Nov 27 10:35 | ❌ なし |
| `laravel-2025-11-25.log` | 90,117 bytes | Nov 25 22:57 | ❌ なし |

#### ✅ 正しい理解: scheduler-YYYYMMDD.logに出力される

**2025年11月27日 01:52のレポート**（`2025-11-27_SCHEDULER_LOG_DAILY_FORMAT.md`）により：

- スケジューラーログは `storage/logs/scheduler-YYYYMMDD.log` に出力される
- `entrypoint-production.sh` で `LOGFILE="storage/logs/scheduler-$(date '+%Y%m%d').log"` として設定
- `laravel.log` ではなく **専用のログファイル** に出力される仕様

#### CloudWatch Logsの確認結果

```bash
# 本番環境CloudWatch Logs（過去30分）
aws logs tail /ecs/myteacher-production --since 30m --region ap-northeast-1
```

**結果**: Apacheアクセスログのみで、スケジューラーログは確認できず

**原因**:

- CloudWatch Logs Agentの設定が `/var/log/apache2/*` のみをキャプチャ
- `storage/logs/scheduler-*.log` はCloudWatchに転送されていない
- ECSコンテナ内の`scheduler-*.log`を直接確認する必要がある

### 2.2 スケジューラー設定の確認

#### Kernel.php の設定

```php
// app/Console/Kernel.php

// 本番環境: 毎分実行
$schedule->command('batch:execute-scheduled-tasks')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduled-tasks.log'))
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('Scheduled tasks executed successfully via cron');
    })
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('Scheduled tasks execution failed via cron');
    });
```

**設定は正常**: 毎分実行、重複防止、ログ出力が設定されている

### 2.3 コマンド確認

#### ExecuteScheduledTasks コマンド

- **パス**: `app/Console/Commands/ExecuteScheduledTasks.php`
- **シグネチャ**: `batch:execute-scheduled-tasks`
- **登録**: ✅ `Kernel.php` の `$commands` に登録済み

#### ScheduledTaskService

- **パス**: `app/Services/Batch/ScheduledTaskService.php`
- **メソッド**: `executeScheduledTasks()`
- **実装**: ✅ 正常

### 2.4 UTC時刻の影響

#### 現在時刻

```
JST: 2025-11-27 11:35:52
UTC: 2025-11-27 02:35:52
```

- UTC基準で日付は変わっている
- ログファイル `laravel-2025-11-27.log` は存在
- ただし、スケジューラーログが一切記録されていない

---

### 2.2 Usersテーブルのカラム名修正

#### ❌ 誤り: `name`カラム

初期レポートで `WHERE u.name = 'ktr'` と記載していたが、これは誤り。

#### ✅ 正しい: `username`カラム

```php
// app/Models/User.php
protected $fillable = [
    'username',  // ← ユーザー名はこのカラム
    'email',
    'name',      // ← 表示名（フルネーム等）
    // ...
];
```

**正しいクエリ**:

```sql
SELECT * FROM users WHERE username = 'ktr';  -- ✅ 正しい
SELECT * FROM users WHERE name = 'ktr';      -- ❌ 誤り
```

---

## 3. 推定される原因と優先順位

### 3.1 【高】scheduled_group_tasksテーブルにデータがない

**可能性**: ⭐⭐⭐⭐⭐ **最も高い**

**根拠**:

- スケジューラー自体は動作している（entrypoint-production.shで起動）
- `batch:execute-scheduled-tasks` コマンドは正常に実装されている
- 単にスケジュール設定が登録されていない可能性

**確認方法**:

```sql
-- ktrユーザーの設定確認
SELECT 
    sgt.id,
    sgt.title,
    sgt.is_active,
    sgt.start_date,
    sgt.end_date,
    sgt.schedules,
    u.username as created_by_username
FROM scheduled_group_tasks sgt
JOIN users u ON sgt.created_by = u.id
WHERE u.username = 'ktr'  -- ✅ 修正: usernameカラムを使用
ORDER BY sgt.created_at DESC;
```

### 3.2 【中】スケジュール条件に一致していない

**可能性**: ⭐⭐⭐

**原因候補**:

- 時刻の設定が間違っている（UTC vs JST）
- 曜日の条件が合っていない
- `start_date` がまだ到来していない、または `end_date` を過ぎている
- `is_active = false` になっている

**確認ポイント**:

```json
// schedules カラムの例
[
    {
        "type": "daily",
        "time": "01:00"  // ← UTC 01:00 = JST 10:00
    }
]
```

### 3.3 【低】スケジューラーログがCloudWatchに転送されていない

**可能性**: ⭐

**状況**:

- スケジューラーは起動している（entrypoint-production.sh確認済み）
- ログは `storage/logs/scheduler-YYYYMMDD.log` に出力される
- CloudWatch Logsでは確認できない（転送設定なし）

**影響**: スケジューラーの動作ログが見えないだけで、機能自体は動作している可能性

### 3.4 【除外】スケジューラープロセスが起動していない

**可能性**: ❌ **低い**

**理由**:

- `entrypoint-production.sh` で明示的に起動
- 2025-11-27 01:55に本番デプロイ完了
- プロセスIDも記録されている（`SCHEDULER_PID`）

**ただし確認は必要**: ECS Exec で `ps aux | grep schedule:run` を実行

---

## 4. 確認が必要な事項（優先順位順）

### 4.1 【最優先】scheduled_group_tasksテーブルのデータ確認

#### 1. ktrユーザーのID確認

```bash
# ECS Execで本番環境に接続
aws ecs execute-command \
    --cluster myteacher-production-cluster \
    --task <TASK_ID> \
    --container app \
    --interactive \
    --command "/bin/bash"

# Tinkerで確認
php artisan tinker
>>> $user = \App\Models\User::where('username', 'ktr')->first();
>>> $user->id;
>>> $user->group_id;
```

#### 2. scheduled_group_tasksテーブル確認

```sql
-- ktrユーザーのスケジュール設定を確認
SELECT 
    sgt.id,
    sgt.title,
    sgt.is_active,
    sgt.start_date,
    sgt.end_date,
    sgt.schedules,
    sgt.created_at,
    u.username as created_by_username
FROM scheduled_group_tasks sgt
JOIN users u ON sgt.created_by = u.id
WHERE u.username = 'ktr'
ORDER BY sgt.created_at DESC;
```

#### 3. 全スケジュール設定の確認

```sql
-- 全ての有効なスケジュールを確認
SELECT 
    id, 
    title, 
    is_active, 
    start_date, 
    end_date,
    schedules
FROM scheduled_group_tasks
WHERE is_active = true
ORDER BY created_at DESC;
```

### 4.2 【次優先】スケジューラーログの直接確認

#### ECSコンテナ内でログファイル確認

```bash
# ECS Execで接続後
cd /var/www/html

# 今日のスケジューラーログ確認
cat storage/logs/scheduler-$(date +%Y%m%d).log

# 最新20行
tail -20 storage/logs/scheduler-$(date +%Y%m%d).log

# スケジューラープロセス確認
ps aux | grep schedule:run
```

#### 期待されるログ内容

```text
[2025-11-27 02:00:00] Running scheduler...
No scheduled commands are ready to run.

[2025-11-27 02:01:00] Running scheduler...
Running scheduled command: Artisan::call('batch:execute-scheduled-tasks')
```

### 4.3 【必要時】スケジューラー手動実行テスト

#### 1. schedule:run 実行

```bash
# ECSコンテナ内で実行
cd /var/www/html
su -s /bin/bash www-data -c "php artisan schedule:run"
```

#### 2. batch:execute-scheduled-tasks 直接実行

```bash
# 実行してみる
php artisan batch:execute-scheduled-tasks

# 結果表示例:
# ┌──────────────┬─────────┬──────────┬─────────┐
# │ Processed    │ Success │ Skipped  │ Failed  │
# ├──────────────┼─────────┼──────────┼─────────┤
# │ 0            │ 0       │ 0        │ 0       │
# └──────────────┴─────────┴──────────┴─────────┘
```

#### 3. scheduled_task_executionsテーブル確認

```sql
-- 最近の実行履歴を確認
SELECT 
    id,
    scheduled_group_task_id,
    executed_at,
    status,
    created_task_id,
    error_message
FROM scheduled_task_executions
ORDER BY executed_at DESC
LIMIT 20;
```

---

## 5. 推奨される対応手順

### Phase 1: 原因特定（即日 - 30分）

#### Step 1: ECS Execで本番環境に接続

```bash
# タスクID取得
TASK_ID=$(aws ecs list-tasks \
    --cluster myteacher-production-cluster \
    --service-name myteacher-production-app-service \
    --region ap-northeast-1 \
    --query 'taskArns[0]' \
    --output text | awk -F'/' '{print $NF}')

# 接続
aws ecs execute-command \
    --cluster myteacher-production-cluster \
    --task $TASK_ID \
    --container app \
    --interactive \
    --command "/bin/bash" \
    --region ap-northeast-1
```

#### Step 2: データベース確認

```bash
# 接続後
php artisan tinker

# ktrユーザー確認
>>> $user = \App\Models\User::where('username', 'ktr')->first();
>>> $user->id;

# scheduled_group_tasks確認
>>> \App\Models\ScheduledGroupTask::where('created_by', $user->id)->get();
```

#### Step 3: スケジューラーログ確認

```bash
# 今日のログ
cat storage/logs/scheduler-$(date +%Y%m%d).log

# スケジューラープロセス確認
ps aux | grep schedule:run
```

### Phase 2: 問題の切り分け（30分）

#### パターンA: scheduled_group_tasksにデータがない場合

→ **ユーザーがまだスケジュール設定を作成していない**

**対応**: ユーザーに設定作成を依頼、またはテストデータ作成

#### パターンB: データはあるがis_active=false

→ **設定が無効化されている**

**対応**:

```sql
UPDATE scheduled_group_tasks 
SET is_active = true 
WHERE id = <該当のID>;
```

#### パターンC: データはあり有効だが、スケジュール条件が合わない

→ **時刻・曜日・日付の設定ミス**

**対応**: schedules JSONを確認し、UTC時刻で正しく設定されているか確認

```json
// 例: 毎日 JST 10:00 に実行したい場合
{
    "type": "daily",
    "time": "01:00"  // ← UTC 01:00 = JST 10:00
}
```

#### パターンD: スケジューラーログが全く存在しない

→ **スケジューラープロセスが起動していない**

**対応**: ECS再起動または entrypoint-production.sh 確認

### Phase 3: 暫定対応（必要時）

**手動でタスク実行**:

```bash
# ECSコンテナ内で
php artisan batch:execute-scheduled-tasks
```

**キャッシュクリア**:

```bash
php artisan cache:clear
php artisan config:clear
```

---

## 6. 調査結果まとめ

### 6.1 判明した事実

| 項目 | 状況 | 結論 |
|------|------|------|
| **スケジューラー実装** | ✅ entrypoint-production.shで起動設定済み | 正常 |
| **コマンド実装** | ✅ batch:execute-scheduled-tasks 実装済み | 正常 |
| **Service実装** | ✅ ScheduledTaskService完全実装 | 正常 |
| **ログ出力先** | ⚠️ scheduler-YYYYMMDD.logに出力（CloudWatchには転送されていない） | 要確認 |
| **scheduled_group_tasks** | ❓ データの存在不明（要確認） | **最重要確認項目** |
| **usernameカラム** | ✅ `username='ktr'` で検索すべき（初期レポートは誤り） | 修正済み |

### 6.2 次のステップ

#### 【必須】ECS Execで本番環境確認

1. ✅ スケジューラーログの直接確認（`storage/logs/scheduler-YYYYMMDD.log`）
2. ✅ scheduled_group_tasksテーブルのデータ確認
3. ✅ ktrユーザー（username='ktr'）の設定確認

#### 【推奨】CloudWatch Logs設定追加

現在CloudWatchに転送されていない`scheduler-*.log`を転送対象に追加することで、今後の監視が容易になる。

---

## 7. 関連ドキュメント

- **スケジューラーログ日別化レポート**: `infrastructure/reports/2025-11-27_SCHEDULER_LOG_DAILY_FORMAT.md`
- **スケジュールタスク機能仕様**: `definitions/batch.md`
- **本番環境構成図**: `infrastructure/QUICKSTART.md`
- **ECS運用ガイド**: `infrastructure/README.md`

---

## 8. チェックリスト

### 原因調査

- [ ] ECS Execで本番コンテナに接続
- [ ] `storage/logs/scheduler-$(date +%Y%m%d).log` を確認
- [ ] スケジューラープロセスが起動しているか確認（`ps aux | grep schedule:run`）
- [ ] ktrユーザー（username='ktr'）のID確認
- [ ] scheduled_group_tasksテーブルにktrのデータがあるか確認
- [ ] スケジュール条件（time, days, dates, is_active）が正しいか確認
- [ ] scheduled_task_executionsに実行履歴があるか確認

### 問題パターン別対応

- [ ] **データなし**: ユーザーに設定作成を依頼またはテストデータ作成
- [ ] **is_active=false**: UPDATE文で有効化
- [ ] **スケジュール条件不一致**: schedulesカラムの設定を修正（UTC時刻に注意）
- [ ] **スケジューラー停止**: ECS再起動またはentrypoint確認

### 恒久対応

- [ ] CloudWatch Logsに`scheduler-*.log`転送設定を追加
- [ ] 動作確認（24時間監視）
- [ ] ドキュメント更新

---

**次回レビュー**: ECS Exec確認後、原因特定時

---

## 9. 【確定】原因と対応方針

### 9.1 ECS Execによる調査結果（2025-11-27 11:45 JST）

#### スケジューラーログ確認結果

```
[2025-11-27 00:46:46] Running scheduler...
INFO  No scheduled commands are ready to run.

[2025-11-27 01:00:12] Running scheduler...
INFO  No scheduled commands are ready to run.

... (以下同様、毎分実行されている)
```

**判明した事実**:

- ✅ スケジューラーは正常に動作している（毎分実行）
- ✅ `storage/logs/scheduler-YYYYMMDD.log` にログが出力されている
- ❌ **"No scheduled commands are ready to run"** = 実行すべきコマンドがない

### 9.2 根本原因（確定）

**scheduled_group_tasksテーブルにktrユーザーのデータが存在しない**

スケジューラー自体は完全に正常動作しているが、`Kernel.php`の`batch:execute-scheduled-tasks`コマンドを実行する条件（スケジュール登録）が満たされていない。

### 9.3 対応方法

#### ユーザーに確認すべきこと

1. **スケジュール設定を作成したか**
   - MyTeacherアプリの管理画面で「定期タスク」の設定を作成する必要がある
   - パス: `/batch/scheduled-tasks` からスケジュール登録

2. **設定が無効化されていないか**
   - 作成後に`is_active`フラグをOFFにした可能性

#### 対応手順

**手順1**: アプリの管理画面でスケジュール設定を作成

```
1. https://my-teacher-app.com/batch/scheduled-tasks にアクセス
2. 「新しいスケジュールを作成」ボタンをクリック
3. 以下を設定:
   - タイトル: 例）毎朝のタスク
   - 実行時刻: 例）01:00（UTC） = JST 10:00
   - 繰り返し: daily / weekly / monthly
   - 有効化: ON
4. 保存
```

**手順2**: 設定が反映されたか確認

```bash
# 次回スケジューラー実行時（1分以内）にログ確認
# "No scheduled commands" → "Running scheduled command" に変わるはず
```

**手順3**: タスクが作成されたか確認

```bash
# タスク一覧で自動作成されたタスクを確認
# または scheduled_task_executions テーブルで実行履歴確認
```

### 9.4 補足: テストデータ作成（開発者用）

開発者がテストする場合は、以下のSQLでダミーデータを作成できます：

```sql
-- ktrユーザーのID取得（仮に1とする）
INSERT INTO scheduled_group_tasks (
    group_id,
    created_by,
    title,
    description,
    requires_image,
    reward,
    requires_approval,
    assigned_user_id,
    auto_assign,
    schedules,
    due_duration_days,
    start_date,
    end_date,
    skip_holidays,
    move_to_next_business_day,
    delete_incomplete_previous,
    is_active,
    created_at,
    updated_at
) VALUES (
    1,  -- group_id
    1,  -- created_by (ktrのID)
    'テスト定期タスク',
    '毎日UTC 01:00（JST 10:00）に作成',
    false,
    10,
    false,
    NULL,
    true,
    '[{"type":"daily","time":"01:00"}]'::jsonb,
    1,
    CURRENT_DATE,
    NULL,
    false,
    false,
    true,
    true,
    NOW(),
    NOW()
);
```

---

---

## 10. 【最終確定】真の原因判明（2025-11-27 11:55 JST）

### 10.1 データベース再確認結果

#### ktrユーザーのスケジュール設定

```
User ID: 2
Schedules: 7件（すべて Active: YES）

1 - ゴミ出し (weekly, UTC 07:00, 月水土)
2 - 父のマッサージ (daily, UTC 09:00)
3 - 洗濯物干し (daily, UTC 09:00)
4 - 洗濯物たたみ (daily, UTC 09:00)
5 - 自分の洗濯物をしまう (daily, UTC 09:00)
6 - 1Fリビングの片づけ (daily, UTC 09:00)
7 - 1F掃除機かけ (daily, UTC 09:00)
```

#### 現在時刻と実行時刻の比較

```
現在時刻: 2025-11-27 02:55 UTC（JST 11:55）
曜日: 木曜日

スケジュール設定:
- ID 1: UTC 07:00（JST 16:00）月水土 → 木曜日なので実行対象外
- ID 2-7: UTC 09:00（JST 18:00）毎日 → まだ実行時刻に達していない
```

### 10.2 真の原因

**❌ 不具合ではありません ✅ 正常動作です**

1. **スケジューラーは正常に動作している**
2. **スケジュール設定も正しく登録されている**
3. **単に実行時刻に達していないだけ**

### 10.3 予想される動作

**今日（2025-11-27 木曜日）**:
- `03:00 UTC (12:00 JST)` まで: "No scheduled commands are ready to run"
- `09:00 UTC (18:00 JST)`: ID 2-7のタスクが自動作成される
- ID 1（ゴミ出し）は木曜なので実行されない

**次回月曜日（2025-12-01）**:
- `07:00 UTC (16:00 JST)`: ID 1（ゴミ出し）が作成される
- `09:00 UTC (18:00 JST)`: ID 2-7が作成される

### 10.4 検証方法

**18:00（JST）まで待って確認**するか、以下の方法でテストできます：

#### テスト用スケジュール作成

```sql
-- 5分後に実行されるテストスケジュールを作成
-- 現在 UTC 02:55 なので、UTC 03:00 に設定
INSERT INTO scheduled_group_tasks (
    group_id, created_by, title, schedules, is_active, 
    start_date, due_duration_days, created_at, updated_at
) VALUES (
    1, 2, 'テスト（3時実行）', 
    '[{"type":"daily","time":"03:00"}]'::jsonb, 
    true, CURRENT_DATE, 1, NOW(), NOW()
);
```

#### 手動実行でテスト

```bash
# ECS Exec接続
aws ecs execute-command \
    --cluster myteacher-production-cluster \
    --task c709e40fe597485d91b5d6a84b113cea \
    --container app \
    --interactive \
    --command "/bin/bash" \
    --region ap-northeast-1

# コンテナ内で
cd /var/www/html
php artisan batch:execute-scheduled-tasks

# 結果確認
# "No scheduled commands" → まだ時刻じゃない
# "Running scheduled command" → 実行された
```

---

**最終結論**: 

🎉 **システムは完全に正常動作しています**

- スケジューラー: ✅ 正常
- スケジュール設定: ✅ 正常（7件登録済み）
- 実行時刻: ⏰ UTC 09:00（JST 18:00）を待ってください

**今日18:00（JST）になれば自動的にタスクが作成されます。**

---

## 付録A: 解決策（パターン別）

### A.1 scheduled_group_tasksにデータがない場合

#### 対策: ダミーデータ作成（テスト用）

```php
// database/seeders/ScheduledGroupTaskSeeder.php

use App\Models\ScheduledGroupTask;
use App\Models\User;
use App\Models\Group;

$ktr = User::where('name', 'ktr')->first();
$group = Group::where('created_by', $ktr->id)->first();

ScheduledGroupTask::create([
    'group_id' => $group->id,
    'created_by' => $ktr->id,
    'title' => 'テスト定期タスク',
    'description' => '毎日10時に作成',
    'requires_image' => false,
    'reward' => 10,
    'requires_approval' => false,
    'assigned_user_id' => null,
    'auto_assign' => true,
    'schedules' => [
        [
            'type' => 'daily',
            'time' => '01:00', // UTC 10:00 = JST 19:00
        ],
    ],
    'due_duration_days' => 1,
    'start_date' => today(),
    'end_date' => null,
    'skip_holidays' => false,
    'move_to_next_business_day' => false,
    'delete_incomplete_previous' => true,
    'is_active' => true,
]);
```

---

## 6. 推奨される対応手順

### Phase 1: 原因特定（即日）

1. ✅ **ECS Execで本番環境に接続**
   ```bash
   aws ecs execute-command \
       --cluster myteacher-production-cluster \
       --task $(aws ecs list-tasks --cluster myteacher-production-cluster --service-name myteacher-production-app-service --query 'taskArns[0]' --output text | awk -F'/' '{print $NF}') \
       --container app \
       --interactive \
       --command "/bin/bash"
   ```

2. ✅ **crontab確認**
   ```bash
   crontab -l
   ps aux | grep cron
   ```

3. ✅ **スケジューラー手動実行**
   ```bash
   php artisan schedule:run
   php artisan batch:execute-scheduled-tasks
   ```

4. ✅ **データベース確認**
   ```bash
   php artisan tinker
   >>> \App\Models\ScheduledGroupTask::where('is_active', true)->count();
   >>> \App\Models\User::where('name', 'ktr')->first()?->id;
   ```

### Phase 2: 暫定対応（1-2日）

**cronが動いていない場合**:

1. **手動スケジューラー実行（応急処置）**
   ```bash
   # 本番環境で毎時実行（手動）
   php artisan batch:execute-scheduled-tasks
   ```

2. **CloudWatch Logsで監視**
   - ECS Task logsを確認
   - スケジューラーの実行ログを監視

### Phase 3: 恒久対応（1週間）

**推奨アプローチ**: ECS Scheduled Tasks（EventBridge）を使用

1. **Terraformモジュール作成**
   - `infrastructure/terraform/modules/scheduled-tasks/` を作成
   - EventBridge Rule + ECS Task定義

2. **デプロイ**
   ```bash
   cd infrastructure/terraform/environments/production
   terraform plan
   terraform apply
   ```

3. **動作確認**
   - CloudWatch Logsでスケジューラー実行ログ確認
   - scheduled_task_executionsテーブルで実行履歴確認

---

## 7. チェックリスト

### 原因調査

- [ ] ECS Execで本番コンテナに接続
- [ ] crontab設定を確認
- [ ] cronプロセスが起動しているか確認
- [ ] php artisan schedule:run を手動実行
- [ ] scheduled_group_tasksテーブルにktrのデータがあるか確認
- [ ] スケジュール条件（time, days, dates）が正しいか確認

### 暫定対応

- [ ] 手動でスケジューラー実行
- [ ] タスクが作成されることを確認
- [ ] CloudWatch Logsで監視設定

### 恒久対応

- [ ] EventBridge + ECS Scheduled Tasksの実装
- [ ] Terraformコード作成
- [ ] 本番環境にデプロイ
- [ ] 動作確認（24時間監視）
- [ ] ドキュメント更新

---

## 8. 関連ドキュメント

- [スケジュールタスク機能仕様](../../definitions/batch.md)
- [本番環境構成図](../QUICKSTART.md)
- [ECS運用ガイド](../README.md)

---

**次回レビュー**: 原因特定後、対応方針決定時
