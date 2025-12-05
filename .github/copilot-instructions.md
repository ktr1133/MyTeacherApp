# MyTeacher - AIタスク管理プラットフォーム

Laravel 12 + Docker構成。**Action-Service-Repositoryパターン**（従来のコントローラーなし）を採用。OpenAI・Stable Diffusion統合、トークンシステム、AI生成アバター機能を実装。

## プロジェクト構造（重要）

**リポジトリルート変更**: `/home/ktr/mtdev/` がGitルート（変更前: `laravel/` がルート）

**重要**: リポジトリルート移行により、Laravelアプリケーション本体は `/home/ktr/mtdev/` 直下に配置されています。

```
/home/ktr/mtdev/                    # ← リポジトリルート = Laravelアプリケーションルート
├── app/                        # Laravelアプリケーション
│   ├── Helpers/                # ヘルパークラス（AuthHelper等）
│   ├── Http/Actions/           # Invokableアクション（コントローラー代替）
│   ├── Services/               # ビジネスロジック（必ずインターフェース付き）
│   ├── Repositories/           # データアクセス（必ずインターフェース付き）
│   └── Http/Responders/        # レスポンス整形
├── routes/                     # ルート定義
│   ├── web.php                 # Web routes
│   └── api.php                 # API routes
├── config/                     # 設定ファイル
├── database/                   # マイグレーション、シーダー
├── definitions/                # プロジェクトドキュメント
├── docs/                       # 技術ドキュメント、レポート
├── infrastructure/             # Terraform、運用スクリプト
├── services/                   # マイクロサービス（削除予定）
├── laravel/                    # ⚠️ 旧構造の残骸（使用しない）
├── composer.json               # Composer設定（ルート直下）
├── package.json                # npm設定
├── artisan                     # Artisanコマンド
└── vendor/                     # Composer依存関係
```

**コマンド実行は `/home/ktr/mtdev/` から** - Dockerコンテナ使用時は `/var/www/html/` にマウント

**注意事項**:
- `laravel/` ディレクトリは旧構造の残骸で、現在は使用していません
- 新規ファイルは必ず `/home/ktr/mtdev/app/` 配下に作成
- `composer` コマンドは `/home/ktr/mtdev/` から実行

## 不具合対応方針（重要）

**原則**: 不具合が発生した際は推測による修正を行わず、必ずログや実行結果に基づいて原因を特定してから修正を実施する。

### 不具合対応手順

1. **ログ・エラー情報の収集**
   ```bash
   # アプリケーションログ確認（日次ローテーション形式）
   tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
   # または最新のログファイルを自動取得
   tail -f storage/logs/$(ls -t storage/logs/laravel-*.log | head -1)
   
   # GitHub Actionsログ確認  
   gh run view [ID] --log
   
   # Docker/ECSログ確認
   docker logs [container_id]
   aws logs get-log-events --log-group-name [name]
   ```

2. **デバッグ情報の追加**
   - 特定が困難な場合は、処理の各ステップにデバッグログを仕込む
   - GitHub Actionsには詳細な検証ステップを追加
   - Laravel処理にはLog::info()による状態出力を追加
   - 一時的なverbose出力やdump()での変数確認を活用

3. **段階的な問題切り分け**
   - 最小限の再現ケースを作成
   - 関連するコンポーネントを1つずつ検証
   - 依存関係やデータフローを詳細に追跡

4. **修正後の検証（必須）**
   - ログベースでの動作確認
   - **修正部分に関連する統合テストの実行**（必須）
   - 同様の問題を防ぐためのテスト追加
   - ドキュメント・手順書の更新

   **テスト実行例**:
   ```bash
   # 特定のテストクラスのみ実行（修正に関連するテスト）
   CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test --filter="AuthenticationTest|ProfileTest"
   
   # 修正に関連するディレクトリ全体のテスト実行
   CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Auth/
   
   # 全テスト実行（重大な修正の場合）
   CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test
   ```

### 禁止事項

- ❌ エラーメッセージを読まずに「よくありそうな修正」を適用
- ❌ ログを確認せずに設定値を推測で変更
- ❌ Stack Overflowの解決策をそのまま適用
- ❌ 「動いたからOK」で根本原因を放置
- ❌ **修正後にテストを実行せずにコミット**

この方針により、確実で持続可能な問題解決を実現し、同様の問題の再発を防止する。

## コード修正時の遵守事項（重要）

**原則**: ソースコードの修正作業が完了した後は、必ず全体を通した観点でチェックを実施する。

### 全体チェックの手順

1. **構文・論理的整合性の確認**
   - 修正したファイルを全体的に読み直し、重複や矛盾がないか確認
   - ステップ番号、変数名、関数名などの連番・命名規則が一貫しているか検証
   - 条件分岐やループの論理が正しく機能するか確認

2. **依存関係の検証**
   - 修正箇所が他の部分に影響を与えていないか確認
   - インターフェース、クラス、メソッドの参照が正しいか検証
   - IDや参照名が正しく設定され、他の箇所から参照可能か確認

3. **実装パターンの統一性**
   - プロジェクトの既存コーディング規約に従っているか確認
   - 類似機能との実装方法が統一されているか検証
   - ドキュメント・コメントが最新の実装を反映しているか確認

4. **エッジケースの考慮**
   - エラーハンドリングが適切に実装されているか確認
   - null/空値/境界値などのエッジケースが考慮されているか検証
   - タイムアウト、リトライ、ロールバックなどの例外処理が実装されているか確認

5. **静的解析ツールによる検証（必須）**
   - **Intelephense**: IDEの静的解析ツールで警告やエラーがないか確認
   - **未使用変数・インポート**: 不要なuse文、未使用の変数を削除
   - **未定義メソッド・プロパティ**: 存在しないメソッドやプロパティへの参照を修正
   - **型不一致**: 引数・戻り値の型が一致しているか検証
   - **名前空間エラー**: use文の漏れ、誤った名前空間参照を修正
   - **DocBlock検証**: PHPDocの型定義が実装と一致しているか確認
   
   **チェック手順**:
   ```bash
   # VSCodeのProblemsパネルで警告・エラーを確認
   # または静的解析ツールを実行
   vendor/bin/phpstan analyse app/ --level=5
   ```
   
   **対応すべき警告・エラー**:
   - ❌ Undefined method/property
   - ❌ Type mismatch in parameter/return
   - ❌ Unused variable/import
   - ❌ Missing use statement
   - ⚠️ PHPDoc type mismatch（推奨）
   
   **無視してよいケース**:
   - ✅ Laravelのマジックメソッド（`Model::factory()`等）でfalse positive
   - ✅ 動的プロパティ（`$model->dynamic_property`）で意図的な場合

### 全体チェックの具体例

**NG例（チェック不足）**:
```yaml
# Step 9が重複していることに気づかずコミット
- name: Update ECS service
  run: |
    echo "✅ Migrations completed successfully"

- name: Update ECS service
  id: service-update
  run: |
    echo "✅ ECS service update initiated"
```

**OK例（全体チェック実施）**:
```yaml
# 修正後、全ファイルを読み直して重複・番号不整合を発見
# → 重複削除、ステップ番号を1-15の連番に修正してからコミット
- name: Update ECS service
  id: service-update
  run: |
    echo "✅ ECS service update initiated"
```

### チェックツールの活用

```bash
# ステップ番号の連番確認
grep -n "^      # [0-9]" .github/workflows/*.yml

# ID重複チェック
grep -n "id: " .github/workflows/*.yml | sort

# インターフェース実装確認
grep -r "implements.*Interface" app/

# 未使用変数・メソッド検出
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse
```

### 禁止事項

- ❌ 修正箇所だけを確認してコミット（全体の整合性未確認）
- ❌ ステップ番号や命名規則の不整合を放置
- ❌ 重複コードや矛盾する実装を見逃す
- ❌ 「動いたからOK」で構造的な問題を放置
- ❌ Intelephenseの警告・エラーを確認せずにコミット

この方針により、品質の高いコード変更を実現し、レビュー・デバッグコストを削減する。

## アーキテクチャ: Action-Service-Repositoryパターン

**従来のコントローラーは存在しない。** すべてのHTTPリクエストは以下のフローを経由:

```
Route → Action (__invoke) → Service → Repository → Model
                  ↓
              Responder → Response
```

### 実装ルール

1. **Action** (`/home/ktr/mtdev/app/Http/Actions/{ドメイン}/`): 単一責任のInvokableクラス
   - 命名: `{動詞}{対象}Action` (例: `StoreTaskAction`, `ApproveTaskAction`)
   - `public function __invoke()` メソッド必須
   - ビジネスロジックは書かない - Serviceに委譲
   - データ取得・登録・更新・削除も書かない - Serviceを経由してRepositoryに委譲

2. **Service** (`/home/ktr/mtdev/app/Services/{ドメイン}/`): ビジネスロジック（データ整形専門）
   - **必ずインターフェースを先に作成**: `{機能}ServiceInterface` + `{機能}Service`
   - `AppServiceProvider::register()` でバインド: `$this->app->bind(Interface::class, Implementation::class)`
   - コンストラクタでRepositoryインターフェース経由で注入
   - **責務**: Repositoryから取得したデータの整形・加工・ビジネスルール適用のみ
   - **禁止**: DB操作（Eloquent ORM直接呼び出し）、外部API直接呼び出し、データCRUD処理
   - **例外**: Modelに関連しないクエリ（例: `auth()->user()`）はServiceに記述可能

3. **Repository** (`/home/ktr/mtdev/app/Repositories/{ドメイン}/`): データアクセス（CRUD専門）
   - **必ずインターフェースを先に作成**: `{対象}RepositoryInterface` + `{対象}EloquentRepository`
   - `AppServiceProvider::register()` でバインド
   - **責務**: データベース操作（取得・作成・更新・削除）、外部API呼び出し（Stripe等）
   - **命名規則**: メソッド名は`create`, `update`, `delete`, `find`, `get`等のCRUD動詞を使用
   - **返り値**: Eloquentモデル、Collection、または生データ（整形しない）
   - **参考**: `TaskEloquentRepository`, `TaskRepositoryInterface`を参照

4. **Responder** (`/home/ktr/mtdev/app/Http/Responders/{ドメイン}/`): レスポンス整形
   - **インターフェース不要** - 直接クラスをActionに注入
   - **新規コードでは必ず使用** (一部レガシーコードは直接返却 - 触る際にリファクタリング)

### 責務分離の具体例

```php
// ❌ NG: ServiceでDB操作
class TaskService {
    public function createTask($data) {
        return Task::create($data); // NG: Eloquent直接呼び出し
    }
}

// ✅ OK: Repository層でDB操作
class TaskEloquentRepository implements TaskRepositoryInterface {
    public function create(array $data): Task {
        return Task::create($data); // OK: RepositoryがDB操作
    }
}

class TaskService implements TaskServiceInterface {
    public function __construct(
        protected TaskRepositoryInterface $repository
    ) {}
    
    public function createTask(User $user, array $data): Task {
        // データ加工・整形
        $data['user_id'] = $user->id;
        $data['priority'] = $data['priority'] ?? 3;
        
        // Repository経由でDB操作
        return $this->repository->create($data);
    }
}
```

### 実装例（完全版）

```php
// routes/web.php
Route::post('/tasks', StoreTaskAction::class)->name('tasks.store');

// StoreTaskAction.php
public function __construct(
    protected TaskManagementServiceInterface $taskService,  // ✅ インターフェース
    protected TaskResponder $responder  // ✅ 直接クラス注入
) {}

public function __invoke(StoreTaskRequest $request): RedirectResponse {
    $task = $this->taskService->createTask($request->user(), $request->validated());
    return $this->responder->success($task);
}

// TaskManagementService.php
public function __construct(
    protected TaskRepositoryInterface $repository  // ✅ Repository注入
) {}

public function createTask(User $user, array $data): Task {
    // データ整形（Serviceの責務）
    $data['user_id'] = $user->id;
    $data['priority'] = $data['priority'] ?? 3;
    
    // Repository経由でDB操作
    return $this->repository->create($data);
}

// TaskEloquentRepository.php
public function create(array $data): Task {
    // DB操作のみ（Repositoryの責務）
    return Task::create($data);
}
```

## 技術スタック

- **バックエンド**: PHP 8.3, Laravel 12, PostgreSQL 16, MinIO (S3互換), Stripe (Cashier)
- **フロントエンド**: **Vanilla JSのみ** (package.jsonにAlpine.jsあるが**使用禁止** - iPad互換性問題), Tailwind CSS 3 + Vite, Chart.js
- **AI**: OpenAI API (タスク分解, DALL-E), Replicate API (Stable Diffusion - アバター生成)
- **テスト**: Pest (PHPUnitラッパー), SQLiteインメモリ
- **キュー**: Databaseドライバ、`php artisan queue:work` で処理

## 1. 重要なワークフロー

### Docker環境の注意事項（重要）

**コンテナマウント構造**: Dockerコンテナは**旧ディレクトリ構造**を使用しています。

```
ホスト側: /home/ktr/mtdev/laravel/ → コンテナ内: /var/www/html/
```

**現在の状況**:
- リポジトリ構造は `/home/ktr/mtdev/` をルートに変更済み
- Dockerコンテナは旧構造（`/home/ktr/mtdev/laravel/`）をマウント
- `laravel/` ディレクトリは空の残骸で、実際のアプリケーションは `/home/ktr/mtdev/` 直下

**コマンド実行方法**:
```bash
# ❌ コンテナ内では実行できない（マウントが空）
docker exec mtdev-app-1 php artisan migrate

# ✅ ホスト側で実行（DB接続情報を上書き）
cd /home/ktr/mtdev
DB_HOST=localhost DB_PORT=5432 php artisan migrate
DB_HOST=localhost DB_PORT=5432 php artisan db:seed
DB_HOST=localhost DB_PORT=5432 php artisan test
```

**理由**: `.env` の `DB_HOST=db` はコンテナ間通信用。ホスト側からは `localhost` で接続。

### セットアップ・開発

```bash
# 初回セットアップ (/home/ktr/mtdev/ から実行)
cd /home/ktr/mtdev
composer install
php artisan key:generate
DB_HOST=localhost DB_PORT=5432 php artisan migrate
npm install && npm run build

# 開発サーバー起動（並列: サーバー、キュー、ログ、Vite HMR）
composer dev

# アセット再ビルド（CSS/JS変更後）
cd /home/ktr/mtdev
npm run build
php artisan optimize:clear
```

### テスト

**重要**: テスト実行時は必ず`CACHE_STORE=array`を指定してRedis接続を回避する。

```bash
# ✅ 正しいテスト実行方法（Redisキャッシュを回避）
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test

# 全テスト実行 (Pest)
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test

# 特定テストファイルのみ実行
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test tests/Feature/Task/StoreTaskTest.php

# 特定テストケースのみ実行（フィルタ）
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test --filter="通常タスクを新規登録できる"

# カバレッジレポート
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test --coverage

# 最初の失敗で停止
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test --stop-on-failure

# エラー詳細表示
CACHE_STORE=array DB_HOST=localhost DB_PORT=5432 php artisan test --display-errors

# ❌ NG: 環境変数なしで実行するとRedis接続でハングする
php artisan test  # Redis接続待ちで無限ループ
```

**注意**: `phpunit.xml`に`CACHE_STORE=array`が設定されているが、`artisan test`コマンドでは環境変数を明示的に指定する必要がある。

### テストデータ作成時の注意事項（重要）

**原則**: テストコードはPest形式で記述し、テストやFactoryでモデルのデータを作成する際は、必ず対象モデルの実際のカラム構造を確認する。

1. **カラムの存在確認**
   - テストコード内で`Model::factory()->create(['column' => 'value'])`を使う前に、マイグレーションファイルまたはモデルの`$fillable`を確認
   - 存在しないカラムを指定すると`SQLSTATE[HY000]: General error: table has no column named XXX`エラーが発生
   - 例: `'status'`カラムが存在しないのに指定してエラー（TaskApiTestで発生）

2. **Factoryのカラム定義**
   - Factoryクラスの`definition()`メソッドは、モデルの`$fillable`プロパティから取得した実際のカラムのみを定義する
   - マイグレーションファイルを参照し、存在するカラムのみを含める
   - リフレクションを使用してモデルから動的にカラムを取得することを推奨:
     ```php
     // モデルのfillableプロパティから取得
     $fillable = (new Task())->getFillable();
     
     // またはマイグレーションファイルを直接確認
     // database/migrations/YYYY_MM_DD_*_tasks.php
     ```

3. **マイグレーションとの整合性確保**
   - Factory作成前に必ず対象テーブルのマイグレーションファイルを読む
   - `$table->string('column_name')`として定義されているカラムのみをFactoryに含める
   - 仮想カラム（アクセサ/ミューテータ）やリレーションはFactoryに含めない

4. **検証手順**
   ```bash
   # マイグレーションファイルでカラム確認
   cat database/migrations/*_create_tasks_table.php | grep '\$table->'
   
   # モデルのfillableプロパティ確認
   grep -A 20 'protected \$fillable' app/Models/Task.php
   ```

5. **CI/CDパイプラインへの負荷低減**
   - CI/CDの速度を重視し、時間がかかる方式は避けること

6. **依存性の活用**
   - ServiceやRepositoryでモデルのカラムを動的に取得するユーティリティクラスを作成し、Factoryで再利用可能にする

**禁止事項**:
- ❌ モデルの構造を確認せずに推測でカラムを指定
- ❌ 他のテストコードからコピーしたカラム名をそのまま使用
- ❌ APIレスポンスに含まれる仮想属性をFactoryに含める
- ❌ マイグレーション未実施のカラムをFactoryに定義

この方針により、「カラムが存在しない」エラーを事前に防止し、テストの信頼性を向上させる。

## 2. ドメイン固有パターン

### グループタスク

複数ユーザーへの同時タスク割当、承認フロー付き:
- `group_task_id` (UUID) で関連タスクをグループ化
- `assigned_by_user_id` ≠ `user_id` (タスク所有者)
- `requires_approval` フラグで自動承認を制御

```php
if ($request->isGroupTask()) {
    $data['group_task_id'] = (string) Str::uuid();
    $task = $this->taskService->createTask($user, $data, $groupFlg = true);
    
    if (!$task->requires_approval) {
        $this->taskApprovalService->approveTask($task, $approver);
    }
}
```

### スケジュールタスク（Cron）

スケジュールに基づいてタスクを自動生成。祝日対応、ランダム割当機能:

```bash
# コマンド
php artisan batch:execute-scheduled-tasks  # 全実行
php artisan batch:execute-task {id}        # 特定タスク実行
php artisan batch:list-tasks --group=1     # タスク一覧

# モデル
ScheduledGroupTask       # スケジュール定義
ScheduledTaskExecution   # 実行履歴
Holiday                  # 祝日マスタ（キャッシュ付き）
```

Cron設定: `* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1`

### トークンシステム

タスク報酬 + AI利用コスト管理:
- トランザクション種別: `consume`, `purchase`, `grant`, `free_reset`, `admin_adjust`, `ai_usage`, `refund`
- トークン関連の複数テーブル更新は必ず `DB::transaction()` を使用
- CHECK制約: 許可された種別のみ（`token_transactions.type` 参照）

### AI統合

**OpenAI** (`App\Services\AI\OpenAIService`):
```php
$result = $openAIService->requestDecomposition($title, $context);
$tokenService->consumeTokens($user, $result['usage']['total_tokens'], 'AI機能: タスク分解');
```

**Stable Diffusion** (`App\Services\AI\StableDiffusionService` - Replicate API経由):
```php
// アバター生成（ジョブをディスパッチ - 長時間処理）
GenerateAvatarImagesJob::dispatch($avatar->id);

// ジョブ内:
$result = $sdService->generateImage($prompt, $seed, ['width' => 512, 'height' => 512]);
if ($avatar->is_transparent) {
    $result = $sdService->removeBackground($result['url']);
}
```

### アバターシステム

AI生成教師キャラクター、コンテキストに応じたコメント表示:
- **データ**: `TeacherAvatar` (一貫性のためseed保存), `AvatarImage` (ポーズ+表情), `AvatarComment` (イベント)
- **イベント**: `config('const.avatar_events')` - `task_created`, `task_completed` など
- **生成**: 非同期ジョブで8画像作成（全身+バスト × 4表情）

### ファイルストレージ（MinIO/S3）

```php
// アップロード
$path = Storage::disk('s3')->putFile('task_approvals', $image, 'public');

// 削除
Storage::disk('s3')->delete($path);

// URL取得
$url = Storage::disk('s3')->url($path);  // 公開URL
$url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));  // 署名付きURL
```

命名規則: `{カテゴリ}/{ユーザーID}/{ファイル名}` (例: `avatars/123/smile_bust_1234567890.png`)

## 3. コーディング規約

### 命名規則
- PHP: `camelCase` (変数・メソッド), `PascalCase` (クラス)
- DB: `snake_case` (カラム・テーブル)
- Action: `{動詞}{対象}Action`
- Service/Repository: `{機能}Service(Interface)` / `{対象}Repository(Interface)`
- Responder: `{機能}Responder` (インターフェース不要)

### PHPDoc必須
クラス、メソッド、プロパティは必ずドキュメント化:
```php
/**
 * タスクを作成する
 * 
 * @param User $user タスク所有者
 * @param array $data タスクデータ
 * @return Task 作成されたタスク
 * @throws \RuntimeException トークン不足の場合
 */
public function createTask(User $user, array $data): Task
```

### エラーハンドリング
```php
try {
    DB::transaction(function () use ($data) {
        // 複数テーブル更新
    });
    return redirect()->route('success')->with('success', '処理が完了しました。');
} catch (\Exception $e) {
    Log::error('処理エラー', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    return redirect()->back()->withErrors(['error' => '処理に失敗しました。'])->withInput();
}
```

### N+1問題対策
```php
// ❌ NG
$tasks = Task::where('user_id', $userId)->get();
foreach ($tasks as $task) {
    echo $task->user->name;  // N+1発生!
}

// ✅ OK
$tasks = Task::with(['user', 'images', 'tags'])->where('user_id', $userId)->get();
```

## 4. 主要ファイル・ディレクトリ

| パス | 説明 |
|------|------|
| `routes/web.php` | ルート定義（Actionを直接参照、use文必須） |
| `app/Providers/AppServiceProvider.php` | DIバインディング（Interface ⇔ Implementation） |
| `app/Console/Kernel.php` | スケジューラー設定（`schedule()` メソッド） |
| `app/Http/Actions/{ドメイン}/` | Invokableアクション（`__invoke()` 必須） |
| `app/Services/{ドメイン}/` | ビジネスロジック（必ずInterface付き、データ整形のみ） |
| `app/Repositories/{ドメイン}/` | データアクセス（必ずInterface付き、CRUD実行） |
| `app/Http/Responders/{ドメイン}/` | レスポンス整形（インターフェース不要） |
| `app/Http/Requests/{ドメイン}/` | FormRequest（バリデーション定義） |
| `app/Jobs/` | 非同期ジョブ（`GenerateAvatarImagesJob` など） |
| `config/const.php` | 定数定義（イベント、トークン種別、ステータス） |
| `config/filesystems.php` | S3/MinIO設定 |
| `config/avatar-options.php` | アバター生成オプション |
| `database/migrations/` | マイグレーションファイル（命名: `YYYY_MM_DD_*`) |
| `definitions/*.md` | 機能要件定義書（リポジトリルート） |
| `infrastructure/` | Terraform、運用スクリプト、レポート |
| `services/` | マイクロサービス（Task Service等） |
| `docker-compose.yml` | DB:5432, App:8080, MinIO:9100/9101 |

## 5. 要件定義管理

機能仕様は `definitions/*.md` に保存・更新:
1. **更新履歴** セクションを冒頭に配置
2. 既存コンテンツに追記（置き換えない）
3. フォーマット: `# {画面名} 要件定義書 → ## 更新履歴 → ## 1. 概要 ...`

## アンチパターン

```php
// ❌ Actionにロジック・DB操作
public function __invoke(Request $request) {
    $task = Task::create($request->all());  // 直接モデル操作!
}

// ❌ ServiceにDB操作
class TaskService implements TaskServiceInterface {
    public function createTask($data) {
        return Task::create($data);  // NG: ServiceがDB操作
    }
}

// ❌ インターフェースなし
class TaskService {}
$this->app->bind(TaskService::class, TaskService::class);

// ❌ Responderなし（新規コードのみ - レガシーは許容）
public function __invoke(Request $request) {
    return view('tasks.index', compact('tasks'));  // Responder経由すべき
}

// ✅ 正しい実装（Service-Repositoryの責務分離）
// Repository: DB操作
public function create(array $data): Task {
    return Task::create($data);
}

// Service: データ整形 + Repository呼び出し
public function createTask(User $user, array $data): Task {
    $data['user_id'] = $user->id;
    $data['priority'] = $data['priority'] ?? 3;
    return $this->repository->create($data);
}

// Action: Serviceに委譲 + Responderで返却
public function __invoke(StoreTaskRequest $request) {
    $task = $this->taskService->createTask($request->user(), $request->validated());
    return $this->responder->success($task);
}
```

## 6. 環境変数・設定

### 必須環境変数
```bash
# AI統合
OPENAI_API_KEY=sk-...          # OpenAI API (タスク分解, DALL-E)
REPLICATE_API_TOKEN=r8_...     # Replicate (Stable Diffusion)

# MinIO/S3
AWS_ACCESS_KEY_ID=minio        # docker-compose.ymlと同期
AWS_SECRET_ACCESS_KEY=minio123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=myteacher
AWS_ENDPOINT=http://s3:9100    # Docker内部通信
AWS_URL=http://localhost:9100  # 外部アクセス
AWS_USE_PATH_STYLE_ENDPOINT=true

# Stripe (決済)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_TEST_MODE=true

# トークン設定
TOKEN_FREE_MONTHLY=1000000     # 月次無料枠
TOKEN_LOW_THRESHOLD=200000     # 警告閾値
```

### 環境依存の注意点
- **Docker**: コンテナ内では `/var/www/html/`、ホストでは `/home/ktr/mtdev/laravel/`
- **S3エンドポイント**: コンテナ間通信は `http://s3:9100`、ブラウザは `http://localhost:9100`
- **DB接続**: 本番はPostgreSQL、テストはSQLiteインメモリ (`phpunit.xml` で自動切替)

## 7. デバッグ・トラブルシューティング

### ログ確認
```bash
# リアルタイムログ監視（Pail）
composer dev  # 自動でpail起動

# 個別確認（日次ローテーション形式）
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log  # アプリケーション
tail -f /var/log/laravel-scheduler.log              # スケジューラー（要root）
tail -f storage/logs/scheduled-tasks.log            # バッチ実行

# キューログ
php artisan queue:failed                            # 失敗ジョブ一覧
php artisan queue:retry {job-id}                    # ジョブ再実行
```

### よくあるエラー
| エラー | 原因 | 対処 |
|--------|------|------|
| `Class Interface not found` | DIバインディング漏れ | `AppServiceProvider::register()` に追加 |
| `SQLSTATE[23503]` (外部キー) | 関連データ削除忘れ | `onDelete('cascade')` 追加 or 手動削除 |
| `Target class [XxxAction] does not exist` | laravel/routes/web.php のuse文漏れ | `use App\Http\Actions\...` 追加 |
| `Call to undefined method` | Eager Loading不足 | `with(['relation'])` 追加 |
| S3エラー | エンドポイント設定ミス | `.env` の `AWS_ENDPOINT` 確認 |

### キューが動かない場合
```bash
# キューワーカー起動確認
ps aux | grep queue:work

# 手動起動
php artisan queue:work --tries=3

# ジョブテーブルクリア（開発環境）
php artisan queue:flush
```

## レポート作成規則（重要）

作業完了時のレポート作成は以下の規則を厳守する：

### ファイル命名規則

```
docs/reports/YYYY-MM-DD-タイトル-report.md
```

**例**:
- `2025-11-29-microservice-removal-completion-report.md`
- `2025-11-27-alpine-js-removal-completion-report.md`
- `2025-11-28-ci-cd-completion-report.md`

### 必須セクション

#### 1. 更新履歴セクション（冒頭に配置）

```markdown
## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| YYYY-MM-DD | 作成者名 | 初版作成: レポートの概要 |
| YYYY-MM-DD | 更新者名 | 更新内容の説明 |
```

#### 2. 概要セクション

作業の全体像、達成した目標、主要な成果を簡潔に記載:

```markdown
## 概要

[システム名]から**[実施内容]**を完了しました。この作業により、以下の目標を達成しました：

- ✅ **目標1**: 具体的な成果
- ✅ **目標2**: 具体的な成果
- ✅ **目標3**: 具体的な成果
```

#### 3. 計画との対応関係

元の計画（実行プラン、要件定義書など）との対応を明記:

```markdown
## 計画との対応

**参照ドキュメント**: `docs/operations/xxx-plan.md`

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| Phase 1: XXX | ✅ 完了 | 計画通り実施 | なし |
| Phase 2: YYY | ⚠️ 一部変更 | ZZZに変更 | 理由: ... |
| Phase 3: AAA | ❌ 未実施 | 手動実施待ち | 理由: ... |
```

#### 4. 実施内容詳細

計画に対して実際に行った作業を具体的に記載:

```markdown
## 実施内容詳細

### 完了した作業

1. **作業項目1**
   - 実施内容の詳細
   - 使用したコマンド・ツール
   - 成果物（ファイルパス、行数など）

2. **作業項目2**
   - ...
```

#### 5. 成果と効果

数値的な効果、品質改善、リスク低減などを記載:

```markdown
## 成果と効果

### 定量的効果
- コスト削減: $XX/月（XX%削減）
- ファイル削減: XXファイル削除
- パフォーマンス改善: XX%高速化

### 定性的効果
- 保守性向上: 複雑性排除
- セキュリティ強化: 脆弱性解消
```

#### 6. 未完了項目・次のステップ

残作業や今後の対応事項を明記:

```markdown
## 未完了項目・次のステップ

### 手動実施が必要な作業
- [ ] 作業1: 理由と手順
- [ ] 作業2: 理由と手順

### 今後の推奨事項
- 項目1: 理由と期限
- 項目2: 理由と期限
```

### レポート作成タイミング

- ✅ Phase/タスク完了時
- ✅ 大規模な変更・削除作業後
- ✅ 不具合調査・修正完了時
- ✅ インフラ変更・デプロイ後
- ✅ パフォーマンス改善・最適化後

### 禁止事項

- ❌ ファイル名に日付なしで作成
- ❌ 更新履歴セクションの省略
- ❌ 計画との対応関係を記載しない
- ❌ 実施内容を曖昧に記載（具体性欠如）
- ❌ 未完了項目を隠蔽・省略

---

## コミュニケーションスタイル

- 要件が不明確な場合は推測せず質問する
- 日本語で応答
- 修正前にクラス全体のコンテキストを参照
- 依存関係はワークスペース検索で確認してから実装
- 新規Service/Repositoryは必ずインターフェースから作成
- FormRequestクラスでバリデーションを実装（Actionには書かない）
