# トークン購入機能 要件定義書

## 更新履歴

| 日付 | 更新者 | 更新内容 |
|------|--------|---------||
| 2025-12-07 | GitHub Copilot | モバイル版要件追加: WebView方式Stripe決済、子ども承認フロー、API仕様 |
| 2025-12-01 | GitHub Copilot | 初版作成: トークン購入機能の要件定義（Web版） |

---

## 1. 概要

### 目的

MyTeacher AIタスク管理プラットフォームにおけるトークン購入機能は、ユーザーがトークンを購入してAI機能を利用するための決済システムです。Stripe Checkoutを使用した安全な決済フローと、子どもユーザー向けの承認フローを提供します。

### 対象ユーザー

- **個人ユーザー（大人）**: 自由にトークンパッケージを購入可能
- **子どもユーザー**: 親の承認が必要（購入リクエスト送信 → 親承認 → 決済実行）
- **グループ管理者**: グループメンバーのトークン購入を承認

### 対応プラットフォーム

| プラットフォーム | 実装状況 | 認証方式 | 決済方式 |
|----------------|---------|---------|----------|
| **Web** | ✅ 実装済み | セッション + CSRF | Stripe Checkout（サーバー側） |
| **モバイル** | 🎯 Phase 2.B-6実装予定 | Sanctum（トークン） | WebView方式（Laravel画面表示） |

**注**: サブスクリプション機能は独立した機能として後日実装予定。

---

## 2. アーキテクチャ構成

app/
│
├── Http/
│   ├── Actions/
│   │   ├── Token/
│   │   │   ├── IndexTokenPurchaseAction.php      # 購入画面表示
│   │   │   ├── ProcessTokenPurchaseAction.php    # 購入処理
│   │   │   ├── IndexTokenHistoryAction.php       # 履歴表示
│   │   │   └── HandleStripeWebhookAction.php     # Webhook処理
│   │   │
│   │   ├── Notification/
│   │   │   ├── IndexNotificationAction.php       # 通知一覧
│   │   │   ├── MarkNotificationAsReadAction.php  # 既読化
│   │   │   └── MarkAllNotificationsAsReadAction.php # 全既読化
│   │   │
│   │   └── Admin/
│   │       ├── Token/
│   │       │   ├── IndexTokenStatsAction.php     # 統計表示
│   │       │   ├── IndexTokenUsersAction.php     # ユーザー一覧
│   │       │   ├── AdjustUserTokenAction.php     # トークン調整
│   │       │   └── GrantFreeTokenAction.php      # 無料付与
│   │       └── Payment/
│   │           └── IndexPaymentHistoryAction.php # 課金履歴
│   │
│   ├── Middleware/
│   │   └── CheckTokenBalance.php              # トークン残高チェック
│   │
│   └── Requests/
│       └── Token/
│           ├── PurchaseTokenRequest.php        # 購入リクエスト
│           └── AdjustTokenRequest.php          # 調整リクエスト
│
├── Models/
│   ├── TokenBalance.php                        # トークン残高
│   ├── TokenTransaction.php                    # トークン履歴
│   ├── TokenPackage.php                        # 商品マスタ
│   ├── Notification.php                        # 通知
│   ├── PaymentHistory.php                      # 課金履歴
│   ├── User.php (修正)                         # Billableトレイト追加
│   └── Group.php (修正)                        # Billableトレイト追加
│
├── Repositories/
│   └── Token/
│       ├── TokenRepositoryInterface.php
│       └── TokenEloquentRepository.php
│
├── Responders/
│   └── Token/
│       ├── TokenPurchaseResponder.php
│       ├── TokenHistoryResponder.php
│       ├── NotificationResponder.php
│       └── Admin/
│           ├── TokenStatsResponder.php
│           └── PaymentHistoryResponder.php
│
└── Services/
    ├── Token/
    │   ├── TokenServiceInterface.php
    │   └── TokenService.php                    # トークン管理
    ├── Notification/
    │   ├── NotificationServiceInterface.php
    │   └── NotificationService.php             # 通知管理
    └── Payment/
        ├── PaymentServiceInterface.php
        └── PaymentService.php                  # 決済処理

database/
└── migrations/
    ├── 2025_01_01_000001_add_stripe_fields_to_users_table.php
    ├── 2025_01_01_000002_add_stripe_fields_to_groups_table.php
    ├── 2025_01_01_000003_create_token_balances_table.php
    ├── 2025_01_01_000004_create_token_transactions_table.php
    ├── 2025_01_01_000005_create_token_packages_table.php
    ├── 2025_01_01_000006_create_notifications_table.php
    └── 2025_01_01_000007_create_payment_histories_table.php

resources/
└── views/
    ├── tokens/
    │   ├── purchase.blade.php                  # 購入画面
    │   └── history.blade.php                   # 履歴画面
    ├── notifications/
    │   └── index.blade.php                     # 通知一覧
    └── admin/
        ├── tokens/
        │   ├── stats.blade.php                 # 統計
        │   └── users.blade.php                 # ユーザー管理
        └── payments/
            └── index.blade.php                 # 課金履歴

config/
└── const.php (追加)                            # トークン設定