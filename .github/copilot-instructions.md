# プロジェクト開発ルールとAI行動指針

あなたはLaravelとモダンな設計パターンに精通したエキスパートエンジニアです。以下のルールを厳格に守り、ユーザーの意図を汲み取った実装を提案してください。

## 0. プロジェクト構造

**重要**: このプロジェクトはDockerベースで、Laravelアプリケーションは `/laravel` ディレクトリに配置されています。

```
/home/ktr/mtdev/
├── docker-compose.yml          # Docker構成（ルート）
├── Dockerfile                  # アプリコンテナ定義
└── laravel/                    # ← Laravelアプリケーション本体
    ├── app/
    ├── routes/
    ├── composer.json
    ├── package.json
    └── ...
```

**作業ディレクトリ**:
- **ホスト（開発時）**: `/home/ktr/mtdev/laravel/` で作業
- **コンテナ内**: `/var/www/html/` にマウント
- **Artisanコマンド実行**: 必ず `laravel/` ディレクトリ内で実行すること

## 1. 技術スタックと環境

### 1.1 バックエンド
- **言語**: PHP 8.3
- **フレームワーク**: Laravel 12
- **DB**: PostgreSQL 16 (Docker Compose)
- **ストレージ**: MinIO (S3互換)
- **決済**: Stripe (Laravel Cashier)
- **AI API**: OpenAI API, Replicate API (Stable Diffusion)

### 1.2 フロントエンド
- **JavaScript**: **Vanilla JS (純粋なJavaScript)**を優先
    - ※`package.json`に Alpine.js が残っているが、**新規実装では絶対に使用禁止**
    - 理由: iPadでの動作不安定のため
    - 既存コードのリファクタリング時は Alpine.js を削除してVanilla JSで書き直すこと
- **CSS**: Tailwind CSS 3 + Vite
- **グラフ**: Chart.js (パフォーマンス可視化)

### 1.3 開発環境
- **実行環境**: WSL上のDocker (Laravel Sailは**使用しない**)
- **Node.js**: 20 LTS (Dockerfile内でインストール)
- **Webサーバー**: Apache (mod_rewrite有効)
- **テスト**: Pest (PHPUnit wrapper)

### 1.4 Docker構成
```yaml
# docker-compose.yml
services:
  db: PostgreSQL 16 (port 5432)
  app: PHP 8.3 + Apache (port 8080)
  s3: MinIO (port 9100/9101)
```

## 2. アーキテクチャ・設計方針

### 2.1 基本方針
コントローラーにロジックを書かず、以下の責務分離を徹底すること。

### 2.2 レイヤー構成
全てのHTTPリクエストは以下のレイヤーを経由:

```
Route → Action (__invoke) → Service(s) → Repository → Model
                    ↓
                 Responder → View/JSON Response
```

**重要**: 従来のコントローラーは存在しない。`app/Http/Actions/` 配下の**Invokableクラス**のみ使用。

### 2.3 各レイヤーの責務

#### Action (Invokableコントローラー)
- **役割**: ルーティングのエントリポイント。単一の責任を持つ。
- **命名**: `{動詞}{対象}Action` (例: `StoreTaskAction`, `ApproveTaskAction`)
- **配置**: `app/Http/Actions/{ドメイン}/` (例: `Task/`, `Token/`, `Admin/`)
- **必須実装**: `public function __invoke()` メソッド

**実装例**:
```php
<?php
namespace App\Http\Actions\Task;

class StoreTaskAction
{
    public function __construct(
        protected TaskManagementServiceInterface $taskService,
        protected GroupServiceInterface $groupService
    ) {}
    
    public function __invoke(StoreTaskRequest $request): RedirectResponse
    {
        // バリデーション済みデータを取得
        $data = $request->validated();
        
        // ビジネスロジックは全てServiceに委譲
        $task = $this->taskService->createTask(
            $request->user(),
            $data
        );
        
        // Responderを使用してレスポンス返却
        return redirect()
            ->route('dashboard')
            ->with('success', 'タスクが登録されました。')
            ->with('avatar_event', config('const.avatar_events.task_created'));
    }
}
```

#### Service
- **役割**: ビジネスロジックを担当。
- **必須**: **必ずインターフェースを作成**し、それを実装すること。
- **配置**: `app/Services/{ドメイン}/{サービス名}ServiceInterface.php` + `{サービス名}Service.php`
- **命名**: `{機能名}ServiceInterface` / `{機能名}Service`

**実装例**:
```php
<?php
namespace App\Services\Task;

interface TaskManagementServiceInterface
{
    public function createTask(User $user, array $data): Task;
    public function updateTask(Task $task, array $data): Task;
}

class TaskManagementService implements TaskManagementServiceInterface
{
    public function __construct(
        private TaskRepositoryInterface $repository
    ) {}
    
    public function createTask(User $user, array $data): Task
    {
        // ビジネスロジック実装
        return $this->repository->create([
            'user_id' => $user->id,
            'title' => $data['title'],
            // ...
        ]);
    }
}
```

#### Repository
- **役割**: データアクセスを担当。
- **必須**: **必ずインターフェースを作成**し、それを実装すること。
- **配置**: `app/Repositories/{ドメイン}/{対象}RepositoryInterface.php` + `{対象}EloquentRepository.php`
- **命名**: `{対象}RepositoryInterface` / `{対象}EloquentRepository`

**実装例**:
```php
<?php
namespace App\Repositories\Task;

interface TaskRepositoryInterface
{
    public function create(array $data): Task;
    public function findById(int $id): ?Task;
}

class TaskEloquentRepository implements TaskRepositoryInterface
{
    public function create(array $data): Task
    {
        return Task::create($data);
    }
    
    public function findById(int $id): ?Task
    {
        return Task::find($id);
    }
}
```

#### Responder
- **役割**: レスポンスの整形・返却を担当。
- **配置**: `app/Http/Responders/{ドメイン}/{機能}Responder.php`
- **使用方針**: **原則として全てのActionでResponderを使用すること**
- **注意**: 現在一部のActionで直接レスポンス返却しているが、新規実装では必ずResponderを経由すること

**実装例**:
```php
<?php
namespace App\Http\Responders\Task;

class TaskSearchResultsResponder
{
    public function response(Collection $tasks, array $searchParams): Response
    {
        return response()->view('tasks.search-results', [
            'tasks' => $tasks,
            'searchType' => $searchParams['type'],
            'totalCount' => $tasks->count(),
        ]);
    }
}
```

### 2.4 依存性注入（DI）

#### DIコンテナの設定
ServiceとRepositoryのインターフェースと具象クラスのバインドは `AppServiceProvider::register()` に記述すること。

**AppServiceProvider.php の例**:
```php
<?php
namespace App\Providers;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            TaskRepositoryInterface::class,
            TaskEloquentRepository::class
        );
        
        // Service bindings
        $this->app->bind(
            TaskManagementServiceInterface::class,
            TaskManagementService::class
        );
    }
}
```

#### Actionでの依存注入パターン
```php
// ❌ NG: 具象クラスを直接注入
public function __construct(TaskService $service) {}

// ✅ OK: インターフェース経由で注入
public function __construct(TaskServiceInterface $service) {}
```

## 3. プロジェクト固有パターン

### 3.1 グループタスク機能
複数ユーザーに同時にタスクを割り当て可能。承認フロー付き。

#### キーポイント
- `group_task_id` (UUID) で関連タスクをグループ化
- `assigned_by_user_id` (割当者) ≠ `user_id` (タスク所有者)
- `requires_approval` フラグで承認要否を制御
- 承認不要の場合は自動承認処理を実行

#### 実装例
```php
// StoreTaskAction での処理
if ($request->isGroupTask()) {
    // グループタスク権限チェック
    if (!$this->groupService->canEditGroup($user) || !$user->group_id) {
        abort(403, 'グループタスク作成権限がありません。');
    }
    
    // 共通識別子を生成
    $data['group_task_id'] = (string) Str::uuid();
    $data['user_id'] = $data['assigned_user_id'];
    $data['requires_approval'] = $request->requiresApproval();
    
    $task = $this->taskManagementService->createTask($user, $data, $groupFlg = true);
    
    // 承認不要の場合は自動承認
    if (!$task->requires_approval) {
        $approver = $this->profileService->findUserById($task->assigned_by_user_id);
        $this->taskApprovalService->approveTask($task, $approver);
    }
}
```

### 3.2 スケジュールタスク (Batch処理)
Cronで定期的にタスクを自動生成。祝日対応、担当者ランダム割当などが可能。

#### Cronセットアップ
```bash
# crontab -e で以下を追加
* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1
```

#### 実行コマンド
```bash
# 全スケジュールタスク実行
php artisan batch:execute-scheduled-tasks

# 特定タスク実行
php artisan batch:execute-task {id}

# Dry-run (実行予定確認)
php artisan scheduled-tasks:execute --dry-run

# タスク一覧表示
php artisan batch:list-tasks
php artisan batch:list-tasks --group=1
```

#### Kernelスケジュール設定 (`app/Console/Kernel.php`)
```php
protected function schedule(Schedule $schedule): void
{
    // 開発環境: 毎分実行（テスト用）
    if (app()->environment('local')) {
        $schedule->command('batch:execute-scheduled-tasks')
            ->everyMinute()
            ->withoutOverlapping(5);
    } 
    // 本番環境: 毎時実行
    else {
        $schedule->command('batch:execute-scheduled-tasks')
            ->hourly()
            ->withoutOverlapping(10);
    }
    
    // 祝日キャッシュ更新: 毎日0時
    $schedule->call(function () {
        Holiday::cacheYearHolidays(now()->year);
        Holiday::cacheYearHolidays(now()->year + 1);
    })->dailyAt('00:00');
    
    // 実行履歴クリーンアップ: 毎週日曜3時
    $schedule->call(function () {
        ScheduledTaskExecution::where('created_at', '<', now()->subMonths(6))->delete();
    })->weeklyOn(0, '03:00');
}
```

#### 関連モデル
- `ScheduledGroupTask`: スケジュール定義
- `ScheduledTaskExecution`: 実行履歴
- `Holiday`: 祝日マスタ (キャッシュ機能付き)

### 3.3 承認待ち統合画面
タスク承認とトークン購入承認を一画面で統合表示。

#### 実装パターン (`ListPendingApprovalsAction`)
```php
public function __invoke(Request $request): View
{
    $user = Auth::user();
    
    // 各サービスから承認待ちデータを取得
    $pendingTasks = $this->taskApprovalService->getPendingTasksForApprover($user);
    $pendingTokens = $this->tokenApprovalService->getPendingRequestsForParent($user);
    
    // 統合・ソート・ページネーション
    $approvals = $this->approvalMergeService->mergeAndSortApprovals(
        $pendingTasks,
        $pendingTokens,
        perPage: 15
    );
    
    return view('tasks.pending-approvals', ['approvals' => $approvals]);
}
```

#### データ統合ロジック (`ApprovalMergeService`)
- タスクとトークン購入を `type` フィールドで識別
- `requested_at` で昇順ソート (古い順)
- ページネーション: 15件/ページ

#### UI差別化
- **タスク**: 紫系グラデーション + 「タスク」バッジ
- **トークン購入**: 黄色系グラデーション + 「トークン購入」バッジ

### 3.4 トークンシステム
タスク完了報酬とトークン購入を管理。AI機能利用時にトークンを消費。

#### トランザクション種別
```sql
-- token_transactions.type CHECK制約
CHECK (type IN (
    'consume',      -- タスクでの消費
    'purchase',     -- 購入
    'grant',        -- 承認時の付与
    'free_reset',   -- 無料トークンリセット
    'admin_adjust', -- 管理者調整
    'ai_usage',     -- AI機能利用
    'refund'        -- 返金
))
```

#### CHECK制約エラー対応
`grant` タイプがCHECK制約に含まれていない場合:
```bash
php artisan make:migration add_grant_to_token_transaction_types
```

```sql
ALTER TABLE token_transactions 
DROP CONSTRAINT IF EXISTS token_transactions_type_check;

ALTER TABLE token_transactions 
ADD CONSTRAINT token_transactions_type_check 
CHECK (type IN ('consume', 'purchase', 'grant', 'free_reset', 'admin_adjust', 'ai_usage', 'refund'));
```

### 3.5 AI機能統合

#### 3.5.1 OpenAI API (タスク分解・テキスト生成)

**サービス**: `App\Services\AI\OpenAIService`

**主要メソッド**:
```php
// タスク分解
public function requestDecomposition(
    string $title,
    string $context = '',
    bool $isRefinement = false
): array;

// DALL-E 3で画像生成
public function generateImage(
    string $prompt,
    string $size = '1024x1024',
    string $quality = 'standard'
): ?string;

// Chat API (汎用)
public function chat(
    string $prompt,
    ?string $systemPrompt = null,
    string $model = 'gpt-4'
): ?array;
```

**使用例**:
```php
use App\Services\AI\OpenAIService;

class TaskProposalService
{
    public function __construct(
        private OpenAIService $openAIService
    ) {}
    
    public function decomposeTask(string $title, string $context): array
    {
        $result = $this->openAIService->requestDecomposition($title, $context);
        
        // トークン消費記録
        $this->tokenService->consumeTokens(
            $user,
            $result['usage']['total_tokens'],
            'AI機能: タスク分解',
            $proposal
        );
        
        return $result;
    }
}
```

**設定** (`.env`):
```env
OPENAI_API_KEY=sk-...
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4o-mini
```

**トークンコスト計算**:
- `prompt_tokens + completion_tokens * 重み係数` で計算
- 重み係数は `config('const.openai_prompt_completion_ratio')`

#### 3.5.2 Stable Diffusion (画像生成)

**サービス**: `App\Services\AI\StableDiffusionService` (Replicate API使用)

**主要メソッド**:
```php
// 画像生成
public function generateImage(
    string $prompt,
    int $seed,
    array $options = []
): ?array;

// 背景除去
public function removeBackground(string $imageUrl): ?array;

// 複数ポーズ生成
public function generateCharacterPoses(
    string $basePrompt,
    array $poses,
    ?int $seed = null
): array;
```

**使用例**:
```php
use App\Services\AI\StableDiffusionService;

class TeacherAvatarService
{
    public function __construct(
        private StableDiffusionService $sdService
    ) {}
    
    public function createAvatar(User $user, array $data): TeacherAvatar
    {
        $seed = random_int(1, 2147483647);
        
        // 画像生成（非同期ジョブで実行）
        GenerateAvatarImagesJob::dispatch($avatar->id);
        
        return $avatar;
    }
}

// GenerateAvatarImagesJob内
$result = $this->sdService->generateImage($prompt, $seed, [
    'draw_model_version' => 'anything-v4.0',
    'width' => 512,
    'height' => 512,
]);

if ($avatar->is_transparent) {
    $result = $this->sdService->removeBackground($result['url']);
}
```

**設定** (`.env`):
```env
REPLICATE_API_TOKEN=r8_...
REPLICATE_DRAW_MODEL_VERSION=...
REPLICATE_TRANSPARENT_MODEL_VERSION=...
```

**ポーリング設定**:
- 最大試行回数: 60回
- 間隔: 2秒
- タイムアウト: 約120秒

**NSFW対策**:
- `negative_prompt` に明示的に除外ワードを設定
- 複数人物、不適切な表現を排除

### 3.6 アバターシステム

#### 概要
AIで生成した教師アバターがユーザーの行動に応じてコメントを表示。

#### データ構造
```php
// TeacherAvatar
- user_id: ユーザーID
- name: アバター名
- description: 説明
- prompt: 生成プロンプト
- seed: シード値 (同一キャラクター生成用)
- is_transparent: 背景透過
- draw_model_version: 使用モデル
- estimated_token_usage: 推定トークン消費量

// AvatarImage
- teacher_avatar_id
- pose_type: 'full_body' | 'bust'
- expression_type: 'smile' | 'normal' | 'surprised' | 'angry'
- s3_path: S3パス
- generation_prediction_id: Replicate予測ID

// AvatarComment
- event_type: イベント種別 (task_created, task_completed, etc.)
- comment_text: コメント内容
```

#### イベント種別 (`config/const.php`)
```php
'avatar_events' => [
    'task_created' => 'task_created',
    'task_completed' => 'task_completed',
    'group_task_created' => 'group_task_created',
    'token_purchased' => 'token_purchased',
    // ... その他
]
```

#### 生成フロー
```
1. ユーザーがアバター作成フォーム送信
   ↓
2. TeacherAvatarService::createAvatar()
   - シード値生成
   - トークン残高確認
   - アバターレコード作成
   ↓
3. GenerateAvatarImagesJob ディスパッチ (非同期)
   ↓
4. Stable Diffusion APIで画像生成
   - full_body × 4表情
   - bust × 4表情
   ↓
5. 背景除去 (is_transparent = true の場合)
   ↓
6. S3にアップロード
   - パス: avatars/{user_id}/{pose}_{expression}_{timestamp}.png
   ↓
7. AvatarImage レコード作成
   ↓
8. トークン消費記録
```

#### 拡張性
- 今後、表情やポーズのバリエーション追加予定
- コメントテンプレートのDB管理化
- アバターのカスタマイズ機能 (髪型、服装など)

### 3.7 ファイルアップロード (S3/MinIO)

#### ストレージ設定
```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'), // MinIO用
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    ],
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
]
```

#### 環境変数設定 (`.env`)
```env
# MinIO (ローカル開発)
AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio123
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=myteacher
AWS_ENDPOINT=http://s3:9100
AWS_URL=http://localhost:9100/myteacher
AWS_USE_PATH_STYLE_ENDPOINT=true

# 本番環境では AWS S3 の認証情報を設定
```

#### アップロード実装パターン

##### パターン1: putFile (推奨)
```php
use Illuminate\Support\Facades\Storage;

// Action または Service内
public function uploadTaskImage(Task $task, UploadedFile $image): TaskImage
{
    // S3にアップロード (自動的にユニークなファイル名生成)
    $path = Storage::disk('s3')->putFile('task_approvals', $image, 'public');
    
    // DBに保存
    return $task->images()->create([
        'task_id' => $task->id,
        'file_path' => $path,
    ]);
}
```

##### パターン2: put (コンテンツを直接アップロード)
```php
// 画像コンテンツを取得 (例: API経由)
$imageContent = Http::get($imageUrl)->body();

$filename = sprintf('avatar_%s_%s.png', $userId, time());
$path = "avatars/{$userId}/{$filename}";

$uploaded = Storage::disk('s3')->put($path, $imageContent, [
    'visibility' => 'public',
    'ContentType' => 'image/png',
]);

if (!$uploaded) {
    throw new \RuntimeException("Failed to upload to S3: {$path}");
}

Log::info('File uploaded to S3', ['path' => $path]);
```

##### パターン3: 複数ファイル一括アップロード
```php
// FormRequest で画像配列を取得
if ($request->hasFile('images')) {
    $images = $request->file('images'); // UploadedFile[]
    
    foreach ($images as $image) {
        $path = Storage::disk('s3')->putFile('task_approvals', $image, 'public');
        
        $task->images()->create([
            'task_id' => $task->id,
            'file_path' => $path,
        ]);
    }
}
```

#### ファイル削除
```php
// S3から削除
Storage::disk('s3')->delete($image->s3_path);

// publicディスクから削除
Storage::disk('public')->delete($user->avatar_path);
```

#### ファイルURL取得
```php
// S3の公開URL
$url = Storage::disk('s3')->url($path);

// 署名付きURL (一時的なアクセス許可)
$url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));
```

#### ベストプラクティス
1. **大容量ファイルはジョブで処理**: 画像生成など時間がかかる処理は `GenerateAvatarImagesJob` のようにキューを使用
2. **バリデーション必須**: `RequestApprovalRequest` でファイルサイズ・形式を検証
3. **エラーハンドリング**: アップロード失敗時はトランザクションロールバック
4. **パス命名規則**: `{カテゴリ}/{ユーザーID}/{ファイル名}` (例: `avatars/123/smile_bust_1234567890.png`)
5. **visibility設定**: 公開ファイルは `'public'` を明示

## 4. コーディング規約

### 4.1 命名規則
- PHP変数・メソッド名: `camelCase`
- DBカラム名: `snake_case`
- クラス名: `PascalCase`
- Action命名: `{動詞}{対象}Action`
- Service命名: `{機能名}Service(Interface)`
- Repository命名: `{対象}Repository(Interface)` または `{対象}EloquentRepository`

### 4.2 ドキュメンテーション（Docコメント）
**必須**: クラス、メソッド、プロパティの新規作成時および修正時は、必ずDocコメント（PHPDoc / JSDoc）を記述すること。

#### PHPDocの例
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
{
    // ...
}
```

#### 既存コードへの対応
修正対象の既存コードにDocコメントがない場合、**修正のタイミングで必ず追記すること**。

### 4.3 コード修正時のルール
1. **クラス全体の参照**: 修正提案時は、必ず**現状のクラスコード全体**を参照・考慮すること。
2. **依存解決**: 修正対象のメソッド内で使用されている「別のクラス/メソッド」の定義がコンテキストにない場合は、まずワークスペース内を検索して参照を試みること。
3. **推測禁止**: 検索しても見つからない場合は、**推測で書かずにユーザーに定義を質問すること**。
4. **インターフェース優先**: 新規Service/Repository作成時は、必ずインターフェースから実装すること。

### 4.4 エラーハンドリング
```php
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

try {
    DB::transaction(function () use ($data) {
        // トランザクション内の処理
    });
    
    return redirect()->route('success')->with('success', '処理が完了しました。');
    
} catch (\Exception $e) {
    Log::error('処理エラー', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'data' => $data,
    ]);
    
    return redirect()->back()
        ->withErrors(['error' => '処理に失敗しました。'])
        ->withInput();
}
```

## 5. 開発ワークフロー

### 5.1 セットアップコマンド
```bash
# 初回セットアップ（laravel/ ディレクトリ内で実行）
cd laravel/
composer setup
# = composer install
# + php artisan key:generate
# + php artisan migrate
# + npm install
# + npm run build

# 開発サーバー起動 (並列実行)
composer dev
# 内部で concurrently を使用して以下を並列実行:
# - php artisan serve (8000番ポート)
# - php artisan queue:listen --tries=1
# - php artisan pail --timeout=0 (ログ監視)
# - npm run dev (Vite HMR)
```

### 5.2 Docker コマンド
```bash
# コンテナ起動（プロジェクトルートで実行）
cd /home/ktr/mtdev/
docker-compose up -d

# アプリケーションコンテナに入る
docker-compose exec app bash

# コンテナ内では /var/www/html/ がカレントディレクトリ（= laravel/）

# PostgreSQL接続
docker-compose exec db psql -U laravel_user -d laravel_db

# MinIO Web UI
# http://localhost:9101 (ユーザー名: minio, パスワード: minio123)

# ログ確認
docker-compose logs -f app
docker-compose logs -f db

# アセットビルド（CSS/JS変更時）
# ホストのlaravel/ディレクトリから実行
cd /home/ktr/mtdev/laravel
docker compose exec app npm run build
docker compose exec app php artisan optimize:clear
```

### 5.3 テストコマンド
```bash
# 全テスト実行
composer test
# = php artisan config:clear
# + php artisan test

# 特定テスト実行
php artisan test tests/Feature/Task/StoreTaskTest.php
php artisan test --filter "タスクを作成できる"

# Unitテストのみ
php artisan test --testsuite=Unit

# Featureテストのみ
php artisan test --testsuite=Feature

# カバレッジレポート生成
php artisan test --coverage
./vendor/bin/phpunit --coverage-html coverage
```

**テスト環境**:
- フレームワーク: Pest (PHPUnit wrapper)
- DB: SQLiteインメモリ (`phpunit.xml`)
- モック: Mockery
- ファクトリ: `database/factories/`

### 5.4 マイグレーション・シーダー
```bash
# マイグレーション実行
php artisan migrate

# ロールバック
php artisan migrate:rollback

# リフレッシュ (全削除→再実行)
php artisan migrate:fresh

# シーダー実行
php artisan db:seed
php artisan db:seed --class=HolidaySeeder

# マイグレーション+シーダー
php artisan migrate:fresh --seed
```

### 5.5 キューワーカー
```bash
# キューワーカー起動
php artisan queue:work

# 特定接続のみ
php artisan queue:work database

# バックグラウンド実行
php artisan queue:work --daemon

# ジョブ確認
php artisan queue:failed        # 失敗ジョブ
php artisan queue:retry {id}    # リトライ
php artisan queue:flush         # 失敗ジョブ削除
```

### 5.6 キャッシュ管理
```bash
# Docker環境でのキャッシュクリア（推奨）
cd /home/ktr/mtdev/laravel
docker compose exec app php artisan optimize:clear
# 上記コマンドは以下を一括実行:
# - config:clear
# - route:clear
# - view:clear
# - cache:clear
# - compiled:clear

# 個別キャッシュクリア（コンテナ内で実行する場合）
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 本番環境での最適化
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. 要件定義の保存・管理プロセス（重要）
チャット内で画面や機能の要件が定義・更新された際は、`definitions/` ディレクトリ内のMarkdownファイル（例: `definitions/LoginScreen.md`）への保存・更新を提案すること。

**ファイル作成・更新時の絶対ルール:**
1. **ファイル構造**: ファイルの冒頭には必ず **「更新履歴」セクション** を設けること。
2. **既存ファイルへの加筆**:
    - ファイルが既に存在する場合は、既存の内容を消去せず、必要な箇所を加筆・修正すること。
    - 必ず「更新履歴」に日付と変更内容の要約を追記すること。
3. **フォーマット例**:
    ```markdown
    # [画面名] 要件定義書

    ## 更新履歴
    - 2025-MM-DD: 初版作成
    - 2025-MM-DD: バリデーションルールの追加（担当: AI提案）

    ## 1. 概要
    ...
    ```

## 7. コミュニケーション
- 実装にあたり、仕様や意図が不明瞭な点は、勝手に判断せず**必ず質問すること**。
- 回答は日本語で行うこと。

## 8. よくある実装パターンと注意点

### 8.1 避けるべきパターン
```php
// ❌ NG: Actionにビジネスロジック
public function __invoke(Request $request) {
    $task = Task::create($request->all()); // 直接Model操作
    return view('tasks.index', ['task' => $task]);
}

// ❌ NG: Serviceインターフェースなし
class TaskService { /* ... */ }
$this->app->bind(TaskService::class, TaskService::class);

// ❌ NG: Responderを使わず直接レスポンス返却（新規実装）
public function __invoke(Request $request) {
    $tasks = $this->taskService->getAllTasks($request->user());
    return view('tasks.index', compact('tasks'));
}
```

### 8.2 推奨パターン
```php
// ✅ OK: Service経由、Interface使用
public function __invoke(StoreTaskRequest $request) {
    $task = $this->taskService->createTask($request->user(), $request->validated());
    
    // Responder経由でレスポンス
    return $this->responder->success($task);
}

// ✅ OK: AppServiceProvider
$this->app->bind(TaskServiceInterface::class, TaskService::class);

// ✅ OK: Responder使用
public function __invoke(Request $request) {
    $tasks = $this->taskService->getAllTasks($request->user());
    return $this->responder->response($tasks);
}
```

### 8.3 トランザクション処理
```php
// 複数テーブル更新を伴う処理は必ずトランザクション内で実行
DB::transaction(function () use ($task, $user) {
    $this->taskRepository->update($task, ['status' => 'approved']);
    $this->tokenService->grantTokens($user, $task->reward);
    $this->notificationService->notifyApproval($task);
});
```

### 8.4 N+1問題対策
```php
// ❌ NG: Eager Loadingなし
$tasks = Task::where('user_id', $userId)->get();
foreach ($tasks as $task) {
    echo $task->user->name; // N+1発生
}

// ✅ OK: Eager Loading使用
$tasks = Task::with(['user', 'images', 'tags'])
    ->where('user_id', $userId)
    ->get();
```


## Key Files Reference

- **ルーティング**: `routes/web.php` (Action直接指定)
- **DIバインディング**: `app/Providers/AppServiceProvider.php`
- **スケジュール定義**: `app/Console/Kernel.php`
- **環境変数**: `.env` (PostgreSQL, MinIO, Stripe設定)
- **Vite設定**: `vite.config.js` (Tailwind統合)
- **定数定義**: `config/const.php` (アバターイベント、トークン設定等)
- **ストレージ設定**: `config/filesystems.php` (S3/MinIO設定)