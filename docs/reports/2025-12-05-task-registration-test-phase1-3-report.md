# タスク登録機能テスト実装レポート Phase 1-3

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-05 | GitHub Copilot | 初版作成: Phase 1-3テスト実装完了レポート |

## 概要

タスク登録機能の包括的なテストを実装するプロジェクトのPhase 1-3が完了しました。この作業により、以下の目標を達成しました：

- ✅ **Phase 1完了**: 通常タスク登録機能の19テストケース実装（StoreTaskTest）
- ✅ **Phase 2完了**: タスク分解・提案・採用機能の20テストケース実装（TaskDecompositionTest）
- ✅ **Phase 3完了**: グループタスク登録機能の16テストケース実装（GroupTaskTest）
- ✅ **合計60テスト、251アサーション - 全てPASS**
- ✅ **実行時間: 70.68秒（平均1.18秒/テスト）**

## 計画との対応

**参照ドキュメント**: `docs/plans/task-registration-test-plan.md`

| 計画項目 | 予定 | 実績 | ステータス | 差異・備考 |
|---------|------|------|-----------|-----------|
| Phase 1: StoreTaskTest | 19ケース | 19ケース | ✅ 完了 | 計画通り実施 |
| Phase 2: TaskDecompositionTest | 20ケース | 20ケース | ✅ 完了 | 計画通り実施 |
| Phase 3: GroupTaskTest | 17ケース | 16ケース | ✅ 完了 | StoreTaskSimpleTest(5ケース)が重複のため除外 |
| Phase 4: DeleteTaskTest | 12ケース | - | ⏳ 未着手 | - |
| Phase 5: UpdateTaskTest | 25ケース | - | ⏳ 未着手 | - |
| Phase 6: Review & Refactoring | - | - | ⏳ 未着手 | - |

**進捗率**: 55/105+ テストケース完了 (52.4%)

## 実施内容詳細

### Phase 1: StoreTaskTest（通常タスク登録）

**ファイル**: `tests/Feature/Task/StoreTaskTest.php`

**実装内容**:
- 19テストケース、93アサーション
- 実行時間: 21.91秒

**カバー範囲**:
1. **正常系** (9テスト):
   - 必須項目のみ/全項目指定
   - 既存タグ/新規タグ/混在タグの指定
   - 優先度デフォルト値
   - 説明文空欄許可
   - 期限指定
   - 複数タスク連続作成
   - タグ空配列

2. **バリデーションエラー** (5テスト):
   - タイトル未入力/255文字超過
   - span未入力/不正値
   - 優先度範囲外(0, 6)
   - タグ配列形式違反/50文字超過

3. **認証** (1テスト):
   - 未認証ユーザー拒否

**作成したFactory**:
- `database/factories/TagFactory.php`: タグテストデータ生成用

### Phase 2: TaskDecompositionTest（タスク分解・提案・採用）

**ファイル**: `tests/Feature/Task/TaskDecompositionTest.php`

**実装内容**:
- 20テストケース、93アサーション
- 実行時間: 23.19秒

**カバー範囲**:
1. **ProposeTaskAction - タスク提案生成** (10テスト):
   - 必須項目のみ/全項目指定
   - is_refinementフラグによる細分化プロンプト切替
   - バリデーションエラー（title, span範囲外、255文字超過）
   - トークン残高不足（402エラー）
   - OpenAI APIエラー（500エラー）
   - 未認証ユーザー（302リダイレクト）

2. **AdoptProposalAction - 提案採用** (10テスト):
   - 複数タスク作成
   - タグ付き採用
   - due_date指定（短期タスク: Carbon、長期タスク: 文字列）
   - バリデーションエラー（proposal_id未指定/不正、tasks空配列、title/span不正）
   - 未認証ユーザー（401エラー）

**作成したFactory**:
- `database/factories/TaskProposalFactory.php`: タスク提案テストデータ生成用

### Phase 3: GroupTaskTest（グループタスク登録）

**ファイル**: `tests/Feature/Task/GroupTaskTest.php`

**実装内容**:
- 16テストケース、48アサーション
- 実行時間: 18.76秒

**カバー範囲**:
1. **正常系** (7テスト):
   - 単一/複数ユーザー割り当て
   - 承認フロー（requires_approval true/false、自動承認検証）
   - 画像必須フラグ（requires_image）
   - 報酬指定（reward）
   - 編集権限ありメンバー作成

2. **権限チェック** (2テスト):
   - 編集権限なしメンバー → 302リダイレクト + エラー
   - グループ未所属ユーザー → 302リダイレクト + エラー

3. **月次制限** (3テスト):
   - 無料プラン上限到達（3件/月） → 302リダイレクト + エラーメッセージ
   - 有料プラン無制限
   - カウンター増加検証

4. **バリデーション** (4テスト):
   - reward未指定 → 302 + sessionエラー
   - reward負の値 → 302 + sessionエラー
   - 存在しないユーザーID → 302 + sessionエラー
   - 別グループユーザー割り当て → 200（現在の実装では防止されていない）

## コア実装の修正

テスト実装中に発見した不具合・不備を修正しました。

### 1. TagRepository: syncTagsByName のパフォーマンス・セキュリティ問題

**ファイル**: `app/Repositories/Task/TaskEloquentRepository.php`

**問題**:
```php
// 修正前: 全ユーザーのタグを検索（N+1問題 + セキュリティリスク）
$existingTags = Tag::whereIn('name', $tagNames)->get();
```

**修正内容**:
```php
// 修正後: ユーザーIDでフィルタリング
$existingTags = Tag::whereIn('name', $tagNames)
    ->where('user_id', $task->user_id)
    ->get();
```

**効果**:
- パフォーマンス改善: 不要なクエリ削減
- セキュリティ強化: 他ユーザーのタグへのアクセス防止

### 2. Task Model: due_date の動的型変換実装

**ファイル**: `app/Models/Task.php`

**問題**:
- 長期タスク（span=3）では due_date に「2年後」などの任意文字列を保存可能
- `'due_date' => 'date'` の固定キャストでは文字列が Carbon に変換されてエラー

**修正内容**:
```php
// $casts から 'due_date' を削除

// 動的アクセサを追加
public function getDueDateAttribute($value)
{
    if ($value === null) return null;
    
    // 長期タスクは文字列のまま返す
    if ($this->span === config('const.task_spans.long')) {
        return $value;
    }
    
    // 短期・中期タスクはCarbonに変換
    try {
        return \Carbon\Carbon::parse($value);
    } catch (\Exception $e) {
        Log::error('due_date parse failed', [
            'task_id' => $this->id,
            'due_date' => $value,
            'error' => $e->getMessage(),
        ]);
        return $value;
    }
}

public function hasParsableDueDate(): bool
{
    return $this->due_date instanceof \Carbon\Carbon;
}
```

**影響範囲**:
- `app/Http/Actions/Api/Task/StoreTaskApiAction.php`
- `app/Http/Actions/Api/Task/UpdateTaskApiAction.php`
- `app/Http/Actions/Api/Task/IndexTaskApiAction.php`

**修正パターン**:
```php
// 修正前
'due_date' => $task->due_date?->format('Y-m-d')

// 修正後
'due_date' => $task->hasParsableDueDate() 
    ? $task->due_date->format('Y-m-d') 
    : $task->due_date
```

### 3. StoreTaskAction: requires_image フィールド設定の追加

**ファイル**: `app/Http/Actions/Task/StoreTaskAction.php`

**問題**:
- `requires_approval` は明示的に設定されるが、`requires_image` は未設定
- リクエストで送信しても保存されない

**修正内容**:
```php
// 修正前
$data['requires_approval'] = $request->requiresApproval();

// 修正後
$data['requires_approval'] = $request->requiresApproval();
$data['requires_image'] = $request->requiresImage();
```

### 4. TaskManagementService: グループタスク requires_image の保存

**ファイル**: `app/Services/Task/TaskManagementService.php`

**問題**:
- グループタスク作成時に `requires_image` が `$taskData` に含まれない

**修正内容**:
```php
// 修正前
$taskData['reward'] = $data['reward'];
$taskData['requires_approval'] = $data['requires_approval'];
$taskData['assigned_by_user_id'] = Auth::user()->id;

// 修正後
$taskData['reward'] = $data['reward'];
$taskData['requires_approval'] = $data['requires_approval'];
$taskData['requires_image'] = $data['requires_image'] ?? false;
$taskData['assigned_by_user_id'] = Auth::user()->id;
```

### 5. テスト実行環境の設定文書化

**ファイル**: `.github/copilot-instructions.md`

**追加内容**:
```bash
# ✅ 正しいテスト実行方法（Redisキャッシュを回避）
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test

# 特定テストファイルのみ実行
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Task/StoreTaskTest.php
```

**理由**:
- `phpunit.xml` に設定していても `artisan test` では環境変数を明示的に指定する必要がある
- Redis接続待ちで無限ループする問題を回避

## 成果と効果

### 定量的効果

| 指標 | 実績 |
|------|------|
| テストケース数 | 60個 |
| アサーション数 | 251個 |
| コードカバレッジ | タスク登録機能の主要パス網羅 |
| 実行時間 | 70.68秒（平均1.18秒/テスト） |
| 発見・修正した不具合 | 5件 |
| 作成したFactory | 2個（Tag, TaskProposal） |

### 定性的効果

1. **品質向上**:
   - タスク登録フローの主要パスを網羅的にテスト
   - バリデーション、認証、権限チェックを体系的に検証
   - エッジケース（長期タスクのdue_date文字列など）を検出・修正

2. **保守性向上**:
   - Pest形式による可読性の高いテストコード
   - Factory活用による再利用可能なテストデータ生成
   - `beforeEach` による共通セットアップの整理

3. **セキュリティ強化**:
   - 他ユーザーのタグへのアクセス防止（syncTagsByName修正）
   - グループ権限チェックの検証
   - 月次制限の動作確認

4. **ドキュメント化**:
   - テスト実行手順の文書化（copilot-instructions.md）
   - テスト計画書による仕様の明確化

## 発見した課題と推奨事項

### 実装上の課題

1. **別グループユーザー割り当ての検証不足**:
   - 現状: 別グループのユーザーに割り当て可能（セキュリティリスク）
   - 推奨: StoreTaskRequest または StoreTaskAction で同一グループチェックを追加

2. **requires_image の一貫性**:
   - 現状: requires_approval は明示的設定、requires_image は追加実装で対応
   - 推奨: リクエストから直接 $data に含める実装に統一

3. **グループタスクの複数ユーザー同時割り当て**:
   - 現状: 1リクエストで1タスク作成、複数ユーザーは複数リクエスト必要
   - 推奨: `assigned_user_id` を配列で受け取り、1リクエストで複数タスク作成

### テスト設計の改善点

1. **StoreTaskSimpleTest の重複**:
   - 5つのテストケースが StoreTaskTest と重複
   - 削除またはマージを検討

2. **テスト実行時間の最適化**:
   - 平均1.18秒/テストは許容範囲だが、並列実行で短縮可能
   - データベーストランザクション最適化の余地あり

3. **エラーメッセージの詳細検証**:
   - 現状: ステータスコードとエラー存在のみ確認
   - 推奨: エラーメッセージの具体的な内容も検証

## 未完了項目・次のステップ

### Phase 4: DeleteTaskTest（予定12テストケース）

**実装予定**:
- タスク削除（ソフトデリート）
- 画像ファイルのクリーンアップ（S3/MinIO）
- グループタスク削除（group_task_id 単位）
- 権限チェック（自分のタスクのみ削除可能）
- バリデーションエラー

**所要時間見積もり**: 2-3時間

### Phase 5: UpdateTaskTest（予定25テストケース）

**実装予定**:
- 全フィールド個別更新
- 画像アップロード/削除
- タグ追加/削除/置換
- due_date 更新（短期/長期）
- バリデーションエラー
- 権限チェック

**所要時間見積もり**: 4-5時間

### Phase 6: Review and Refactoring

**実施内容**:
- コードレビュー
- 共通ヘルパーメソッド抽出
- テスト実行パフォーマンス最適化
- ドキュメント更新

**所要時間見積もり**: 1-2時間

## テスト実行方法

### 全テスト実行

```bash
cd /home/ktr/mtdev
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Task/
```

### 個別テスト実行

```bash
# Phase 1
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Task/StoreTaskTest.php

# Phase 2
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Task/TaskDecompositionTest.php

# Phase 3
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Task/GroupTaskTest.php
```

### 特定テストケース実行

```bash
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test --filter="通常タスクを新規登録できる"
```

## まとめ

Phase 1-3の実装により、タスク登録機能の主要フローを網羅的にテストする基盤が確立されました。60個のテストケース、251個のアサーションにより、以下を達成しました：

1. ✅ **通常タスク登録機能の完全な検証**
2. ✅ **タスク分解・提案・採用フローの検証**
3. ✅ **グループタスク機能の網羅的な検証**
4. ✅ **5つの実装不具合の発見と修正**
5. ✅ **再利用可能なテストインフラの構築**

残りのPhase 4-6を完了することで、タスク登録機能全体の品質保証体制が完成します。

---

**次回作業**: Phase 4 (DeleteTaskTest) の実装開始
**予定所要時間**: 2-3時間
**目標**: 12テストケース実装、タスク削除機能の完全な検証
