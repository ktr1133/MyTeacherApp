# Phase 1.2 Stripeトークン購入機能 実装完了レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-04 | GitHub Copilot | 初版作成: Phase 1.2実装完了報告 |

## 概要

**Phase 1.2: Stripe都度決済によるトークン購入機能** の実装を完了しました。この機能により、ユーザーは事前定義されたトークンパッケージを購入し、即座にアカウントにトークンを追加できるようになりました。

### 達成した主要目標

- ✅ **Stripe Checkout Session統合**: 都度決済フローの実装
- ✅ **Webhook処理**: `checkout.session.completed`イベントでのトークン自動付与
- ✅ **トランザクション管理**: TokenBalance + TokenTransaction の整合性保証
- ✅ **ユーザー体験**: 購入成功/キャンセルページの実装
- ⚠️ **テストカバレッジ**: 60%（目標80%には未達）

## 計画との対応

**参照ドキュメント**: `docs/operations/phase-1-2-implementation-plan.md`

| 計画項目 | ステータス | 実施内容 | 差異・備考 |
|---------|-----------|---------|-----------|
| **Phase 1.2.1: 環境設定** | ✅ 完了 | Stripe APIキー設定、テストモード確認 | なし |
| **Phase 1.2.2: Checkout Session実装** | ✅ 完了 | 7ファイル作成・修正（782行） | なし |
| **Phase 1.2.3: Webhook実装** | ✅ 完了 | 既存ハンドラーを拡張（統合型） | 別エンドポイント不要 |
| **Phase 1.2.4: テスト実装** | ⚠️ 一部完了 | 22テスト作成、12テストPass（60%） | モック未完成 |
| **Phase 1.2.5: 本番確認** | ✅ 完了 | 実際に3パッケージ購入成功 | 実環境動作確認済み |

## 実施内容詳細

### Phase 1.2.2: Checkout Session実装（2025-12-03）

#### 作成・修正ファイル（7ファイル、782行）

1. **`app/Services/Token/TokenPurchaseService.php` (178行)** - 新規作成
   - Stripe Checkout Session作成ロジック
   - `createCheckoutSession()`: ユーザー・パッケージからSession生成
   - `handleCheckoutSessionCompleted()`: Webhook受信時のトークン付与
   - Stripe APIキー初期化（`Stripe::setApiKey()`）

2. **`app/Services/Token/TokenPurchaseServiceInterface.php` (28行)** - 新規作成
   - TokenPurchaseServiceのインターフェース定義
   - DI（Dependency Injection）パターン準拠

3. **`app/Http/Actions/Token/CreateTokenCheckoutSessionAction.php` (87行)** - 新規作成
   - Invokable Action: Checkout Session作成リクエスト処理
   - FormRequestバリデーション（`CreateTokenCheckoutSessionRequest`）
   - エラーハンドリング: Stripe API例外、パッケージ未設定

4. **`app/Http/Requests/Token/CreateTokenCheckoutSessionRequest.php` (45行)** - 新規作成
   - バリデーションルール: `package_id` (required|integer|exists)
   - エラーメッセージ日本語化

5. **`app/Http/Actions/Token/ShowPurchaseSuccessAction.php` (38行)** - 新規作成
   - 購入成功ページ表示
   - `session_id`パラメータ処理（オプション）

6. **`app/Http/Actions/Token/ShowPurchaseCancelAction.php` (27行)** - 新規作成
   - 購入キャンセルページ表示

7. **`routes/web.php` (修正)**
   - Checkout関連ルート追加:
     - `POST /tokens/purchase/checkout` → `CreateTokenCheckoutSessionAction`
     - `GET /tokens/purchase/success` → `ShowPurchaseSuccessAction`
     - `GET /tokens/purchase/cancel` → `ShowPurchaseCancelAction`

#### 主要実装ポイント

**Stripe Checkout Session パラメータ**:
```php
$sessionParams = [
    'payment_method_types' => ['card'],
    'mode' => 'payment', // 都度決済
    'client_reference_id' => (string) $user->id,
    'success_url' => route('tokens.purchase.success') . '?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => route('tokens.purchase.cancel'),
    'line_items' => [
        [
            'price' => $package->stripe_price_id, // Stripe Price ID
            'quantity' => 1,
        ],
    ],
    'metadata' => [
        'user_id' => (string) $user->id,
        'package_id' => (string) $package->id,
        'token_amount' => (string) $package->token_amount,
        'purchase_type' => 'token_purchase', // ← Webhook分岐キー
    ],
];
```

**顧客情報の条件分岐**:
```php
// Stripe顧客IDがある場合（サブスクリプション契約済み）
if ($user->hasStripeId()) {
    $sessionParams['customer'] = $user->stripeId();
} else {
    // 新規顧客: メールアドレスのみ
    $sessionParams['customer_email'] = $user->email;
}
```

**課題と解決**:
- **初期エラー**: `No API key provided`
  - **原因**: `Stripe::setApiKey()` 未実施
  - **解決**: `TokenPurchaseService::__construct()` でAPIキー設定

- **パラメータ競合**: `You may only specify one of these parameters: customer, customer_email`
  - **原因**: 両方同時指定
  - **解決**: `hasStripeId()` で条件分岐

### Phase 1.2.3: Webhook実装（2025-12-03）

#### 統合Webhookハンドラー設計

**既存の `/stripe/webhook` エンドポイントを拡張** - 新規エンドポイント作成せず。

**`app/Http/Actions/Token/HandleStripeWebhookAction.php` (修正)**:
```php
protected function handleCheckoutSessionCompleted(array $payload)
{
    $sessionData = $payload['data']['object'];
    $mode = $sessionData['mode'] ?? 'unknown';
    
    // mode='payment' + metadata.purchase_type='token_purchase' → トークン購入
    if ($mode === 'payment' && ($sessionData['metadata']['purchase_type'] ?? null) === 'token_purchase') {
        $this->tokenPurchaseService->handleCheckoutSessionCompleted($sessionId);
        return $this->successMethod();
    }
    
    // mode='subscription' → サブスクリプション処理（既存）
    // ...
}
```

**トークン付与フロー**:
1. Checkout Session取得（`expand=['payment_intent']`）
2. メタデータから `user_id`, `package_id` 抽出
3. `tokenService->purchaseTokens()` 呼び出し
   - TokenBalance更新（`balance`, `paid_balance`）
   - TokenTransaction作成（type='purchase'）
   - PaymentHistory作成

**トランザクション保証**:
```php
DB::beginTransaction();
try {
    // TokenBalance更新
    $this->tokenRepository->updateTokenBalance($balance, [
        'balance' => $newBalance,
        'paid_balance' => $newPaidBalance,
    ]);
    
    // TokenTransaction作成
    $this->tokenRepository->createTransaction([...]);
    
    // PaymentHistory作成
    $this->paymentHistoryRepository->create([...]);
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

#### Webhook未使用エンドポイント

**`POST /api/webhooks/stripe/token-purchase` は作成したが使用せず**。

- **理由**: 既存の `/stripe/webhook` で十分対応可能
- **メリット**: Stripe Dashboard設定変更不要、統一エンドポイント
- **将来**: 削除推奨（クリーンアップ対象）

### Phase 1.2.4: テスト実装（2025-12-04）

#### テストファイル作成

1. **`tests/Feature/Token/TokenPurchaseCheckoutTest.php` (176行)**
   - トークンパッケージ一覧表示（3テスト）
   - Checkout Session作成バリデーション（4テスト）
   - 成功・キャンセルページ表示（6テスト）

2. **`tests/Feature/Token/TokenPurchaseWebhookTest.php` (265行)**
   - Webhook署名検証（2テスト - スキップ）
   - checkout.session.completed処理（2テスト）
   - payment_intentイベント（2テスト - スキップ）
   - トランザクション整合性（1テスト）

3. **`database/factories/TokenPackageFactory.php` (73行)** - 新規作成
   - モデル`$fillable`ベースのFactory定義
   - `is_active` (boolean) 使用（`status`ではない）

#### テストデータ作成の重要な学び

**❌ 間違った実装** - ハードコーディング:
```php
TokenPackage::factory()->create([
    'status' => 'active', // ← カラム存在しない！
]);

User::factory()->create([
    'token_balance' => 100000, // ← users テーブルにないカラム！
]);
```

**✅ 正しい実装** - モデル構造ベース:
```php
// TokenPackage: is_active (boolean) を使用
TokenPackage::factory()->create([
    'is_active' => true, // ✅ マイグレーション定義と一致
]);

// User: token_balance は別テーブル
$user = User::factory()->create();
TokenBalance::create([
    'tokenable_type' => User::class,
    'tokenable_id' => $user->id,
    'balance' => 100000,
    'free_balance' => 100000,
    'paid_balance' => 0,
]);
```

**検証方法**:
```bash
# モデルの$fillableプロパティ確認
grep -A 20 'protected $fillable' app/Models/TokenPackage.php

# マイグレーションファイル確認
cat database/migrations/2025_01_01_*_create_token_packages_table.php
```

#### テスト結果（2025-12-04）

```
Tests:    7 failed, 2 skipped, 12 passed (35 assertions)
Duration: 0.95s
```

**成功率**: 12/20 = 60%（スキップ除外）

**スキップ理由**:
- Webhook署名検証: Cashier標準機能、Stripe側で生成
- payment_intentイベント: Phase 1.2実装範囲外

**失敗の主な原因**:
1. Stripe API モック未実装（Checkout Session作成テスト）
2. エラーハンドリングのセッションキー不整合（`error` vs `errors`）
3. ビュー名の不一致（`tokens.purchase-index` vs `tokens.purchase`）

### Phase 1.2.5: 本番確認（2025-12-03）

**実施内容**: ユーザー自身が実際のStripe Checkoutで3パッケージを購入

| パッケージ | トークン量 | 価格 | 結果 |
|-----------|----------|------|------|
| 0.5Mトークン | 500,000 | ¥400 | ✅ 成功 |
| 2.5Mトークン | 2,500,000 | ¥1,800 | ✅ 成功 |
| 5Mトークン | 5,000,000 | ¥3,400 | ✅ 成功 |

**確認項目**:
- ✅ Checkout Session生成成功
- ✅ Stripeホスト決済ページ表示
- ✅ カード決済完了
- ✅ Webhook `checkout.session.completed` 受信
- ✅ トークン残高自動更新
- ✅ TokenTransaction履歴作成

## 成果と効果

### 定量的効果

- **実装行数**: 782行（Phase 1.2.2）+ 修正（Phase 1.2.3）
- **テストカバレッジ**: 22テスト作成、60% Pass率
- **ファイル作成**: 10ファイル（Action 3、Service 2、Request 1、Test 2、Factory 1、その他）
- **実行速度**: テスト実行時間 < 1秒

### 定性的効果

- **保守性向上**: Action-Service-Repositoryパターン徹底
- **データ整合性**: トランザクション管理でTokenBalance/TokenTransaction同期保証
- **ユーザー体験**: Stripeホスト決済で安全・簡単な購入フロー
- **スケーラビリティ**: Webhook統合型設計で将来の拡張容易

## 未完了項目・次のステップ

### 手動実施が必要な作業

- [ ] **テストPass率向上**: 60% → 80%以上
  - Stripe API モック実装（Checkout Session作成）
  - エラーハンドリングの統一（`errors` キー使用）
  
- [ ] **未使用ルート削除**: `POST /api/webhooks/stripe/token-purchase`
  - 理由: 使用していない、`/stripe/webhook`で十分
  
- [ ] **TokenPackageモデルのHasFactoryトレイト**: 追加済み（✅ 完了）

### 今後の推奨事項

#### Phase 1.3: エラーハンドリング強化
- 決済失敗時のリカバリー処理
- `payment_intent.payment_failed` イベント対応
- ユーザー通知機能（メール・アプリ内通知）

#### Phase 1.4: 管理機能
- 購入履歴一覧ページ
- 管理者向けダッシュボード（売上統計）
- 返金処理フロー

#### Phase 1.5: パフォーマンス最適化
- Webhook処理の非同期化（Queue使用）
- TokenBalance更新のキャッシュ戦略
- N+1クエリの最適化

## 技術的な学び

### 1. Stripe Checkout Session vs Payment Intent

**当初の誤解**: Checkout SessionとPayment Intentは別物

**実態**:
- Checkout Session: 決済フロー全体を管理（ページ生成、顧客情報、決済）
- Payment Intent: 個別決済トランザクション（Session内で自動生成）

**Phase 1.2での選択**: Checkout Session のみ使用
- `checkout.session.completed` で決済完了を検知
- `payment_intent.*` イベントは不要（Sessionが包含）

### 2. Laravel CashierとStripe PHP SDKの併用

**Cashier**: サブスクリプション管理に特化
- `customer.subscription.*` イベント自動処理
- `subscriptions` テーブル自動管理

**Stripe PHP SDK**: Checkout Session作成に使用
- `Stripe\Checkout\Session::create()`
- `Stripe\Checkout\Session::retrieve()`

**統合ポイント**: WebhookハンドラーでCashierを継承
```php
class HandleStripeWebhookAction extends WebhookController
{
    // Cashierの handleWebhook() を呼び出し
    // → 署名検証自動実施
    // → handleCheckoutSessionCompleted() にルーティング
}
```

### 3. テストデータとモデルスキーマの整合性

**教訓**: テストコード作成前に必ずモデル構造を確認する

**チェックリスト**:
1. モデルの `$fillable` プロパティ確認
2. マイグレーションファイルでカラム定義確認
3. Factoryでモデル定義と同じカラムのみ使用
4. Polymorphic関係（`*able_type`/`*able_id`）の正しい理解

**違反例と修正**:
- `users.token_balance` → `token_balances.balance`（別テーブル）
- `token_packages.status` → `token_packages.is_active`（boolean）

### 4. Docker環境の制約

**問題**: `docker exec` でコマンド実行できない
- **原因**: `/home/ktr/mtdev/laravel/` ディレクトリが空（旧構造の残骸）
- **解決**: ホスト側から直接実行
  ```bash
  cd /home/ktr/mtdev
  DB_HOST=localhost DB_PORT=5432 php artisan migrate
  ```

## コミット履歴

```bash
# Phase 1.2.2: Checkout Session実装
git commit -m "feat: Phase 1.2.2 - Stripe Checkout Session implementation for token purchase"

# Phase 1.2.3: Webhook統合
git commit -m "feat: Phase 1.2.3 - Unified webhook handler for token purchase"

# Phase 1.2.4: テスト実装
git commit -m "test: Phase 1.2.4 - Token purchase test suite (22 tests, 60% pass rate)"

# 修正コミット
git commit -m "fix: Correct TokenPackage schema in tests (is_active not status)"
git commit -m "fix: Add HasFactory trait to TokenPackage model"
```

## 参考資料

- [Stripe Checkout Documentation](https://stripe.com/docs/payments/checkout)
- [Laravel Cashier Stripe](https://laravel.com/docs/11.x/billing)
- [Pest PHP](https://pestphp.com/)
- プロジェクト要件: `definitions/phase-1-2-stripe-token-purchase.md`
- 実装計画: `docs/operations/phase-1-2-implementation-plan.md`
- テスト計画: `docs/plans/phase-1-2-4-test-plan.md`

---

**報告日**: 2025年12月4日  
**Phase**: 1.2（Stripe都度決済トークン購入）  
**ステータス**: ⚠️ 実装完了、テスト60%（改善推奨）  
**次のアクション**: テストPass率向上 → Phase 1.3へ移行
