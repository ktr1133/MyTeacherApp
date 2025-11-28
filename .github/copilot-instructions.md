# MyTeacher - AIタスク管理プラットフォーム

Laravel 12 + Docker構成。**Action-Service-Repositoryパターン**（従来のコントローラーなし）を採用。OpenAI・Stable Diffusion統合、トークンシステム、AI生成アバター機能を実装。

## プロジェクト構造（重要）

**リポジトリルート変更**: `/home/ktr/mtdev/` がGitルート（変更前: `laravel/` がルート）

```
/home/ktr/mtdev/                    # ← リポジトリルート
├── docker-compose.yml          # DB (PostgreSQL), App (Apache/PHP), S3 (MinIO)
├── definitions/                # プロジェクトドキュメント
├── infrastructure/             # Terraform, 運用スクリプト
├── services/                   # マイクロサービス
└── laravel/                    # ← Laravelアプリケーション本体
    ├── app/
    │   ├── Http/Actions/       # Invokableアクション（コントローラー代替）
    │   ├── Services/           # ビジネスロジック（必ずインターフェース付き）
    │   ├── Repositories/       # データアクセス（必ずインターフェース付き）
    │   └── Responders/         # レスポンス整形
    ├── routes/web.php          # ルートはActionを直接指定
    └── composer.json           # setup: `composer setup`, dev: `composer dev`
```

**コマンド実行は `/home/ktr/mtdev/laravel/` から** - Dockerコンテナ内では `/var/www/html/` にマウント

## 不具合対応方針（重要）

**原則**: 不具合が発生した際は推測による修正を行わず、必ずログや実行結果に基づいて原因を特定してから修正を実施する。

### 不具合対応手順

1. **ログ・エラー情報の収集**
   ```bash
   # アプリケーションログ確認
   tail -f storage/logs/laravel.log
   
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

4. **修正後の検証**
   - ログベースでの動作確認
   - 同様の問題を防ぐためのテスト追加
   - ドキュメント・手順書の更新

### 禁止事項

- ❌ エラーメッセージを読まずに「よくありそうな修正」を適用
- ❌ ログを確認せずに設定値を推測で変更
- ❌ Stack Overflowの解決策をそのまま適用
- ❌ 「動いたからOK」で根本原因を放置

この方針により、確実で持続可能な問題解決を実現し、同様の問題の再発を防止する。

## アーキテクチャ: Action-Service-Repositoryパターン

**従来のコントローラーは存在しない。** すべてのHTTPリクエストは以下のフローを経由:

```
Route → Action (__invoke) → Service → Repository → Model
                  ↓
              Responder → Response
```

### 実装ルール

1. **Action** (`laravel/app/Http/Actions/{ドメイン}/`): 単一責任のInvokableクラス
   - 命名: `{動詞}{対象}Action` (例: `StoreTaskAction`, `ApproveTaskAction`)
   - `public function __invoke()` メソッド必須
   - ビジネスロジックは書かない - Serviceに委譲

2. **Service** (`laravel/app/Services/{ドメイン}/`): ビジネスロジック
   - **必ずインターフェースを先に作成**: `{機能}ServiceInterface` + `{機能}Service`
   - `AppServiceProvider::register()` でバインド: `$this->app->bind(Interface::class, Implementation::class)`
   - コンストラクタでインターフェース経由で注入

3. **Repository** (`laravel/app/Repositories/{ドメイン}/`): データアクセス
   - **必ずインターフェースを先に作成**: `{対象}RepositoryInterface` + `{対象}EloquentRepository`
   - `AppServiceProvider::register()` でバインド

4. **Responder** (`laravel/app/Http/Responders/{ドメイン}/`): レスポンス整形
   - **新規コードでは必ず使用** (一部レガシーコードは直接返却 - 触る際にリファクタリング)

### 実装例

```php
// laravel/routes/web.php
Route::post('/tasks', StoreTaskAction::class)->name('tasks.store');

// StoreTaskAction.php
public function __construct(
    protected TaskManagementServiceInterface $taskService  // ✅ インターフェース
) {}

public function __invoke(StoreTaskRequest $request): RedirectResponse {
    $task = $this->taskService->createTask($request->user(), $request->validated());
    return redirect()->route('dashboard')->with('success', 'タスクが登録されました。');
}
```

## 技術スタック

- **バックエンド**: PHP 8.3, Laravel 12, PostgreSQL 16, MinIO (S3互換), Stripe (Cashier)
- **フロントエンド**: **Vanilla JSのみ** (package.jsonにAlpine.jsあるが**使用禁止** - iPad互換性問題), Tailwind CSS 3 + Vite, Chart.js
- **AI**: OpenAI API (タスク分解, DALL-E), Replicate API (Stable Diffusion - アバター生成)
- **テスト**: Pest (PHPUnitラッパー), SQLiteインメモリ
- **キュー**: Databaseドライバ、`php artisan queue:work` で処理

## 1. 重要なワークフロー

### セットアップ・開発

```bash
# 初回セットアップ (/home/ktr/mtdev/laravel/ から実行)
cd /home/ktr/mtdev/laravel
composer setup  # 依存関係、キー生成、マイグレーション、アセットビルド

# 開発サーバー起動（並列: サーバー、キュー、ログ、Vite HMR）
composer dev

# Docker操作（プロジェクトルートから）
cd /home/ktr/mtdev
docker-compose up -d
docker-compose exec app bash  # コンテナに入る (cwd: /var/www/html/)

# アセット再ビルド（CSS/JS変更後）
cd /home/ktr/mtdev/laravel
docker compose exec app npm run build
docker compose exec app php artisan optimize:clear
```

### テスト

```bash
composer test                    # 全テスト実行 (Pest)
php artisan test --filter "..."  # 特定テスト
php artisan test --coverage      # カバレッジレポート
```

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
| `laravel/routes/web.php` | ルート定義（Actionを直接参照、use文必須） |
| `laravel/app/Providers/AppServiceProvider.php` | DIバインディング（Interface ⇔ Implementation） |
| `laravel/app/Console/Kernel.php` | スケジューラー設定（`schedule()` メソッド） |
| `laravel/app/Http/Actions/{ドメイン}/` | Invokableアクション（`__invoke()` 必須） |
| `laravel/app/Services/{ドメイン}/` | ビジネスロジック（必ずInterface付き） |
| `laravel/app/Repositories/{ドメイン}/` | データアクセス（必ずInterface付き） |
| `laravel/app/Http/Responders/{ドメイン}/` | レスポンス整形（新規コードで使用） |
| `laravel/app/Http/Requests/{ドメイン}/` | FormRequest（バリデーション定義） |
| `laravel/app/Jobs/` | 非同期ジョブ（`GenerateAvatarImagesJob` など） |
| `laravel/config/const.php` | 定数定義（イベント、トークン種別、ステータス） |
| `laravel/config/filesystems.php` | S3/MinIO設定 |
| `laravel/config/avatar-options.php` | アバター生成オプション |
| `laravel/database/migrations/` | マイグレーションファイル（命名: `YYYY_MM_DD_*`) |
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
// ❌ Actionにロジック
public function __invoke(Request $request) {
    $task = Task::create($request->all());  // 直接モデル操作!
}

// ❌ インターフェースなし
class TaskService {}
$this->app->bind(TaskService::class, TaskService::class);

// ❌ Responderなし（新規コードのみ - レガシーは許容）
public function __invoke(Request $request) {
    return view('tasks.index', compact('tasks'));  // Responder経由すべき
}

// ✅ 正しい実装
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

# 個別確認
tail -f storage/logs/laravel.log                 # アプリケーション
tail -f /var/log/laravel-scheduler.log           # スケジューラー（要root）
tail -f storage/logs/scheduled-tasks.log         # バッチ実行

# キューログ
php artisan queue:failed                         # 失敗ジョブ一覧
php artisan queue:retry {job-id}                 # ジョブ再実行
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

## コミュニケーションスタイル

- 要件が不明確な場合は推測せず質問する
- 日本語で応答
- 修正前にクラス全体のコンテキストを参照
- 依存関係はワークスペース検索で確認してから実装
- 新規Service/Repositoryは必ずインターフェースから作成
- FormRequestクラスでバリデーションを実装（Actionには書かない）
