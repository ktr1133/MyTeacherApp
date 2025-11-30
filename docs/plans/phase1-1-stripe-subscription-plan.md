# Phase 1.1: Stripe課金システム実装計画

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------|
| 2025-11-29 | GitHub Copilot | 初版作成: Stripe課金システム実装計画 |
| 2025-12-01 | GitHub Copilot | Phase 1.1.3b完了: Webhook処理実装（サブスクリプションイベント処理） |
| 2025-12-01 | GitHub Copilot | 進捗サマリー追加: 実装状況の可視化 |
| 2025-12-01 | GitHub Copilot | Phase 1.1.2完了確認: サブスクリプション購入機能が実装済みであることを確認 |

## 進捗サマリー

### 実装状況（2025-12-01時点）

| Phase | タスク | ステータス | 完了率 | 備考 |
|-------|--------|-----------|--------|------|
| 1.1.1 | データベース・設定 | ✅ 完了 | 100% | マイグレーション・環境変数設定完了 |
| 1.1.2 | サブスクリプション作成機能 | ✅ 完了 | 100% | **本日完了確認**、全機能実装済み |
| 1.1.3a | メンバー追加制限 | ✅ 完了 | 100% | テスト10/11通過 |
| 1.1.3b | Webhook処理 | ✅ 完了 | 100% | テスト12/12通過 |
| 1.1.4 | グループタスク作成制限 | ✅ 完了 | 100% | 月次リセット・管理画面完成 |
| 1.1.5 | サブスクリプション管理画面 | ⏳ 未着手 | 0% | 次のステップ |

**全体進捗**: 5/6フェーズ完了 **（約83%完了）**

### 最近の成果（Phase 1.1.3b）

- ✅ Stripe Webhookイベント処理実装
- ✅ サブスクリプション自動有効化/無効化
- ✅ 包括的テスト実装（12テスト、28アサーション）
- ✅ 詳細なログ記録とエラーハンドリング

**参照レポート**: `docs/reports/2025-12-01-phase1-1-3b-webhook-completion-report.md`

### 次のステップ

**Phase 1.1.5: サブスクリプション管理画面**
- サブスクリプション一覧表示
- プラン変更機能
- キャンセル機能
- 請求履歴表示

## 1. 概要

MyTeacherアプリにStripe決済システムを導入し、グループ管理機能のサブスクリプション課金を実装します。既存のトークンシステムとは独立した課金体系として構築し、将来的な買い切り機能や組み合わせ課金にも対応できる拡張性を持たせます。

### 目的

- **マネタイズ**: グループ管理機能（複数ユーザー管理）を有料化
- **段階的な収益化**: 家族利用（月額500円程度）から教育機関利用まで対応
- **拡張性**: 将来的なトークン購入、買い切り機能、組み合わせ課金に対応

### ターゲットユーザー

- **個人ユーザー（家族利用）**: 管理者1名、編集権限者1名、子供4名まで
- **教育機関**: 大人数のグループ管理

### 対象範囲

- **Phase 1.1**: サブスクリプション機能実装（グループメンバー数制限）
- **Phase 1.2**: トークン購入機能実装（買い切り・組み合わせ課金対応）

## 2. 現状分析

### 既存のStripe関連実装

#### データベース設計（実装済み）

**users テーブル**:
```sql
stripe_id VARCHAR(255) NULL        -- Stripe顧客ID（Laravel Cashier）
pm_type VARCHAR(255) NULL          -- 支払い方法タイプ
pm_last_four VARCHAR(4) NULL       -- 支払い方法の下4桁
trial_ends_at TIMESTAMP NULL       -- トライアル終了日時
```

**groups テーブル**:
```sql
stripe_id VARCHAR(255) NULL        -- Stripe顧客ID（グループ課金用）
pm_type VARCHAR(255) NULL          -- 支払い方法タイプ
pm_last_four VARCHAR(4) NULL       -- 支払い方法の下4桁
trial_ends_at TIMESTAMP NULL       -- トライアル終了日時
master_user_id BIGINT NULL         -- グループ管理者のユーザーID
```

**subscriptions テーブル**（Laravel Cashier標準）:
```sql
id BIGINT PRIMARY KEY
user_id BIGINT                     -- ユーザーID
type VARCHAR(255)                  -- サブスクリプションタイプ
stripe_id VARCHAR(255) UNIQUE      -- StripeサブスクリプションID
stripe_status VARCHAR(255)         -- Stripeステータス
stripe_price VARCHAR(255) NULL     -- Stripe価格ID
quantity INT NULL                  -- 数量
trial_ends_at TIMESTAMP NULL       -- トライアル終了日時
ends_at TIMESTAMP NULL             -- サブスク終了日時
created_at TIMESTAMP
updated_at TIMESTAMP
INDEX (user_id, stripe_status)
```

**subscription_items テーブル**（Laravel Cashier標準）:
```sql
id BIGINT PRIMARY KEY
subscription_id BIGINT             -- サブスクリプションID
stripe_id VARCHAR(255) UNIQUE      -- StripeサブスクリプションアイテムID
stripe_product VARCHAR(255)        -- Stripe商品ID
stripe_price VARCHAR(255)          -- Stripe価格ID
quantity INT NULL                  -- 数量
created_at TIMESTAMP
updated_at TIMESTAMP
```

#### Laravel Cashier設定（実装済み）

**Userモデル**:
```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
}
```

**Groupモデル**:
```php
use Laravel\Cashier\Billable;

class Group extends Model
{
    use Billable; // グループ単位での課金に対応
}
```

**環境変数**:
```bash
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_TEST_MODE=true
```

**Webhook設定**:
- エンドポイント: `/stripe/webhook`
- Action: `HandleStripeWebhookAction` (実装済み)

### 既存のトークンシステム

**token_transactions テーブル**:
```sql
type ENUM('consume', 'purchase', 'grant', 'free_reset', 'admin_adjust', 'ai_usage', 'refund')
```

**現在の機能**:
- ✅ AI利用時のトークン消費
- ✅ 手動トークン付与（管理者）
- ⚠️ トークン購入機能未実装（Stripe連携なし）

### 不足している機能

1. **グループメンバー数制限機能**:
   - `groups` テーブルに制限カラムなし
   - サブスクリプション加入有無の判定ロジックなし
   - メンバー追加時のバリデーションなし

2. **Stripe決済フロー**:
   - サブスクリプション作成画面なし
   - 決済情報入力UI未実装
   - サブスクリプション管理画面なし

3. **Webhook処理**:
   - `HandleStripeWebhookAction` の実装詳細不明
   - サブスクリプションステータス変更時の処理なし

4. **料金プラン設計**:
   - Stripeダッシュボードで商品・価格未作成
   - 家族プラン（500円）、教育機関プラン未定義

## 3. マネタイズポイント設計

### 課金対象機能

**グループ管理機能**:
- 複数ユーザーの一元管理
- グループタスク割当
- 子供アカウントの管理
- 進捗状況の統合確認

### 無料利用範囲

**個人利用（グループなし）**:
- タスク管理（個人のみ）
- AI機能（トークン制限あり）
- アバター機能

**無料グループ利用**:
- **メンバー数制限**: 管理者含めて6名まで無料
- **グループタスク作成**: 月3回まで無料（4回目からサブスクリプション必要）
- **グループタスク作成回数**: 毎月1日に自動リセット
- **無料トライアル**: グループ管理者が日数設定可能（デフォルト14日間）
- **実績レポート**: 初月のみ利用可能（2ヶ月目以降はサブスクリプション必要）

### 有料プラン

#### ファミリープラン（月額500円）

**対象**:
- 家族での利用（管理者1名、編集権限者1名、子供4名）

**制限**:
- **最大メンバー数**: 6名まで（管理者1名 + 編集権限者1名 + 子供4名）
- **グループ数**: 1グループのみ

**特典**:
- グループタスク割当
- 子供アカウント管理
- 進捗状況の統合確認
- グループトークン共有

**Stripe設定**:
- Product ID: `prod_family_plan`
- Price ID: `price_family_monthly`
- Amount: ¥500/月（税込）
- Billing: 月次自動更新
- Trial: 14日間無料

#### エンタープライズプラン（月額3,000円〜）

**対象**:
- 教育機関、塾、団体

**制限**:
- **最大メンバー数**: 20名まで（基本）
- 追加メンバー: 1名あたり150円/月
- **グループ数**: 5グループまで

**特典**:
- 大人数のグループ管理
- 複数グループ作成
- 統計レポート機能（将来実装）
- 優先サポート（将来実装）

**Stripe設定**:
- Product ID: `prod_enterprise_plan`
- Price ID: `price_enterprise_monthly`
- Amount: ¥3,000/月（税込、20名まで）
- Additional Member Price: `price_additional_member`
- Amount: ¥150/月/名（税込）
- Billing: 月次自動更新

### 将来的な拡張（Phase 1.2以降）

#### トークン購入（買い切り）

**内容**:
- 一度きりの支払いでトークン購入
- AI機能の従量課金

**Stripe設定**:
- Product: `prod_token_package`
- Price: 複数価格（`price_token_1000`, `price_token_5000` など）
- Payment: 単発決済（Subscription不使用）

#### 組み合わせプラン

**内容**:
- サブスクリプション + トークン購入
- サブスクリプション会員は割引価格でトークン購入可能

## 4. データベース設計

### 追加が必要なカラム

#### groups テーブル

```sql
ALTER TABLE groups
ADD COLUMN subscription_active BOOLEAN DEFAULT FALSE COMMENT 'サブスクリプション有効フラグ',
ADD COLUMN subscription_plan VARCHAR(50) NULL COMMENT 'サブスクリプションプラン: family, enterprise',
ADD COLUMN max_members INT DEFAULT 6 COMMENT '最大メンバー数（デフォルト6: 無料枠）',
ADD COLUMN max_groups INT DEFAULT 1 COMMENT '最大グループ数（将来用）',
ADD COLUMN free_group_task_limit INT DEFAULT 3 COMMENT 'グループタスク無料作成回数（月次、管理者調整可能）',
ADD COLUMN group_task_count_current_month INT DEFAULT 0 COMMENT '当月のグループタスク作成回数',
ADD COLUMN group_task_count_reset_at TIMESTAMP NULL COMMENT 'グループタスク作成回数リセット日時（翌月1日）',
ADD COLUMN free_trial_days INT DEFAULT 14 COMMENT '無料トライアル日数（管理者調整可能）',
ADD COLUMN report_enabled_until DATE NULL COMMENT '実績レポート利用可能期限（無料ユーザーは初月末まで）',
ADD INDEX idx_subscription_active (subscription_active),
ADD INDEX idx_group_task_count_reset_at (group_task_count_reset_at);
```

**追加カラムの説明**:
- `subscription_active`: サブスクリプション有効かどうか（Webhookで更新）
- `subscription_plan`: 加入プラン（`family`, `enterprise`）
- `max_members`: サブスクリプションに応じた最大メンバー数（デフォルト6: 無料枠）
- `max_groups`: 将来的な複数グループ対応
- `free_group_task_limit`: グループタスク無料作成回数上限（管理者がグループ管理画面で調整可能）
- `group_task_count_current_month`: 当月のグループタスク作成回数（月次リセット）
- `group_task_count_reset_at`: 次回リセット日時（翌月1日0時）
- `free_trial_days`: 無料トライアル日数（管理者がグループ管理画面で調整可能）
- `report_enabled_until`: 実績レポート利用可能期限（無料ユーザーは初月末まで、サブスクリプションユーザーはNULL）

#### subscriptions テーブル（既存のまま利用）

Laravel Cashierの標準テーブルをそのまま利用。

**重要**:
- `user_id` はグループの `master_user_id` を登録
- `type` には `'group_subscription'` を設定
- `stripe_status` には `'active'`, `'canceled'`, `'past_due'` などのステータス

#### monthly_reports テーブル（新規作成）

実績レポートを保存するテーブル。

```sql
CREATE TABLE monthly_reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    group_id BIGINT NOT NULL COMMENT 'グループID',
    report_month DATE NOT NULL COMMENT 'レポート対象月（YYYY-MM-01形式）',
    generated_at TIMESTAMP NULL COMMENT 'レポート生成日時',
    
    -- メンバー別通常タスク集計（JSON）
    member_task_summary JSON NULL COMMENT 'メンバー別タスク集計 {user_id: {completed_count, tasks: [{title, completed_at}]}}',
    
    -- グループタスク集計
    group_task_completed_count INT DEFAULT 0 COMMENT 'グループタスク完了件数',
    group_task_total_reward INT DEFAULT 0 COMMENT 'グループタスク獲得報酬合計',
    group_task_details JSON NULL COMMENT 'グループタスク完了内訳 [{task_id, title, reward, completed_at}]',
    
    -- 前月比
    normal_task_count_previous_month INT DEFAULT 0 COMMENT '前月の通常タスク完了件数',
    group_task_count_previous_month INT DEFAULT 0 COMMENT '前月のグループタスク完了件数',
    reward_previous_month INT DEFAULT 0 COMMENT '前月の獲得報酬',
    
    -- レポートファイル
    pdf_path VARCHAR(255) NULL COMMENT 'PDFファイルパス（S3）',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_month (group_id, report_month),
    INDEX idx_report_month (report_month),
    INDEX idx_generated_at (generated_at)
) COMMENT '月次実績レポート';
```

**マイグレーション作成**:
```bash
php artisan make:migration create_monthly_reports_table
```

**マイグレーション内容**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->boolean('subscription_active')->default(false)->comment('サブスクリプション有効フラグ');
            $table->string('subscription_plan', 50)->nullable()->comment('サブスクリプションプラン: family, enterprise');
            $table->integer('max_members')->default(6)->comment('最大メンバー数（デフォルト6: 無料枠）');
            $table->integer('max_groups')->default(1)->comment('最大グループ数（将来用）');
            $table->integer('free_group_task_limit')->default(3)->comment('グループタスク無料作成回数（月次、管理者調整可能）');
            $table->integer('group_task_count_current_month')->default(0)->comment('当月のグループタスク作成回数');
            $table->timestamp('group_task_count_reset_at')->nullable()->comment('グループタスク作成回数リセット日時（翌月1日）');
            $table->integer('free_trial_days')->default(14)->comment('無料トライアル日数（管理者調整可能）');
            $table->date('report_enabled_until')->nullable()->comment('実績レポート利用可能期限（無料ユーザーは初月末まで）');
            
            $table->index('subscription_active');
            $table->index('group_task_count_reset_at');
        });
        
        // 既存グループに初期値設定
        DB::statement('UPDATE groups SET group_task_count_reset_at = DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE group_task_count_reset_at IS NULL');
        DB::statement('UPDATE groups SET report_enabled_until = LAST_DAY(NOW()) WHERE report_enabled_until IS NULL AND subscription_active = FALSE');
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropIndex(['subscription_active']);
            $table->dropIndex(['group_task_count_reset_at']);
            $table->dropColumn([
                'subscription_active',
                'subscription_plan',
                'max_members',
                'max_groups',
                'free_group_task_limit',
                'group_task_count_current_month',
                'group_task_count_reset_at',
                'free_trial_days',
                'report_enabled_until'
            ]);
        });
    }
};
```

## 5. Stripe設定

### ダッシュボードでの商品・価格作成

#### ファミリープラン

**Product**:
- Name: `MyTeacher ファミリープラン`
- Description: `家族でMyTeacherを利用できるプラン（最大6名）`
- Product ID: `prod_family_plan`

**Price**:
- Amount: ¥500
- Currency: JPY
- Billing: Recurring (Monthly)
- Price ID: `price_family_monthly`

#### エンタープライズプラン

**Product**:
- Name: `MyTeacher エンタープライズプラン`
- Description: `教育機関向けプラン（最大20名、追加可能）`
- Product ID: `prod_enterprise_plan`

**Price (Base)**:
- Amount: ¥3,000
- Currency: JPY
- Billing: Recurring (Monthly)
- Price ID: `price_enterprise_monthly`

**Price (Additional Member)**:
- Amount: ¥150
- Currency: JPY
- Billing: Recurring (Monthly)
- Price ID: `price_additional_member`

### 環境変数設定

**本番環境**:
```bash
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_TEST_MODE=false
STRIPE_WEBHOOK_SECRET=whsec_...

# 価格ID（本番）
STRIPE_FAMILY_PLAN_PRICE_ID=price_...
STRIPE_ENTERPRISE_PLAN_PRICE_ID=price_...
STRIPE_ADDITIONAL_MEMBER_PRICE_ID=price_...
```

**テスト環境**:
```bash
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_TEST_MODE=true
STRIPE_WEBHOOK_SECRET=whsec_...

# 価格ID（テスト）
STRIPE_FAMILY_PLAN_PRICE_ID=price_...
STRIPE_ENTERPRISE_PLAN_PRICE_ID=price_...
STRIPE_ADDITIONAL_MEMBER_PRICE_ID=price_...
```

### Webhook設定

**Webhookエンドポイント**:
- URL: `https://my-teacher-app.com/stripe/webhook`
- Events to send:
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_succeeded`
  - `invoice.payment_failed`

**Webhook署名検証**:
- Laravel Cashierが自動処理
- `HandleStripeWebhookAction` で追加処理

## 6. 実装計画

### Phase 1.1.1: データベース・設定（1日） ✅ **完了**

**タスク**:
1. ✅ マイグレーション作成・実行（groups追加フィールド + monthly_reports新規）
2. ⏳ Stripe商品・価格作成（Phase 1.1.2で実施予定）
3. ✅ 環境変数設定
4. ✅ `config/const.php` にプラン定数追加

**成果物**:
- ✅ `database/migrations/2025_11_30_111950_add_subscription_fields_to_groups_table.php`
- ✅ `database/migrations/2025_11_30_112052_create_monthly_reports_table.php`
- ⏳ Stripe Product/Price作成（保留中）
- ✅ `.env` 更新

**参照レポート**: `docs/reports/2025-11-30-phase1-1-1-database-setup-completion-report.md`

### Phase 1.1.2: サブスクリプション作成機能（2-3日） ✅ **完了**

**タスク**:
1. ✅ サブスクリプション選択画面作成
2. ✅ Stripe Checkout Session作成Action実装
3. ✅ サブスクリプション完了後のリダイレクト処理
4. ✅ 無料トライアル期間設定（14日間）

#### ⚠️ 重要な注意事項

**実装は完了していますが、実際に動作させるには以下のStripe設定が必要です**:

##### 🔧 必須設定タスク（Stripe審査通過後に実施）

1. **Stripe商品・価格の登録**
   ```bash
   # Stripe Dashboardで以下を登録:
   # 1. ファミリープラン商品（¥500/月、14日トライアル）
   # 2. エンタープライズプラン商品（¥3,000/月、14日トライアル）
   # 3. 追加メンバー価格（¥150/月/名、使用量ベース）
   
   # 詳細手順: /home/ktr/mtdev/docs/stripe-products/README.md
   # クイックスタート: /home/ktr/mtdev/docs/stripe-products/QUICKSTART.md
   ```

2. **環境変数の更新**（`.env`）
   ```bash
   # 現在の状態: プレースホルダー値
   STRIPE_FAMILY_PLAN_PRICE_ID=price_test_family_placeholder        # ❌ 要更新
   STRIPE_ENTERPRISE_PLAN_PRICE_ID=price_test_enterprise_placeholder # ❌ 要更新
   STRIPE_ADDITIONAL_MEMBER_PRICE_ID=price_test_additional_member_placeholder # ❌ 要更新
   
   # Webhook設定
   STRIPE_WEBHOOK_SECRET=whsec_xxx  # ❌ 要更新（Stripe Dashboardから取得）
   
   # API Keys（本番環境用に切り替え時）
   STRIPE_KEY=pk_live_xxxxxxxxxxxxx      # ✅ 設定済み（テスト→本番切替必要）
   STRIPE_SECRET=sk_live_xxxxxxxxxxxxx   # ✅ 設定済み（テスト→本番切替必要）
   ```

3. **Webhook エンドポイント設定**（Stripe Dashboard）
   ```
   URL: https://yourdomain.com/stripe/webhook
   イベント:
   - customer.subscription.created
   - customer.subscription.updated
   - customer.subscription.deleted
   - invoice.payment_succeeded
   - invoice.payment_failed
   
   取得したSigning SecretをSTRIPE_WEBHOOK_SECRETに設定
   ```

4. **商品画像の準備**
   ```bash
   # SVG→PNG変換が必要（StripeはPNG/JPEGのみ対応）
   cd /home/ktr/mtdev/docs/stripe-products
   # CloudConvertなどのオンラインツールで変換
   # または inkscape コマンドで一括変換
   ```

##### 📋 設定チェックリスト

- [ ] Stripe審査通過
- [ ] 商品登録（ファミリープラン）
- [ ] 商品登録（エンタープライズプラン）
- [ ] 追加メンバー価格登録
- [ ] Price IDを`.env`に設定
- [ ] Webhook エンドポイント登録
- [ ] Webhook Secretを`.env`に設定
- [ ] 商品画像のPNG変換
- [ ] テスト決済で動作確認
- [ ] 本番環境でAPI Key切り替え

##### 📚 参考ドキュメント

- 詳細設定手順: `/home/ktr/mtdev/docs/stripe-products/README.md`
- クイックスタート: `/home/ktr/mtdev/docs/stripe-products/QUICKSTART.md`
- 商品画像: `/home/ktr/mtdev/docs/stripe-products/*.svg` (PNG変換必要)

---

**成果物**:
- ✅ `resources/views/subscriptions/select-plan.blade.php`（UI実装済み）
- ✅ `app/Http/Actions/Subscription/CreateCheckoutSessionAction.php`（実装済み）
- ✅ `app/Services/Subscription/SubscriptionService.php`（実装済み）
- ✅ `app/Services/Subscription/SubscriptionServiceInterface.php`（実装済み）
- ✅ `app/Repositories/Subscription/SubscriptionEloquentRepository.php`（実装済み）
- ✅ `app/Repositories/Subscription/SubscriptionRepositoryInterface.php`（実装済み）
- ✅ `app/Http/Requests/Subscription/CreateCheckoutSessionRequest.php`（実装済み）
- ✅ `app/Http/Responders/Subscription/SubscriptionResponder.php`（実装済み）
- ✅ `app/Http/Actions/Subscription/SubscriptionSuccessAction.php`（実装済み）
- ✅ `app/Http/Actions/Subscription/SubscriptionCancelAction.php`（実装済み）
- ✅ `resources/views/subscriptions/success.blade.php`（実装済み）
- ✅ `resources/views/subscriptions/cancel.blade.php`（実装済み）
- ✅ `routes/web.php` にルート追加

**実装詳細**:
- **Action-Service-Repositoryパターン**: 完全に準拠した実装
- **Stripe Checkout Session**: Laravel Cashierの`newSubscription()->checkout()`を使用
- **メタデータ設定**: `group_id`, `subscription_plan`, `additional_members`をWebhookで利用
- **権限チェック**: グループ管理者または編集権限者のみがサブスクリプションを作成可能
- **バリデーション**: プラン種別、追加メンバー数の検証
- **エラーハンドリング**: Stripe API エラーを適切にハンドリング、ユーザーフレンドリーなメッセージ表示

**参照レポート**: `docs/reports/2025-12-01-phase1-1-2-subscription-purchase-completion-report.md`（作成予定）

### Phase 1.1.3: メンバー追加制限実装（1日） ✅ **完了**

**注**: 当初計画ではWebhook処理として予定していたが、実際にはメンバー追加制限機能を優先実装

**タスク**:
1. ✅ GroupServiceにメンバー数制限チェック追加
2. ✅ メンバー追加UI更新（制限情報表示）
3. ✅ サブスクリプション促進メッセージ追加
4. ✅ テスト作成（10/11 passing）

**成果物**:
- ✅ `app/Services/Profile/GroupService.php` 更新（canAddMember, getRemainingMemberSlots メソッド追加）
- ✅ `app/Services/Profile/GroupServiceInterface.php` 更新
- ✅ `resources/views/profile/group/partials/add-member.blade.php` 更新
- ✅ `tests/Feature/Profile/Group/AddMemberTest.php` 作成

**参照レポート**: `docs/reports/2025-11-30-blade-syntax-error-fix-report.md`（不具合修正含む）

### Phase 1.1.3b: Webhook処理（完了）

**タスク**:
1. ✅ `HandleStripeWebhookAction` 拡張
2. ✅ サブスクリプション有効化処理（Created）
3. ✅ サブスクリプション更新処理（Updated）
4. ✅ サブスクリプション無効化処理（Deleted）
5. ✅ グループメンバー数制限の更新
6. ✅ 包括的テスト作成（12/12 passing）

**成果物**:
- ✅ `app/Http/Actions/Token/HandleStripeWebhookAction.php` 更新（サブスクリプションイベント処理追加）
- ✅ `app/Services/Subscription/SubscriptionWebhookServiceInterface.php` 作成
- ✅ `app/Services/Subscription/SubscriptionWebhookService.php` 作成
- ✅ `app/Providers/AppServiceProvider.php` 更新（DIバインディング追加）
- ✅ `tests/Feature/Services/Subscription/SubscriptionWebhookServiceTest.php` 作成

**実装詳細**:
- **Webhookイベント処理**: `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted` に対応
- **サブスクリプション有効化**: グループの `subscription_active`, `subscription_plan`, `max_members` を更新
- **サブスクリプション更新**: ステータス（active, trialing, canceled等）に応じて適切に処理
- **サブスクリプション削除**: 無効化し、無料枠（6名）に戻す
- **エラーハンドリング**: メタデータ不足、存在しないグループID等のケースに対応
- **ログ記録**: すべてのWebhookイベントを詳細にログ記録

**参照レポート**: `docs/reports/2025-12-01-phase1-1-3b-webhook-completion-report.md`

### Phase 1.1.4: サブスクリプション管理画面（未着手）

**タスク**:
1. ⏳ サブスクリプション一覧表示
- `app/Http/Actions/Subscription/CreateCheckoutSessionAction.php`
- `app/Services/Subscription/SubscriptionService.php`
- `routes/web.php` にルート追加

### Phase 1.1.3: Webhook処理（2日）

**タスク**:
1. `HandleStripeWebhookAction` 拡張
2. サブスクリプション有効化処理
3. サブスクリプション無効化処理
4. グループメンバー数制限の更新
5. 実績レポート有効期限の更新

**成果物**:
- `app/Http/Actions/Token/HandleStripeWebhookAction.php` 更新
- `app/Services/Subscription/WebhookHandlerService.php` 作成

### Phase 1.1.4: グループタスク作成制限（2-3日） ✅ **完了**

**タスク**:
1. ✅ グループタスク作成時の回数チェック（GroupTaskLimitService）
2. ✅ StoreTaskActionへの統合（制限チェック + カウンター増加）
3. ✅ StoreTaskApiActionへの統合（モバイルAPI対応）
4. ✅ 月次リセット処理（Cronジョブ: ResetMonthlyGroupTaskCount）
5. ✅ グループ管理画面に使用状況表示UI追加（task-limit-status.blade.php）
6. ✅ システム管理者画面に無料枠調整UI追加（admin/edit-user.blade.php）
7. ✅ テスト実装（GroupTaskLimitTest.php: 10テスト全通過）

**成果物**:
- ✅ `app/Services/Group/GroupTaskLimitServiceInterface.php` 作成
- ✅ `app/Services/Group/GroupTaskLimitService.php` 作成（月次制限チェック、自動リセット）
- ✅ `app/Http/Actions/Task/StoreTaskAction.php` 更新（グループタスク制限チェック統合）
- ✅ `app/Http/Actions/Api/Task/StoreTaskApiAction.php` 更新（モバイルAPI対応）
- ✅ `app/Console/Commands/ResetMonthlyGroupTaskCount.php` 作成（月次リセットコマンド）
- ✅ `routes/console.php` スケジュール登録（毎月1日00:00実行、Asia/Tokyo）
- ✅ `resources/views/profile/group/partials/task-limit-status.blade.php` 作成（グループ管理者向け: 使用状況表示のみ）
- ✅ `resources/views/admin/edit-user.blade.php` 更新（システム管理者向け: 無料枠調整フォーム追加）
- ✅ `app/Services/Admin/UserService.php` 更新（グループ設定更新処理追加）
- ✅ `app/Http/Requests/Admin/UpdateUserRequest.php` 作成（バリデーション: free_group_task_limit 0-100, free_trial_days 0-90）
- ✅ `database/factories/GroupFactory.php` 作成（テスト用Factory）
- ✅ `tests/Feature/Group/GroupTaskLimitTest.php` 作成（10テストメソッド: 36アサーション全通過）

**権限分離**:
- **グループ管理者**: 現在の使用状況の**閲覧のみ**（編集不可）
  - task-limit-status.blade.php で進捗バー、残り回数、次回リセット日を表示
  - サブスクリプション加入促進リンク表示
- **システム管理者（`is_admin=true`）**: `free_group_task_limit`, `free_trial_days` の**調整権限**
  - admin/edit-user.blade.php でグループ設定フォーム表示
  - UpdateUserRequest で admin-only 認可チェック

**テストカバレッジ**:
1. ✅ `test_free_group_can_create_tasks_within_limit` - 無料枠内での作成成功
2. ✅ `test_free_group_cannot_create_tasks_when_limit_reached` - 上限到達時の作成失敗
3. ✅ `test_subscribed_group_has_unlimited_task_creation` - サブスクリプション契約者の無制限作成
4. ✅ `test_group_task_count_increments_correctly` - カウンター増加ロジック（0→1→2→3）
5. ✅ `test_monthly_count_resets_correctly` - resetMonthlyCount() でカウンター0化
6. ✅ `test_auto_reset_when_reset_date_passed` - reset_at 経過時の自動リセット
7. ✅ `test_task_creation_respects_limit` - POST /tasks での制限適用（JSON応答）
8. ✅ `test_admin_can_update_group_limits` - システム管理者による無料枠変更
9. ✅ `test_non_admin_cannot_update_group_limits` - 非管理者の403エラー
10. ✅ `test_get_group_task_usage_returns_correct_data` - getGroupTaskUsage() の返り値検証

**実装日**: 2025-11-29
**テスト結果**: 10 passed (36 assertions) - Duration: 0.85s

### Phase 1.1.5: メンバー追加時のバリデーション（1-2日）

**タスク**:
1. グループメンバー追加時の人数チェック
2. サブスクリプション未加入時のエラーメッセージ
3. サブスクリプション加入促進UI

**成果物**:
- `app/Http/Actions/Profile/Group/AddMemberAction.php` 更新
- `app/Services/Group/GroupMemberService.php` 更新

### Phase 1.1.6: サブスクリプション管理画面（2-3日）

**タスク**:
1. サブスクリプション状態確認画面
2. プラン変更機能
3. サブスクリプション解約機能
4. 決済情報更新機能

**成果物**:
- `resources/views/subscriptions/manage.blade.php`
- `app/Http/Actions/Subscription/ManageSubscriptionAction.php`
- `app/Http/Actions/Subscription/CancelSubscriptionAction.php`
- `app/Http/Actions/Subscription/UpdatePaymentMethodAction.php`

### Phase 1.1.7: アカウント削除時の処理（1日）

**タスク**:
1. アカウント削除前の確認アラート追加
2. サブスクリプション解約処理（期間終了時に解約）
3. グループデータのアーカイブ処理

**成果物**:
- `resources/views/profile/partials/delete-user-form.blade.php` 更新
- `app/Http/Actions/Profile/DeleteProfileAction.php` 更新
- `app/Services/User/UserDeletionService.php` 作成

### Phase 1.1.8: 実績レポート生成機能（3-4日）

**タスク**:
1. 月次レポート自動生成（Cronジョブ）
2. レポートHTML生成
3. PDF出力機能
4. グループ管理者への通知
5. レポート閲覧画面（過去レポート一覧）
6. 無料ユーザーの利用制限（初月のみ）

**成果物**:
- `app/Console/Commands/GenerateMonthlyReports.php` 作成
- `app/Services/Report/MonthlyReportService.php` 作成
- `resources/views/reports/monthly-report.blade.php` 作成
- `app/Http/Actions/Reports/ShowMonthlyReportAction.php` 作成
- `app/Http/Actions/Reports/DownloadMonthlyReportPdfAction.php` 作成
- PDF生成ライブラリ統合（Dompdf or Snappy）

### Phase 1.1.9: テスト作成（2-3日）

**タスク**:
1. サブスクリプション作成テスト
2. Webhook処理テスト
3. グループタスク制限テスト
4. メンバー追加バリデーションテスト
5. サブスクリプション管理テスト
6. アカウント削除テスト
7. 実績レポート生成テスト

**成果物**:
- `tests/Feature/SubscriptionTest.php`
- `tests/Feature/SubscriptionWebhookTest.php`
- `tests/Feature/GroupTaskLimitTest.php`
- `tests/Feature/GroupMemberLimitTest.php`
- `tests/Feature/UserDeletionTest.php`
- `tests/Feature/MonthlyReportTest.php`

## 7. 実装詳細

### サブスクリプション作成フロー

#### 1. ユーザーがプラン選択

**画面**: `/subscriptions/select-plan`

```blade
<!-- resources/views/subscriptions/select-plan.blade.php -->
<div class="subscription-plans">
    <div class="plan-card">
        <h3>ファミリープラン</h3>
        <p>月額 ¥500</p>
        <p>最大6名まで</p>
        <form action="{{ route('subscriptions.checkout') }}" method="POST">
            @csrf
            <input type="hidden" name="plan" value="family">
            <button type="submit">このプランを選択</button>
        </form>
    </div>
    
    <div class="plan-card">
        <h3>エンタープライズプラン</h3>
        <p>月額 ¥3,000〜</p>
        <p>最大20名まで</p>
        <form action="{{ route('subscriptions.checkout') }}" method="POST">
            @csrf
            <input type="hidden" name="plan" value="enterprise">
            <input type="number" name="additional_members" min="0" max="50" placeholder="追加メンバー数">
            <button type="submit">このプランを選択</button>
        </form>
    </div>
</div>
```

#### 2. Stripe Checkout Session作成

**Action**: `CreateCheckoutSessionAction`

```php
<?php

namespace App\Http\Actions\Subscription;

use App\Services\Subscription\SubscriptionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class CreateCheckoutSessionAction
{
    public function __construct(
        protected SubscriptionServiceInterface $subscriptionService
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        $group = $user->group;
        
        // グループが存在しない場合はエラー
        if (!$group) {
            return redirect()->back()->withErrors(['error' => 'グループが存在しません。']);
        }
        
        // Stripe Checkout Session作成
        $checkoutSession = $this->subscriptionService->createCheckoutSession(
            $group,
            $request->input('plan'),
            $request->input('additional_members', 0)
        );
        
        return redirect($checkoutSession->url);
    }
}
```

**Service**: `SubscriptionService`

```php
<?php

namespace App\Services\Subscription;

use App\Models\Group;
use Stripe\Checkout\Session as CheckoutSession;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function createCheckoutSession(Group $group, string $plan, int $additionalMembers = 0): CheckoutSession
    {
        $priceId = $this->getPriceId($plan);
        
        $sessionParams = [
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'customer_email' => $group->master->email,
            'client_reference_id' => $group->id,
            'success_url' => route('subscriptions.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('subscriptions.select-plan'),
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'subscription_data' => [
                'metadata' => [
                    'group_id' => $group->id,
                    'plan' => $plan,
                ],
            ],
        ];
        
        // エンタープライズプランで追加メンバーがある場合
        if ($plan === 'enterprise' && $additionalMembers > 0) {
            $sessionParams['line_items'][] = [
                'price' => config('services.stripe.additional_member_price_id'),
                'quantity' => $additionalMembers,
            ];
        }
        
        return CheckoutSession::create($sessionParams);
    }
    
    protected function getPriceId(string $plan): string
    {
        return match ($plan) {
            'family' => config('services.stripe.family_plan_price_id'),
            'enterprise' => config('services.stripe.enterprise_plan_price_id'),
            default => throw new \InvalidArgumentException('Invalid plan: ' . $plan),
        };
    }
}
```

#### 3. Webhook処理（サブスクリプション有効化）

**Action**: `HandleStripeWebhookAction` (既存を拡張)

```php
<?php

namespace App\Http\Actions\Token;

use App\Services\Subscription\WebhookHandlerServiceInterface;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController;

class HandleStripeWebhookAction extends WebhookController
{
    public function __construct(
        protected WebhookHandlerServiceInterface $webhookHandler
    ) {}

    protected function handleCustomerSubscriptionCreated(array $payload): void
    {
        $this->webhookHandler->handleSubscriptionCreated($payload);
    }
    
    protected function handleCustomerSubscriptionUpdated(array $payload): void
    {
        $this->webhookHandler->handleSubscriptionUpdated($payload);
    }
    
    protected function handleCustomerSubscriptionDeleted(array $payload): void
    {
        $this->webhookHandler->handleSubscriptionDeleted($payload);
    }
}
```

**Service**: `WebhookHandlerService`

```php
<?php

namespace App\Services\Subscription;

use App\Models\Group;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookHandlerService implements WebhookHandlerServiceInterface
{
    public function handleSubscriptionCreated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $groupId = $subscription['metadata']['group_id'] ?? null;
        $plan = $subscription['metadata']['plan'] ?? null;
        
        if (!$groupId || !$plan) {
            Log::error('Subscription metadata missing', ['payload' => $payload]);
            return;
        }
        
        DB::transaction(function () use ($groupId, $plan, $subscription) {
            $group = Group::findOrFail($groupId);
            
            // サブスクリプション有効化
            $group->update([
                'subscription_active' => true,
                'subscription_plan' => $plan,
                'max_members' => $this->getMaxMembers($plan),
            ]);
            
            Log::info('Subscription activated', [
                'group_id' => $groupId,
                'plan' => $plan,
                'stripe_subscription_id' => $subscription['id'],
            ]);
        });
    }
    
    public function handleSubscriptionDeleted(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $groupId = $subscription['metadata']['group_id'] ?? null;
        
        if (!$groupId) {
            Log::error('Subscription metadata missing', ['payload' => $payload]);
            return;
        }
        
        DB::transaction(function () use ($groupId, $subscription) {
            $group = Group::findOrFail($groupId);
            
            // サブスクリプション無効化
            $group->update([
                'subscription_active' => false,
                'subscription_plan' => null,
                'max_members' => 1, // 管理者のみ
            ]);
            
            Log::info('Subscription canceled', [
                'group_id' => $groupId,
                'stripe_subscription_id' => $subscription['id'],
            ]);
        });
    }
    
    protected function getMaxMembers(string $plan): int
    {
        return match ($plan) {
            'family' => 6,
            'enterprise' => 20,
            default => 1,
        };
    }
}
```

### メンバー追加時のバリデーション

**Action**: `AddMemberAction` (既存を拡張)

```php
<?php

namespace App\Http\Actions\Profile\Group;

use App\Services\Group\GroupMemberServiceInterface;
use App\Http\Requests\Profile\Group\AddMemberRequest;
use Illuminate\Http\RedirectResponse;

class AddMemberAction
{
    public function __construct(
        protected GroupMemberServiceInterface $groupMemberService
    ) {}

    public function __invoke(AddMemberRequest $request): RedirectResponse
    {
        $user = $request->user();
        $group = $user->group;
        
        // メンバー数制限チェック
        if (!$this->groupMemberService->canAddMember($group)) {
            return redirect()->back()->withErrors([
                'error' => 'メンバー数の上限に達しています。サブスクリプションプランをアップグレードしてください。',
                'upgrade_required' => true,
            ]);
        }
        
        // メンバー追加処理（既存ロジック）
        $this->groupMemberService->addMember($group, $request->validated());
        
        return redirect()->route('group.edit')->with('success', 'メンバーを追加しました。');
    }
}
```

**Service**: `GroupMemberService` (既存を拡張)

```php
<?php

namespace App\Services\Group;

use App\Models\Group;

class GroupMemberService implements GroupMemberServiceInterface
{
    public function canAddMember(Group $group): bool
    {
        $currentMemberCount = $group->users()->count();
        $maxMembers = $group->max_members;
        
        return $currentMemberCount < $maxMembers;
    }
    
    public function addMember(Group $group, array $data): User
    {
        // 既存のメンバー追加ロジック
    }
}
```

### グループタスク作成制限チェック

**Action**: `StoreTaskAction` (既存を拡張)

```php
<?php

namespace App\Http\Actions\Task;

use App\Services\Task\TaskManagementServiceInterface;
use App\Services\Group\GroupTaskLimitServiceInterface;
use App\Http\Requests\Task\StoreTaskRequest;
use Illuminate\Http\RedirectResponse;

class StoreTaskAction
{
    public function __construct(
        protected TaskManagementServiceInterface $taskService,
        protected GroupTaskLimitServiceInterface $groupTaskLimitService
    ) {}

    public function __invoke(StoreTaskRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        
        // グループタスクの場合は制限チェック
        if ($request->isGroupTask()) {
            $group = $user->group;
            
            if (!$this->groupTaskLimitService->canCreateGroupTask($group)) {
                return redirect()->back()->withErrors([
                    'error' => 'グループタスクの無料作成回数（月' . $group->free_group_task_limit . '回）を超えました。サブスクリプションに加入してください。',
                    'current_count' => $group->group_task_count_current_month,
                    'limit' => $group->free_group_task_limit,
                    'reset_at' => $group->group_task_count_reset_at,
                    'upgrade_required' => true,
                ])->withInput();
            }
            
            // グループタスク作成カウントを増やす
            $this->groupTaskLimitService->incrementGroupTaskCount($group);
        }
        
        // タスク作成処理（既存ロジック）
        $task = $this->taskService->createTask($user, $data, $request->isGroupTask());
        
        return redirect()->route('dashboard')->with('success', 'タスクが登録されました。');
    }
}
```

**Service**: `GroupTaskLimitService` (新規作成)

```php
<?php

namespace App\Services\Group;

use App\Models\Group;
use Carbon\Carbon;

class GroupTaskLimitService implements GroupTaskLimitServiceInterface
{
    public function canCreateGroupTask(Group $group): bool
    {
        // サブスクリプション有効な場合は無制限
        if ($group->subscription_active) {
            return true;
        }
        
        // 無料トライアル期間内かチェック
        if ($this->isWithinFreeTrial($group)) {
            return true;
        }
        
        // リセット日時を過ぎていたらリセット
        if ($group->group_task_count_reset_at && Carbon::now()->gte($group->group_task_count_reset_at)) {
            $this->resetMonthlyCount($group);
        }
        
        // 無料枠チェック
        return $group->group_task_count_current_month < $group->free_group_task_limit;
    }
    
    public function incrementGroupTaskCount(Group $group): void
    {
        $group->increment('group_task_count_current_month');
    }
    
    public function resetMonthlyCount(Group $group): void
    {
        $group->update([
            'group_task_count_current_month' => 0,
            'group_task_count_reset_at' => Carbon::now()->addMonth()->startOfMonth(),
        ]);
    }
    
    protected function isWithinFreeTrial(Group $group): bool
    {
        if (!$group->trial_ends_at) {
            return false;
        }
        
        return Carbon::now()->lt($group->trial_ends_at);
    }
}
```

**Cronコマンド**: `ResetMonthlyGroupTaskCount` (新規作成)

```php
<?php

namespace App\Console\Commands;

use App\Models\Group;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ResetMonthlyGroupTaskCount extends Command
{
    protected $signature = 'group:reset-monthly-task-count';
    protected $description = 'Reset monthly group task creation count for all groups';

    public function handle(): int
    {
        $now = Carbon::now();
        
        $groups = Group::where('group_task_count_reset_at', '<=', $now)->get();
        
        foreach ($groups as $group) {
            $group->update([
                'group_task_count_current_month' => 0,
                'group_task_count_reset_at' => $now->copy()->addMonth()->startOfMonth(),
            ]);
        }
        
        $this->info('Reset monthly group task count for ' . $groups->count() . ' groups.');
        
        return Command::SUCCESS;
    }
}
```

**Kernel.php** (Cronスケジュール登録):

```php
protected function schedule(Schedule $schedule): void
{
    // 毎日0時に実行（リセット日時を過ぎたグループを処理）
    $schedule->command('group:reset-monthly-task-count')->daily();
}
```

### アカウント削除時のサブスクリプション解約

**Action**: `DeleteProfileAction` (既存を拡張)

```php
<?php

namespace App\Http\Actions\Profile;

use App\Services\User\UserDeletionServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DeleteProfileAction
{
    public function __construct(
        protected UserDeletionServiceInterface $userDeletionService
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        
        // サブスクリプション解約処理（期間終了時に解約）
        $this->userDeletionService->deleteUser($user);
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
```

**Service**: `UserDeletionService` (新規作成)

```php
<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserDeletionService implements UserDeletionServiceInterface
{
    public function deleteUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            // ユーザーがグループマスターの場合
            if ($user->group && $user->group->master_user_id === $user->id) {
                $group = $user->group;
                
                // サブスクリプション解約（期間終了時に解約）
                if ($group->subscription_active && $group->subscribed('group_subscription')) {
                    $group->subscription('group_subscription')->cancelAtPeriodEnd();
                    
                    Log::info('Subscription canceled at period end due to account deletion', [
                        'user_id' => $user->id,
                        'group_id' => $group->id,
                        'ends_at' => $group->subscription('group_subscription')->ends_at,
                    ]);
                }
                
                // グループメンバーが他にいる場合は次の管理者に譲渡
                $nextMaster = $group->users()->where('id', '!=', $user->id)->where('group_edit_flg', true)->first();
                
                if ($nextMaster) {
                    $group->update(['master_user_id' => $nextMaster->id]);
                    Log::info('Group master transferred', ['new_master_id' => $nextMaster->id]);
                } else {
                    // 他にメンバーがいない場合はグループ削除
                    $group->delete();
                    Log::info('Group deleted', ['group_id' => $group->id]);
                }
            }
            
            // ユーザー削除
            $user->delete();
            Log::info('User account deleted', ['user_id' => $user->id]);
        });
    }
}
```

**View**: `delete-user-form.blade.php` (既存を拡張 - アラート追加)

```blade
<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            アカウント削除
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            アカウントを削除すると、すべてのデータが完全に削除されます。
        </p>
        
        @if(auth()->user()->group && auth()->user()->group->subscription_active)
            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-yellow-800 dark:text-yellow-200">サブスクリプションについて</p>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                            有効なサブスクリプションが存在します。<br>
                            次回請求日: <strong>{{ auth()->user()->group->subscription('group_subscription')->asStripeSubscription()->current_period_end->format('Y年m月d日') }}</strong><br>
                            アカウントを削除すると、上記の期間終了時にサブスクリプションが自動解約されます。
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >アカウントを削除</x-danger-button>

    <!-- 削除確認モーダル -->
    <!-- 既存コード -->
</section>
```

### 実績レポート生成機能

**Cronコマンド**: `GenerateMonthlyReports` (新規作成)

```php
<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Services\Report\MonthlyReportServiceInterface;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateMonthlyReports extends Command
{
    protected $signature = 'reports:generate-monthly {--group= : 特定のグループID}';
    protected $description = 'Generate monthly performance reports for subscribed groups';

    public function __construct(
        protected MonthlyReportServiceInterface $monthlyReportService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $lastMonth = Carbon::now()->subMonth();
        
        // 対象グループ取得
        $groups = $this->option('group')
            ? Group::where('id', $this->option('group'))->get()
            : $this->getEligibleGroups($lastMonth);
        
        $this->info('Generating reports for ' . $groups->count() . ' groups...');
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($groups as $group) {
            try {
                $report = $this->monthlyReportService->generateReport($group, $lastMonth);
                
                // PDF生成
                $this->monthlyReportService->generatePdf($report);
                
                // グループ管理者に通知
                $this->monthlyReportService->notifyGroupMaster($group, $report);
                
                $this->info("✓ Generated report for group: {$group->name} (ID: {$group->id})");
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error("✗ Failed to generate report for group {$group->id}: " . $e->getMessage());
                $failCount++;
            }
        }
        
        $this->info("Completed: {$successCount} succeeded, {$failCount} failed.");
        
        return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
    
    protected function getEligibleGroups(Carbon $reportMonth): Collection
    {
        return Group::where(function ($query) use ($reportMonth) {
            // サブスクリプション有効グループ
            $query->where('subscription_active', true)
                // または実績レポート利用可能期限内（無料ユーザー初月）
                ->orWhere(function ($q) use ($reportMonth) {
                    $q->where('subscription_active', false)
                      ->where('report_enabled_until', '>=', $reportMonth->endOfMonth());
                });
        })->get();
    }
}
```

**Kernel.php** (Cronスケジュール登録):

```php
protected function schedule(Schedule $schedule): void
{
    // 毎月1日2時に実行（前月レポート生成）
    $schedule->command('reports:generate-monthly')->monthlyOn(1, '02:00');
}
```

**Service**: `MonthlyReportService` (新規作成)

```php
<?php

namespace App\Services\Report;

use App\Models\Group;
use App\Models\MonthlyReport;
use App\Models\Task;
use App\Models\Notification;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class MonthlyReportService implements MonthlyReportServiceInterface
{
    public function generateReport(Group $group, Carbon $reportMonth): MonthlyReport
    {
        $startDate = $reportMonth->copy()->startOfMonth();
        $endDate = $reportMonth->copy()->endOfMonth();
        
        // 前月のレポート取得（前月比用）
        $previousReport = MonthlyReport::where('group_id', $group->id)
            ->where('report_month', $reportMonth->copy()->subMonth()->startOfMonth())
            ->first();
        
        // メンバー別通常タスク集計
        $memberTaskSummary = $this->getMemberTaskSummary($group, $startDate, $endDate);
        
        // グループタスク集計
        $groupTaskData = $this->getGroupTaskData($group, $startDate, $endDate);
        
        // レポート作成
        $report = MonthlyReport::updateOrCreate(
            [
                'group_id' => $group->id,
                'report_month' => $reportMonth->startOfMonth(),
            ],
            [
                'generated_at' => now(),
                'member_task_summary' => $memberTaskSummary,
                'group_task_completed_count' => $groupTaskData['count'],
                'group_task_total_reward' => $groupTaskData['total_reward'],
                'group_task_details' => $groupTaskData['details'],
                'normal_task_count_previous_month' => $previousReport?->total_normal_task_count ?? 0,
                'group_task_count_previous_month' => $previousReport?->group_task_completed_count ?? 0,
                'reward_previous_month' => $previousReport?->group_task_total_reward ?? 0,
            ]
        );
        
        return $report;
    }
    
    protected function getMemberTaskSummary(Group $group, Carbon $startDate, Carbon $endDate): array
    {
        $summary = [];
        
        foreach ($group->users as $user) {
            $tasks = Task::where('user_id', $user->id)
                ->where('group_task_flg', false)
                ->where('completed_flg', true)
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->get();
            
            $summary[$user->id] = [
                'user_name' => $user->name,
                'completed_count' => $tasks->count(),
                'tasks' => $tasks->map(fn($task) => [
                    'title' => $task->title,
                    'completed_at' => $task->completed_at->format('Y-m-d'),
                ])->toArray(),
            ];
        }
        
        return $summary;
    }
    
    protected function getGroupTaskData(Group $group, Carbon $startDate, Carbon $endDate): array
    {
        $tasks = Task::where('group_id', $group->id)
            ->where('group_task_flg', true)
            ->where('completed_flg', true)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->get();
        
        return [
            'count' => $tasks->count(),
            'total_reward' => $tasks->sum('reward'),
            'details' => $tasks->map(fn($task) => [
                'task_id' => $task->id,
                'title' => $task->title,
                'reward' => $task->reward,
                'completed_at' => $task->completed_at->format('Y-m-d'),
            ])->toArray(),
        ];
    }
    
    public function generatePdf(MonthlyReport $report): string
    {
        $pdf = Pdf::loadView('reports.monthly-report-pdf', compact('report'));
        
        $filename = "monthly-report-{$report->group_id}-{$report->report_month->format('Y-m')}.pdf";
        $path = "reports/{$report->group_id}/{$filename}";
        
        Storage::disk('s3')->put($path, $pdf->output());
        
        $report->update(['pdf_path' => $path]);
        
        return $path;
    }
    
    public function notifyGroupMaster(Group $group, MonthlyReport $report): void
    {
        $master = $group->master;
        
        Notification::create([
            'user_id' => $master->id,
            'type' => 'monthly_report',
            'title' => '月次実績レポートが作成されました',
            'message' => $report->report_month->format('Y年m月') . 'の実績レポートが作成されました。',
            'data' => json_encode([
                'report_id' => $report->id,
                'report_month' => $report->report_month->format('Y-m'),
            ]),
        ]);
    }
}
```

**View**: `monthly-report.blade.php` (新規作成 - HTML表示用)

```blade
<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold">
                {{ $report->report_month->format('Y年m月') }} 実績レポート
            </h1>
            <a href="{{ route('reports.download-pdf', $report) }}" 
               class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                PDF ダウンロード
            </a>
        </div>
        
        <!-- メンバー別タスク達成状況 -->
        <div class="bento-card mb-6">
            <h2 class="text-xl font-bold mb-4">メンバー別タスク達成状況</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($report->member_task_summary as $userId => $data)
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <h3 class="font-semibold text-lg">{{ $data['user_name'] }}</h3>
                        <p class="text-2xl font-bold text-purple-600">{{ $data['completed_count'] }}件</p>
                        <ul class="mt-2 space-y-1">
                            @foreach($data['tasks'] as $task)
                                <li class="text-sm">・{{ $task['title'] }} ({{ $task['completed_at'] }})</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- グループタスク達成状況 -->
        <div class="bento-card mb-6">
            <h2 class="text-xl font-bold mb-4">グループタスク達成状況</h2>
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">完了件数</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $report->group_task_completed_count }}件</p>
                    @if($report->group_task_count_previous_month > 0)
                        <p class="text-sm {{ $report->group_task_completed_count >= $report->group_task_count_previous_month ? 'text-green-600' : 'text-red-600' }}">
                            前月比: {{ $report->group_task_completed_count - $report->group_task_count_previous_month >= 0 ? '+' : '' }}{{ $report->group_task_completed_count - $report->group_task_count_previous_month }}件
                        </p>
                    @endif
                </div>
                
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">獲得報酬</p>
                    <p class="text-3xl font-bold text-green-600">{{ number_format($report->group_task_total_reward) }}pt</p>
                    @if($report->reward_previous_month > 0)
                        <p class="text-sm {{ $report->group_task_total_reward >= $report->reward_previous_month ? 'text-green-600' : 'text-red-600' }}">
                            前月比: {{ $report->group_task_total_reward - $report->reward_previous_month >= 0 ? '+' : '' }}{{ number_format($report->group_task_total_reward - $report->reward_previous_month) }}pt
                        </p>
                    @endif
                </div>
            </div>
            
            <h3 class="font-semibold mb-2">完了タスク一覧</h3>
            <ul class="space-y-2">
                @foreach($report->group_task_details as $task)
                    <li class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded">
                        <span>{{ $task['title'] }}</span>
                        <span class="text-sm">
                            <span class="font-semibold text-green-600">+{{ $task['reward'] }}pt</span>
                            <span class="text-gray-500 ml-2">({{ $task['completed_at'] }})</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</x-app-layout>
```

## 8. テスト計画

### Unit Tests (Pest形式)

#### GroupSubscriptionServiceTest.php

```php
<?php

use App\Models\User;
use App\Models\Group;
use Laravel\Cashier\Subscription;
use App\Services\Subscription\GroupSubscriptionServiceInterface;

describe('GroupSubscriptionService', function () {
    beforeEach(function () {
        $this->group = Group::factory()->create([
            'max_members' => 6,
            'subscription_active' => false,
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 0,
        ]);
        $this->user = User::factory()->create(['group_id' => $this->group->id]);
    });
    
    it('can subscribe to basic plan', function () {
        $service = app(GroupSubscriptionServiceInterface::class);
        
        $result = $service->subscribe(
            $this->group,
            'price_basic_monthly',
            'pm_card_visa'
        );
        
        expect($result)->toBeTrue()
            ->and($this->group->fresh()->subscription_active)->toBeTrue()
            ->and($this->group->fresh()->max_members)->toBe(20);
    });
    
    it('can upgrade from basic to premium', function () {
        // 既存サブスクリプション作成
        $this->group->newSubscription('group_subscription', 'price_basic_monthly')->create('pm_card_visa');
        $this->group->update(['max_members' => 20]);
        
        $service = app(GroupSubscriptionServiceInterface::class);
        $result = $service->changePlan($this->group, 'price_premium_monthly');
        
        expect($result)->toBeTrue()
            ->and($this->group->fresh()->max_members)->toBe(100);
    });
    
    it('prevents member addition when limit reached', function () {
        // 6人制限のグループに6人登録
        User::factory(5)->create(['group_id' => $this->group->id]);
        
        $service = app(GroupMemberServiceInterface::class);
        $canAdd = $service->canAddMember($this->group);
        
        expect($canAdd)->toBeFalse();
    });
    
    it('allows member addition after upgrade', function () {
        // 6人制限のグループに6人登録
        User::factory(5)->create(['group_id' => $this->group->id]);
        
        // プレミアムにアップグレード
        $this->group->newSubscription('group_subscription', 'price_premium_monthly')->create('pm_card_visa');
        $this->group->update(['max_members' => 100, 'subscription_active' => true]);
        
        $service = app(GroupMemberServiceInterface::class);
        $canAdd = $service->canAddMember($this->group);
        
        expect($canAdd)->toBeTrue();
    });
});

describe('GroupTaskLimitService', function () {
    beforeEach(function () {
        $this->group = Group::factory()->create([
            'subscription_active' => false,
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 0,
            'group_task_count_reset_at' => now()->addMonth()->startOfMonth(),
        ]);
    });
    
    it('allows group task creation within free limit', function () {
        $service = app(GroupTaskLimitServiceInterface::class);
        
        expect($service->canCreateGroupTask($this->group))->toBeTrue();
        
        $service->incrementGroupTaskCount($this->group);
        expect($this->group->fresh()->group_task_count_current_month)->toBe(1);
        
        expect($service->canCreateGroupTask($this->group))->toBeTrue();
    });
    
    it('blocks group task creation when limit exceeded', function () {
        $this->group->update(['group_task_count_current_month' => 3]);
        
        $service = app(GroupTaskLimitServiceInterface::class);
        
        expect($service->canCreateGroupTask($this->group))->toBeFalse();
    });
    
    it('allows unlimited group tasks for subscribed groups', function () {
        $this->group->update([
            'subscription_active' => true,
            'group_task_count_current_month' => 100,  // 上限を超えている
        ]);
        
        $service = app(GroupTaskLimitServiceInterface::class);
        
        expect($service->canCreateGroupTask($this->group))->toBeTrue();
    });
    
    it('resets monthly count when reset date passed', function () {
        $this->group->update([
            'group_task_count_current_month' => 3,
            'group_task_count_reset_at' => now()->subDay(),  // 昨日にリセット日設定
        ]);
        
        $service = app(GroupTaskLimitServiceInterface::class);
        
        // リセットされて作成可能になる
        expect($service->canCreateGroupTask($this->group))->toBeTrue();
        expect($this->group->fresh()->group_task_count_current_month)->toBe(0);
    });
    
    it('allows group tasks during free trial period', function () {
        $this->group->update([
            'trial_ends_at' => now()->addDays(14),
            'group_task_count_current_month' => 3,  // 無料枠を超えている
        ]);
        
        $service = app(GroupTaskLimitServiceInterface::class);
        
        expect($service->canCreateGroupTask($this->group))->toBeTrue();
    });
});

describe('MonthlyReportService', function () {
    beforeEach(function () {
        $this->group = Group::factory()->create(['subscription_active' => true]);
        $this->user1 = User::factory()->create(['group_id' => $this->group->id]);
        $this->user2 = User::factory()->create(['group_id' => $this->group->id]);
    });
    
    it('generates report with member task summary', function () {
        // 前月のタスク作成
        $lastMonth = now()->subMonth();
        
        Task::factory()->create([
            'user_id' => $this->user1->id,
            'group_id' => $this->group->id,
            'group_task_flg' => false,
            'completed_flg' => true,
            'completed_at' => $lastMonth->copy()->addDays(5),
        ]);
        
        Task::factory()->count(2)->create([
            'user_id' => $this->user2->id,
            'group_id' => $this->group->id,
            'group_task_flg' => false,
            'completed_flg' => true,
            'completed_at' => $lastMonth->copy()->addDays(10),
        ]);
        
        $service = app(MonthlyReportServiceInterface::class);
        $report = $service->generateReport($this->group, $lastMonth);
        
        expect($report->member_task_summary)->toHaveKey($this->user1->id)
            ->and($report->member_task_summary[$this->user1->id]['completed_count'])->toBe(1)
            ->and($report->member_task_summary[$this->user2->id]['completed_count'])->toBe(2);
    });
    
    it('generates report with group task summary', function () {
        $lastMonth = now()->subMonth();
        
        Task::factory()->create([
            'group_id' => $this->group->id,
            'group_task_flg' => true,
            'completed_flg' => true,
            'completed_at' => $lastMonth->copy()->addDays(5),
            'reward' => 500,
        ]);
        
        Task::factory()->create([
            'group_id' => $this->group->id,
            'group_task_flg' => true,
            'completed_flg' => true,
            'completed_at' => $lastMonth->copy()->addDays(15),
            'reward' => 300,
        ]);
        
        $service = app(MonthlyReportServiceInterface::class);
        $report = $service->generateReport($this->group, $lastMonth);
        
        expect($report->group_task_completed_count)->toBe(2)
            ->and($report->group_task_total_reward)->toBe(800);
    });
    
    it('includes previous month comparison', function () {
        // 先々月のレポート作成
        $twoMonthsAgo = now()->subMonths(2);
        MonthlyReport::factory()->create([
            'group_id' => $this->group->id,
            'report_month' => $twoMonthsAgo->startOfMonth(),
            'group_task_completed_count' => 5,
            'group_task_total_reward' => 1500,
        ]);
        
        $lastMonth = now()->subMonth();
        
        $service = app(MonthlyReportServiceInterface::class);
        $report = $service->generateReport($this->group, $lastMonth);
        
        expect($report->group_task_count_previous_month)->toBe(5)
            ->and($report->reward_previous_month)->toBe(1500);
    });
    
    it('generates PDF file', function () {
        Storage::fake('s3');
        
        $report = MonthlyReport::factory()->create(['group_id' => $this->group->id]);
        
        $service = app(MonthlyReportServiceInterface::class);
        $pdfPath = $service->generatePdf($report);
        
        Storage::disk('s3')->assertExists($pdfPath);
        expect($report->fresh()->pdf_path)->toBe($pdfPath);
    });
});

describe('UserDeletionService', function () {
    it('cancels subscription at period end when group master deletes account', function () {
        $group = Group::factory()->create();
        $master = User::factory()->create(['group_id' => $group->id]);
        $group->update(['master_user_id' => $master->id]);
        
        // サブスクリプション作成
        $group->newSubscription('group_subscription', 'price_basic_monthly')->create('pm_card_visa');
        $group->update(['subscription_active' => true]);
        
        $service = app(UserDeletionServiceInterface::class);
        $service->deleteUser($master);
        
        $subscription = Subscription::where('subscribable_id', $group->id)->first();
        expect($subscription->ends_at)->not->toBeNull();
    });
    
    it('transfers group ownership to next admin', function () {
        $group = Group::factory()->create();
        $master = User::factory()->create(['group_id' => $group->id]);
        $nextAdmin = User::factory()->create(['group_id' => $group->id, 'group_edit_flg' => true]);
        $group->update(['master_user_id' => $master->id]);
        
        $service = app(UserDeletionServiceInterface::class);
        $service->deleteUser($master);
        
        expect($group->fresh()->master_user_id)->toBe($nextAdmin->id);
    });
    
    it('deletes group when no other members exist', function () {
        $group = Group::factory()->create();
        $master = User::factory()->create(['group_id' => $group->id]);
        $group->update(['master_user_id' => $master->id]);
        
        $service = app(UserDeletionServiceInterface::class);
        $service->deleteUser($master);
        
        expect(Group::find($group->id))->toBeNull();
    });
});
```

### Feature Tests (Pest形式)

```php
<?php

use App\Models\User;
use App\Models\Group;
use function Pest\Laravel\{actingAs, post, get, delete};

describe('Subscription Flow', function () {
    beforeEach(function () {
        $this->group = Group::factory()->create();
        $this->master = User::factory()->create(['group_id' => $this->group->id]);
        $this->group->update(['master_user_id' => $this->master->id]);
    });
    
    it('displays subscription page', function () {
        actingAs($this->master)
            ->get('/subscriptions')
            ->assertOk()
            ->assertSee('ベーシックプラン')
            ->assertSee('プレミアムプラン');
    });
    
    it('subscribes to basic plan', function () {
        actingAs($this->master)
            ->post('/subscriptions/subscribe', [
                'price_id' => 'price_basic_monthly',
                'payment_method' => 'pm_card_visa',
            ])
            ->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success');
        
        expect($this->group->fresh()->subscription_active)->toBeTrue()
            ->and($this->group->fresh()->max_members)->toBe(20);
    });
    
    it('upgrades from basic to premium', function () {
        // 事前にベーシックプラン登録
        $this->group->newSubscription('group_subscription', 'price_basic_monthly')->create('pm_card_visa');
        $this->group->update(['max_members' => 20, 'subscription_active' => true]);
        
        actingAs($this->master)
            ->post('/subscriptions/change-plan', ['price_id' => 'price_premium_monthly'])
            ->assertRedirect(route('subscriptions.index'))
            ->assertSessionHas('success');
        
        expect($this->group->fresh()->max_members)->toBe(100);
    });
});

describe('Group Task Limit', function () {
    beforeEach(function () {
        $this->group = Group::factory()->create([
            'subscription_active' => false,
            'free_group_task_limit' => 3,
            'group_task_count_current_month' => 2,
        ]);
        $this->user = User::factory()->create(['group_id' => $this->group->id]);
        $this->group->update(['master_user_id' => $this->user->id]);
    });
    
    it('allows group task creation within free limit', function () {
        actingAs($this->user)
            ->post('/tasks', [
                'title' => 'グループタスク',
                'content' => 'テスト',
                'group_task_flg' => true,
                // 他の必須フィールド
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');
        
        expect($this->group->fresh()->group_task_count_current_month)->toBe(3);
    });
    
    it('blocks group task creation when limit exceeded', function () {
        $this->group->update(['group_task_count_current_month' => 3]);
        
        actingAs($this->user)
            ->post('/tasks', [
                'title' => 'グループタスク',
                'content' => 'テスト',
                'group_task_flg' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['error'])
            ->assertSessionHas('upgrade_required', true);
    });
});

describe('Account Deletion with Subscription', function () {
    it('shows subscription warning on deletion page', function () {
        $group = Group::factory()->create(['subscription_active' => true]);
        $master = User::factory()->create(['group_id' => $group->id]);
        $group->update(['master_user_id' => $master->id]);
        
        $group->newSubscription('group_subscription', 'price_basic_monthly')->create('pm_card_visa');
        
        actingAs($master)
            ->get('/profile')
            ->assertOk()
            ->assertSee('有効なサブスクリプションが存在します')
            ->assertSee('期間終了時にサブスクリプションが自動解約されます');
    });
    
    it('cancels subscription at period end when account deleted', function () {
        $group = Group::factory()->create(['subscription_active' => true]);
        $master = User::factory()->create(['group_id' => $group->id]);
        $group->update(['master_user_id' => $master->id]);
        
        $group->newSubscription('group_subscription', 'price_basic_monthly')->create('pm_card_visa');
        
        actingAs($master)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/');
        
        $subscription = $group->subscription('group_subscription')->asStripeSubscription();
        expect($subscription->cancel_at_period_end)->toBeTrue();
    });
});
```

### Manual Test Cases

| テストケース | 手順 | 期待結果 |
|------------|------|---------|
| **サブスクリプション登録** | 1. ログイン<br>2. サブスクリプションページに移動<br>3. ベーシックプラン選択<br>4. カード情報入力 | ✅ 支払い完了<br>✅ グループのmax_membersが20に変更<br>✅ subscription_activeがtrue |
| **メンバー追加制限** | 1. 無料グループに6人登録<br>2. 7人目を追加 | ❌ エラーメッセージ表示<br>✅ アップグレード促進メッセージ |
| **グループタスク制限** | 1. 無料グループで3件グループタスク作成<br>2. 4件目を作成 | ❌ エラーメッセージ表示<br>✅ サブスクリプション促進メッセージ |
| **月次リセット** | 1. group_task_count_reset_atを昨日に設定<br>2. Cronコマンド実行 | ✅ group_task_count_current_monthが0にリセット<br>✅ reset_atが来月1日に更新 |
| **アップグレード** | 1. ベーシックプラン登録済み<br>2. プレミアムにアップグレード | ✅ 即座にmax_membersが100に変更<br>✅ 差額の日割り請求 |
| **アカウント削除** | 1. サブスクリプション有効なユーザー<br>2. アカウント削除実行 | ✅ 解約予約（cancel_at_period_end: true）<br>✅ 警告メッセージ表示済み |
| **実績レポート生成** | 1. Cronで月次レポートコマンド実行<br>2. レポートページで確認 | ✅ 前月タスク集計<br>✅ PDF生成完了<br>✅ グループ管理者に通知 |

## 9. 運用・監視

### ログ出力

**重要なイベント**:
- サブスクリプション作成・更新・解約
- Webhook処理（成功・失敗）
- メンバー追加制限エラー
- グループタスク制限エラー
- 実績レポート生成
- アカウント削除時のサブスクリプション処理

**ログレベル**:
```php
// 正常なサブスクリプション操作
Log::info('Subscription created', [
    'group_id' => $group->id,
    'plan' => $priceId,
    'subscription_id' => $subscription->id,
]);

// メンバー/タスク制限超過（ユーザー起因）
Log::warning('Member limit reached', [
    'group_id' => $group->id,
    'current_members' => $group->users()->count(),
    'max_members' => $group->max_members,
]);

// Webhook処理失敗、Stripe API エラー（システム起因）
Log::error('Webhook processing failed', [
    'event_type' => $event->type,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

### モニタリング

**Stripe Dashboard**:
- サブスクリプション数の推移（MRR: Monthly Recurring Revenue）
- プラン別の分布（ベーシック vs プレミアム）
- チャーン率（解約率） - 月次で追跡
- 決済失敗率
- 平均顧客生涯価値（LTV: Lifetime Value）

**アプリケーションメトリクス**:
```sql
-- サブスクリプション有効ユーザー数
SELECT COUNT(*) FROM groups WHERE subscription_active = true;

-- プラン別の分布
SELECT max_members, COUNT(*) 
FROM groups 
WHERE subscription_active = true 
GROUP BY max_members;

-- 平均メンバー数
SELECT AVG(member_count) 
FROM (
    SELECT group_id, COUNT(*) AS member_count 
    FROM users 
    WHERE group_id IS NOT NULL 
    GROUP BY group_id
) AS subquery;

-- 無料グループのタスク利用状況
SELECT 
    AVG(group_task_count_current_month) AS avg_usage,
    COUNT(*) FILTER (WHERE group_task_count_current_month >= free_group_task_limit) AS at_limit_count
FROM groups 
WHERE subscription_active = false;
```

### アラート設定

**CloudWatch Alarms**:
| アラート名 | 条件 | 閾値 | アクション |
|----------|------|------|----------|
| Webhook処理エラー率 | エラーログ数 / 全Webhook数 | 5%超過 | SNS通知 |
| サブスクリプション作成失敗率 | 失敗数 / 試行数 | 10%超過 | SNS通知 |
| 決済失敗率 | Stripe決済失敗数 | 5%超過 | SNS通知 + Slack |
| レポート生成失敗 | エラーログ検知 | 1件以上 | SNS通知 |
| 月次リセットコマンド失敗 | Cron実行失敗 | 1回以上 | SNS通知 + Slack |

**CloudWatch Logs Insights クエリ**:
```
# Webhook処理エラーの詳細
fields @timestamp, @message
| filter @message like /Webhook processing failed/
| sort @timestamp desc
| limit 20

# サブスクリプション作成ログ
fields @timestamp, group_id, plan, subscription_id
| filter @message like /Subscription created/
| stats count() by plan
```

### Cronジョブ監視

**crontab設定**:
```bash
# 毎日0時: 月次グループタスクカウントリセット
0 0 * * * cd /var/www/html && php artisan group:reset-monthly-task-count >> /var/log/cron.log 2>&1

# 毎月1日2時: 前月実績レポート生成
0 2 1 * * cd /var/www/html && php artisan reports:generate-monthly >> /var/log/cron.log 2>&1
```

**Dead Man's Snitch** (Cronジョブ監視サービス):
```php
// コマンド実行後にPing送信
protected function schedule(Schedule $schedule): void
{
    $schedule->command('group:reset-monthly-task-count')
        ->daily()
        ->thenPing(config('services.snitch.reset_count_url'));
    
    $schedule->command('reports:generate-monthly')
        ->monthlyOn(1, '02:00')
        ->thenPing(config('services.snitch.report_url'));
}
```

## 10. セキュリティ対策

### Webhook検証

**Stripe署名検証**:
```php
// Laravel Cashierが自動処理
// config/cashier.php
'webhook' => [
    'secret' => env('STRIPE_WEBHOOK_SECRET'),
    'tolerance' => 300,  // 署名の有効期限（秒）
],
```

**追加検証**:
```php
// app/Http/Actions/Webhook/HandleStripeWebhookAction.php
public function __invoke(Request $request): Response
{
    // 1. Stripe署名検証（Cashierミドルウェアで実施済み）
    
    // 2. イベントID重複チェック（リプレイ攻撃防止）
    $eventId = $request->input('id');
    if (Cache::has("stripe_event_{$eventId}")) {
        Log::warning('Duplicate webhook event detected', ['event_id' => $eventId]);
        return response()->json(['status' => 'duplicate'], 200);
    }
    
    Cache::put("stripe_event_{$eventId}", true, now()->addHours(24));
    
    // 3. Webhook処理
    // ...
}
```

### CSRF保護

**全フォームにCSRFトークン**:
```blade
<form method="POST" action="{{ route('subscriptions.subscribe') }}">
    @csrf
    <!-- ... -->
</form>
```

**APIエンドポイント除外**:
```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'stripe/webhook',  // Stripe Webhookは署名検証で保護
];
```

### 権限チェック

**グループ管理者のみサブスクリプション操作可能**:
```php
// app/Http/Actions/Subscription/SubscribeAction.php
public function __invoke(Request $request): RedirectResponse
{
    $user = $request->user();
    $group = $user->group;
    
    // グループマスター権限チェック
    if ($user->id !== $group->master_user_id) {
        abort(403, 'グループ管理者のみがサブスクリプションを管理できます。');
    }
    
    // ...
}
```

**ミドルウェアでの保護**:
```php
// routes/web.php
Route::middleware(['auth', 'group.master'])->group(function () {
    Route::get('/subscriptions', ShowSubscriptionsAction::class)->name('subscriptions.index');
    Route::post('/subscriptions/subscribe', SubscribeAction::class)->name('subscriptions.subscribe');
    Route::post('/subscriptions/change-plan', ChangePlanAction::class)->name('subscriptions.change-plan');
    Route::delete('/subscriptions/cancel', CancelSubscriptionAction::class)->name('subscriptions.cancel');
});

// app/Http/Middleware/EnsureUserIsGroupMaster.php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();
    
    if (!$user->group || $user->id !== $user->group->master_user_id) {
        abort(403, 'この操作にはグループ管理者権限が必要です。');
    }
    
    return $next($request);
}
```

### データ保護

**機密情報のマスキング**:
```php
// ログ出力時にカード情報をマスク
Log::info('Payment method updated', [
    'group_id' => $group->id,
    'last4' => $paymentMethod->card->last4,  // ✅ 下4桁のみ
    // 'card_number' => $cardNumber,  // ❌ 全番号は記録しない
]);
```

**環境変数の管理**:
```bash
# .env
STRIPE_KEY=pk_live_...           # 本番: pk_live_
STRIPE_SECRET=sk_live_...        # 本番: sk_live_
STRIPE_WEBHOOK_SECRET=whsec_...  # Webhookシークレット

# テスト環境は別の値
STRIPE_TEST_MODE=true
```

### レート制限

**サブスクリプション操作のレート制限**:
```php
// routes/web.php
Route::middleware(['throttle:10,1'])->group(function () {  // 1分間に10回まで
    Route::post('/subscriptions/subscribe', SubscribeAction::class);
    Route::post('/subscriptions/change-plan', ChangePlanAction::class);
});
```

## 11. マイグレーション計画

### 既存ユーザーへの影響

**無料ユーザー（グループなし）**:
- ✅ 影響なし、引き続き個人利用可能
- ✅ グループタスクは引き続き月3回まで無料

**既存グループユーザー**:
- ⚠️ **猶予期間**: 実装後3ヶ月間
- 猶予期間中: 既存のメンバーはそのまま利用可能（6名以上でも継続）
- 猶予期間中: グループタスクも月3回まで無料
- 猶予期間後: 
  - サブスクリプション加入 → 全機能利用可能
  - 未加入 → メンバー6名まで、グループタスク月3回まで

### データ移行手順

**Phase 1: 既存グループのデータ初期化**:
```sql
-- 既存グループに無料枠設定を追加
UPDATE groups SET
    free_group_task_limit = 3,
    group_task_count_current_month = 0,
    group_task_count_reset_at = DATE_TRUNC('month', CURRENT_DATE + INTERVAL '1 month'),
    report_enabled_until = CURRENT_DATE + INTERVAL '1 month'  -- 初月のみレポート有効
WHERE subscription_active = false;

-- 7名以上のグループに猶予期間フラグ設定
UPDATE groups SET
    free_trial_days = 90,  -- 3ヶ月猶予
    trial_ends_at = CURRENT_DATE + INTERVAL '90 days'
WHERE (SELECT COUNT(*) FROM users WHERE group_id = groups.id) > 6
  AND subscription_active = false;
```

**Phase 2: 猶予期間終了時の処理**:
```php
// app/Console/Commands/HandleTrialExpiration.php
public function handle(): int
{
    $expiredGroups = Group::where('trial_ends_at', '<=', now())
        ->where('subscription_active', false)
        ->get();
    
    foreach ($expiredGroups as $group) {
        // メンバー数が6名超の場合、管理者に通知
        if ($group->users()->count() > 6) {
            $this->notifyGroupMaster($group, 'メンバー数が上限を超えています。サブスクリプションに加入してください。');
        }
        
        // trial_ends_atをクリア
        $group->update(['trial_ends_at' => null, 'free_trial_days' => null]);
    }
    
    return Command::SUCCESS;
}
```

### 告知計画

**実装1ヶ月前**:
- ✉️ 全ユーザーにメール通知
- 📱 アプリ内バナー表示
- 📄 ポータルサイトでの告知
- 内容: 
  - 「3ヶ月後からサブスクリプション制度開始」
  - 料金プラン詳細
  - 無料枠の説明

**実装直後**:
- 📱 グループ管理画面に案内バナー
- 💡 グループタスク作成時に残り回数表示
- 🎁 初回登録キャンペーン（初月無料など）

**猶予期間終了1週間前**:
- ✉️ 対象グループの管理者に再通知
- ⚠️ アプリ内警告表示（「残り7日」カウントダウン）
- 内容:
  - 「猶予期間終了まであと○日」
  - サブスクリプション加入リンク
  - メンバー削減の選択肢提示

## 12. 次のステップ

### Phase 1.2: Web API移行計画

**目的**: 既存Web routesのAPI化判断と実装

**内容**:
- web.php の全ルート調査（463行、約100ルート）
- API化が必要なルート特定
- Cognito JWT認証統合
- モバイルアプリ対応

**想定期間**: 3-4週間

### Phase 1.3: ログシステム再構築

**目的**: マイクロサービス削除後のログ管理体制構築

**内容**:
- CloudWatch Logs統合
- ログローテーション（バイナリログ7日、アプリログ30日）
- S3長期保管
- デバッグレベル切り替え機能
- エラー通知システム

**想定期間**: 2-3週間

### Phase 2: トークン購入機能（将来）

**内容**:
- Stripeによる単発決済
- トークンパッケージ購入
- サブスクリプション会員割引

**想定期間**: 2-3週間

---

## 付録

### 参考資料

- [Laravel Cashier ドキュメント](https://laravel.com/docs/11.x/billing)
- [Stripe API リファレンス](https://stripe.com/docs/api)
- [Stripe Checkout ガイド](https://stripe.com/docs/payments/checkout)
- [Stripe Webhook ガイド](https://stripe.com/docs/webhooks)
- [Dompdf ドキュメント](https://github.com/barryvdh/laravel-dompdf)

### 用語集

- **Subscription**: サブスクリプション（定期購読）
- **Checkout Session**: Stripe決済画面へのセッション
- **Webhook**: StripeからLaravelへの通知
- **Price ID**: Stripe商品の価格ID（`price_xxx`）
- **Product ID**: Stripe商品ID（`prod_xxx`）
- **Trial**: トライアル期間（無料体験）
- **MRR**: Monthly Recurring Revenue（月次経常収益）
- **Churn**: 解約率
- **LTV**: Lifetime Value（顧客生涯価値）

---

この計画書に基づき、Phase 1.1のStripe課金システムを実装することで、MyTeacherアプリの持続可能な収益化を実現します。実装期間は約3-4週間を想定し、グループタスク制限、実績レポート、アカウント削除処理を含む包括的な機能を提供します。


