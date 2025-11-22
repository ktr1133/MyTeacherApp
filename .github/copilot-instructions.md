# MyTeacher - AIタスク管理プラットフォーム

Laravel 12 + Docker構成。**Action-Service-Repositoryパターン**（従来のコントローラーなし）を採用。OpenAI・Stable Diffusion統合、トークンシステム、AI生成アバター機能を実装。

## プロジェクト構造（重要）

**Docker構成**: Laravelは `/laravel` サブディレクトリに配置

```
/home/ktr/mtdev/
├── docker-compose.yml          # DB (PostgreSQL), App (Apache/PHP), S3 (MinIO)
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

## アーキテクチャ: Action-Service-Repositoryパターン

**従来のコントローラーは存在しない。** すべてのHTTPリクエストは以下のフローを経由:

```
Route → Action (__invoke) → Service → Repository → Model
                  ↓
              Responder → Response
```

### 実装ルール

1. **Action** (`app/Http/Actions/{ドメイン}/`): 単一責任のInvokableクラス
   - 命名: `{動詞}{対象}Action` (例: `StoreTaskAction`, `ApproveTaskAction`)
   - `public function __invoke()` メソッド必須
   - ビジネスロジックは書かない - Serviceに委譲

2. **Service** (`app/Services/{ドメイン}/`): ビジネスロジック
   - **必ずインターフェースを先に作成**: `{機能}ServiceInterface` + `{機能}Service`
   - `AppServiceProvider::register()` でバインド: `$this->app->bind(Interface::class, Implementation::class)`
   - コンストラクタでインターフェース経由で注入

3. **Repository** (`app/Repositories/{ドメイン}/`): データアクセス
   - **必ずインターフェースを先に作成**: `{対象}RepositoryInterface` + `{対象}EloquentRepository`
   - `AppServiceProvider::register()` でバインド

4. **Responder** (`app/Http/Responders/{ドメイン}/`): レスポンス整形
   - **新規コードでは必ず使用** (一部レガシーコードは直接返却 - 触る際にリファクタリング)

### 実装例

```php
// routes/web.php
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

## 4. 主要ファイル

- **ルート**: `routes/web.php` (Actionを直接参照)
- **DIバインディング**: `app/Providers/AppServiceProvider.php`
- **スケジューラー**: `app/Console/Kernel.php`
- **設定**: `config/const.php` (アバターイベント、トークン), `config/filesystems.php` (S3), `config/avatar-options.php`
- **Docker**: `docker-compose.yml` (DB:5432, App:8080, MinIO:9100/9101)

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

## コミュニケーションスタイル

- 要件が不明確な場合は推測せず質問する
- 日本語で応答
- 修正前にクラス全体のコンテキストを参照
- 依存関係はワークスペース検索で確認してから実装
- 新規Service/Repositoryは必ずインターフェースから作成
