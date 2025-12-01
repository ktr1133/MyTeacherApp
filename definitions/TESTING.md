# テスト実行ガイド

## テスト実行方法

### 全テストを実行

```bash
php artisan test
```

または

```bash
./vendor/bin/phpunit
```

### 特定のテストスイートを実行

```bash
# Unit tests のみ
php artisan test --testsuite=Unit

# Feature tests のみ
php artisan test --testsuite=Feature
```

### 特定のテストクラスを実行

```bash
php artisan test tests/Unit/Repositories/Batch/ScheduledTaskEloquentRepositoryTest.php
```

### 特定のテストメソッドを実行

```bash
php artisan test --filter スケジュールタスクを作成できる
```

### カバレッジレポート生成

```bash
php artisan test --coverage
```

詳細なHTMLレポート:

```bash
./vendor/bin/phpunit --coverage-html coverage
```

## テストデータベース

テストは自動的にSQLiteのインメモリデータベースを使用します。
設定は `phpunit.xml` で定義されています。

## モックの使用

Mockeryを使用してリポジトリやサービスをモック化しています。

```php
$this->scheduledTaskRepository = Mockery::mock(ScheduledTaskRepositoryInterface::class);
```

## ファクトリ

テストデータの作成にはLaravelのファクトリを使用しています。

```php
$user = User::factory()->create(['group_id' => 1]);
$scheduledTask = ScheduledTask::factory()->create();
```

## テスト項目

### Repository Tests
- CRUD操作
- データ取得・検索
- 実行履歴の記録と取得

### Service Tests
- ビジネスロジック
- スケジュール判定
- タスク作成処理
- エラーハンドリング

### Feature Tests
- HTTP リクエスト/レスポンス
- 認証・認可
- バリデーション
- セッション

### Command Tests
- コマンド実行
- 出力確認
- データベース変更の確認

## 継続的インテグレーション

GitHub Actionsで自動テストを実行できます（設定ファイルは別途作成）。

## トラブルシューティング

### テストが失敗する場合

1. データベースマイグレーションを確認
2. ファクトリの定義を確認
3. 環境変数を確認
4. キャッシュをクリア: `php artisan config:clear`