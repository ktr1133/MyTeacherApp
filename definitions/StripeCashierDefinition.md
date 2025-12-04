# Stripe & Laravel Cashier 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-03 | GitHub Copilot | 初版作成: Stripe決済とLaravel Cashier統合の要件定義 |

---

## 1. 概要

本プロジェクト「MyTeacher」では、**Stripe**を決済プロバイダーとして、**Laravel Cashier**をStripe統合ライブラリとして使用し、サブスクリプション課金機能を実装しています。

### 1.1 決済の対象

- **サブスクリプション課金**: ファミリープラン、エンタープライズプラン
- **従量課金**: エンタープライズプランの追加メンバー
- **単発購入**: トークンパッケージ購入（将来実装予定）

### 1.2 カスタマイズの特徴

本プロジェクトでは、Cashierのデフォルト設計を以下の点でカスタマイズしています：

| 項目 | Cashierデフォルト | 本プロジェクト | 理由 |
|------|------------------|--------------|------|
| **Billableモデル** | `User` | `Group` | グループ単位で課金するため |
| **subscriptions.user_id** | UserのID | GroupのID | BillableがGroupのため |
| **決済フロー** | Blade + Alpine.js | Vanilla JS + Checkout Session | iPad互換性確保 |
| **Webhook処理** | 基本的なsubscription管理 | カスタムロジック追加（`groups`テーブル更新） | グループ機能との連携 |

---

## 2. Billableモデルのカスタマイズ

### 2.1 GroupモデルをBillableに設定

**目的**: ユーザー個人ではなく、**グループ単位**でサブスクリプション契約を管理する。

#### Groupモデルの実装

```php
// app/Models/Group.php
namespace App\Models;

use Laravel\Cashier\Billable;

class Group extends Model
{
    use Billable;

    /**
     * Cashierのデフォルトはuser_idだが、Groupモデルではidを使用
     * subscriptions.user_idにはGroup IDが格納される
     */
    public function getForeignKey()
    {
        return 'user_id';
    }

    protected $fillable = [
        'name',
        'master_user_id',
        'stripe_id',              // Stripe Customer ID
        'pm_type',                // Payment Method Type (Cashier標準)
        'pm_last_four',           // カード下4桁 (Cashier標準)
        'trial_ends_at',          // トライアル期限 (Cashier標準)
        'subscription_active',    // サブスクリプション有効フラグ（カスタム）
        'subscription_plan',      // プラン種別: 'family', 'enterprise' (カスタム)
        'max_members',            // 最大メンバー数（カスタム）
        'max_groups',             // 最大グループ数（カスタム）
    ];
}
```

#### Cashier初期化の設定

**重要**: Cashierは静的プロパティ`$customerModel`を使用するため、ServiceProviderで明示的に初期化が必要。

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    // Cashier設定: Groupモデルを課金対象として使用
    \Laravel\Cashier\Cashier::useCustomerModel(\App\Models\Group::class);
}
```

**注意点**:
- `config/cashier.php`の`'model'`設定だけでは不十分
- 実行時にCashierが参照する静的プロパティを`useCustomerModel()`で設定する必要がある
- この初期化を忘れると、Webhook処理でGroup検索が失敗する

#### 設定ファイル

```php
// config/cashier.php
return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'currency' => 'jpy',
    'currency_locale' => 'ja_JP',
    
    // Billableモデル指定（参考値、実行時は使用されない）
    'model' => env('CASHIER_MODEL', App\Models\Group::class),
    
    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => 300,
    ],
];
```

### 2.2 データベース設計

#### groupsテーブル（カスタム拡張）

```sql
CREATE TABLE groups (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    master_user_id BIGINT NOT NULL,
    
    -- Cashier標準カラム
    stripe_id VARCHAR(255) UNIQUE INDEX,       -- Stripe Customer ID
    pm_type VARCHAR(255) NULL,                 -- Payment Method Type
    pm_last_four VARCHAR(4) NULL,              -- カード下4桁
    trial_ends_at TIMESTAMP NULL,              -- トライアル期限
    
    -- カスタムカラム
    subscription_active BOOLEAN DEFAULT FALSE, -- サブスクリプション有効フラグ
    subscription_plan VARCHAR(255) NULL,       -- 'family' or 'enterprise'
    max_members INT DEFAULT 6,                 -- 最大メンバー数
    max_groups INT DEFAULT 1,                  -- 最大グループ数
    free_group_task_limit INT DEFAULT 3,       -- 無料グループタスク上限/月
    group_task_count_current_month INT DEFAULT 0,
    group_task_count_reset_at TIMESTAMP NULL,
    free_trial_days INT DEFAULT 14,
    report_enabled_until TIMESTAMP NULL,       -- レポート有効期限
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**設計方針**:
- Cashier標準カラム（`stripe_id`, `pm_type`等）とカスタムカラム（`subscription_plan`等）を併用
- `subscription_active`と`subscription_plan`はアプリケーション側で管理（高速アクセス用）
- 正式なサブスクリプション情報は`subscriptions`テーブルを参照

#### subscriptionsテーブル（Cashier標準）

```sql
CREATE TABLE subscriptions (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,           -- ⚠️ 実際にはGroup IDを格納
    type VARCHAR(255) DEFAULT 'default',
    stripe_id VARCHAR(255) UNIQUE,     -- Stripe Subscription ID
    stripe_status VARCHAR(255),        -- 'active', 'canceled', 'past_due', etc.
    stripe_price VARCHAR(255) NULL,    -- Stripe Price ID
    quantity INT NULL,
    trial_ends_at TIMESTAMP NULL,
    ends_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX (user_id, stripe_status)
);
```

**重要**: 
- `user_id`カラム名は変更できないが、実際には**Group ID**を保存
- Cashierがこのカラムを使用してBillableモデルを検索
- `stripe_status`で実際のサブスクリプション状態を管理

#### subscription_itemsテーブル（Cashier標準）

```sql
CREATE TABLE subscription_items (
    id BIGINT PRIMARY KEY,
    subscription_id BIGINT NOT NULL,
    stripe_id VARCHAR(255) UNIQUE,     -- Stripe Subscription Item ID
    stripe_product VARCHAR(255),       -- Stripe Product ID
    stripe_price VARCHAR(255),         -- Stripe Price ID
    quantity INT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);
```

**用途**:
- 基本プラン（ファミリー/エンタープライズ）
- エンタープライズプランの追加メンバー（従量課金）

---

## 3. サブスクリプションプラン設計

### 3.1 プラン定義

#### config/const.php

```php
'stripe' => [
    'test_mode' => env('STRIPE_TEST_MODE', true),
    
    'subscription_plans' => [
        'family' => [
            'name' => 'ファミリープラン',
            'price_id' => env('STRIPE_FAMILY_PLAN_PRICE_ID'),
            'amount' => 500,  // 円/月
            'currency' => 'jpy',
            'interval' => 'month',
            'max_members' => 6,
            'max_groups' => 1,
            'trial_days' => 14,
            'features' => [
                'unlimited_group_tasks' => true,
                'monthly_reports' => true,
                'group_token_sharing' => true,
            ],
        ],
        'enterprise' => [
            'name' => 'エンタープライズプラン',
            'price_id' => env('STRIPE_ENTERPRISE_PLAN_PRICE_ID'),
            'amount' => 3000,  // 円/月（基本20名まで）
            'currency' => 'jpy',
            'interval' => 'month',
            'max_members' => 20,
            'max_groups' => 5,
            'trial_days' => 14,
            'features' => [
                'unlimited_group_tasks' => true,
                'monthly_reports' => true,
                'group_token_sharing' => true,
                'statistics_reports' => true,  // 将来実装
                'priority_support' => true,    // 将来実装
            ],
        ],
    ],
    
    // 追加メンバー（エンタープライズプラン専用）
    'additional_member_price_id' => env('STRIPE_ADDITIONAL_MEMBER_PRICE_ID'),
    'additional_member_amount' => 150,  // 円/月/名
    
    // 無料プラン制限
    'free_plan' => [
        'max_members' => 6,
        'max_groups' => 1,
        'group_task_limit_per_month' => 3,
        'report_free_months' => 1,
    ],
],
```

### 3.2 環境変数

#### .env設定

```bash
# Stripe API Key
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxx          # 公開可能キー
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxx       # シークレットキー
STRIPE_TEST_MODE=true                              # テストモード

# Stripe Price ID（Stripeダッシュボードで作成）
STRIPE_FAMILY_PLAN_PRICE_ID=price_xxxxxxxxxxxxxxxxxxxxx
STRIPE_ENTERPRISE_PLAN_PRICE_ID=price_xxxxxxxxxxxxxxxxxxxxx
STRIPE_ADDITIONAL_MEMBER_PRICE_ID=price_xxxxxxxxxxxxxxxxxxxxx

# Webhook設定
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxx

# Cashierモデル
CASHIER_MODEL=App\Models\Group
```

**本番環境での注意点**:
- `STRIPE_TEST_MODE=false`に変更
- 本番用のAPI Key（`pk_live_`, `sk_live_`）を使用
- 本番用のPrice IDに変更
- Webhook URLを本番環境に設定

---

## 4. 決済フロー実装

### 4.1 Checkout Session方式（推奨）

**理由**: iPad互換性、セキュリティ、PCI DSS準拠の容易さ

#### フロー概要

```
1. ユーザーがプラン選択
   ↓
2. Laravel: Checkout Session作成（Repository層）
   ↓
3. Stripe: Checkout Session URLを返却
   ↓
4. ブラウザ: StripeのCheckout画面にリダイレクト
   ↓
5. ユーザー: カード情報入力・決済完了
   ↓
6. Stripe: 成功URL（success_url）にリダイレクト
   ↓
7. Stripe: Webhookでイベント送信（checkout.session.completed）
   ↓
8. Laravel: Webhook処理（subscriptions + groups テーブル更新）
   ↓
9. ユーザー: サブスクリプション画面でプラン表示
```

#### Repository実装

```php
// app/Repositories/Subscription/SubscriptionEloquentRepository.php
public function createCheckoutSession(Group $group, string $plan, int $additionalMembers = 0): Checkout
{
    $planConfig = config("const.stripe.subscription_plans.{$plan}");
    
    $lineItems = [
        [
            'price' => $planConfig['price_id'],
            'quantity' => 1,
        ],
    ];
    
    // エンタープライズプランで追加メンバーがある場合
    if ($plan === 'enterprise' && $additionalMembers > 0) {
        $lineItems[] = [
            'price' => config('const.stripe.additional_member_price_id'),
            'quantity' => $additionalMembers,
        ];
    }
    
    return $group->newSubscription('default', $planConfig['price_id'])
        ->trialDays($planConfig['trial_days'])
        ->checkout([
            'success_url' => route('subscriptions.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('subscriptions.index'),
            'metadata' => [
                'plan' => $plan,
                'additional_members' => $additionalMembers,
            ],
        ]);
}
```

**ポイント**:
- `$group->newSubscription()`がCashierの標準メソッド
- `trialDays()`で無料トライアル設定
- `metadata`にカスタムデータを保存（Webhook時に取得可能）

#### Action実装

```php
// app/Http/Actions/Subscription/CreateCheckoutSessionAction.php
public function __invoke(CreateCheckoutRequest $request): RedirectResponse
{
    $group = $request->user()->group;
    $plan = $request->validated('plan');
    $additionalMembers = $request->validated('additional_members', 0);
    
    try {
        $checkout = $this->subscriptionService->createCheckoutSession(
            $group, 
            $plan, 
            $additionalMembers
        );
        
        return redirect($checkout->url);
    } catch (\Exception $e) {
        Log::error('Checkout session creation failed', [
            'group_id' => $group->id,
            'error' => $e->getMessage(),
        ]);
        
        return redirect()
            ->route('subscriptions.index')
            ->withErrors(['error' => 'チェックアウトセッションの作成に失敗しました。']);
    }
}
```

### 4.2 成功時の処理

```php
// app/Http/Actions/Subscription/SubscriptionSuccessAction.php
public function __invoke(Request $request): Response
{
    $sessionId = $request->query('session_id');
    
    // Checkout Sessionの情報を取得（オプション）
    // 実際のデータ更新はWebhookで行う
    
    return $this->responder->success('サブスクリプションの登録が完了しました。');
}
```

**重要**: 
- 成功画面ではデータ更新を行わない
- Webhookが確実にデータを更新する（非同期・冪等性確保）

---

## 5. Webhook処理のカスタマイズ

### 5.1 Webhook処理の責務分離

```
Stripe Webhook
    ↓
HandleStripeWebhookAction（カスタム）
    ↓
    ├─ 親クラス（CashierのWebhookController）
    │  └─ subscriptionsテーブル自動更新
    │
    └─ カスタムロジック（SubscriptionWebhookService）
       └─ groupsテーブル更新
```

### 5.2 Webhook Action実装

```php
// app/Http/Actions/Token/HandleStripeWebhookAction.php
class HandleStripeWebhookAction extends CashierWebhookController
{
    public function __construct(
        private PaymentServiceInterface $paymentService,
        private SubscriptionWebhookServiceInterface $subscriptionWebhookService
    ) {}

    /**
     * Checkout完了イベント
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        try {
            // カスタムロジック: groupsテーブルのsubscription_plan等を更新
            $this->subscriptionWebhookService->handleCheckoutCompleted($payload);
            
            Log::info('Webhook: Checkout session completed', [
                'session_id' => $payload['data']['object']['id'],
                'customer_id' => $payload['data']['object']['customer'],
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook: Checkout session handling failed', [
                'session_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
        
        return $this->successMethod();
    }

    /**
     * サブスクリプション作成イベント
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        Log::info('Webhook: Before parent call', [
            'customer_id' => $payload['data']['object']['customer'] ?? 'unknown',
            'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
        ]);
        
        // 親クラスのハンドラーでsubscriptionsテーブルを自動更新
        try {
            $response = parent::handleCustomerSubscriptionCreated($payload);
            Log::info('Webhook: Parent call succeeded');
        } catch (\Exception $e) {
            Log::error('Webhook: Parent call failed', [
                'error' => $e->getMessage(),
            ]);
            $response = $this->successMethod();
        }
        
        // カスタムロジック: groupsテーブル更新
        try {
            $this->subscriptionWebhookService->handleSubscriptionCreated($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Subscription created custom handling failed', [
                'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
        
        return $response;
    }

    /**
     * サブスクリプション更新イベント
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        // 親クラスで subscriptions テーブルを更新
        $response = parent::handleCustomerSubscriptionUpdated($payload);
        
        // カスタムロジック: groupsテーブル更新
        try {
            $this->subscriptionWebhookService->handleSubscriptionUpdated($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Subscription updated custom handling failed', [
                'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
        
        return $response;
    }

    /**
     * サブスクリプション削除イベント
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        // 親クラスで subscriptions テーブルを更新
        $response = parent::handleCustomerSubscriptionDeleted($payload);
        
        // カスタムロジック: groupsテーブル更新
        try {
            $this->subscriptionWebhookService->handleSubscriptionDeleted($payload);
        } catch (\Exception $e) {
            Log::error('Webhook: Subscription deleted custom handling failed', [
                'subscription_id' => $payload['data']['object']['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
        
        return $response;
    }
}
```

### 5.3 Webhook Service実装

```php
// app/Services/Subscription/SubscriptionWebhookService.php
class SubscriptionWebhookService implements SubscriptionWebhookServiceInterface
{
    public function __construct(
        protected SubscriptionRepositoryInterface $repository
    ) {}

    public function handleCheckoutCompleted(array $payload): void
    {
        $session = $payload['data']['object'];
        $customerId = $session['customer'];
        $metadata = $session['metadata'] ?? [];
        
        // Stripe Customer IDからGroupを検索
        $group = \Laravel\Cashier\Cashier::findBillable($customerId);
        
        if (!$group) {
            Log::warning('Webhook: Group not found', ['customer_id' => $customerId]);
            return;
        }
        
        // groupsテーブルを更新
        $this->repository->updateGroupSubscription($group, [
            'subscription_active' => true,
            'subscription_plan' => $metadata['plan'] ?? 'family',
            'max_members' => $this->getMaxMembers($metadata['plan'] ?? 'family'),
            'max_groups' => $this->getMaxGroups($metadata['plan'] ?? 'family'),
        ]);
        
        Log::info('Webhook: Group subscription updated', [
            'group_id' => $group->id,
            'plan' => $metadata['plan'] ?? 'family',
        ]);
    }

    public function handleSubscriptionCreated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $customerId = $subscription['customer'];
        
        $group = \Laravel\Cashier\Cashier::findBillable($customerId);
        
        if (!$group) {
            Log::warning('Webhook: Group not found', ['customer_id' => $customerId]);
            return;
        }
        
        // groupsテーブルを更新（念のため）
        $this->repository->updateGroupSubscription($group, [
            'subscription_active' => true,
        ]);
    }

    public function handleSubscriptionUpdated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $customerId = $subscription['customer'];
        $status = $subscription['status'];
        
        $group = \Laravel\Cashier\Cashier::findBillable($customerId);
        
        if (!$group) {
            return;
        }
        
        // ステータスに応じてgroupsテーブルを更新
        $this->repository->updateGroupSubscription($group, [
            'subscription_active' => in_array($status, ['active', 'trialing']),
        ]);
    }

    public function handleSubscriptionDeleted(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $customerId = $subscription['customer'];
        
        $group = \Laravel\Cashier\Cashier::findBillable($customerId);
        
        if (!$group) {
            return;
        }
        
        // groupsテーブルを無料プランに戻す
        $this->repository->updateGroupSubscription($group, [
            'subscription_active' => false,
            'subscription_plan' => null,
            'max_members' => 6,
            'max_groups' => 1,
        ]);
    }
}
```

**設計ポイント**:
- Webhook処理は**冪等性**を確保（同じイベントが複数回来ても問題ない）
- エラーは記録するが、StripeにはSuccessレスポンスを返す（再送を防ぐ）
- `Cashier::findBillable()`でGroupを検索（静的プロパティ使用）

### 5.4 Webhook URL設定

#### ルーティング

```php
// routes/web.php
Route::post(
    'stripe/webhook',
    \App\Http\Actions\Token\HandleStripeWebhookAction::class
)->name('cashier.webhook');
```

#### Stripeダッシュボード設定

1. **Developers** > **Webhooks** > **Add endpoint**
2. **Endpoint URL**: `https://yourdomain.com/stripe/webhook`
3. **Events to send**:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
4. **Signing secret**をコピーして`.env`の`STRIPE_WEBHOOK_SECRET`に設定

---

## 6. サブスクリプション状態の取得

### 6.1 Repository実装

```php
// app/Repositories/Subscription/SubscriptionEloquentRepository.php
public function getCurrentSubscription(Group $group): ?Subscription
{
    // Eloquentリレーション経由で取得
    return $group->subscriptions()
        ->where('type', 'default')
        ->where('stripe_status', 'active')
        ->latest()
        ->first();
}

public function isSubscriptionActive(Subscription $subscription): bool
{
    return in_array($subscription->stripe_status, ['active', 'trialing']);
}
```

**重要**: 
- `$group->subscriptions()`を使用（`subscriptions.user_id`にはGroup IDが入っている）
- `stripe_status`が`'active'`または`'trialing'`の場合、サブスクリプション有効

### 6.2 Service実装（データ整形）

```php
// app/Services/Subscription/SubscriptionService.php
public function getCurrentSubscription(Group $group): ?array
{
    // Repository経由で取得
    $subscription = $this->repository->getCurrentSubscription($group);
    
    if (!$subscription) {
        return null;
    }
    
    // アクティブチェック
    if (!$this->repository->isSubscriptionActive($subscription)) {
        return null;
    }
    
    // データ整形（Serviceの責務）
    $plan = $group->subscription_plan;
    
    if (empty($plan) || !isset(config('const.stripe.subscription_plans')[$plan])) {
        return null;
    }
    
    $planConfig = config("const.stripe.subscription_plans.{$plan}");
    
    return [
        'plan' => $plan,
        'name' => $planConfig['name'],
        'amount' => $planConfig['amount'],
        'currency' => $planConfig['currency'],
        'interval' => $planConfig['interval'],
        'stripe_status' => $subscription->stripe_status,
        'trial_ends_at' => $subscription->trial_ends_at,
        'ends_at' => $subscription->ends_at,
        'created_at' => $subscription->created_at,
    ];
}
```

---

## 7. トークン購入（将来実装）

### 7.1 Payment Intent方式

**目的**: 単発のトークンパッケージ購入

#### フロー

```
1. ユーザーがトークンパッケージを選択
   ↓
2. Laravel: Payment Intent作成
   ↓
3. Stripe Elements: カード情報入力UI表示
   ↓
4. ユーザー: カード情報入力・確認
   ↓
5. Stripe: Payment Intent確定
   ↓
6. Stripe: Webhookでpayment_intent.succeededイベント送信
   ↓
7. Laravel: Webhook処理でトークン付与
```

#### 実装イメージ

```php
// app/Repositories/Token/TokenEloquentRepository.php
public function createPaymentIntent(User $user, int $amount, array $metadata): PaymentIntent
{
    return $user->charge($amount, null, [
        'metadata' => $metadata,
    ]);
}

// Webhook処理
protected function handlePaymentIntentSucceeded(array $payload): void
{
    $paymentIntent = $payload['data']['object'];
    $metadata = $paymentIntent['metadata'];
    
    $userId = $metadata['user_id'];
    $packageId = $metadata['package_id'];
    
    // トークン付与処理
    $this->paymentService->handlePaymentSucceeded($payload);
}
```

---

## 8. 注意事項・ベストプラクティス

### 8.1 Cashier初期化の重要性

**問題**: `config/cashier.php`に`'model' => App\Models\Group::class`を設定しても、Cashierは実行時にこの値を読み込まない。

**原因**: Cashierは静的プロパティ`Cashier::$customerModel`を使用しており、デフォルト値は`'App\Models\User'`。

**解決策**: `AppServiceProvider::boot()`で明示的に初期化する。

```php
public function boot(): void
{
    \Laravel\Cashier\Cashier::useCustomerModel(\App\Models\Group::class);
}
```

### 8.2 Webhook処理の冪等性

Stripeは同じWebhookイベントを複数回送信する可能性があります。

**対策**:
- データベース更新時に`updateOrCreate()`や条件付きUPDATEを使用
- `stripe_id`をUNIQUE制約で保護
- 既に処理済みのイベントはスキップ

```php
public function handleSubscriptionCreated(array $payload): void
{
    $subscription = $payload['data']['object'];
    $stripeId = $subscription['id'];
    
    // 既に存在する場合はスキップ
    if (Subscription::where('stripe_id', $stripeId)->exists()) {
        Log::info('Webhook: Subscription already exists', ['stripe_id' => $stripeId]);
        return;
    }
    
    // 処理続行...
}
```

### 8.3 テスト環境と本番環境の分離

**重要**: Test ModeとLive Modeで異なるAPI Keyを使用する。

```bash
# テスト環境 (.env)
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxx
STRIPE_TEST_MODE=true
STRIPE_FAMILY_PLAN_PRICE_ID=price_test_xxxxxxxxxxxxxxxxxxxxx

# 本番環境 (.env.production)
STRIPE_KEY=pk_live_xxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_live_xxxxxxxxxxxxxxxxxxxxx
STRIPE_TEST_MODE=false
STRIPE_FAMILY_PLAN_PRICE_ID=price_xxxxxxxxxxxxxxxxxxxxx
```

**注意点**:
- Test ModeとLive Modeで`price_id`が異なる
- Webhook URLも環境ごとに設定が必要
- テスト用カード番号: `4242 4242 4242 4242`

### 8.4 セキュリティ

#### Webhook署名検証

Cashierは自動的にWebhook署名を検証します（`STRIPE_WEBHOOK_SECRET`使用）。

```php
// CashierのWebhookControllerが自動的に検証
protected function verifyWebhookSignature(Request $request)
{
    // Stripe署名検証ロジック
}
```

#### エラーハンドリング

```php
try {
    $checkout = $group->newSubscription('default', $priceId)->checkout();
    return redirect($checkout->url);
} catch (\Laravel\Cashier\Exceptions\IncompletePayment $e) {
    // 決済未完了
    Log::error('Payment incomplete', ['error' => $e->getMessage()]);
    return redirect()->back()->withErrors(['error' => '決済が完了しませんでした。']);
} catch (\Exception $e) {
    // その他のエラー
    Log::error('Checkout failed', ['error' => $e->getMessage()]);
    return redirect()->back()->withErrors(['error' => 'エラーが発生しました。']);
}
```

### 8.5 ログとモニタリング

#### 必須ログ

```php
// Webhook受信ログ
Log::info('Webhook received', [
    'event' => $payload['type'],
    'object_id' => $payload['data']['object']['id'],
]);

// 決済成功ログ
Log::info('Payment succeeded', [
    'customer_id' => $customerId,
    'amount' => $amount,
]);

// エラーログ
Log::error('Webhook processing failed', [
    'event' => $payload['type'],
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

#### Stripeダッシュボード確認項目

- Webhookの配信状態（成功/失敗）
- Payment Intentのステータス
- Subscriptionのステータス
- 顧客情報（Customer）

---

## 9. 関連ドキュメント

- **Stripe公式ドキュメント**: https://stripe.com/docs
- **Laravel Cashier公式**: https://laravel.com/docs/11.x/billing
- **プロジェクト内**:
  - `docs/stripe-products/STRIPE_SETUP_GUIDE.md`: Stripe商品設定ガイド
  - `docs/plans/phase1-1-stripe-subscription-plan.md`: サブスクリプション実装計画
  - `definitions/Purchase.md`: 決済機能要件定義
  - `docs/reports/2025-12-03-subscription-display-bug-fix-report.md`: Cashier初期化問題の修正レポート

---

## 10. まとめ

本プロジェクトでは、Laravel CashierをカスタマイズしてGroup単位のサブスクリプション課金を実現しています。

**重要なカスタマイズポイント**:
1. **BillableモデルをGroupに変更** - `Cashier::useCustomerModel()`で初期化必須
2. **subscriptions.user_idにGroup IDを格納** - 命名に惑わされない
3. **Webhook処理の二重管理** - Cashier標準（subscriptions）+ カスタム（groups）
4. **Checkout Session方式** - iPad互換性とセキュリティを重視
5. **冪等性の確保** - Webhook再送に対応

この設計により、安全で拡張可能なサブスクリプション課金システムを実現しています。

---

**作成日**: 2025-12-03  
**作成者**: GitHub Copilot  
**対象バージョン**: Laravel 12, Laravel Cashier 15.x, Stripe API 2024-11-20  
**レビュー**: 要レビュー
