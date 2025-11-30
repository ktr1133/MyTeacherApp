# Phase 1.1.4: グループタスク作成制限機能 完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-01 | GitHub Copilot | 初版作成: Phase 1.1.4 グループタスク作成制限機能の実装完了報告 |

## 概要

MyTeacherアプリの**Phase 1.1.4: グループタスク作成制限機能**を完了しました。この作業により、無料プランユーザーに対する月次グループタスク作成回数の制限機能を実装し、サブスクリプション課金システムの基盤となる制限機能を確立しました。

達成した主要な目標：

- ✅ **月次制限管理**: 無料プランユーザーは月5回までグループタスク作成可能、サブスクリプション契約者は無制限
- ✅ **自動リセット機能**: 毎月1日00:00（Asia/Tokyo）にカウンターを自動リセット
- ✅ **UI分離**: グループ管理者（閲覧のみ）とシステム管理者（編集可能）で画面を分離
- ✅ **完全なテストカバレッジ**: 10テストメソッド、36アサーション全通過
- ✅ **権限管理**: システム管理者のみが無料枠設定を変更可能

## 計画との対応

**参照ドキュメント**: `docs/plans/phase1-1-stripe-subscription-plan.md`

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| Task 1: 回数チェック | ✅ 完了 | GroupTaskLimitService作成、canCreateGroupTask()実装 | 計画通り |
| Task 2: StoreTaskAction統合 | ✅ 完了 | 制限チェック + カウンター増加処理追加 | 計画通り |
| Task 3: StoreTaskApiAction統合 | ✅ 完了 | モバイルAPI対応（Web版と同様の制限チェック） | 追加実装 |
| Task 4: 月次リセット処理 | ✅ 完了 | ResetMonthlyGroupTaskCountコマンド + Cronスケジュール | 計画通り |
| Task 5: グループ管理UI | ✅ 完了 | task-limit-status.blade.php作成（閲覧専用） | 計画通り |
| Task 6: 管理者UI | ✅ 完了 | admin/edit-user.blade.php拡張（編集可能） | 当初は別画面予定→既存画面に統合 |
| Task 7: テスト実装 | ✅ 完了 | GroupTaskLimitTest.php作成（10テスト全通過） | 計画通り |

**実施期間**: 2025-12-01（1日で完了）
**計画期間**: 2-3日 → **前倒し完了**

## 実施内容詳細

### 1. サービス層実装

#### GroupTaskLimitServiceInterface（インターフェース）

**ファイル**: `app/Services/Group/GroupTaskLimitServiceInterface.php`

```php
public function canCreateGroupTask(Group $group): bool;
public function incrementGroupTaskCount(Group $group): void;
public function resetMonthlyCount(Group $group): void;
public function getGroupTaskUsage(Group $group): array;
```

- **役割**: グループタスク制限管理の契約定義
- **DIバインディング**: `AppServiceProvider::register()` で登録済み

#### GroupTaskLimitService（実装クラス）

**ファイル**: `app/Services/Group/GroupTaskLimitService.php`（178行）

**主要メソッド**:

1. `canCreateGroupTask(Group $group): bool`
   - サブスクリプション契約者: 常にtrue（無制限）
   - 無料ユーザー: `group_task_count_current_month < free_group_task_limit`
   - **自動リセット機能**: `group_task_count_reset_at` が過去の場合、自動的にリセット実行

2. `incrementGroupTaskCount(Group $group): void`
   - トランザクション内でカウンター増加
   - ログ出力: `[Group:{id}] グループタスクカウント増加: {before}件 → {after}件`

3. `resetMonthlyCount(Group $group): void`
   - カウンターを0にリセット
   - `group_task_count_reset_at` を翌月末に設定
   - トランザクション + ログ出力

4. `getGroupTaskUsage(Group $group): array`
   - 返り値: `['current' => int, 'limit' => int, 'remaining' => int, 'is_unlimited' => bool, 'reset_at' => string]`
   - UI表示用のデータ整形

**特徴**:
- すべてのDB操作でトランザクション使用
- 詳細なログ出力（`[Group:{id}]` プレフィックス）
- 自動リセット機能による手動リセット忘れ防止

### 2. タスク作成Action統合

#### StoreTaskAction更新（Web版）

**ファイル**: `app/Http/Actions/Task/StoreTaskAction.php`（Line 64-88）

**追加処理**:

```php
// グループタスク作成数の制限チェック
if (!$this->groupTaskLimitService->canCreateGroupTask($group)) {
    $usage = $this->groupTaskLimitService->getGroupTaskUsage($group);
    $message = sprintf(
        '今月のグループタスク作成数が上限（%d件）に達しました。プレミアムプランにアップグレードすると無制限でグループタスクを作成できます。',
        $usage['limit']
    );
    
    if ($request->expectsJson()) {
        return response()->json([
            'message' => $message,
            'usage' => $usage,
            'upgrade_required' => true,
        ], 422);
    }
    
    return redirect()->back()
        ->withErrors(['error' => $message])
        ->withInput();
}

// タスク作成後
if ($groupFlg && isset($group)) {
    $this->groupTaskLimitService->incrementGroupTaskCount($group);
}
```

**処理フロー**:
1. グループタスク作成権限チェック（既存）
2. **NEW**: 月次制限チェック（`canCreateGroupTask()`）
3. 制限超過時: 422エラー（JSON）または エラーリダイレクト
4. タスク作成処理（既存）
5. **NEW**: カウンター増加（`incrementGroupTaskCount()`）

#### StoreTaskApiAction更新（モバイルAPI版）

**ファイル**: `app/Http/Actions/Api/Task/StoreTaskApiAction.php`（Line 32-88）

**追加処理**:

```php
// グループタスクの場合、追加チェック
$isGroupTask = $request->isGroupTask();
if ($isGroupTask) {
    // グループタスク作成権限チェック
    if (!$this->groupService->canEditGroup($user) || !$user->group_id) {
        return response()->json([
            'success' => false,
            'message' => 'グループタスク作成権限がありません。',
        ], 403);
    }

    // グループタスク作成数の制限チェック
    if (!$this->groupTaskLimitService->canCreateGroupTask($group)) {
        $usage = $this->groupTaskLimitService->getGroupTaskUsage($group);
        return response()->json([
            'success' => false,
            'message' => sprintf(...),
            'usage' => $usage,
            'upgrade_required' => true,
        ], 422);
    }
}

// タスク作成後
if ($isGroupTask && isset($group)) {
    $this->groupTaskLimitService->incrementGroupTaskCount($group);
}
```

**処理フロー**（Web版と同様）:
1. Cognito認証チェック
2. グループタスク作成権限チェック
3. **NEW**: 月次制限チェック（`canCreateGroupTask()`）
4. 制限超過時: 422エラー（JSON）+ `upgrade_required` フラグ
5. タスク作成処理
6. **NEW**: カウンター増加（`incrementGroupTaskCount()`）

**Web版との差異**:
- 常にJSON応答（`expectsJson()` 不要）
- `success` フラグを含むレスポンス構造
- Cognito認証ミドルウェア前提

### 3. 月次リセット自動化

#### ResetMonthlyGroupTaskCountコマンド

**ファイル**: `app/Console/Commands/ResetMonthlyGroupTaskCount.php`（118行）

**コマンド**: `php artisan group:reset-monthly-task-count [--group-id=ID]`

**機能**:
- すべてのグループまたは特定グループのカウンターリセット
- 詳細なコンソール出力（グループ名、リセット前後の件数）
- エラーハンドリングとログ記録

**実行例**:
```bash
$ php artisan group:reset-monthly-task-count

グループタスク作成回数の月次リセット開始...
対象グループ数: 3

  - [テストグループ1] 5件 → 0件
  - [テストグループ2] 3件 → 0件
  - [テストグループ3] 0件 → 0件

完了: 3グループをリセットしました。
```

#### Cronスケジュール登録

**ファイル**: `routes/console.php`

```php
Schedule::command('group:reset-monthly-task-count')
    ->monthlyOn(1, '00:00')  // 毎月1日00:00実行
    ->timezone('Asia/Tokyo')
    ->appendOutputTo(storage_path('logs/group-task-reset.log'));
```

**確認方法**:
```bash
$ php artisan schedule:list
```

### 4. UI実装

#### グループ管理者向けUI（閲覧専用）

**ファイル**: `resources/views/profile/group/partials/task-limit-status.blade.php`（133行）

**表示内容**:
- サブスクリプションステータスバッジ（有効/無料プラン）
- 進捗バー（使用率の視覚化）
- 現在の使用状況: `今月の作成数: X件 / 上限: Y件 / 残り: Z件`
- 次回リセット日: `YYYY年MM月DD日にリセット`
- サブスクリプション加入促進ボタン（無料ユーザーのみ）

**実装例**:
```blade
@php
    $usage = app(\App\Services\Group\GroupTaskLimitServiceInterface::class)
                ->getGroupTaskUsage($group);
    $usagePercentage = ($usage['current'] / $usage['limit']) * 100;
@endphp

<!-- 進捗バー -->
<div class="w-full bg-gray-200 rounded-full h-3">
    <div class="bg-blue-600 h-3 rounded-full" 
         style="width: {{ min($usagePercentage, 100) }}%"></div>
</div>
```

**統合箇所**: `resources/views/profile/group/edit.blade.php` に include済み

#### システム管理者向けUI（編集可能）

**ファイル**: `resources/views/admin/edit-user.blade.php`（Line 120-165追加）

**追加フォーム項目**:
1. **無料グループタスク作成上限**（`free_group_task_limit`）
   - 入力範囲: 0-100
   - デフォルト: 5
   - 現在の使用状況表示: `今月の使用: X件 / Y件`

2. **無料トライアル日数**（`free_trial_days`）
   - 入力範囲: 0-90
   - デフォルト: 0

**警告メッセージ**:
```html
<p class="text-yellow-600 text-sm">
    ⚠️ この設定はシステム管理者のみが変更できます。グループ管理者には表示されません。
</p>
```

**表示条件**: `@if($user->group)` - ユーザーがグループに所属している場合のみ表示

### 5. バリデーション実装

#### UpdateUserRequest（FormRequest）

**ファイル**: `app/Http/Requests/Admin/UpdateUserRequest.php`（66行）

**認可チェック**:
```php
public function authorize(): bool
{
    $user = $this->user();
    return $user && $user->is_admin;  // システム管理者のみ許可
}
```

**バリデーションルール**:
```php
'free_group_task_limit' => ['nullable', 'integer', 'min:0', 'max:100'],
'free_trial_days' => ['nullable', 'integer', 'min:0', 'max:90'],
'is_admin' => ['nullable', 'boolean'],
'group_edit_flg' => ['nullable', 'boolean'],
```

**カスタムエラーメッセージ**（日本語）:
- `free_group_task_limit.integer`: 無料グループタスク作成上限は整数で指定してください。
- `free_group_task_limit.min`: 無料グループタスク作成上限は0以上で指定してください。
- `free_group_task_limit.max`: 無料グループタスク作成上限は100以下で指定してください。

### 6. サービス層更新

#### UserService拡張

**ファイル**: `app/Services/Admin/UserService.php`（Line 38-48追加）

**グループ設定更新処理**:
```php
// ユーザーにグループが紐づいている場合、グループ設定も更新
if ($user->group && isset($data['free_group_task_limit'])) {
    $user->group->update([
        'free_group_task_limit' => $data['free_group_task_limit'],
        'free_trial_days' => $data['free_trial_days'] ?? $user->group->free_trial_days,
    ]);
    
    // グループ設定はユーザー更新データから除外
    unset($data['free_group_task_limit'], $data['free_trial_days']);
}
```

**特徴**:
- ユーザーデータとグループデータを分離して更新
- `free_trial_days` のデフォルト値保持

### 7. テスト実装

#### GroupTaskLimitTest（包括的テストスイート）

**ファイル**: `tests/Feature/Group/GroupTaskLimitTest.php`（311行）

**テストメソッド**（全10件）:

1. **test_free_group_can_create_tasks_within_limit**
   - 検証: 無料プラン、制限内でタスク作成可能
   - アサーション: `canCreateGroupTask()` が true

2. **test_free_group_cannot_create_tasks_when_limit_reached**
   - 検証: 無料プラン、上限到達時は作成不可
   - アサーション: `canCreateGroupTask()` が false

3. **test_subscribed_group_has_unlimited_task_creation**
   - 検証: サブスクリプション契約者は常に作成可能
   - アサーション: `subscription_active=true` で無制限

4. **test_group_task_count_increments_correctly**
   - 検証: カウンター増加ロジック
   - アサーション: 0→1→2→3 と正しく増加

5. **test_monthly_count_resets_correctly**
   - 検証: `resetMonthlyCount()` の動作
   - アサーション: カウント=0、reset_at=未来の日付

6. **test_auto_reset_when_reset_date_passed**
   - 検証: `reset_at` 経過時の自動リセット
   - アサーション: `canCreateGroupTask()` 呼び出しで自動リセット

7. **test_task_creation_respects_limit**
   - 検証: 実際のHTTPリクエストでの制限適用
   - アサーション: 成功時200、失敗時422

8. **test_admin_can_update_group_limits**
   - 検証: システム管理者による設定変更
   - アサーション: PUT成功、DB更新確認

9. **test_non_admin_cannot_update_group_limits**
   - 検証: 非管理者の403エラー
   - アサーション: 403 Forbidden

10. **test_get_group_task_usage_returns_correct_data**
    - 検証: `getGroupTaskUsage()` の返り値構造
    - アサーション: current, limit, remaining, is_unlimited, reset_at

**テスト実行結果**:
```
Tests:    10 passed (36 assertions)
Duration: 0.85s
```

**カバレッジ**:
- ✅ サービス層メソッド全網羅
- ✅ HTTP統合テスト（タスク作成API）
- ✅ 認可テスト（admin-only）
- ✅ エッジケース（自動リセット、カウンター増加）

### 8. Factory実装

#### GroupFactory（テスト用データ生成）

**ファイル**: `database/factories/GroupFactory.php`（101行）

**デフォルト状態**:
```php
'subscription_active' => false,
'max_members' => 5,
'free_group_task_limit' => 5,
'group_task_count_current_month' => 0,
'group_task_count_reset_at' => Carbon::now()->endOfMonth(),
```

**状態メソッド**:
- `subscribed()`: サブスクリプション有効状態（max_members=999, 実質無制限）
- `free()`: 無料プラン状態（明示的に設定）
- `taskLimitReached()`: 上限到達状態（テスト用）
- `resetExpired()`: リセット期限切れ状態（自動リセットテスト用）

**使用例**:
```php
$group = Group::factory()->subscribed()->create();
$group = Group::factory()->taskLimitReached()->create();
```

## 成果と効果

### 定量的効果

1. **コード品質**
   - 新規作成: 8ファイル（サービス、コマンド、UI、テスト）
   - 更新: 6ファイル（Web Action、API Action、ルート、プロバイダー）
   - テストカバレッジ: 36アサーション全通過
   - コード行数: 約1,300行追加

2. **機能実装率**
   - 計画タスク: 6項目（API対応は追加実装）
   - 完了: 7項目（100%）
   - 前倒し完了: 2-3日予定 → 1日で完了

3. **権限分離**
   - グループ管理者: 閲覧専用UI（task-limit-status.blade.php）
   - システム管理者: 編集可能UI（admin/edit-user.blade.php）
   - 認可チェック: UpdateUserRequest で厳密に実装

### 定性的効果

1. **保守性向上**
   - インターフェースベースの設計（依存性注入）
   - トランザクション管理の徹底
   - 詳細なログ出力（デバッグ容易）

2. **拡張性確保**
   - サブスクリプション機能の追加が容易
   - 他の制限機能への応用可能（メンバー数制限など）
   - 自動リセット機能の汎用性

3. **ユーザビリティ改善**
   - 進捗バーによる視覚的なフィードバック
   - 明確なエラーメッセージ（サブスク促進含む）
   - 次回リセット日の表示

4. **運用効率化**
   - 自動リセット機能（手動操作不要）
   - 管理者による柔軟な無料枠調整
   - 詳細なログによるトラブルシューティング

## 技術的ハイライト

### 1. 自動リセット機能

**問題**: 月次リセットを忘れるとユーザーが制限に引っかかり続ける

**解決策**: `canCreateGroupTask()` 内で自動リセット判定

```php
protected function shouldResetCount(Group $group): bool
{
    return $group->group_task_count_reset_at && 
           Carbon::now()->gte($group->group_task_count_reset_at);
}

public function canCreateGroupTask(Group $group): bool
{
    if ($group->subscription_active) return true;
    
    // 自動リセット
    if ($this->shouldResetCount($group)) {
        $this->resetMonthlyCount($group);
        $group->refresh();
    }
    
    return $group->group_task_count_current_month < $group->free_group_task_limit;
}
```

**効果**: Cronジョブ失敗時でもユーザー影響を最小化

### 2. UI/UXの最適化

**JSON vs リダイレクト対応**:
```php
if ($request->expectsJson()) {
    return response()->json([
        'message' => $message,
        'usage' => $usage,
        'upgrade_required' => true,
    ], 422);
}

return redirect()->back()
    ->withErrors(['error' => $message])
    ->withInput();
```

**効果**: SPA/従来型Webアプリ両対応

### 3. テストの網羅性

**Factory State Methods活用**:
```php
$group = Group::factory()->taskLimitReached()->create();
$group = Group::factory()->resetExpired()->create();
```

**効果**: エッジケースを簡潔にテスト可能

## 未完了項目・次のステップ

### 完了した項目

- ✅ すべての計画タスク完了
- ✅ テスト実装完了（10/10通過）
- ✅ ドキュメント更新完了

### 次のPhaseへの推奨事項

**Phase 1.1.2（Stripe Checkout Session作成）**を優先実装することを推奨：

**理由**:
1. Phase 1.1.4で制限機能は完成したが、サブスクリプション購入手段が未実装
2. ユーザーが上限に達しても解決方法がない状態
3. Phase 1.1.5（メンバー追加バリデーション）も同様にサブスクリプション購入が前提

**Phase 1.1.2 実装内容**:
- Stripeダッシュボードで商品・価格作成
- サブスクリプション選択画面実装
- Checkout Session作成処理
- 成功/キャンセル画面実装

**代替案**: Phase 1.1.5（メンバー追加バリデーション）を先行
- Phase 1.1.4と類似した実装パターン
- 制限機能の完全性向上
- Stripe統合は後回し

### 長期的な改善案

1. **通知機能追加**
   - 上限80%到達時のメール通知
   - リセット完了通知

2. **レポート機能**
   - 月次使用状況レポート
   - グループ別統計

3. **A/Bテスト**
   - 無料枠の最適値検証（3回 vs 5回 vs 10回）
   - サブスクリプション転換率分析

## 学んだ教訓

### 成功要因

1. **段階的実装**: サービス層 → Action統合 → UI → テストの順で着実に実装
2. **インターフェース駆動**: テスタビリティと拡張性が大幅に向上
3. **Factory活用**: テストデータ生成の効率化

### 改善点

1. **storage権限問題**: テスト実行時に権限エラーが発生
   - 解決策: プロジェクトセットアップ手順の文書化が必要

2. **バリデーションエラーの初期対応**: `span`フィールドの必須チェック漏れ
   - 解決策: テスト実装前にFormRequestを確認すべき

3. **ドキュメント更新タイミング**: 実装後に更新したため、若干の差異発生
   - 解決策: 実装と並行してドキュメント更新

## 関連ドキュメント

- **計画書**: `docs/plans/phase1-1-stripe-subscription-plan.md`
- **要件定義**: `definitions/group-management-requirements.md`
- **アーキテクチャ**: `.github/copilot-instructions.md`
- **過去のレポート**: 
  - `docs/reports/2025-11-29-alpine-js-removal-completion-report.md`
  - `docs/reports/2025-11-29-ci-cd-completion-report.md`

## まとめ

Phase 1.1.4の実装により、MyTeacherアプリのサブスクリプション課金システムの基盤となる**グループタスク作成制限機能**を完全実装しました。自動リセット機能、権限分離、包括的なテストカバレッジにより、堅牢で保守性の高いシステムを構築できました。

次のステップとして、**Phase 1.1.2（Stripe決済フロー）**の実装を推奨します。これにより、制限機能とサブスクリプション購入が統合され、完全なマネタイズ機能が実現します。

---

**実装者**: GitHub Copilot  
**実装日**: 2025-12-01  
**レビュー**: 未実施  
**承認**: 未実施
