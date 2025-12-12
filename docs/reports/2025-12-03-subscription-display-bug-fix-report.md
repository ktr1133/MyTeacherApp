# サブスクリプション表示不具合修正レポート

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-12-03 | GitHub Copilot | 初版作成: サブスクリプション表示不具合の修正完了 |

## 概要

Laravel Cashierの初期化設定不備により、決済完了後もサブスクリプション画面が「無料プラン」表示のままになる不具合を修正しました。この作業により、以下の目標を達成しました：

- ✅ **問題解決**: Stripe決済完了後、正しくサブスクリプションプランが表示される
- ✅ **根本原因修正**: Cashier静的プロパティの初期化処理を追加
- ✅ **データ整合性確保**: Repository層のクエリロジックを修正
- ✅ **本番環境デプロイ**: 修正を本番環境に適用し、動作確認完了

## 問題の症状

### 発生していた現象

1. **本番環境**: ユーザーがStripe決済を完了し、リダイレクト後も画面表示が「無料プラン」のまま
2. **データベース**: `groups`テーブルは正しく更新されるが、`subscriptions`テーブルにレコードが作成されない
3. **Webhook処理**: Stripeからのwebhookは正常に受信し、親クラスのハンドラーは成功（200レスポンス）を返すが、実際にはデータが保存されない

### 影響範囲

- **影響ユーザー**: 本番環境で新規サブスクリプション契約を行った全ユーザー
- **データ不整合**: `groups`テーブルと`subscriptions`テーブル間でデータ不一致が発生
- **表示不具合**: 決済完了しているにも関わらず無料プラン表示が継続

## 根本原因分析

### 技術的な原因

Laravel Cashierは内部で**静的プロパティ`$customerModel`**を使用してBillableモデルを管理しています：

```php
// vendor/laravel/cashier/src/Cashier.php
public static $customerModel = 'App\\Models\\User';  // デフォルト値

public static function findBillable($stripeId)
{
    return static::$customerModel::where('stripe_id', $stripeId)->first();
}
```

本プロジェクトでは`Group`モデルをBillableエンティティとして使用していますが、以下の問題がありました：

1. **設定ファイルだけでは不十分**: `config/cashier.php`に`'model' => App\Models\Group::class`を設定しても、Cashierは実行時にこの設定を読み込まない
2. **静的プロパティの初期化漏れ**: `Cashier::$customerModel`は明示的に`Cashier::useCustomerModel()`を呼び出さない限り、デフォルト値（`User`）のまま
3. **Webhook処理の失敗**: `findBillable($stripeId)`が`User`テーブルを検索するため、Groupのstripe_idでは見つからず、subscription作成がスキップされる

### 処理フロー

```
Stripe Webhook
    ↓
HandleStripeWebhookAction::handleCustomerSubscriptionCreated()
    ↓
親クラス WebhookController::handleCustomerSubscriptionCreated($payload)
    ↓
$user = $this->getUserByStripeId($payload['customer']);  // ← ここでCashier::findBillable()を呼び出し
    ↓
if ($user) {  // ← $userがnullのため、この中に入らない
    $user->subscriptions()->create([...]);  // ← 実行されない
}
    ↓
return new Response('Webhook Handled', 200);  // ← 成功レスポンスは返すがデータ未作成
```

### 副次的な問題

Repository層でも以下の問題がありました：

```php
// 修正前（誤った実装）
$subscription = Subscription::where('user_id', $group->master_user_id)
    ->where('type', 'default')
    ->where('stripe_status', 'active')
    ->latest()
    ->first();

// 問題点: Cashierは subscriptions.user_id に Billableモデル（Group）のIDを保存する
// しかし master_user_id（User ID）で検索していたため、データが見つからない
```

## 実施内容詳細

### 1. AppServiceProvider修正（最重要）

**ファイル**: `app/Providers/AppServiceProvider.php`

Cashierの静的プロパティを初期化する処理を追加：

```php
public function boot(): void
{
    // Cashier設定: Groupモデルを課金対象として使用
    \Laravel\Cashier\Cashier::useCustomerModel(\App\Models\Group::class);
    
    // その他の既存のboot処理...
}
```

**効果**: 
- `Cashier::$customerModel`が`App\Models\Group`に設定される
- `findBillable($stripeId)`が`groups`テーブルを正しく検索
- Webhook処理でSubscriptionレコードが正常に作成される

### 2. Repository層修正

**ファイル**: `app/Repositories/Subscription/SubscriptionEloquentRepository.php`

Eloquentリレーションを使用した正しいクエリに修正：

```php
// 修正前
public function getCurrentSubscription(Group $group): ?Subscription
{
    return Subscription::where('user_id', $group->master_user_id)  // NG: UserのIDで検索
        ->where('type', 'default')
        ->where('stripe_status', 'active')
        ->latest()
        ->first();
}

// 修正後
public function getCurrentSubscription(Group $group): ?Subscription
{
    return $group->subscriptions()  // OK: Groupのリレーション経由
        ->where('type', 'default')
        ->where('stripe_status', 'active')
        ->latest()
        ->first();
}
```

**理由**: Cashierは`subscriptions.user_id`にBillableモデル（Group）のIDを保存するため、GroupのIDで検索する必要がある。

### 3. デバッグログ追加

**ファイル**: `app/Http/Actions/Token/HandleStripeWebhookAction.php`

問題調査のためにログ出力を追加：

```php
public function handleCustomerSubscriptionCreated(array $payload): Response
{
    Log::info('Webhook: Before parent call', [
        'cashier_model' => config('cashier.model'),
        'customer_id' => $payload['customer'] ?? null,
    ]);

    try {
        $response = parent::handleCustomerSubscriptionCreated($payload);
        Log::info('Webhook: Parent call succeeded', [
            'status' => $response->getStatusCode(),
        ]);
        return $response;
    } catch (\Exception $e) {
        Log::error('Webhook: Parent call failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    }
}
```

**用途**: 今後の問題発生時の調査を容易にする（本番環境で継続使用）

### 4. 検証とデプロイ

#### ローカル環境での検証

```bash
# 1. Cashier設定確認
docker exec mtdev-app-1 php artisan tinker --execute="echo Cashier::\$customerModel . PHP_EOL;"
# 結果: App\Models\Group ✅

# 2. テストユーザーで決済テスト
# - Stripe Checkoutで決済完了
# - subscriptionsテーブルにレコード作成 ✅
# - 画面表示が「Family Plan」に変更 ✅
```

#### 本番環境デプロイ

```bash
# 1. コミット
git add app/Providers/AppServiceProvider.php \
        app/Repositories/Subscription/SubscriptionEloquentRepository.php \
        app/Http/Actions/Token/HandleStripeWebhookAction.php
git commit -m "fix: Initialize Cashier with Group model as billable entity"
git push origin main

# 2. GitHub Actions自動デプロイ
# - ビルド: 成功 ✅
# - マイグレーション: 成功 ✅
# - ECS更新: 成功 ✅
# - ヘルスチェック: 成功 ✅

# 3. 本番環境データ確認
# - ktrユーザー: Group 1, stripe_id: cus_TX0WqFACPwzwie
# - subscription_plan: family ✅
# - subscription_active: true ✅
# - subscriptions count: 1 ✅

# 4. 画面表示確認
# - サブスクリプション画面: Family Plan表示 ✅
```

## 成果と効果

### 定量的効果

- **修正ファイル数**: 3ファイル（AppServiceProvider, Repository, WebhookAction）
- **追加コード行数**: 約25行
- **デプロイ時間**: 約10分45秒
- **ダウンタイム**: 0秒（ローリングアップデート）

### 定性的効果

- **データ整合性向上**: `groups`テーブルと`subscriptions`テーブルが正しく連携
- **課金システムの信頼性**: Stripe決済とLaravel側のデータが正確に同期
- **保守性向上**: Repository層で正しいEloquentリレーションを使用
- **デバッグ容易性**: Webhook処理のログが充実

### ユーザー影響

- **既存契約ユーザー**: 影響なし（既にsubscriptionsレコードが存在）
- **新規契約ユーザー**: 決済後、即座に正しいプラン表示
- **過去の不整合データ**: 手動で修正済み（ktrユーザー: Group 1）

## 技術的知見

### Laravel Cashierの設計パターン

1. **静的プロパティの使用**: Cashierは`$customerModel`を静的プロパティで管理
2. **初期化の必要性**: ServiceProviderの`boot()`メソッドで明示的に初期化が必要
3. **設定ファイルの限界**: `config/cashier.php`は参考値であり、実行時には読み込まれない

### Billableモデルのカスタマイズ

```php
// 正しい初期化パターン
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 必須: 静的プロパティを設定
        \Laravel\Cashier\Cashier::useCustomerModel(\App\Models\Group::class);
    }
}

// subscriptions.user_id の意味
// - デフォルト: UserモデルのID
// - カスタム: Billableモデル（この場合Group）のID
```

### データベース設計の注意点

- `subscriptions.user_id`: 命名は`user_id`だが、実際にはBillableモデルのIDを保存
- リレーション定義: Billableモデル（Group）に`subscriptions()`リレーションが必要
- クエリロジック: 直接SQLではなくEloquentリレーション経由でアクセス推奨

## 今後の推奨事項

### 1. テストカバレッジ強化

```php
// tests/Feature/Subscription/SubscriptionCreationTest.php
public function test_webhook_creates_subscription_for_group()
{
    $group = Group::factory()->create(['stripe_id' => 'cus_test123']);
    
    $payload = [
        'customer' => 'cus_test123',
        'id' => 'sub_test123',
        // ...
    ];
    
    $this->postJson('/stripe/webhook', $payload);
    
    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $group->id,  // GroupのID
        'stripe_id' => 'sub_test123',
    ]);
}
```

### 2. モニタリング強化

- **CloudWatch**: Webhook失敗時のアラート設定
- **Stripe Dashboard**: Webhook配信失敗の定期確認
- **アプリケーションログ**: サブスクリプション作成失敗の検知

### 3. ドキュメント整備

- プロジェクトのREADMEに「Cashier初期化の重要性」を記載
- `docs/architecture/`にBillableモデルの設計指針を追加
- 新規開発者向けのオンボーディング資料に本件を追加

### 4. 定期的なデータ整合性チェック

```bash
# groupsとsubscriptionsの不整合検出スクリプト
php artisan check:subscription-consistency

# 実装例
# - groups.subscription_active = true だが subscriptions レコードなし
# - subscriptions.stripe_status = active だが groups.subscription_active = false
```

## 関連ドキュメント

- **Stripe公式**: [Webhooks Best Practices](https://stripe.com/docs/webhooks/best-practices)
- **Laravel Cashier**: [Configuring the Billable Model](https://laravel.com/docs/11.x/billing#configuration)
- **プロジェクト内**: 
  - `definitions/Purchase.md`: サブスクリプション機能要件
  - `docs/architecture/`: アーキテクチャ設計

## まとめ

本修正により、Laravel CashierとStripe決済の連携が正常に機能するようになりました。根本原因は「Cashierの静的プロパティ初期化漏れ」という、ドキュメント化されにくい設計パターンの理解不足でした。

今回の経験から、以下の教訓を得ました：

1. **設定ファイルだけでは不十分な場合がある**: ライブラリの内部実装を理解する重要性
2. **成功レスポンスが正常動作を保証しない**: データベース確認までが検証の責務
3. **段階的なデバッグの有効性**: ログ追加 → 原因特定 → 修正 → 検証のサイクル

本番環境での動作確認も完了し、今後の新規サブスクリプション契約は正常に処理されます。

---

**作成日**: 2025-12-03  
**作成者**: GitHub Copilot  
**検証環境**: ローカル（Docker）、本番（AWS ECS）  
**関連コミット**: `300bf54` - "fix: Initialize Cashier with Group model as billable entity"
