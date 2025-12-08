# サブスクリプション管理API 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-01-18 | GitHub Copilot | 初版作成: モバイルアプリ向けサブスクリプション管理API仕様 |

---

## 1. 概要

MyTeacherモバイルアプリにおけるサブスクリプション管理機能のためのREST API仕様書です。Webアプリケーションと同等の機能を提供し、ネイティブUIでのサブスクリプション管理を実現します。

### 1.1 目的

- Stripe決済を利用したサブスクリプション管理機能の提供
- プラン選択、決済、変更、キャンセル、請求履歴の管理
- グループ単位でのサブスクリプション制御

### 1.2 対象ユーザー

- **親ユーザー**: グループ管理者（サブスクリプション管理権限あり）
- **子どもユーザー**: サブスクリプション管理画面は非表示（アクセス禁止）

### 1.3 プラン種別

| プラン名 | 価格 | 最大メンバー数 | 最大グループ数 | 無料トライアル |
|---------|------|--------------|-------------|-------------|
| family | 500円/月 | 6名 | 1グループ | 14日間 |
| enterprise | 3,000円/月 + 150円/追加メンバー | 20名（基本） | 5グループ | 14日間 |

### 1.4 関連テーブル

- `subscriptions`: Stripe連携のサブスクリプション情報（user_id、stripe_id、stripe_status等）
- `subscription_items`: Stripeサブスクリプションアイテム（stripe_product、stripe_price等）
- `groups`: グループ情報 + サブスク状態（subscription_active、subscription_plan等）

---

## 2. 認証・権限

### 2.1 認証方式

- **Sanctumトークン認証**: `Authorization: Bearer {token}` ヘッダー
- **すべてのエンドポイント**: `auth:sanctum` ミドルウェア必須

### 2.2 権限チェック

| チェック項目 | 条件 | エラー時レスポンス |
|------------|------|------------------|
| 子どもテーマ | `useChildTheme()` が `true` の場合アクセス拒否 | 403 Forbidden |
| グループ管理権限 | グループ管理者 または `group_edit_flg = true` | 403 Forbidden |

**チェック実装**:
```php
// SubscriptionService::canManageSubscription() を使用
if (!$this->subscriptionService->canManageSubscription($user, $group)) {
    return response()->json(['error' => 'サブスクリプション管理権限がありません。'], 403);
}
```

---

## 3. APIエンドポイント一覧

| No | メソッド | エンドポイント | 説明 | 画面遷移 |
|----|---------|--------------|------|---------|
| 1 | GET | `/api/v1/subscriptions/plans` | プラン一覧取得 | SubscriptionManageScreen |
| 2 | GET | `/api/v1/subscriptions/current` | 現在のサブスク情報取得 | SubscriptionManageScreen |
| 3 | POST | `/api/v1/subscriptions/checkout` | Checkout Session作成 | WebView（Stripe Checkout） |
| 4 | GET | `/api/v1/subscriptions/invoices` | 請求履歴取得 | SubscriptionManageScreen |
| 5 | POST | `/api/v1/subscriptions/update` | プラン変更 | SubscriptionManageScreen |
| 6 | POST | `/api/v1/subscriptions/cancel` | サブスクキャンセル | SubscriptionManageScreen |
| 7 | POST | `/api/v1/subscriptions/billing-portal` | Billing Portal URL取得 | WebView（Stripe Billing Portal） |

---

## 4. エンドポイント詳細仕様

### 4.1 プラン一覧取得

**エンドポイント**: `GET /api/v1/subscriptions/plans`

**説明**: サブスクリプションプラン一覧を取得します。config/const.phpから取得し、現在のプラン情報も含めて返却します。

**リクエスト**:
- **認証**: 必須（Sanctumトークン）
- **パラメータ**: なし

**レスポンス** (200 OK):
```json
{
  "plans": [
    {
      "plan_id": "family",
      "name": "ファミリープラン",
      "amount": 500,
      "currency": "jpy",
      "interval": "month",
      "max_members": 6,
      "max_groups": 1,
      "trial_days": 14,
      "features": {
        "unlimited_group_tasks": true,
        "monthly_reports": true,
        "group_token_sharing": true
      }
    },
    {
      "plan_id": "enterprise",
      "name": "エンタープライズプラン",
      "amount": 3000,
      "currency": "jpy",
      "interval": "month",
      "max_members": 20,
      "max_groups": 5,
      "trial_days": 14,
      "features": {
        "unlimited_group_tasks": true,
        "monthly_reports": true,
        "group_token_sharing": true,
        "statistics_reports": true,
        "priority_support": true
      }
    }
  ],
  "additional_member_price": 150,
  "current_plan": "family"
}
```

**エラーレスポンス**:
- 401 Unauthorized: 認証トークン不正
- 403 Forbidden: 子どもテーマユーザー

**実装メモ**:
- `SubscriptionService::getAvailablePlans()` を使用
- `SubscriptionService::getCurrentSubscription($group)` でcurrent_plan取得
- `config('const.stripe.subscription_plans')` から取得

---

### 4.2 現在のサブスク情報取得

**エンドポイント**: `GET /api/v1/subscriptions/current`

**説明**: ログインユーザーのグループの現在のサブスクリプション情報を取得します。未加入の場合はnullを返却します。

**リクエスト**:
- **認証**: 必須（Sanctumトークン）
- **パラメータ**: なし

**レスポンス** (200 OK - サブスク加入済み):
```json
{
  "subscription": {
    "plan": "family",
    "active": true,
    "stripe_status": "active",
    "ends_at": null,
    "trial_ends_at": "2025-02-01T15:00:00Z"
  }
}
```

**レスポンス** (200 OK - 未加入):
```json
{
  "subscription": null
}
```

**フィールド説明**:
- `plan`: プラン種別（family/enterprise）
- `active`: サブスク有効フラグ（`groups.subscription_active`）
- `stripe_status`: Stripeステータス（active/trialing/past_due/canceled等）
- `ends_at`: サブスク終了日時（キャンセル済みの場合のみ）
- `trial_ends_at`: 無料トライアル終了日時

**エラーレスポンス**:
- 401 Unauthorized: 認証トークン不正
- 403 Forbidden: 子どもテーマユーザー

**実装メモ**:
- `SubscriptionService::getCurrentSubscription($group)` を使用
- `groups.subscription_plan`、`groups.subscription_active` を参照
- `subscriptions.stripe_status`、`subscriptions.ends_at` を含む

---

### 4.3 Checkout Session作成

**エンドポイント**: `POST /api/v1/subscriptions/checkout`

**説明**: Stripe Checkout Sessionを作成し、WebViewで決済画面を開くためのURLを返却します。

**リクエスト**:
- **認証**: 必須（Sanctumトークン）
- **Content-Type**: `application/json`
- **Body**:
```json
{
  "plan": "family",
  "additional_members": 0
}
```

**パラメータ説明**:
- `plan`: プラン種別（family/enterprise）【必須】
- `additional_members`: 追加メンバー数（enterpriseのみ、デフォルト0）【任意】

**レスポンス** (200 OK):
```json
{
  "checkout_url": "https://checkout.stripe.com/pay/cs_test_xxx..."
}
```

**エラーレスポンス**:
- 400 Bad Request: バリデーションエラー
```json
{
  "error": "プランを選択してください。",
  "errors": {
    "plan": ["プランを選択してください。"]
  }
}
```
- 401 Unauthorized: 認証トークン不正
- 403 Forbidden: 子どもテーマユーザー、管理権限なし
- 500 Internal Server Error: Stripe API呼び出しエラー

**バリデーション規則**:
```php
[
    'plan' => ['required', 'string', 'in:family,enterprise'],
    'additional_members' => ['sometimes', 'integer', 'min:0', 'max:100'],
]
```

**実装メモ**:
- `SubscriptionService::createCheckoutSession($group, $plan, $additionalMembers)` を使用
- `return_url`: モバイルアプリのディープリンク（例: `myteacher://subscription/success`）
- `cancel_url`: モバイルアプリのディープリンク（例: `myteacher://subscription/cancel`）
- familyプランの場合、`additional_members` は無視

---

### 4.4 請求履歴取得

**エンドポイント**: `GET /api/v1/subscriptions/invoices`

**説明**: グループの請求履歴を取得します。デフォルトで直近10件を返却します。

**リクエスト**:
- **認証**: 必須（Sanctumトークン）
- **パラメータ**:
  - `limit` (optional): 取得件数（デフォルト10、最大50）

**リクエスト例**:
```
GET /api/v1/subscriptions/invoices?limit=20
```

**レスポンス** (200 OK):
```json
{
  "invoices": [
    {
      "id": "in_xxx",
      "date": "2025-01-15 15:00:00",
      "total": 500,
      "amount_paid": 500,
      "status": "paid",
      "currency": "jpy",
      "invoice_pdf": "https://pay.stripe.com/invoice/xxx/pdf"
    },
    {
      "id": "in_yyy",
      "date": "2024-12-15 15:00:00",
      "total": 500,
      "amount_paid": 500,
      "status": "paid",
      "currency": "jpy",
      "invoice_pdf": "https://pay.stripe.com/invoice/yyy/pdf"
    }
  ]
}
```

**フィールド説明**:
- `id`: Stripe請求書ID
- `date`: 請求日時（Y-m-d H:i:s形式）
- `total`: 請求総額（円、整数）
- `amount_paid`: 支払済み金額（円、整数）
- `status`: 請求ステータス（paid/open/void/uncollectible）
- `currency`: 通貨コード（jpy）
- `invoice_pdf`: PDF請求書URL

**エラーレスポンス**:
- 401 Unauthorized: 認証トークン不正
- 403 Forbidden: 子どもテーマユーザー、管理権限なし
- 404 Not Found: サブスクリプション未加入

**実装メモ**:
- `SubscriptionService::getInvoiceHistory($group, $limit)` を使用
- `SubscriptionRepository::getInvoices($group, $limit)` でStripe APIから取得
- サブスク未加入の場合は404返却

---

### 4.5 プラン変更

**エンドポイント**: `POST /api/v1/subscriptions/update`

**説明**: 現在のサブスクリプションプランを変更します（即時反映）。

**リクエスト**:
- **認証**: 必須（Sanctumトークン）
- **Content-Type**: `application/json`
- **Body**:
```json
{
  "plan": "enterprise"
}
```

**パラメータ説明**:
- `plan`: 変更先プラン種別（family/enterprise）【必須】

**レスポンス** (200 OK):
```json
{
  "message": "プランを変更しました。",
  "subscription": {
    "plan": "enterprise",
    "active": true,
    "stripe_status": "active",
    "ends_at": null,
    "trial_ends_at": null
  }
}
```

**エラーレスポンス**:
- 400 Bad Request: バリデーションエラー、同じプランへの変更
```json
{
  "error": "既に同じプランに加入しています。"
}
```
- 401 Unauthorized: 認証トークン不正
- 403 Forbidden: 子どもテーマユーザー、管理権限なし
- 404 Not Found: サブスクリプション未加入
- 500 Internal Server Error: Stripe API呼び出しエラー

**バリデーション規則**:
```php
[
    'plan' => ['required', 'string', 'in:family,enterprise'],
]
```

**実装メモ**:
- `SubscriptionService::updateSubscriptionPlan($group, $plan)` を使用
- `SubscriptionRepository::swap($subscription, $newPriceId)` でStripe API呼び出し
- プラン変更後、`groups.subscription_plan` も更新
- 即時反映（prorated=true）

---

### 4.6 サブスクキャンセル

**エンドポイント**: `POST /api/v1/subscriptions/cancel`

**説明**: サブスクリプションをキャンセルします。期間終了時に解約されます（即時解約ではありません）。

**リクエスト**:
- **認証**: 必須（Sanctumトークン）
- **Content-Type**: `application/json`
- **Body**: なし

**レスポンス** (200 OK):
```json
{
  "message": "サブスクリプションをキャンセルしました。期間終了まで利用可能です。",
  "subscription": {
    "plan": "family",
    "active": true,
    "stripe_status": "active",
    "ends_at": "2025-02-15T15:00:00Z",
    "trial_ends_at": null
  }
}
```

**エラーレスポンス**:
- 401 Unauthorized: 認証トークン不正
- 403 Forbidden: 子どもテーマユーザー、管理権限なし
- 404 Not Found: サブスクリプション未加入
- 500 Internal Server Error: Stripe API呼び出しエラー

**実装メモ**:
- `SubscriptionService::cancelSubscription($subscription)` を使用
- `SubscriptionRepository::cancel($subscription)` でStripe API呼び出し
- `subscriptions.ends_at` に期間終了日時が設定される
- 期間終了まで機能は利用可能（`stripe_status` は `active` のまま）

---

### 4.7 Billing Portal URL取得

**エンドポイント**: `POST /api/v1/subscriptions/billing-portal`

**説明**: Stripe Billing PortalのセッションURLを取得します。WebViewで決済方法変更、請求書ダウンロード等の操作が可能です。

**リクエスト**:
- **認証**: 必須（Sanctumトークン）
- **Content-Type**: `application/json`
- **Body**: なし

**レスポンス** (200 OK):
```json
{
  "portal_url": "https://billing.stripe.com/session/xxx..."
}
```

**エラーレスポンス**:
- 401 Unauthorized: 認証トークン不正
- 403 Forbidden: 子どもテーマユーザー、管理権限なし
- 404 Not Found: サブスクリプション未加入
- 500 Internal Server Error: Stripe API呼び出しエラー

**実装メモ**:
- `SubscriptionService::createBillingPortalSession($group)` を使用
- `SubscriptionRepository::createBillingPortalSession($group)` でStripe API呼び出し
- `return_url`: モバイルアプリのディープリンク（例: `myteacher://subscription/manage`）

---

## 5. データ構造詳細

### 5.1 subscriptionsテーブル

| カラム名 | 型 | 説明 |
|---------|---|------|
| id | bigint | 主キー |
| user_id | bigint | グループID（実際はGroupモデルのID） |
| type | varchar(255) | サブスクタイプ（default） |
| stripe_id | varchar(255) | StripeサブスクリプションID（sub_xxx） |
| stripe_status | varchar(255) | Stripeステータス（active/trialing/past_due/canceled等） |
| stripe_price | varchar(255) | Stripe価格ID |
| quantity | integer | 数量（通常1） |
| trial_ends_at | timestamp | 無料トライアル終了日時 |
| ends_at | timestamp | サブスク終了日時（キャンセル時のみ） |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |

### 5.2 subscription_itemsテーブル

| カラム名 | 型 | 説明 |
|---------|---|------|
| id | bigint | 主キー |
| subscription_id | bigint | サブスクリプションID |
| stripe_id | varchar(255) | StripeサブスクリプションアイテムID（si_xxx） |
| stripe_product | varchar(255) | Stripe商品ID（prod_xxx） |
| stripe_price | varchar(255) | Stripe価格ID（price_xxx） |
| meter_id | varchar(255) | メーターID（メータープランの場合） |
| meter_event_name | varchar(255) | メーターイベント名 |
| quantity | integer | 数量 |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |

### 5.3 groupsテーブル（サブスク関連カラム）

| カラム名 | 型 | 説明 |
|---------|---|------|
| subscription_active | boolean | サブスク有効フラグ |
| subscription_plan | varchar(255) | プラン種別（family/enterprise） |
| max_members | integer | 最大メンバー数（デフォルト6） |
| max_groups | integer | 最大グループ数（デフォルト1） |
| free_group_task_limit | integer | 無料グループタスク作成回数（月次） |
| group_task_count_current_month | integer | 今月のグループタスク作成回数 |
| group_task_count_reset_at | timestamp | グループタスクカウントリセット日時 |
| free_trial_days | integer | 無料トライアル日数 |
| report_enabled_until | date | 実績レポート利用可能期限 |

---

## 6. エラーハンドリング

### 6.1 エラーレスポンス形式

```json
{
  "error": "エラーメッセージ",
  "errors": {
    "field_name": ["詳細エラーメッセージ"]
  }
}
```

### 6.2 HTTPステータスコード

| コード | 説明 |
|-------|------|
| 200 OK | 成功 |
| 400 Bad Request | バリデーションエラー、不正なリクエスト |
| 401 Unauthorized | 認証失敗 |
| 403 Forbidden | 権限不足 |
| 404 Not Found | リソース不存在 |
| 500 Internal Server Error | サーバーエラー |

### 6.3 Stripe APIエラー

Stripe API呼び出しエラーは500 Internal Server Errorで返却し、ログに詳細を記録します。

```php
try {
    $result = $this->subscriptionService->createCheckoutSession($group, $plan);
    return response()->json(['checkout_url' => $result->url], 200);
} catch (\Exception $e) {
    Log::error('Stripe Checkout Session作成エラー', [
        'error' => $e->getMessage(),
        'group_id' => $group->id,
        'plan' => $plan,
    ]);
    return response()->json(['error' => '決済処理でエラーが発生しました。'], 500);
}
```

---

## 7. 実装上の注意事項

### 7.1 アーキテクチャパターン

**Action-Service-Repositoryパターン** を遵守します。

```
Route → Action (__invoke) → Service → Repository → Model
                  ↓
              Responder → Response
```

**実装例**:
```php
// routes/api.php
Route::middleware('auth:sanctum')->prefix('v1/subscriptions')->group(function () {
    Route::get('/plans', GetSubscriptionPlansAction::class);
    Route::get('/current', GetCurrentSubscriptionAction::class);
    Route::post('/checkout', CreateCheckoutSessionAction::class);
    Route::get('/invoices', GetInvoiceHistoryAction::class);
    Route::post('/update', UpdateSubscriptionPlanAction::class);
    Route::post('/cancel', CancelSubscriptionAction::class);
    Route::post('/billing-portal', CreateBillingPortalSessionAction::class);
});

// GetSubscriptionPlansAction.php
public function __invoke(Request $request): JsonResponse {
    // 権限チェック
    if ($request->user()->useChildTheme()) {
        return response()->json(['error' => 'アクセス権限がありません。'], 403);
    }
    
    // Service呼び出し
    $plans = $this->subscriptionService->getAvailablePlans();
    $currentPlan = $this->subscriptionService->getCurrentSubscription($request->user()->group);
    
    // Responder経由で返却
    return $this->responder->success([
        'plans' => $plans,
        'additional_member_price' => config('const.stripe.additional_member_amount'),
        'current_plan' => $currentPlan ? $currentPlan['plan'] : null,
    ]);
}
```

### 7.2 Responder使用（推奨）

新規コードではResponderを使用してレスポンス整形を統一します。

```php
// SubscriptionResponder.php
public function success(array $data): JsonResponse {
    return response()->json($data, 200);
}

public function error(string $message, int $code = 400): JsonResponse {
    return response()->json(['error' => $message], $code);
}

public function validationError(array $errors): JsonResponse {
    return response()->json(['error' => 'バリデーションエラー', 'errors' => $errors], 400);
}
```

### 7.3 トランザクション管理

サブスクリプション情報と関連テーブル（groups等）の更新は必ず `DB::transaction()` を使用します。

```php
try {
    DB::transaction(function () use ($group, $plan) {
        // Stripe API呼び出し
        $subscription = $this->repository->swap($subscription, $newPriceId);
        
        // groups.subscription_plan更新
        $group->subscription_plan = $plan;
        $group->save();
    });
    return response()->json(['message' => 'プランを変更しました。'], 200);
} catch (\Exception $e) {
    Log::error('プラン変更エラー', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'プラン変更に失敗しました。'], 500);
}
```

### 7.4 ログ出力

重要な処理は必ずログ出力します。

```php
Log::info('Checkout Session作成', [
    'group_id' => $group->id,
    'plan' => $plan,
    'additional_members' => $additionalMembers,
]);

Log::error('Stripe APIエラー', [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

### 7.5 テスト必須項目

- ✅ 認証トークンなしでアクセス拒否（401）
- ✅ 子どもテーマユーザーがアクセス拒否（403）
- ✅ 管理権限なしでアクセス拒否（403）
- ✅ バリデーションエラー（400）
- ✅ プラン一覧取得成功（200）
- ✅ 現在のサブスク情報取得（加入済み/未加入）
- ✅ Checkout Session作成成功（200）
- ✅ 請求履歴取得成功（200）
- ✅ プラン変更成功（200）
- ✅ サブスクキャンセル成功（200）
- ✅ Billing Portal URL取得成功（200）

---

## 8. 参考情報

### 8.1 参照ドキュメント

- **計画書**: `docs/plans/phase2-mobile-app-implementation-plan.md` (Phase 2.B-6)
- **トークン購入要件定義**: `definitions/mobile/TokenPurchaseWebView.md`（WebView方式の参考）
- **Webアプリルート**: `routes/web.php` (Lines 320-340)
- **SubscriptionService**: `app/Services/Subscription/SubscriptionService.php`
- **SubscriptionServiceInterface**: `app/Services/Subscription/SubscriptionServiceInterface.php`
- **SubscriptionRepositoryInterface**: `app/Repositories/Subscription/SubscriptionRepositoryInterface.php`
- **プラン設定**: `config/const.php` (Lines 130-180)
- **OpenAPI仕様書**: `docs/api/openapi.yaml` (Subscriptionsタグ)

### 8.2 OpenAPI仕様書の更新

**重要**: APIエンドポイントを追加した場合は、必ず `docs/api/openapi.yaml` にも定義を追加してください。

**更新手順**:
1. `openapi.yaml` のtagsセクションに `Subscriptions` タグを追加（既に追加済み）
2. pathsセクションに7つのエンドポイント定義を追加（既に追加済み）
3. 各エンドポイントのリクエスト/レスポンススキーマを定義
4. 認証方式（`SanctumAuth`）を明記
5. エラーレスポンス（401、403、404、500）を定義

**追加済みエンドポイント**:
- `GET /subscriptions/plans` - プラン一覧取得
- `GET /subscriptions/current` - 現在のサブスク情報取得
- `POST /subscriptions/checkout` - Checkout Session作成
- `GET /subscriptions/invoices` - 請求履歴取得
- `POST /subscriptions/update` - プラン変更
- `POST /subscriptions/cancel` - サブスクキャンセル
- `POST /subscriptions/billing-portal` - Billing Portal URL取得

### 8.3 既存実装との齟齬確認結果

| 確認項目 | 結果 | 備考 |
|---------|------|------|
| テーブル構造 | ✅ 齟齬なし | subscriptions、subscription_items、groups |
| Service実装 | ✅ 齟齬なし | 19メソッド実装済み |
| Repository実装 | ✅ 齟齬なし | 9メソッド実装済み |
| プラン設定 | ✅ 齟齬なし | family、enterpriseプラン定義済み |
| サブスク条件付き機能 | ✅ 動作確認済み | canSelectPeriod、canAccessPastReport等 |
| OpenAPI仕様書 | ✅ 更新完了 | docs/api/openapi.yaml に7エンドポイント追加済み |

### 8.4 Webアプリとの差異

| 項目 | Webアプリ | モバイルアプリ |
|------|----------|-------------|
| 認証方式 | セッション + CSRF | Sanctumトークン |
| レスポンス形式 | Blade View | JSON API |
| Stripe Checkout | サーバーサイドレンダリング | WebView（ディープリンク） |
| Billing Portal | サーバーサイドレンダリング | WebView（ディープリンク） |
| OpenAPI仕様書 | 不要 | 必須（docs/api/openapi.yaml） |

---

## 9. 次のステップ

1. **APIエンドポイント実装**:
   - Action、Service、Repository、Responderの実装
   - ルート定義（`routes/api.php`）
   - DIバインディング（`AppServiceProvider`）

2. **OpenAPI仕様書の更新** ✅ **完了**:
   - `docs/api/openapi.yaml` に7エンドポイント定義追加済み
   - Subscriptionsタグ追加済み
   - リクエスト/レスポンススキーマ定義完了

3. **テスト実装**:
   - Feature Test: 7エンドポイントの統合テスト
   - Unit Test: Service、Repositoryのロジックテスト

4. **モバイル画面実装**:
   - `definitions/mobile/SubscriptionManagementScreen.md` 作成済み
   - SubscriptionManageScreen.tsx実装

5. **動作確認**:
   - Stripe Test Modeでの決済フロー確認
   - サブスク条件付き機能の動作確認

---

**重要**: 今後、新しいAPIエンドポイントを追加する際は、必ず以下の手順を実施してください：

1. ✅ 要件定義書作成（本ドキュメント）
2. ✅ **OpenAPI仕様書更新** ← 【必須】`docs/api/openapi.yaml` に定義追加
3. ⬜ バックエンドAPI実装（routes/api.php、Action、Service等）
4. ⬜ テスト実装
5. ⬜ モバイル画面実装

---

以上
